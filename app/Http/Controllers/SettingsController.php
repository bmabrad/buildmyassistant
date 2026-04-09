<?php

namespace App\Http\Controllers;

use App\Mail\VerifyNewEmailMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        return view('settings.index', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        if ($request->session()->has('impersonating_from')) {
            abort(403, 'Cannot update settings while impersonating.');
        }

        $user = $request->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        ]);

        // Update name fields immediately
        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? null,
            'name' => trim($validated['first_name'] . ' ' . ($validated['last_name'] ?? '')),
        ]);

        // If email changed, send verification to new address
        if ($validated['email'] !== $user->email) {
            // Also check it's not already taken by another user (pending_email collision)
            $existingPending = User::where('pending_email', $validated['email'])
                ->where('id', '!=', $user->id)
                ->exists();

            if ($existingPending) {
                return back()->withErrors(['email' => 'This email address is already pending verification by another account.']);
            }

            $user->update(['pending_email' => $validated['email']]);

            $verifyUrl = URL::temporarySignedRoute(
                'settings.verify-email',
                now()->addMinutes(60),
                ['user' => $user->id]
            );

            Mail::to($validated['email'])->send(new VerifyNewEmailMail($user, $verifyUrl));

            return back()->with('success', 'Your name has been updated. We have sent a verification link to your new email address.');
        }

        return back()->with('success', 'Your details have been updated.');
    }

    public function verifyEmail(Request $request, User $user)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'This verification link is invalid or has expired.');
        }

        if (! $user->pending_email) {
            return redirect('/settings')->with('success', 'Your email address is already up to date.');
        }

        $user->update([
            'email' => $user->pending_email,
            'pending_email' => null,
        ]);

        return redirect('/settings')->with('success', 'Your email address has been updated.');
    }

    public function cancelPendingEmail(Request $request)
    {
        if ($request->session()->has('impersonating_from')) {
            abort(403, 'Cannot update settings while impersonating.');
        }

        $request->user()->update(['pending_email' => null]);

        return back()->with('success', 'Pending email change has been cancelled.');
    }
}
