<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function start(Request $request, User $user)
    {
        $admin = $request->user();

        if (! $admin || ! $admin->is_admin) {
            abort(403);
        }

        if ($user->is_admin) {
            abort(403, 'Cannot impersonate another admin.');
        }

        $request->session()->put('impersonating_from', $admin->id);

        Auth::login($user);

        return redirect('/dashboard');
    }

    public function stop(Request $request)
    {
        $adminId = $request->session()->get('impersonating_from');

        if (! $adminId) {
            return redirect('/dashboard');
        }

        $request->session()->forget('impersonating_from');

        Auth::loginUsingId($adminId);

        return redirect('/admin');
    }
}
