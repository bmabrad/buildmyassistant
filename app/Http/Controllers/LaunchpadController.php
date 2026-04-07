<?php

namespace App\Http\Controllers;

use App\Models\Assistant;
use App\Services\InstructionSheetPdfService;
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

    public function downloadInstructions(Request $request, string $token)
    {
        $task = $request->attributes->get('launchpad_task');

        $instructionSheet = $task->chats()
            ->where('is_instruction_sheet', true)
            ->reorder()
            ->latest('id')
            ->first();

        if (! $instructionSheet) {
            abort(404);
        }

        return response($instructionSheet->content, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="your-assistant-instructions.txt"',
        ]);
    }

    public function downloadInstructionsPdf(Request $request, string $token)
    {
        $task = $request->attributes->get('launchpad_task');

        // Support downloading a specific instruction sheet by message ID
        $messageId = $request->query('message');

        if ($messageId) {
            $instructionSheet = $task->chats()
                ->where('id', $messageId)
                ->where('is_instruction_sheet', true)
                ->first();
        } else {
            $instructionSheet = $task->chats()
                ->where('is_instruction_sheet', true)
                ->reorder()
                ->latest('id')
                ->first();
        }

        if (! $instructionSheet) {
            abort(404);
        }

        $pdfService = app(InstructionSheetPdfService::class);
        $pdf = $pdfService->generate($task, $instructionSheet->content);
        $filename = $pdfService->filename($task);

        return $pdf->download($filename);
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
