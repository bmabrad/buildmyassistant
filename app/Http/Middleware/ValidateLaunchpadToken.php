<?php

namespace App\Http\Middleware;

use App\Models\Assistant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateLaunchpadToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('token');

        $task = Assistant::where('token', $token)->first();

        if (! $task) {
            abort(404);
        }

        // Transition pending tasks to active on first access
        if ($task->status === 'pending') {
            $task->update(['status' => 'active']);
        }

        $request->attributes->set('launchpad_task', $task);

        return $next($request);
    }
}
