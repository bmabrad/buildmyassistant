<?php

namespace App\Http\Controllers;

use App\Mail\MagicLinkMail;
use App\Models\Assistant;
use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || $user->password === null) {
            return back()
                ->withInput(['email' => $request->email])
                ->withErrors(['password' => 'No password set yet. Use the magic link below, or set a password from your dashboard.']);
        }

        if (! Auth::attempt($request->only('email', 'password'), remember: true)) {
            return back()
                ->withInput(['email' => $request->email])
                ->withErrors(['email' => 'Those credentials do not match our records.']);
        }

        $request->session()->regenerate();

        $this->linkLegacyTasks(Auth::user());

        if (Auth::user()->is_admin) {
            return redirect()->intended('/admin');
        }

        return redirect()->intended('/dashboard');
    }

    public function sendMagicLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->email;
        $rateLimitKey = 'magic-link:' . $email;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return back()
                ->withInput(['email' => $email])
                ->withErrors(['email' => "Too many requests. Please try again in {$seconds} seconds."]);
        }

        RateLimiter::hit($rateLimitKey, 3600);

        $user = User::where('email', $email)->first();

        if (! $user) {
            return back()
                ->withInput(['email' => $email])
                ->withErrors(['email' => 'No account found for that email. Your account is created automatically when you make your first purchase.']);
        }

        $magicLink = MagicLink::generateFor($email);

        Mail::to($email)->send(new MagicLinkMail($magicLink, $user));

        return back()
            ->withInput(['email' => $email])
            ->with('magic_link_sent', true);
    }

    public function verifyMagicLink(string $token)
    {
        $magicLink = MagicLink::where('token', $token)->first();

        if (! $magicLink || ! $magicLink->isValid()) {
            return view('auth.magic-link-invalid');
        }

        $magicLink->markUsed();

        $user = User::where('email', $magicLink->email)->first();

        if (! $user) {
            return view('auth.magic-link-invalid');
        }

        Auth::login($user, remember: true);

        session()->regenerate();

        $this->linkLegacyTasks($user);

        return redirect($user->is_admin ? '/admin' : '/dashboard');
    }

    private function linkLegacyTasks(User $user): void
    {
        Assistant::where('email', $user->email)
            ->whereNull('user_id')
            ->update(['user_id' => $user->id]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
