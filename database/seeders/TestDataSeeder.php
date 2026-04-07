<?php

namespace Database\Seeders;

use App\Models\Chat;
use App\Models\Assistant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── User 1: Karen — 3 completed builds, password set, saved payment method ──

        $karen = User::create([
            'name' => 'Karen Whitfield',
            'first_name' => 'Karen',
            'last_name' => 'Whitfield',
            'email' => 'karen.whitfield@outlook.com.au',
            'password' => 'password',
            'email_verified_at' => now()->subDays(14),
        ]);
        $karen->forceFill([
            'stripe_id' => 'cus_UI2Lynlyve0Bec',
            'pm_type' => 'visa',
            'pm_last_four' => '4242',
        ])->save();

        $this->createCompletedBuild($karen, [
            'assistant_name' => 'Client Intake Navigator',
            'bottleneck_summary' => 'Onboarding questionnaire and initial consult scheduling for new coaching clients',
            'created_at' => now()->subDays(14),
        ], $this->clientIntakeMessages());

        $this->createCompletedBuild($karen, [
            'assistant_name' => 'Session Prep Buddy',
            'bottleneck_summary' => 'Pre-session prep notes compiled from client journal entries and progress logs',
            'created_at' => now()->subDays(7),
        ], $this->sessionPrepMessages());

        $this->createCompletedBuild($karen, [
            'assistant_name' => 'Follow-Up Drafter',
            'bottleneck_summary' => 'Post-session follow-up emails with action items and resource links',
            'created_at' => now()->subDays(2),
        ], $this->followUpMessages());

        // ── User 2: David — 1 completed + 1 in-progress, no password (magic link only) ──

        $david = User::create([
            'name' => 'David Nguyen',
            'first_name' => 'David',
            'last_name' => 'Nguyen',
            'email' => 'david.nguyen@gmail.com',
            'password' => null,
            'email_verified_at' => now()->subDays(5),
        ]);
        $david->forceFill([
            'stripe_id' => 'cus_UI2LpCf5xboCds',
            'pm_type' => null,
            'pm_last_four' => null,
        ])->save();

        $this->createCompletedBuild($david, [
            'assistant_name' => 'Meal Plan Builder',
            'bottleneck_summary' => 'Weekly meal plans customised to client dietary requirements and macros',
            'created_at' => now()->subDays(5),
        ], $this->mealPlanMessages());

        $this->createInProgressBuild($david, [
            'created_at' => now()->subHours(3),
        ], $this->inProgressNutritionMessages());

        // ── User 3: Simone — 1 in-progress only, no password, no saved payment method ──

        $simone = User::create([
            'name' => 'Simone Barker',
            'first_name' => 'Simone',
            'last_name' => 'Barker',
            'email' => 'simone.barker@bigpond.com',
            'password' => null,
            'email_verified_at' => now()->subHours(1),
        ]);
        $simone->forceFill([
            'stripe_id' => 'cus_UI2L7m8638J7bB',
            'pm_type' => null,
            'pm_last_four' => null,
        ])->save();

        $this->createInProgressBuild($simone, [
            'created_at' => now()->subMinutes(45),
        ], $this->inProgressBookkeepingMessages());

        // ── Legacy tasks (no user_id, emails don't match any seeded user) ──

        $legacyAssistant1 = Assistant::create([
            'token' => (string) Str::uuid(),
            'stripe_payment_id' => 'cs_test_legacy_001',
            'stripe_customer_id' => 'cus_test_legacy_001',
            'name' => 'Margaret Thompson',
            'email' => 'margaret.thompson@iinet.net.au',
            'status' => 'completed',
            'phase' => 1,
            'phase_1_complete' => true,
            'assistant_name' => 'Proposal Writer',
            'bottleneck_summary' => 'Drafting consulting proposals from brief notes and past templates',
            'user_id' => null,
            'created_at' => now()->subDays(30),
        ]);
        $this->seedMessages($legacyAssistant1, $this->legacyProposalMessages());

        $legacyAssistant2 = Assistant::create([
            'token' => (string) Str::uuid(),
            'stripe_payment_id' => 'cs_test_legacy_002',
            'stripe_customer_id' => 'cus_test_legacy_002',
            'name' => 'Ross Patterson',
            'email' => 'ross.patterson@optusnet.com.au',
            'status' => 'active',
            'phase' => 1,
            'phase_1_complete' => false,
            'user_id' => null,
            'created_at' => now()->subDays(25),
        ]);
        $this->seedMessages($legacyAssistant2, $this->legacyInProgressMessages());
    }

    private function createCompletedBuild(User $user, array $taskAttrs, array $messages): void
    {
        $assistant = Assistant::create(array_merge([
            'token' => (string) Str::uuid(),
            'stripe_payment_id' => 'cs_test_' . Str::random(10),
            'stripe_customer_id' => $user->stripe_id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'completed',
            'phase' => 1,
            'phase_1_complete' => true,
            'user_id' => $user->id,
        ], $taskAttrs));

        $this->seedMessages($assistant, $messages);
    }

    private function createInProgressBuild(User $user, array $taskAttrs, array $messages): void
    {
        $assistant = Assistant::create(array_merge([
            'token' => (string) Str::uuid(),
            'stripe_payment_id' => 'cs_test_' . Str::random(10),
            'stripe_customer_id' => $user->stripe_id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'active',
            'phase' => 1,
            'phase_1_complete' => false,
            'user_id' => $user->id,
        ], $taskAttrs));

        $this->seedMessages($assistant, $messages);
    }

    private function seedMessages(Assistant $assistant, array $messages): void
    {
        $baseTime = $assistant->created_at ?? now();

        foreach ($messages as $i => $msg) {
            Chat::create([
                'task_id' => $assistant->id,
                'role' => $msg['role'],
                'content' => $msg['content'],
                'phase' => 1,
                'is_instruction_sheet' => $msg['is_instruction_sheet'] ?? false,
                'created_at' => $baseTime->copy()->addMinutes($i * 2),
            ]);
        }
    }

    // ───────────────────────────────────────────────────
    // Message sets for completed builds
    // ───────────────────────────────────────────────────

    private function clientIntakeMessages(): array
    {
        return [
            ['role' => 'assistant', 'content' => "Welcome! I'm your AI guide from Build My Assistant. I'm here to help you design a custom AI assistant for a process that's eating your time.\n\nLet's start with the big picture. What's the one process or task in your business that you'd most like to hand off to an AI assistant?"],
            ['role' => 'user', 'content' => "I spend ages onboarding new coaching clients. There's a questionnaire I send out, then I have to read through their answers, schedule the initial consult, and send a welcome pack. It takes me about 90 minutes per new client."],
            ['role' => 'assistant', 'content' => "Client onboarding — that's a great candidate for automation. 90 minutes per client adds up fast, especially if you're growing.\n\nLet me understand the steps. You mentioned a questionnaire, reviewing answers, scheduling, and sending a welcome pack. Can you walk me through what happens in order, from the moment someone signs up as a new client?"],
            ['role' => 'user', 'content' => "Sure. They fill out a Google Form with about 15 questions — their goals, health history, preferred session times, that sort of thing. I get notified, read through it, then send them a Calendly link for the initial session. After they book, I email them a welcome PDF and a link to my client portal."],
            ['role' => 'assistant', 'content' => "Clear process. So the flow is: Google Form response → you review → send Calendly link → they book → you send welcome pack.\n\nA few questions to sharpen this up:\n1. When you review their form answers, what are you actually looking for? Are you screening for anything, or is it more about familiarising yourself?\n2. Is the Calendly link the same for everyone, or do you pick different session types based on their answers?\n3. Is the welcome PDF always the same, or do you customise it?"],
            ['role' => 'user', 'content' => "Good questions. I'm mainly checking for red flags — if someone has a serious health condition, I want to know before the first session so I can prepare. The Calendly link is the same for everyone. The welcome PDF is always the same but I personalise the email with their name and reference something from their form answers so it doesn't feel robotic."],
            ['role' => 'assistant', 'content' => "So the review step has real value — you're screening and personalising. That's important to preserve in the assistant.\n\nLet me check: when you say you personalise the email by referencing their form answers, can you give me an example? Like what would you write for a client who mentioned they want to lose weight for a wedding?"],
            ['role' => 'user', 'content' => "Something like: 'I can see your wedding is coming up in October — congratulations! We'll make sure our first session maps out a realistic plan that fits around your wedding prep schedule.' Just something to show I've actually read their answers."],
            ['role' => 'assistant', 'content' => "That's a nice touch — it builds trust right away. Your assistant can absolutely do that.\n\nNow, what tools are you using day-to-day? You mentioned Google Forms and Calendly. What about email — Gmail? And is the client portal something like Practice Better or a custom setup?"],
            ['role' => 'user', 'content' => "Gmail for email, Google Forms for the questionnaire, Calendly for booking, and I use Practice Better as my client portal. Everything's pretty standard."],
            ['role' => 'assistant', 'content' => "Great stack — all of those have good integration options.\n\nLet me think about what this assistant would actually do. Here's what I'm picturing:\n\n1. A new form response comes in\n2. The assistant reads the answers and flags any health red flags for you to review\n3. It drafts a personalised welcome email referencing specific form answers\n4. The email includes the Calendly link and the welcome PDF\n5. You review the draft, approve or tweak it, and send\n\nDoes that match what you'd want, or would you change anything?"],
            ['role' => 'user', 'content' => "That's spot on. I'd love it if the assistant could also create a brief summary of the client — like a one-page profile I could glance at before our first session. Goals, any flags, preferred times, that sort of thing."],
            ['role' => 'assistant', 'content' => "Love it. A pre-session client brief — that's a natural extension.\n\nI've got everything I need. Let me put together your instruction sheet.\n\n---\n\n# Your AI Assistant Instruction Sheet\n\n## Assistant name\nClient Intake Navigator\n\n## What the assistant handles\nOnboarding questionnaire and initial consult scheduling for new coaching clients\n\n## When to use it\nEvery time a new client submits the intake questionnaire via Google Form.\n\n## What you'll need to connect\n- Google Forms (intake questionnaire)\n- Gmail (sending welcome emails)\n- Calendly (booking link)\n- Practice Better (client portal link)\n\n## Step-by-step instructions for your assistant\n\n### Step 1: Read the intake form response\nWhen a new Google Form response arrives, read all 15 fields. Pay special attention to health history and any medical conditions mentioned.\n\n### Step 2: Flag red flags\nIf the client mentions any serious health conditions (e.g., diabetes, heart disease, eating disorders, recent surgery), create a summary note flagged as \"Review before first session\" and notify the coach.\n\n### Step 3: Create a client brief\nGenerate a one-page summary including:\n- Client name and contact details\n- Primary goals (in their own words)\n- Any health flags or considerations\n- Preferred session times\n- Key details worth noting for the first conversation\n\n### Step 4: Draft a personalised welcome email\nWrite a warm, professional email that:\n- Greets the client by name\n- References a specific detail from their form answers (e.g., their main goal or an upcoming event they mentioned)\n- Includes the Calendly link for booking their initial session\n- Attaches the welcome PDF\n- Provides the Practice Better portal link\n- Signs off with the coach's name\n\n### Step 5: Present for review\nShow the coach the client brief and draft email. Wait for approval before sending.\n\n## Tone and style\nWarm, professional, encouraging. Match the coach's natural communication style — conversational but knowledgeable. Avoid clinical or overly formal language.\n\n## What the assistant should never do\n- Send emails without the coach's approval\n- Diagnose or comment on health conditions\n- Promise specific outcomes or timelines\n- Share client information with anyone other than the coach", 'is_instruction_sheet' => true],
        ];
    }

    private function sessionPrepMessages(): array
    {
        return [
            ['role' => 'assistant', 'content' => "Welcome back! Ready to build another assistant. What process would you like to tackle this time?"],
            ['role' => 'user', 'content' => "Before each coaching session I spend about 30 minutes reading through the client's journal entries from the past week and pulling together prep notes. I'd love an assistant to do that for me."],
            ['role' => 'assistant', 'content' => "Session prep — that's a great one. 30 minutes per client per session adds up fast.\n\nTell me more about these journal entries. Where do clients write them? And what does your prep note usually look like when you're done?"],
            ['role' => 'user', 'content' => "Clients log entries in Practice Better. They write about meals, exercise, mood, and any challenges. My prep note is usually dot points: what they struggled with, what went well, trends I've noticed, and topics to bring up in the session."],
            ['role' => 'assistant', 'content' => "So the assistant would read the journal entries from Practice Better and produce a structured prep note with struggles, wins, trends, and suggested talking points.\n\nHow far back should it look? Just since the last session, or a longer window?"],
            ['role' => 'user', 'content' => "Since the last session. Usually that's one week but sometimes two if we've had a gap."],
            ['role' => 'assistant', 'content' => "Got it. And when you say trends — are you looking at things like 'client has mentioned feeling tired three times this week' or more like 'client has consistently hit their protein target for 10 days straight'?"],
            ['role' => 'user', 'content' => "Both, really. Patterns in what they're struggling with and patterns in what's going well. Also if something comes up repeatedly that we haven't addressed yet."],
            ['role' => 'assistant', 'content' => "Makes sense. One more question: do you want the prep note before every session automatically, or would you rather trigger it manually when you're ready to prep?"],
            ['role' => 'user', 'content' => "Automatically would be amazing. If it could land in my inbox an hour before each session, that would be perfect."],
            ['role' => 'assistant', 'content' => "I've got everything I need. Here's your instruction sheet.\n\n---\n\n# Your AI Assistant Instruction Sheet\n\n## Assistant name\nSession Prep Buddy\n\n## What the assistant handles\nPre-session prep notes compiled from client journal entries and progress logs\n\n## When to use it\nAutomatically triggered one hour before each scheduled coaching session.\n\n## What you'll need to connect\n- Practice Better (client journal entries and progress data)\n- Google Calendar or Calendly (session schedule)\n- Gmail (delivering the prep note)\n\n## Step-by-step instructions for your assistant\n\n### Step 1: Identify the upcoming session\nOne hour before a scheduled session, identify which client is booked and pull their profile.\n\n### Step 2: Gather journal entries\nRetrieve all journal entries from Practice Better since the client's last completed session. Include meal logs, exercise entries, mood tracking, and free-text reflections.\n\n### Step 3: Analyse for patterns\nReview the entries and identify:\n- **Struggles:** recurring challenges, missed targets, negative mood patterns\n- **Wins:** consistent positive behaviours, targets met, improvements\n- **Trends:** anything mentioned three or more times, or a notable shift from previous weeks\n- **Unaddressed topics:** issues the client has raised that haven't been discussed in a session yet\n\n### Step 4: Generate the prep note\nFormat as a concise document with these sections:\n- Client name and session date/time\n- Quick snapshot (1-2 sentences summarising the week)\n- Struggles (dot points)\n- Wins (dot points)\n- Trends to watch (dot points)\n- Suggested talking points for the session (3-5 items)\n\n### Step 5: Deliver\nEmail the prep note to the coach one hour before the session.\n\n## Tone and style\nDirect, concise, factual. This is a working document for the coach, not client-facing. Use dot points and keep it scannable.\n\n## What the assistant should never do\n- Contact the client directly\n- Make diagnostic statements about the client's health\n- Skip entries or selectively filter data\n- Share prep notes with anyone other than the coach", 'is_instruction_sheet' => true],
        ];
    }

    private function followUpMessages(): array
    {
        return [
            ['role' => 'assistant', 'content' => "Welcome back! What process are we building an assistant for today?"],
            ['role' => 'user', 'content' => "After every coaching session I write a follow-up email to the client with action items, any resources I mentioned, and a summary of what we covered. It takes me 20 minutes per client and I always fall behind on them."],
            ['role' => 'assistant', 'content' => "Follow-up emails — a common bottleneck. 20 minutes each, and the backlog builds up.\n\nWalk me through what a typical follow-up email looks like. What sections does it have?"],
            ['role' => 'user', 'content' => "Usually: a quick recap of what we discussed, the 3-4 action items we agreed on, links to any resources I mentioned (recipes, articles, exercises), and a reminder of when our next session is."],
            ['role' => 'assistant', 'content' => "Structured and repeatable — perfect for automation. How do you currently track what happened in the session? Do you take notes during the call, or work from memory?"],
            ['role' => 'user', 'content' => "I take quick dot-point notes in a Google Doc during the session. Pretty rough — just enough to jog my memory."],
            ['role' => 'assistant', 'content' => "So the assistant would take your rough session notes and turn them into a polished follow-up email. Where do you keep your resource library — the recipes and articles you share?"],
            ['role' => 'user', 'content' => "I have a Google Drive folder with my go-to resources organised by topic. Probably about 50 files in there."],
            ['role' => 'assistant', 'content' => "Great. And the tone of these emails — are they casual and friendly, or more structured and professional?"],
            ['role' => 'user', 'content' => "Friendly and encouraging. I want clients to feel supported, not like they're getting a corporate memo. I usually add a personal note at the end."],
            ['role' => 'assistant', 'content' => "Here's your instruction sheet.\n\n---\n\n# Your AI Assistant Instruction Sheet\n\n## Assistant name\nFollow-Up Drafter\n\n## What the assistant handles\nPost-session follow-up emails with action items and resource links\n\n## When to use it\nAfter each coaching session, when you've finished your session notes.\n\n## What you'll need to connect\n- Google Docs (session notes)\n- Google Drive (resource library)\n- Gmail (sending the follow-up)\n- Google Calendar or Calendly (next session date)\n\n## Step-by-step instructions for your assistant\n\n### Step 1: Read the session notes\nOpen the coach's session notes for the client. Extract key discussion points, agreed action items, and any resources mentioned by name or topic.\n\n### Step 2: Match resources\nSearch the Google Drive resource library for files matching the topics or resources mentioned in the session notes. Attach the relevant links.\n\n### Step 3: Look up the next session\nCheck the calendar for the client's next booked session. Include the date and time in the email.\n\n### Step 4: Draft the follow-up email\nStructure:\n- Greeting using the client's first name\n- Brief recap of what was discussed (2-3 sentences, warm tone)\n- Action items as a numbered list\n- Resources section with linked files from Google Drive\n- Next session reminder\n- Personal sign-off from the coach\n\n### Step 5: Present for review\nShow the draft to the coach for approval before sending.\n\n## Tone and style\nFriendly, encouraging, supportive. Conversational but clear. Avoid jargon. The client should feel like they're hearing from their coach, not from a system.\n\n## What the assistant should never do\n- Send the email without the coach's approval\n- Invent action items that weren't in the session notes\n- Diagnose or give medical advice\n- Share session content with anyone other than the client and coach", 'is_instruction_sheet' => true],
        ];
    }

    private function mealPlanMessages(): array
    {
        return [
            ['role' => 'assistant', 'content' => "Welcome! I'm your AI guide from Build My Assistant. Let's design a custom AI assistant for a process that's eating your time.\n\nWhat's the one task you'd most like to hand off?"],
            ['role' => 'user', 'content' => "I'm a nutritionist and I spend hours every week building meal plans for clients. Each one is different based on their dietary requirements, allergies, calorie targets, and food preferences. I'd love an assistant to draft them for me."],
            ['role' => 'assistant', 'content' => "Meal planning — that's a high-value, repetitive task. Perfect candidate.\n\nTell me about your current process. How do you build a meal plan today, step by step?"],
            ['role' => 'user', 'content' => "I start with their dietary profile — calories, macros, allergies, preferences. Then I plan 7 days of meals: breakfast, morning snack, lunch, afternoon snack, dinner. I try to reuse ingredients across the week to keep the shopping list simple. Then I create a shopping list and format the whole thing in a PDF."],
            ['role' => 'assistant', 'content' => "That's thorough. The ingredient reuse is a smart constraint — it keeps things practical.\n\nWhere do you store client dietary profiles? And how do clients receive the finished meal plan?"],
            ['role' => 'user', 'content' => "Profiles are in Practice Better. I email the PDF to clients and also upload it to their Practice Better portal."],
            ['role' => 'assistant', 'content' => "How much customisation happens week to week? Is it a completely new plan each time, or do you rotate from a template library?"],
            ['role' => 'user', 'content' => "Mix of both. I have about 200 recipes I rotate through, but I always customise based on what's in season and any feedback from the client about the previous week. If they hated a meal, I swap it out and note the preference."],
            ['role' => 'assistant', 'content' => "So the assistant needs to know the recipe library, client preferences including dislikes, seasonal availability, and the macro targets. That's a well-defined problem.\n\nOne more thing — do you want the assistant to generate the PDF, or just give you the structured plan to drop into your own template?"],
            ['role' => 'user', 'content' => "If it could output a formatted PDF ready to send, that would be the dream. But even a structured plan I can paste into my template would save me hours."],
            ['role' => 'assistant', 'content' => "Here's your instruction sheet.\n\n---\n\n# Your AI Assistant Instruction Sheet\n\n## Assistant name\nMeal Plan Builder\n\n## What the assistant handles\nWeekly meal plans customised to client dietary requirements and macros\n\n## When to use it\nWeekly, when preparing the next week's meal plans for clients.\n\n## What you'll need to connect\n- Practice Better (client dietary profiles and feedback)\n- Recipe library (Google Sheets or database of 200+ recipes)\n- Gmail (sending the finished plan)\n\n## Step-by-step instructions for your assistant\n\n### Step 1: Pull the client profile\nRetrieve the client's dietary profile from Practice Better: calorie target, macro split, allergies, food preferences, and any dislikes noted from previous weeks.\n\n### Step 2: Check previous feedback\nReview any notes or feedback from the client about last week's plan. Flag meals they disliked and exclude those recipes.\n\n### Step 3: Build the meal plan\nGenerate a 7-day meal plan with 5 meals per day (breakfast, morning snack, lunch, afternoon snack, dinner). Each meal should:\n- Meet the daily calorie and macro targets\n- Avoid allergens and disliked foods\n- Prioritise seasonal ingredients\n- Reuse key ingredients across multiple meals to simplify shopping\n\n### Step 4: Generate the shopping list\nCreate a consolidated shopping list grouped by category (produce, protein, dairy, pantry) with quantities for the full week.\n\n### Step 5: Format and deliver\nFormat the meal plan and shopping list as a clean, printable PDF. Present to the nutritionist for review before sending to the client.\n\n## Tone and style\nClean, practical, easy to scan. Use simple language — clients range from health-savvy to complete beginners.\n\n## What the assistant should never do\n- Ignore allergies or dietary restrictions\n- Recommend supplements or medications\n- Send a plan to a client without the nutritionist's review\n- Exceed or significantly undershoot the calorie/macro targets", 'is_instruction_sheet' => true],
        ];
    }

    // ───────────────────────────────────────────────────
    // Message sets for in-progress builds
    // ───────────────────────────────────────────────────

    private function inProgressNutritionMessages(): array
    {
        return [
            ['role' => 'assistant', 'content' => "Welcome back! What process are we building an assistant for today?"],
            ['role' => 'user', 'content' => "I want to automate the way I handle client progress check-ins. Every fortnight, clients send me their weight, measurements, and progress photos, and I have to compile it all into a tracking spreadsheet and write a progress summary."],
            ['role' => 'assistant', 'content' => "Progress tracking — another time-intensive task. Let me understand the current flow.\n\nHow do clients send you their check-in data? Is it a form, email, or something in Practice Better?"],
            ['role' => 'user', 'content' => "They submit it through a form in Practice Better. Weight, waist measurement, and they upload a photo. Some also add notes about how they're feeling."],
        ];
    }

    private function inProgressBookkeepingMessages(): array
    {
        return [
            ['role' => 'assistant', 'content' => "Welcome! I'm your AI guide from Build My Assistant. Let's design a custom AI assistant for a process that's eating your time.\n\nWhat's the one task you'd most like to hand off?"],
            ['role' => 'user', 'content' => "I'm a business coach and every week I spend ages categorising receipts and expenses for my clients. They send me photos of receipts and bank statements and I have to enter everything into Xero."],
            ['role' => 'assistant', 'content' => "Expense categorisation — that's a great fit for automation. How many clients are you doing this for, and roughly how many receipts per week?"],
        ];
    }

    // ───────────────────────────────────────────────────
    // Message sets for legacy tasks
    // ───────────────────────────────────────────────────

    private function legacyProposalMessages(): array
    {
        return [
            ['role' => 'assistant', 'content' => "Welcome! I'm your AI guide from Build My Assistant. Let's design a custom AI assistant for a process that's eating your time.\n\nWhat's the one task you'd most like to hand off?"],
            ['role' => 'user', 'content' => "I run a small consulting firm and writing proposals takes me forever. I want an assistant that can draft proposals based on my notes from the initial client call."],
            ['role' => 'assistant', 'content' => "Proposal writing — a classic bottleneck for consultants. How long does a typical proposal take you, and what does the finished product look like?"],
            ['role' => 'user', 'content' => "About 3 hours per proposal. It's usually 4-6 pages: executive summary, scope of work, timeline, pricing, and terms. I have templates but I customise heavily for each client."],
            ['role' => 'assistant', 'content' => "So the assistant needs your call notes, your templates, and your pricing structure. Where do you keep your proposal templates and past proposals?"],
            ['role' => 'user', 'content' => "All in Google Drive, organised by client. I've got about 40 past proposals in there."],
            ['role' => 'assistant', 'content' => "And the call notes — are they structured in any way, or just freeform notes?"],
            ['role' => 'user', 'content' => "Pretty freeform. I jot things down during the call — what they need, budget range, timeline, any constraints. Usually half a page of dot points."],
            ['role' => 'assistant', 'content' => "Here's your instruction sheet.\n\n---\n\n# Your AI Assistant Instruction Sheet\n\n## Assistant name\nProposal Writer\n\n## What the assistant handles\nDrafting consulting proposals from brief notes and past templates\n\n## When to use it\nAfter an initial client call, when you have your call notes ready.\n\n## What you'll need to connect\n- Google Drive (proposal templates and past proposals)\n- Google Docs (call notes)\n- Gmail (sending the draft for review)\n\n## Step-by-step instructions for your assistant\n\n### Step 1: Read the call notes\nReview the consultant's call notes for the prospective client. Extract: client needs, budget indicators, timeline, constraints, and any specific requests.\n\n### Step 2: Select a template\nSearch past proposals in Google Drive for the closest match based on industry, service type, and scope. Use this as the structural starting point.\n\n### Step 3: Draft the proposal\nGenerate a 4-6 page proposal with these sections:\n- Executive summary (tailored to the client's specific needs)\n- Scope of work (detailed deliverables)\n- Timeline (realistic based on the scope)\n- Pricing (based on the consultant's standard rates and the scope)\n- Terms and conditions (from the standard template)\n\n### Step 4: Present for review\nShare the draft with the consultant for review and approval.\n\n## Tone and style\nProfessional, confident, clear. Match the consultant's existing proposal voice.\n\n## What the assistant should never do\n- Send proposals to clients without the consultant's approval\n- Invent scope items not discussed in the call\n- Discount or modify pricing without explicit instruction\n- Share client information across proposals", 'is_instruction_sheet' => true],
        ];
    }

    private function legacyInProgressMessages(): array
    {
        return [
            ['role' => 'assistant', 'content' => "Welcome! I'm your AI guide from Build My Assistant. Let's design a custom AI assistant for a process that's eating your time.\n\nWhat's the one task you'd most like to hand off?"],
            ['role' => 'user', 'content' => "I need help with writing client reports. Every month I pull data from three different spreadsheets and write a narrative report for each client. Takes me about two hours per report."],
            ['role' => 'assistant', 'content' => "Monthly client reporting — that's a solid automation target. What kind of data are you pulling from those spreadsheets, and what does the final report look like?"],
        ];
    }
}
