<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tasks = $user->assistants()->get();

        return view('dashboard.index', [
            'user' => $user,
            'tasks' => $tasks,
        ]);
    }

    public function updatePassword(Request $request)
    {
        if ($request->session()->has('impersonating_from')) {
            abort(403, 'Cannot change password while impersonating.');
        }

        $user = $request->user();
        $hasPassword = $user->password !== null;

        $rules = [
            'password' => ['required', 'confirmed', Password::min(8)],
        ];

        if ($hasPassword) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $request->validate($rules);

        $user->password = Hash::make($request->password);
        $user->save();

        return back()->with('password_updated', true);
    }

    public function billing(Request $request)
    {
        if ($request->session()->has('impersonating_from')) {
            abort(403, 'Cannot update payment method while impersonating.');
        }

        return $request->user()->redirectToBillingPortal(url('/dashboard'));
    }

    public function newBuild(Request $request)
    {
        $user = $request->user();

        try {
            if (! $user->hasDefaultPaymentMethod()) {
                return redirect()->route('launchpad');
            }

            $paymentMethod = $user->defaultPaymentMethod();
        } catch (\Exception $e) {
            return redirect()->route('launchpad');
        }

        return view('dashboard.confirm-build', [
            'user' => $user,
            'paymentMethod' => $paymentMethod,
        ]);
    }

    public function chargeNewBuild(Request $request)
    {
        $user = $request->user();

        if (! $user->hasDefaultPaymentMethod()) {
            return redirect()->route('launchpad');
        }

        try {
            $user->charge(700, $user->defaultPaymentMethod()->id, [
                'currency' => 'aud',
                'description' => 'AI Assistant Launchpad — Build My Assistant',
                'return_url' => route('dashboard'),
            ]);
        } catch (\Exception $e) {
            report($e);

            return back()->withErrors(['charge' => 'Payment failed. You can try again or use a new card.'])
                ->with('show_fallback', true);
        }

        $task = \App\Models\Assistant::create([
            'token' => (string) \Illuminate\Support\Str::uuid(),
            'stripe_payment_id' => 'dashboard_' . now()->timestamp,
            'stripe_customer_id' => $user->stripe_id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'pending',
            'phase' => 1,
            'playbook_delivered' => false,
            'user_id' => $user->id,
        ]);

        \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\PostPurchaseMail($task));

        return redirect()->route('launchpad.chat', $task->token);
    }
}
