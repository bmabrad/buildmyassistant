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

        return view('dashboard.index', [
            'user' => $user,
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
}
