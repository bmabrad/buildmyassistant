<?php

namespace App\Http\Controllers;

use App\Models\Assistant;
use App\Services\PlaybookPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Cashier\Checkout;

class LaunchpadController extends Controller
{
    public function show()
    {
        $canQuickBuy = false;

        if ($user = auth()->user()) {
            try {
                $canQuickBuy = $user->hasDefaultPaymentMethod();
            } catch (\Exception $e) {
                // Fall through to normal checkout
            }
        }

        return view('launchpad.sales', ['canQuickBuy' => $canQuickBuy]);
    }

    public function checkout()
    {
        $options = [
            'success_url' => route('launchpad.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('launchpad'),
            'customer_creation' => 'always',
            'payment_intent_data' => [
                'setup_future_usage' => 'off_session',
            ],
            'invoice_creation' => [
                'enabled' => true,
            ],
        ];

        if ($user = auth()->user()) {
            $options['customer_email'] = $user->email;
        }

        return Checkout::guest()->create(config('services.stripe.launchpad_price_id'), $options);
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

        // Auto-login the user on first purchase
        if (! Auth::check() && $task->user_id) {
            Auth::loginUsingId($task->user_id);
        }

        return redirect()->route('launchpad.chat', $task->token);
    }

    public function chat(Request $request, string $token)
    {
        $task = $request->attributes->get('launchpad_task');

        return view('launchpad.chat', ['task' => $task]);
    }

    public function downloadPlaybookPdf(Request $request, string $token)
    {
        $task = $request->attributes->get('launchpad_task');
        $deliverable = $this->findDeliverable($task, $request->query('message'));

        if (! $deliverable) {
            abort(404);
        }

        $pdfService = app(PlaybookPdfService::class);

        return $pdfService->download($task, $deliverable);
    }

    public function downloadInstructionsMd(Request $request, string $token)
    {
        $task = $request->attributes->get('launchpad_task');
        $deliverable = $this->findDeliverable($task, $request->query('message'));

        if (! $deliverable) {
            abort(404);
        }

        // Use the parsed instructions_content if available, otherwise fall back to full content
        $content = $deliverable->instructions_content ?? $deliverable->content;
        $name = $task->name ?? 'Customer';
        $filename = $name . ' - Assistant Instructions.md';

        return response($content, 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function downloadChat(Request $request, string $token)
    {
        $task = $request->attributes->get('launchpad_task');

        $messages = $task->chats()->orderBy('created_at', 'asc')->get();

        $content = $messages->map(function ($message) {
            $role = $message->role === 'user' ? 'You' : 'Guide';
            $timestamp = $message->created_at->format('Y-m-d H:i');
            return "[{$role}] ({$timestamp})\n{$message->content}";
        })->implode("\n\n");

        return response($content, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="launchpad-chat.txt"',
        ]);
    }

    private function findDeliverable(Assistant $task, ?string $messageId): ?\App\Models\Chat
    {
        if ($messageId) {
            return $task->chats()
                ->where('id', $messageId)
                ->where('is_deliverable', true)
                ->first();
        }

        return $task->chats()
            ->where('is_deliverable', true)
            ->reorder()
            ->latest('id')
            ->first();
    }

    private function findTaskForSession(string $sessionId): ?Assistant
    {
        // Try up to 5 times over ~5 seconds to handle the race condition
        // where the buyer's redirect arrives before the webhook creates the task.
        $attempts = 5;

        for ($i = 0; $i < $attempts; $i++) {
            $task = Assistant::where('stripe_payment_id', $sessionId)->first();

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
