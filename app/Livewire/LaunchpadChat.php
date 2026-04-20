<?php

namespace App\Livewire;

use App\Jobs\GenerateLaunchpadOutputJob;
use App\Models\ChatSession;
use App\Models\Lead;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class LaunchpadChat extends Component
{
    public int $step = 0;

    public string $buyerName = '';
    public string $businessDescription = '';
    public string $toolsUsed = '';
    public string $businessRole = '';
    public string $whoTheyServe = '';
    public array $timeDrainChecks = [];
    public string $timeDrainOther = '';
    public string $tediousWork = '';
    public string $oneHandoffToday = '';
    public string $aiUsageLevel = '';
    public string $buyerEmail = '';

    public ?string $sessionToken = null;
    public ?int $leadId = null;

    public const TOOL_OPTIONS = [
        'Claude CoWork',
        'Claude',
        'ChatGPT',
        'Gemini',
        'Copilot',
        'None yet',
    ];

    public const TIME_DRAIN_OPTIONS = [
        'Client emails and messages',
        'Scheduling and rescheduling',
        'Proposals and quotes',
        'Invoicing and chasing payment',
        'Writing content or newsletters',
        'Social media and marketing',
        'Finding new leads',
        'Session notes and follow-ups',
        'Onboarding new clients',
        'Admin and paperwork',
    ];

    public const AI_USAGE_OPTIONS = [
        'Light' => 'Light, I have tried it a few times.',
        'Regular' => 'Regular, I use it most weeks.',
        'Power user' => 'Power user, it is part of my daily workflow.',
    ];

    public function mount(): void
    {
        $this->sessionToken = (string) Str::uuid();
        ChatSession::create([
            'session_token' => $this->sessionToken,
            'source' => 'launchpad',
            'current_step' => 0,
            'answers' => [],
            'last_activity_at' => now(),
        ]);
    }

    public function start(): void
    {
        $this->step = 1;
        $this->persistSession();
    }

    public function submitStep(): void
    {
        match ($this->step) {
            1 => $this->submitBuyerName(),
            2 => $this->submitBusinessDescription(),
            3 => null, // advanced by chooseTool
            4 => $this->submitBusinessRole(),
            5 => $this->submitWhoTheyServe(),
            6 => $this->submitTimeDrains(),
            7 => $this->submitTediousWork(),
            8 => $this->submitOneHandoffToday(),
            9 => null, // advanced by chooseAiUsage / skipAiUsage
            10 => $this->submitEmail(),
            default => null,
        };
    }

    public function chooseTool(string $tool): void
    {
        if (! in_array($tool, self::TOOL_OPTIONS, true)) {
            return;
        }
        $this->toolsUsed = $tool;
        $this->advance();
    }

    public function chooseAiUsage(string $level): void
    {
        if (! array_key_exists($level, self::AI_USAGE_OPTIONS)) {
            return;
        }
        $this->aiUsageLevel = $level;
        $this->advance();
    }

    public function skipAiUsage(): void
    {
        $this->aiUsageLevel = '';
        $this->advance();
    }

    private function submitBuyerName(): void
    {
        $this->buyerName = trim($this->buyerName);
        $this->validate([
            'buyerName' => ['required', 'string', 'max:100'],
        ], ['buyerName.required' => 'Just a first name is fine, whatever you want me to call you.']);
        $this->advance();
    }

    private function submitBusinessDescription(): void
    {
        $this->businessDescription = trim($this->businessDescription);
        $this->validate([
            'businessDescription' => ['required', 'string', 'min:20', 'max:500'],
        ], [
            'businessDescription.required' => 'Give me a little more so I can actually picture it. What do you do, and who do you do it for?',
            'businessDescription.min' => 'Give me a little more so I can actually picture it. What do you do, and who do you do it for?',
        ]);
        $this->advance();
    }

    private function submitBusinessRole(): void
    {
        $this->businessRole = trim($this->businessRole);
        $this->validate([
            'businessRole' => ['required', 'string', 'max:120'],
        ]);
        $this->advance();
    }

    private function submitWhoTheyServe(): void
    {
        $this->whoTheyServe = trim($this->whoTheyServe);
        $this->validate([
            'whoTheyServe' => ['required', 'string', 'min:15', 'max:500'],
        ], [
            'whoTheyServe.required' => 'Give me a bit more. Who actually pays you, and what stage or situation are they in?',
            'whoTheyServe.min' => 'Give me a bit more. Who actually pays you, and what stage or situation are they in?',
        ]);
        $this->advance();
    }

    private function submitTimeDrains(): void
    {
        $this->timeDrainOther = trim($this->timeDrainOther);
        if (empty($this->timeDrainChecks) && $this->timeDrainOther === '') {
            throw ValidationException::withMessages([
                'timeDrainChecks' => 'Pick at least one, or tell me in your own words. I need something to work with.',
            ]);
        }
        $this->advance();
    }

    private function submitTediousWork(): void
    {
        $this->tediousWork = trim($this->tediousWork);
        $this->validate([
            'tediousWork' => ['required', 'string', 'min:15', 'max:500'],
        ], [
            'tediousWork.required' => 'One more line. What is the stuff you avoid until Friday afternoon?',
            'tediousWork.min' => 'One more line. What is the stuff you avoid until Friday afternoon?',
        ]);
        $this->advance();
    }

    private function submitOneHandoffToday(): void
    {
        $this->oneHandoffToday = trim($this->oneHandoffToday);
        $this->validate([
            'oneHandoffToday' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'oneHandoffToday.required' => 'Be specific. One task. What would you hand off first?',
            'oneHandoffToday.min' => 'Be specific. One task. What would you hand off first?',
        ]);
        $this->advance();
    }

    private function submitEmail(): void
    {
        $this->buyerEmail = trim(strtolower($this->buyerEmail));
        $this->validate([
            'buyerEmail' => ['required', 'email:rfc'],
        ], [
            'buyerEmail.required' => 'That does not look like a valid email. Double-check it for me.',
            'buyerEmail.email' => 'That does not look like a valid email. Double-check it for me.',
        ]);

        $rateKey = 'launchpad-chat:' . (request()->ip() ?: 'unknown');
        if (RateLimiter::tooManyAttempts($rateKey, config('launchpad.chat_rate_limit', 5))) {
            $seconds = RateLimiter::availableIn($rateKey);
            $minutes = max(1, (int) ceil($seconds / 60));
            throw ValidationException::withMessages([
                'buyerEmail' => "Too many submissions from this connection. Please try again in about {$minutes} minute" . ($minutes === 1 ? '.' : 's.'),
            ]);
        }
        RateLimiter::hit($rateKey, 3600);

        $lead = $this->persistLead();
        $isDuplicate = $this->isDuplicateRecentSubmission();
        $lead->update([
            'buyer_email' => $this->buyerEmail,
            'status' => $isDuplicate ? 'duplicate' : 'completed',
            'completed_at' => now(),
        ]);
        $this->leadId = $lead->id;

        if (! $isDuplicate) {
            GenerateLaunchpadOutputJob::dispatch($lead);
        }

        $this->step = 11;
        $this->persistSession();
    }

    private function advance(): void
    {
        $lead = $this->persistLead();
        $this->leadId = $lead->id;

        $next = $this->step + 1;

        // Q9 is skipped when tools_used is "None yet". ai_usage_level defaults to "Light".
        if ($next === 9 && $this->toolsUsed === 'None yet') {
            $this->aiUsageLevel = 'Light';
            $next = 10;
            $this->persistLead();
        }

        $this->step = $next;
        $this->persistSession();
    }

    private function persistLead(): Lead
    {
        $payload = array_filter([
            'buyer_name' => $this->buyerName !== '' ? $this->buyerName : null,
            'business_description' => $this->businessDescription !== '' ? $this->businessDescription : null,
            'tools_used' => $this->toolsUsed !== '' ? $this->toolsUsed : null,
            'business_role' => $this->businessRole !== '' ? $this->businessRole : null,
            'who_they_serve' => $this->whoTheyServe !== '' ? $this->whoTheyServe : null,
            'time_drains' => $this->combineTimeDrains(),
            'tedious_work' => $this->tediousWork !== '' ? $this->tediousWork : null,
            'one_handoff_today' => $this->oneHandoffToday !== '' ? $this->oneHandoffToday : null,
            'ai_usage_level' => $this->aiUsageLevel !== '' ? $this->aiUsageLevel : null,
        ], fn ($v) => $v !== null);

        if (empty($payload) && $this->leadId === null) {
            // Nothing to save yet (e.g. opening screen click).
            return $this->findOrCreateLeadShell();
        }

        $lead = $this->findOrCreateLeadShell();
        $lead->fill($payload);
        $lead->save();

        return $lead;
    }

    private function findOrCreateLeadShell(): Lead
    {
        if ($this->leadId !== null) {
            return Lead::findOrFail($this->leadId);
        }
        $lead = Lead::create([
            'source' => 'launchpad',
            'status' => 'partial',
        ]);
        $this->leadId = $lead->id;

        return $lead;
    }

    private function combineTimeDrains(): ?string
    {
        $parts = [];
        foreach ($this->timeDrainChecks as $check) {
            if (in_array($check, self::TIME_DRAIN_OPTIONS, true)) {
                $parts[] = $check;
            }
        }
        if ($this->timeDrainOther !== '') {
            $parts[] = $this->timeDrainOther;
        }
        if (empty($parts)) {
            return null;
        }

        return implode('; ', $parts);
    }

    private function persistSession(): void
    {
        ChatSession::where('session_token', $this->sessionToken)->update([
            'lead_id' => $this->leadId,
            'current_step' => $this->step,
            'answers' => [
                'buyer_name' => $this->buyerName,
                'business_description' => $this->businessDescription,
                'tools_used' => $this->toolsUsed,
                'business_role' => $this->businessRole,
                'who_they_serve' => $this->whoTheyServe,
                'time_drain_checks' => $this->timeDrainChecks,
                'time_drain_other' => $this->timeDrainOther,
                'tedious_work' => $this->tediousWork,
                'one_handoff_today' => $this->oneHandoffToday,
                'ai_usage_level' => $this->aiUsageLevel,
                'buyer_email' => $this->buyerEmail,
            ],
            'last_activity_at' => now(),
        ]);
    }

    private function isDuplicateRecentSubmission(): bool
    {
        return Lead::where('buyer_email', $this->buyerEmail)
            ->where('id', '!=', $this->leadId)
            ->where('status', '!=', 'partial')
            ->where('completed_at', '>=', now()->subHours(24))
            ->exists();
    }

    #[Layout('components.layouts.public')]
    public function render()
    {
        return view('livewire.launchpad-chat', [
            'toolOptions' => self::TOOL_OPTIONS,
            'timeDrainOptions' => self::TIME_DRAIN_OPTIONS,
            'aiUsageOptions' => self::AI_USAGE_OPTIONS,
        ]);
    }
}
