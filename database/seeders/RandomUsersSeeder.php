<?php

namespace Database\Seeders;

use App\Models\Chat;
use App\Models\Assistant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RandomUsersSeeder extends Seeder
{
    private array $assistantNames = [
        'Client Onboarding Guide',
        'Meeting Notes Summariser',
        'Proposal Drafter',
        'Email Follow-Up Writer',
        'Session Prep Assistant',
        'Invoice Chaser',
        'Content Calendar Planner',
        'Social Media Caption Writer',
        'Discovery Call Scheduler',
        'Lead Qualifier',
        'FAQ Responder',
        'Feedback Collector',
        'Workshop Facilitator Notes',
        'Course Outline Builder',
        'Testimonial Requester',
        'Weekly Report Generator',
        'Expense Categoriser',
        'Client Check-In Drafter',
        'SOPs Documenter',
        'Blog Post Outliner',
        'Podcast Show Notes Writer',
        'Referral Follow-Up',
        'Contract Clause Explainer',
        'Waitlist Manager',
        'Offboarding Checklist',
        'Meal Plan Builder',
        'Progress Tracker',
        'Intake Form Reviewer',
        'Appointment Reminder Drafter',
        'Partnership Outreach Writer',
    ];

    private array $bottlenecks = [
        'Sending welcome emails and scheduling first calls with new clients',
        'Writing up meeting notes and action items after every call',
        'Drafting proposals from discovery call notes',
        'Following up with prospects who went quiet after the first email',
        'Compiling session prep notes from client journal entries',
        'Chasing overdue invoices with polite reminder emails',
        'Planning a month of social media content across platforms',
        'Writing captions and hashtags for Instagram and LinkedIn posts',
        'Scheduling and confirming discovery calls with new leads',
        'Qualifying inbound leads based on intake form responses',
        'Answering the same client questions over and over by email',
        'Collecting and organising client feedback after each program',
        'Preparing facilitation notes and agendas for group workshops',
        'Building course outlines from rough topic lists',
        'Asking happy clients for testimonials at the right time',
        'Pulling data from three tools into a weekly client report',
        'Sorting receipts and categorising expenses for bookkeeping',
        'Sending fortnightly check-in emails to all active clients',
        'Documenting standard operating procedures from scratch',
        'Creating blog post outlines from keyword research',
        'Writing show notes and timestamps for podcast episodes',
        'Following up with clients who referred someone',
        'Explaining contract terms to clients in plain language',
        'Managing a waitlist and notifying people when spots open',
        'Running through an offboarding checklist when a client finishes',
        'Creating weekly meal plans customised to client dietary needs',
        'Compiling client progress data into a visual summary',
        'Reviewing intake forms and flagging anything unusual',
        'Drafting appointment reminder emails a day before each session',
        'Writing outreach emails to potential collaboration partners',
    ];

    private array $userMessages = [
        "I run a coaching business and this process takes me ages every week.",
        "It's about 2 hours each time I do it. I'd love to get that time back.",
        "I use Google Workspace for most things — Gmail, Docs, Sheets, Calendar.",
        "My clients are mostly small business owners and professionals.",
        "I usually do this manually. Copy-paste from one doc to another.",
        "The tone should be warm and professional. Not too corporate.",
        "I'd want to review everything before it goes out to clients.",
        "Yes, that's exactly right. You've captured it perfectly.",
        "I handle about 15-20 clients at any given time.",
        "Ideally the assistant would do the first draft and I'd just tweak it.",
        "No, I don't have any templates yet. That's part of the problem.",
        "I keep everything in Notion and Practice Better.",
        "The biggest pain is the repetitiveness. Same steps every single time.",
        "I'd say I spend about 30 minutes per client on this each week.",
        "That summary looks great. Can we also add a section about tone?",
    ];

    private array $assistantMessages = [
        "Great — that's a solid process to automate. Let me ask a few questions to understand the details.",
        "How many clients are you doing this for each week, roughly?",
        "And when you say you do it manually, walk me through the exact steps from start to finish.",
        "What tools are you using day to day? Email, project management, anything specific?",
        "Got it. So the main pain point is the time spent on repetitive steps, not the complexity of the work itself?",
        "That makes sense. What tone do you want the assistant to use when communicating with clients?",
        "Do you have any existing templates or examples I could base the assistant's output on?",
        "Perfect. And do you want the assistant to handle the output end-to-end, or would you prefer to review before anything goes out?",
        "Let me make sure I understand the full workflow before I put the instruction sheet together.",
        "One more question — are there any edge cases or situations where the process changes?",
    ];

    public function run(): void
    {
        $userCount = rand(25, 30);

        for ($i = 0; $i < $userCount; $i++) {
            $user = User::factory()->create([
                'password' => rand(0, 1) ? bcrypt('password') : null,
                'stripe_id' => 'cus_test_random_' . Str::random(8),
                'pm_type' => rand(0, 1) ? fake()->randomElement(['visa', 'mastercard']) : null,
                'pm_last_four' => rand(0, 1) ? (string) rand(1000, 9999) : null,
                'created_at' => now()->subDays(rand(1, 60)),
            ]);

            $taskCount = rand(1, 9);

            for ($j = 0; $j < $taskCount; $j++) {
                $isCompleted = rand(0, 100) < 70;
                $nameIndex = ($i * 4 + $j) % count($this->assistantNames);

                $assistant = Assistant::create([
                    'token' => (string) Str::uuid(),
                    'stripe_payment_id' => 'cs_test_' . Str::random(10),
                    'stripe_customer_id' => $user->stripe_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $isCompleted ? 'completed' : 'active',
                    'phase' => 1,
                    'playbook_delivered' => $isCompleted,
                    'assistant_name' => $isCompleted ? $this->assistantNames[$nameIndex] : null,
                    'bottleneck_summary' => $this->bottlenecks[$nameIndex],
                    'user_id' => $user->id,
                    'created_at' => $user->created_at->addDays($j * rand(1, 5)),
                ]);

                $this->seedMessages($assistant, $isCompleted);
            }
        }
    }

    private function seedMessages(Assistant $assistant, bool $isCompleted): void
    {
        $messageCount = $isCompleted ? rand(8, 12) : rand(3, 6);
        $baseTime = $assistant->created_at;

        for ($i = 0; $i < $messageCount; $i++) {
            $isLast = $i === $messageCount - 1;
            $role = ($i % 2 === 0) ? 'assistant' : 'user';

            if ($isLast && $isCompleted) {
                $role = 'assistant';
            }

            $isSheet = $isLast && $isCompleted && $role === 'assistant';

            $content = $role === 'user'
                ? $this->userMessages[array_rand($this->userMessages)]
                : $this->assistantMessages[array_rand($this->assistantMessages)];

            if ($isSheet) {
                $content = $this->generateInstructionSheet($assistant);
            }

            Chat::create([
                'task_id' => $assistant->id,
                'role' => $role,
                'content' => $content,
                'phase' => 1,
                'is_deliverable' => $isSheet,
                'created_at' => $baseTime->copy()->addMinutes($i * rand(1, 4)),
            ]);
        }
    }

    private function generateInstructionSheet(Assistant $assistant): string
    {
        $name = $assistant->assistant_name ?? 'Untitled Assistant';
        $summary = $assistant->bottleneck_summary ?? 'General business task automation';

        return <<<SHEET
        # Your AI Assistant Instruction Sheet

        ## Assistant name
        {$name}

        ## What the assistant handles
        {$summary}

        ## When to use it
        Use this assistant whenever you need to perform this task. It works best when you provide clear inputs and review the output before sending to clients.

        ## Step-by-step instructions

        ### Step 1: Gather the inputs
        Collect the relevant information from your tools — client details, recent notes, and any context needed.

        ### Step 2: Process and draft
        The assistant reviews the inputs, identifies key points, and creates a draft output following your preferred format and tone.

        ### Step 3: Review and send
        Check the draft, make any tweaks, and send it on its way. The assistant learns from your edits over time.

        ## Tone and style
        Warm, professional, and clear. Match the voice your clients already know and trust.

        ## What the assistant should never do
        - Send anything to clients without your review
        - Make up information not provided in the inputs
        - Give advice outside your area of expertise
        SHEET;
    }
}
