<?php

namespace App\Http\Controllers;

use App\Models\LaunchpadTask;
use Illuminate\Http\Request;
use Laravel\Cashier\Checkout;

class LaunchpadController extends Controller
{
    public function show()
    {
        return view('launchpad.sales');
    }

    public function checkout()
    {
        return Checkout::guest()->create(config('services.stripe.launchpad_price_id'), [
            'success_url' => route('launchpad.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('launchpad'),
            'customer_creation' => 'always',
        ]);
    }

    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return redirect()->route('launchpad');
        }

        $task = $this->findTaskForSession($sessionId);

        if (! $task) {
            return redirect()->route('launchpad')
                ->with('error', 'We could not find your session. Please contact support.');
        }

        return redirect()->route('launchpad.chat', $task->token);
    }

    public function chat(Request $request, string $token)
    {
        $task = $request->attributes->get('launchpad_task');

        return view('launchpad.chat', ['task' => $task]);
    }

    private function findTaskForSession(string $sessionId): ?LaunchpadTask
    {
        // Try up to 5 times over ~5 seconds to handle the race condition
        // where the buyer's redirect arrives before the webhook creates the task.
        $attempts = 5;

        for ($i = 0; $i < $attempts; $i++) {
            $task = LaunchpadTask::where('stripe_payment_id', $sessionId)->first();

            if ($task) {
                return $task;
            }

            if ($i < $attempts - 1) {
                sleep(1);
            }
        }

        return null;
    }
}
