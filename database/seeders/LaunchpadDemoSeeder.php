<?php

namespace Database\Seeders;

use App\Models\ChatSession;
use App\Models\Delivery;
use App\Models\Generation;
use App\Models\Lead;
use App\Models\PromptVersion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LaunchpadDemoSeeder extends Seeder
{
    public function run(): void
    {
        $prompt = PromptVersion::where('product', 'launchpad')->where('is_active', true)->first();

        $this->delivered('Sarah', 'sarah@example.com', [
            'business_description' => 'Leadership coaching for mid-career executives in finance and tech',
            'business_role' => 'Executive coach',
            'who_they_serve' => 'Directors and VPs navigating a step up into exec roles',
            'tools_used' => 'Claude CoWork',
            'time_drains' => 'Client emails and messages; Scheduling and rescheduling; Session notes and follow-ups',
            'tedious_work' => 'Writing session notes and chasing late invoices',
            'one_handoff_today' => 'Drafting the follow-up emails I send after every client session',
            'ai_usage_level' => 'Regular',
        ], 'Coach', 'Exec', $prompt);

        $this->delivered('Morgan', 'morgan@example.com', [
            'business_description' => 'Life coaching for high-performing creatives in media and design',
            'business_role' => 'Life coach',
            'who_they_serve' => 'Senior creatives leaning into a transition',
            'tools_used' => 'Claude',
            'time_drains' => 'Writing content or newsletters; Social media and marketing',
            'tedious_work' => 'Drafting the same social posts every week from voice memos',
            'one_handoff_today' => 'First drafts of my weekly newsletter',
            'ai_usage_level' => 'Power user',
        ], 'Coach', 'Marketing', $prompt);

        $this->generatedOnly('Marcus', 'marcus@example.com', [
            'business_description' => 'Operations consulting for 10-50 person SaaS companies',
            'business_role' => 'Management consultant',
            'who_they_serve' => 'Founders and Heads of Ops at early-stage SaaS',
            'tools_used' => 'ChatGPT',
            'time_drains' => 'Proposals and quotes; Finding new leads',
            'tedious_work' => 'Writing proposals from scratch for repeat scope',
            'one_handoff_today' => 'Drafting project proposals against the boilerplate',
            'ai_usage_level' => 'Regular',
        ], 'Consultant', 'Sales', $prompt);

        $this->fallbackDelivered('Jen', 'jen@example.com', [
            'business_description' => 'Financial advisory for founders approaching an exit',
            'business_role' => 'Financial advisor',
            'who_they_serve' => 'Founders planning a liquidity event in the next 18 months',
            'tools_used' => 'Claude',
            'time_drains' => 'Invoicing and chasing payment; Admin and paperwork',
            'tedious_work' => 'Monthly invoices for retainer clients',
            'one_handoff_today' => 'Drafting monthly retainer invoices from the CRM',
            'ai_usage_level' => 'Light',
        ], $prompt);

        $this->completedPending('Ben', 'ben@example.com', [
            'business_description' => 'Self-paced career-change course for mid-career professionals',
            'business_role' => 'Course creator',
            'who_they_serve' => 'Mid-career professionals planning a career pivot',
            'tools_used' => 'None yet',
            'time_drains' => 'Writing content or newsletters; Onboarding new clients',
            'tedious_work' => 'Welcome sequences for new students',
            'one_handoff_today' => 'Drafting new student welcome sequences',
            'ai_usage_level' => 'Light',
        ]);

        $this->duplicate('Pat', 'pat@example.com', [
            'business_description' => 'Strategy consulting for family-owned manufacturers',
            'business_role' => 'Strategy consultant',
            'who_they_serve' => 'Second-generation operators of manufacturing businesses',
            'tools_used' => 'Gemini',
            'time_drains' => 'Proposals and quotes; Finding new leads',
            'tedious_work' => 'Drafting proposals from prior engagements',
            'one_handoff_today' => 'Proposal drafting',
            'ai_usage_level' => 'Regular',
        ]);

        $this->partial('Alex', 'alex@example.com', 5, [
            'business_description' => 'Brand design studio for indie consumer goods brands',
            'business_role' => 'Brand designer',
            'who_they_serve' => 'Founders launching a first product run',
            'tools_used' => 'Claude CoWork',
        ]);

        $this->partial('Robin', null, 1, [
            'business_description' => null,
            'business_role' => null,
            'who_they_serve' => null,
            'tools_used' => null,
        ]);
    }

    private function delivered(string $name, string $email, array $answers, string $businessType, string $assistantPick, ?PromptVersion $prompt): void
    {
        $lead = Lead::create(array_merge([
            'source' => 'launchpad',
            'status' => 'delivered',
            'buyer_name' => $name,
            'buyer_email' => $email,
            'business_type' => $businessType,
            'assistant_pick' => $assistantPick,
            'completed_at' => now()->subHours(3),
            'generated_at' => now()->subHours(3)->addSeconds(45),
            'delivered_at' => now()->subHours(3)->addSeconds(75),
        ], $answers));

        $generation = Generation::create([
            'lead_id' => $lead->id,
            'prompt_version_id' => $prompt?->id,
            'product' => 'launchpad',
            'model' => 'claude-sonnet-4-6',
            'status' => 'success',
            'input_payload' => $this->inputFor($lead),
            'output' => $this->sampleOutput($name, $lead->tools_used ?: 'Claude CoWork', $businessType, $assistantPick),
            'latency_ms' => random_int(900, 2800),
            'input_tokens' => 3200,
            'output_tokens' => 1400,
        ]);

        Delivery::create([
            'lead_id' => $lead->id,
            'channel' => 'email',
            'provider' => 'resend',
            'provider_message_id' => 'msg_' . Str::random(16),
            'to_email' => $email,
            'subject' => "Your starter prompt for the {$assistantPick} Assistant, {$name}",
            'status' => 'sent',
            'sent_at' => $lead->delivered_at,
            'attachments' => [['filename' => "launchpad-{$lead->id}-{$generation->id}.pdf", 'path' => "storage/launchpad/deliveries/launchpad-{$lead->id}-{$generation->id}.pdf"]],
        ]);
    }

    private function generatedOnly(string $name, string $email, array $answers, string $businessType, string $assistantPick, ?PromptVersion $prompt): void
    {
        $lead = Lead::create(array_merge([
            'source' => 'launchpad',
            'status' => 'generated',
            'buyer_name' => $name,
            'buyer_email' => $email,
            'business_type' => $businessType,
            'assistant_pick' => $assistantPick,
            'completed_at' => now()->subHour(),
            'generated_at' => now()->subMinutes(58),
        ], $answers));

        $generation = Generation::create([
            'lead_id' => $lead->id,
            'prompt_version_id' => $prompt?->id,
            'product' => 'launchpad',
            'model' => 'claude-sonnet-4-6',
            'status' => 'success',
            'input_payload' => $this->inputFor($lead),
            'output' => $this->sampleOutput($name, $lead->tools_used ?: 'Claude CoWork', $businessType, $assistantPick),
            'latency_ms' => 1600,
            'input_tokens' => 3100,
            'output_tokens' => 1350,
        ]);

        Delivery::create([
            'lead_id' => $lead->id,
            'channel' => 'email',
            'provider' => 'resend',
            'to_email' => $email,
            'subject' => "Your starter prompt for the {$assistantPick} Assistant, {$name}",
            'status' => 'failed',
            'error' => 'Resend webhook returned 502 Bad Gateway. Will retry on next dispatch.',
        ]);
    }

    private function fallbackDelivered(string $name, string $email, array $answers, ?PromptVersion $prompt): void
    {
        $lead = Lead::create(array_merge([
            'source' => 'launchpad',
            'status' => 'delivered',
            'buyer_name' => $name,
            'buyer_email' => $email,
            'business_type' => 'Advisor',
            'assistant_pick' => 'Exec',
            'completed_at' => now()->subDays(2)->addMinutes(5),
            'generated_at' => now()->subDays(2)->addMinutes(6),
            'delivered_at' => now()->subDays(2)->addMinutes(7),
        ], $answers));

        $firstAttempt = Generation::create([
            'lead_id' => $lead->id,
            'prompt_version_id' => $prompt?->id,
            'product' => 'launchpad',
            'model' => 'claude-sonnet-4-6',
            'status' => 'schema_failed',
            'input_payload' => $this->inputFor($lead),
            'error' => 'other_four must be an array of exactly 4 entries.',
            'raw_response' => '{"business_type":"Advisor","other_four":[{...three entries...}]}',
            'latency_ms' => 2100,
            'input_tokens' => 3150,
            'output_tokens' => 1420,
        ]);

        $retry = Generation::create([
            'lead_id' => $lead->id,
            'prompt_version_id' => $prompt?->id,
            'retry_of_id' => $firstAttempt->id,
            'product' => 'launchpad',
            'model' => 'claude-sonnet-4-6',
            'status' => 'schema_failed',
            'input_payload' => $this->inputFor($lead),
            'error' => 'other_four must be an array of exactly 4 entries.',
            'raw_response' => '{"business_type":"Advisor","other_four":[...]}',
            'latency_ms' => 1890,
            'input_tokens' => 3150,
            'output_tokens' => 1380,
        ]);

        $fallback = Generation::create([
            'lead_id' => $lead->id,
            'prompt_version_id' => $prompt?->id,
            'retry_of_id' => $retry->id,
            'product' => 'launchpad',
            'model' => 'fallback',
            'status' => 'fallback',
            'input_payload' => $this->inputFor($lead),
            'output' => $this->sampleOutput($name, $lead->tools_used ?: 'Claude CoWork', 'Advisor', 'Exec'),
        ]);

        Delivery::create([
            'lead_id' => $lead->id,
            'channel' => 'email',
            'provider' => 'resend',
            'provider_message_id' => 'msg_' . Str::random(16),
            'to_email' => $email,
            'subject' => "Your starter prompt for the Exec Assistant, {$name}",
            'status' => 'sent',
            'sent_at' => $lead->delivered_at,
            'attachments' => [['filename' => "launchpad-{$lead->id}-{$fallback->id}.pdf", 'path' => "storage/launchpad/deliveries/launchpad-{$lead->id}-{$fallback->id}.pdf"]],
        ]);
    }

    private function completedPending(string $name, string $email, array $answers): void
    {
        Lead::create(array_merge([
            'source' => 'launchpad',
            'status' => 'completed',
            'buyer_name' => $name,
            'buyer_email' => $email,
            'completed_at' => now()->subMinutes(2),
        ], $answers));
    }

    private function duplicate(string $name, string $email, array $answers): void
    {
        Lead::create(array_merge([
            'source' => 'launchpad',
            'status' => 'delivered',
            'buyer_name' => $name,
            'buyer_email' => $email,
            'business_type' => 'Consultant',
            'assistant_pick' => 'Sales',
            'completed_at' => now()->subHours(6),
            'generated_at' => now()->subHours(6)->addSeconds(40),
            'delivered_at' => now()->subHours(6)->addSeconds(75),
        ], $answers));

        Lead::create(array_merge([
            'source' => 'launchpad',
            'status' => 'duplicate',
            'buyer_name' => $name,
            'buyer_email' => $email,
            'completed_at' => now()->subMinutes(30),
        ], $answers));
    }

    private function partial(string $name, ?string $email, int $step, array $answers): void
    {
        $lead = Lead::create(array_merge([
            'source' => 'launchpad',
            'status' => 'partial',
            'buyer_name' => $name,
            'buyer_email' => $email,
        ], $answers));

        ChatSession::create([
            'session_token' => (string) Str::uuid(),
            'lead_id' => $lead->id,
            'source' => 'launchpad',
            'current_step' => $step,
            'answers' => array_merge(['buyer_name' => $name], $answers),
            'last_activity_at' => now()->subMinutes(45),
        ]);
    }

    private function inputFor(Lead $lead): array
    {
        return [
            'buyer_name' => $lead->buyer_name,
            'buyer_email' => $lead->buyer_email,
            'business_description' => $lead->business_description,
            'business_role' => $lead->business_role,
            'who_they_serve' => $lead->who_they_serve,
            'tools_used' => $lead->tools_used,
            'time_drains' => $lead->time_drains,
            'tedious_work' => $lead->tedious_work,
            'one_handoff_today' => $lead->one_handoff_today,
            'ai_usage_level' => $lead->ai_usage_level,
        ];
    }

    private function sampleOutput(string $name, string $toolName, string $businessType, string $assistantPick): array
    {
        $roleNames = [
            'Exec' => 'Exec Assistant',
            'Finance' => 'Finance Assistant',
            'Sales' => 'Sales Assistant',
            'Marketing' => 'Marketing Assistant',
            'Operations' => 'Operations Assistant',
        ];
        $allRoles = ['Exec', 'Finance', 'Sales', 'Marketing', 'Operations'];
        $others = array_values(array_diff($allRoles, [$assistantPick]));

        $roleRemits = [
            'Exec' => 'Handles inbox, calendar, meeting prep, and daily admin.',
            'Finance' => 'Handles the numbers. Invoicing, cashflow, expense tracking.',
            'Sales' => 'Handles leads and pipeline. Research, proposals, follow-up.',
            'Marketing' => 'Handles content and promotion. Newsletters, posts, copy.',
            'Operations' => 'Handles delivery. Onboarding, SOPs, client workflow.',
        ];
        $rolePains = [
            'Exec' => 'the inbox starts costing you an hour a day',
            'Finance' => 'chasing payment starts eating an afternoon a week',
            'Sales' => 'discovery requests sit unanswered for days',
            'Marketing' => 'you have said the same thing to five clients and none of it is on your website',
            'Operations' => 'you are repeating the same setup steps with every new client',
        ];

        return [
            'business_type' => $businessType,
            'assistant_pick' => [
                'role' => $assistantPick,
                'reason' => "Based on what you told us, {$roleNames[$assistantPick]} is the most direct fit.",
            ],
            'cover' => [
                'subtitle' => "A starter prompt for your {$roleNames[$assistantPick]}.",
                'time_savings_line' => 'We typically see three to five hours a week back once this assistant is running.',
            ],
            'your_first_assistant' => [
                'remit_paragraph' => "Your {$roleNames[$assistantPick]} takes the first pass on the work that drains your time. " . $roleRemits[$assistantPick],
                'pain_paragraph' => 'You told us about the admin eating your week. This is exactly the work this assistant handles.',
                'time_savings_paragraph' => 'We typically see three to five hours a week back in the first two weeks. By the end of month one it usually climbs past six.',
            ],
            'starter_prompt' => "You are the {$roleNames[$assistantPick]} for {$name}. Your job is to take the first pass on the work that drains their time so they can focus on their core craft.\n\nWhat you handle:\n- " . $roleRemits[$assistantPick] . "\n- Ask one clarifying question when the ask is ambiguous.\n- Match their voice from examples they give you.\n\nWhat you do not do:\n- Do not send anything on their behalf without approval.\n- Do not wander outside your scope.\n\nStart by asking for three things: a recent example of the work, a recent reply they were proud of, and voice preferences they want you to match.",
            'install_steps' => [
                "Open {$toolName} and create a new project named '{$roleNames[$assistantPick]}'.",
                'Paste the prompt above into the project instructions.',
                'Give it three real tasks from your week. The assistant learns your voice fastest when the work is real.',
            ],
            'email_body' => [
                'subject' => "Your starter prompt for the {$roleNames[$assistantPick]}, {$name}",
                'opening_line' => "Based on what you told us, your first assistant should be the {$roleNames[$assistantPick]}. Here is a starter prompt you can use in {$toolName} today.",
                'upgrade_line' => 'This starter works. If you want a version trained on your specific business, voice, and clients, the Assistant Builder takes you through a short build and hands you the custom version for seven dollars.',
            ],
            'other_four' => array_map(fn ($role) => [
                'role' => $role,
                'one_line_remit' => $roleRemits[$role],
                'typical_pain' => 'When ' . $rolePains[$role] . '.',
            ], $others),
            'no_ai_appendix' => null,
        ];
    }
}
