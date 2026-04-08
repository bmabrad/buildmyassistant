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
        // ── User 1: Karen — 5 builds at each step for testing, password set, saved payment method ──

        $karen = User::create([
            'name' => 'Karen Whitfield',
            'first_name' => 'Karen',
            'last_name' => 'Whitfield',
            'email' => 'karen.whitfield@outlook.com.au',
            'password' => 'password',
            'email_verified_at' => now()->subDays(14),
        ]);
        $karen->forceFill([
            'stripe_id' => 'cus_UIH29y5ajZ1u2V',
            'pm_type' => 'visa',
            'pm_last_four' => '4242',
        ])->save();

        // Build 1: Paused at end of Step 1 (Bottleneck Discovery complete)
        $this->createInProgressBuild($karen, [
            'bottleneck_summary' => 'Email triage and response drafting',
            'created_at' => now()->subDays(14),
        ], $this->karenStep1Messages());

        // Build 2: Paused at end of Step 2 (Process Map complete)
        $this->createInProgressBuild($karen, [
            'bottleneck_summary' => 'Invoice chasing and payment follow-ups',
            'created_at' => now()->subDays(10),
        ], $this->karenStep2Messages());

        // Build 3: Paused at end of Step 3 (Assistant Design complete)
        $this->createInProgressBuild($karen, [
            'bottleneck_summary' => 'Client onboarding questionnaire and welcome pack',
            'created_at' => now()->subDays(7),
        ], $this->karenStep3Messages());

        // Build 4: At Step 4 (Instruction sheet delivered)
        $this->createCompletedBuild($karen, [
            'assistant_name' => 'Session Prep Buddy',
            'bottleneck_summary' => 'Pre-session prep notes from client journal entries',
            'created_at' => now()->subDays(4),
        ], $this->karenStep4Messages());

        // Build 5: At Step 5 (Launch — offered to go deeper, session closing)
        $this->createCompletedBuild($karen, [
            'assistant_name' => 'Follow-Up Drafter',
            'bottleneck_summary' => 'Post-session follow-up emails with action items',
            'created_at' => now()->subDays(2),
        ], $this->karenStep5Messages());

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
            'stripe_id' => 'cus_UIH2GZY6LzBorm',
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
            'stripe_id' => 'cus_UIH2gAmvwBuT6Z',
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
            'playbook_delivered' => true,
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
            'playbook_delivered' => false,
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
            'playbook_delivered' => true,
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
            'playbook_delivered' => false,
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
                'is_deliverable' => $msg['is_deliverable'] ?? false,
                'created_at' => $baseTime->copy()->addMinutes($i * 2),
            ]);
        }
    }

    // ───────────────────────────────────────────────────
    // Karen's step-based message sets (one per step)
    // ───────────────────────────────────────────────────

    private function karenStep1Messages(): array
    {
        // Email triage — paused at end of Step 1 (Bottleneck Discovery confirmed)
        return [
            ['role' => 'assistant', 'content' => "Hey Karen! I'm your AI guide from Build My Assistant, and I'm excited to build something that's going to save you a heap of time.\n\nThis session is all about designing a custom AI assistant for a process that's eating into your day. Do you already know what process you'd like to automate, or would you like me to help you figure that out?"],
            ['role' => 'user', 'content' => "I know exactly what it is. I spend way too long triaging my inbox every morning. Reading emails, deciding what needs a reply, drafting responses, flagging things for later. It eats up the first two hours of my day."],
            ['role' => 'assistant', 'content' => "Email triage — that's one of the best processes to hand off to an assistant. Two hours every morning is a lot of your best energy going to sorting and responding.\n\nSo you're reading through everything that's come in, deciding what needs attention, and drafting replies. Is that roughly every morning, or does it pile up over the week too?"],
            ['role' => 'user', 'content' => "Every single morning. And if I skip a day it's even worse. I'd say I get about 40-50 emails overnight and through the morning."],
            ['role' => 'assistant', 'content' => "40 to 50 emails a day, every day. That's a serious volume. And it sounds like the cost isn't just the time — it's that it burns through your focus before you get to the work that actually matters.\n\nAre most of those emails from clients, or is it a mix of clients, suppliers, newsletters, and admin?"],
            ['role' => 'user', 'content' => "Mix of everything. Maybe 15-20 are from clients or potential clients that actually need a reply. The rest is newsletters, notifications from tools, supplier invoices, and stuff I can just archive."],
            ['role' => 'assistant', 'content' => "So roughly half your inbox is noise you could archive straight away, and the other half needs real attention. That's a really clear split — perfect for an assistant.\n\nLet me make sure I've got the full picture: you're spending about two hours every morning triaging 40 to 50 emails, where about half need a real response and the rest can be archived or filed. Is that right?"],
            ['role' => 'user', 'content' => "That's exactly it."],
        ];
    }

    private function karenStep2Messages(): array
    {
        // Invoice chasing — through Step 1 + Step 2 (Process Map complete)
        return [
            ['role' => 'assistant', 'content' => "Hey Karen! I'm your AI guide from Build My Assistant. I can already tell we're going to build something great today.\n\nDo you already know what process you'd like to automate, or would you like me to help you figure that out?"],
            ['role' => 'user', 'content' => "Yes — chasing invoices. I send invoices to clients after each coaching package and then I have to follow up when they don't pay on time. It's awkward and it takes ages."],
            ['role' => 'assistant', 'content' => "Invoice chasing — nobody enjoys that one, and it's the kind of thing that slips when you're busy. How often are you having to follow up, and how many clients are we talking about?"],
            ['role' => 'user', 'content' => "I've got about 25 active clients. I'd say maybe 8-10 of them are late on any given month. I usually have to send two or three follow-ups before they pay."],
            ['role' => 'assistant', 'content' => "So you're sending maybe 20 to 30 follow-up emails a month, plus tracking who's paid and who hasn't. That's a lot of mental overhead on top of the actual sending.\n\nYou're spending time on invoicing roughly every week, and it takes about how long each time you sit down to chase them?"],
            ['role' => 'user', 'content' => "Probably 45 minutes to an hour each week. I check Xero for who's overdue, then write individual emails. I try to keep them friendly because these are my clients, but it's still uncomfortable."],
            ['role' => 'assistant', 'content' => "About an hour a week on something that drains your energy. That's a solid candidate.\n\nSo the process is: check Xero for overdue invoices, identify who needs a nudge, draft a friendly follow-up email for each one, and send. Is that the full picture?"],
            ['role' => 'user', 'content' => "Pretty much. I also keep a mental note of how many times I've chased someone — the first reminder is gentle, the second is firmer, and if there's a third I usually call them instead."],
            ['role' => 'assistant', 'content' => "Got it — escalating tone based on how many reminders they've had. That's exactly the kind of logic an assistant can handle beautifully.\n\nLet me map out everything that's actually involved in this process so we can make sure your assistant covers all of it.\n\n**Your invoice chasing process:**\n\n1. **Check overdue invoices** — log into Xero, pull up the aged receivables or overdue list. *(Automatic — assistant can review this directly)*\n2. **Identify who needs chasing** — filter out anyone who's only a day or two late vs. genuinely overdue. *(Learnable — assistant learns your threshold from past patterns)*\n3. **Check chase history** — how many reminders has this client already received? *(Automatic — assistant tracks its own send history)*\n4. **Select the right tone** — first reminder is gentle, second is firmer, third triggers a phone call flag. *(Needs guidance — you'll set the tone rules)*\n5. **Draft the follow-up email** — personalised to the client, referencing the specific invoice number and amount. *(Automatic — assistant drafts from templates + client context)*\n6. **Send or queue for review** — depending on your comfort level, either send directly or present for your approval. *(Needs guidance — your call on how much autonomy)*\n7. **Log the chase** — record that a follow-up was sent so the next check knows where things stand. *(Automatic)*\n8. **Flag for phone call** — if a client hits the third reminder, flag them for a personal call instead. *(Automatic based on rules)*\n\nDoes that cover everything? Are there bits in there you keep skipping because there's just not enough time?"],
            ['role' => 'user', 'content' => "That's incredibly thorough. The logging is the bit I always skip — I just try to remember who I've chased and when. Having that tracked automatically would be huge."],
            ['role' => 'assistant', 'content' => "That's exactly the kind of thing that falls through the cracks when you're doing it manually. Your assistant will handle all the tracking so you never have to hold it in your head.\n\nWould it help to have something handle most of this for you, so you're only stepping in for the phone calls and the occasional tricky client?"],
            ['role' => 'user', 'content' => "Yes, absolutely. That would save me so much mental energy."],
        ];
    }

    private function karenStep3Messages(): array
    {
        // Client onboarding — through Steps 1-3 (Assistant Design complete)
        return [
            ['role' => 'assistant', 'content' => "Hey Karen! I'm your AI guide from Build My Assistant. Let's build you something that's going to make a real difference.\n\nDo you already know what process you'd like to automate, or would you like me to help you figure that out?"],
            ['role' => 'user', 'content' => "I want to automate my client onboarding. Every time I get a new coaching client, I send them a questionnaire, review their answers, schedule the first session, and send a welcome pack. Takes me about 90 minutes per client."],
            ['role' => 'assistant', 'content' => "Client onboarding at 90 minutes per new client — that adds up fast, especially when you're growing. And it's the kind of process where consistency matters because it's the client's first impression of working with you.\n\nSo you're sending a questionnaire, reviewing answers, scheduling, and sending a welcome pack. Is that roughly the same every time, or does it change a lot between clients?"],
            ['role' => 'user', 'content' => "Same every time. The questionnaire is a Google Form with about 15 questions. I review it to check for any health red flags, then send a Calendly link for the first session and email them a welcome PDF."],
            ['role' => 'assistant', 'content' => "Consistent and repeatable — perfect for automation. 15 questions on a Google Form, a health screening review, Calendly booking, and a welcome email with a PDF. That's a clean process.\n\nLet me map out everything involved.\n\n**Your client onboarding process:**\n\n1. **Receive questionnaire response** — Google Form submission triggers the process. *(Automatic)*\n2. **Screen for health red flags** — review answers for serious conditions like diabetes, heart disease, eating disorders, recent surgery. *(Learnable — assistant learns what you flag from your past screening patterns)*\n3. **Create client brief** — summarise key details: goals, health notes, preferred times. *(Automatic — extracted directly from form answers)*\n4. **Draft personalised welcome email** — reference something specific from their answers so it feels personal, include Calendly link and welcome PDF. *(Learnable — assistant learns your personalisation style from past welcome emails)*\n5. **Present for review** — show you the client brief and draft email before anything is sent. *(Needs guidance — you confirm or tweak)*\n6. **Send welcome email** — after your approval, send the email with attachments. *(Automatic after approval)*\n7. **Log in Practice Better** — create or update the client record in your portal. *(Automatic)*\n\nDoes that cover everything?"],
            ['role' => 'user', 'content' => "That's spot on. The personalisation bit is really important to me — I don't want clients to feel like they're getting a template."],
            ['role' => 'assistant', 'content' => "Absolutely — and that's something your assistant will be great at. I'll set it up to review your past welcome emails to learn how you personalise them. It'll pick up on the kinds of details you reference and the tone you use, so every email feels like it came from you.\n\nNow for the design. Based on what you've told me, I'd set the assistant up to present you with a client brief and a draft welcome email for each new client. You review both, make any tweaks, and approve before it sends. Does that feel like the right level of control, or would you rather it just sends automatically?"],
            ['role' => 'user', 'content' => "No, I definitely want to review before it sends. Especially for the health screening — I want to see the flags myself."],
            ['role' => 'assistant', 'content' => "Makes sense — review before send, especially on health flags. That's a firm boundary we'll build in.\n\nOne more thing: are there any hard rules your assistant should always follow? Things like specific conditions that should always be flagged, or anything that should never go in a welcome email?"],
            ['role' => 'user', 'content' => "Never mention pricing or package details in the welcome email. And always flag if someone mentions they're pregnant or have an eating disorder — those need special consideration for the first session."],
            ['role' => 'assistant', 'content' => "Noted. No pricing in the welcome email, and pregnancy and eating disorders always get flagged regardless of anything else. Those are clear rules your assistant will follow every time.\n\nSo far your assistant will handle the full onboarding flow: screen questionnaire responses for health flags, create a client brief, draft a personalised welcome email that matches your voice, and present everything for your review before sending. It'll learn your personalisation style from your past emails and follow your rules on flagging and content. Does that feel right?"],
            ['role' => 'user', 'content' => "That feels really right. I'm excited about this one."],
        ];
    }

    private function karenStep4Messages(): array
    {
        // Session prep — full through Step 4 (Instruction sheet delivered)
        return [
            ['role' => 'assistant', 'content' => "Hey Karen! Great to see you back. Let's build your next assistant.\n\nDo you already know what process you'd like to automate this time?"],
            ['role' => 'user', 'content' => "Before each coaching session I spend about 30 minutes reading through the client's journal entries and pulling together prep notes. I'd love an assistant for that."],
            ['role' => 'assistant', 'content' => "Session prep — 30 minutes per client per session adds up to serious hours over a week. And it's the kind of task where the quality of your prep directly affects the session.\n\nSo you're reading journal entries and compiling prep notes. Is this something you do before every single session, or just for certain clients?"],
            ['role' => 'user', 'content' => "Every session. I have about 15 active clients and see each one weekly or fortnightly. So that's a lot of prep."],
            ['role' => 'assistant', 'content' => "At 30 minutes each, that could be five to eight hours a week just on prep. That's a huge win if we can automate most of it.\n\nYour clients log journal entries in Practice Better, is that right? And what does your finished prep note usually look like?"],
            ['role' => 'user', 'content' => "Yes, Practice Better for journals. My prep note is usually dot points: what they struggled with, what went well, any trends I've noticed, and topics to bring up in the session."],
            ['role' => 'assistant', 'content' => "Clear structure. Let me map out the full process.\n\n**Your session prep process:**\n\n1. **Identify upcoming session** — check who's booked in next. *(Automatic — pulled from calendar)*\n2. **Gather journal entries** — retrieve all entries from Practice Better since the last session. *(Automatic — date-range lookup)*\n3. **Read and analyse entries** — look for struggles, wins, mood patterns, recurring themes. *(Learnable — assistant learns what you consider noteworthy from your past prep notes)*\n4. **Spot trends** — flag anything mentioned three or more times, or notable shifts from previous weeks. *(Learnable from your historical notes)*\n5. **Identify unaddressed topics** — things the client has raised but haven't been discussed yet. *(Learnable — cross-reference with session history)*\n6. **Compile prep note** — format as structured dot points with your standard sections. *(Automatic)*\n7. **Deliver** — email the prep note to you before the session. *(Automatic)*\n\nDoes that cover everything?"],
            ['role' => 'user', 'content' => "That's perfect. The trend spotting is the bit that takes me the longest — having that automated would be amazing."],
            ['role' => 'assistant', 'content' => "That's exactly where your assistant will shine. It can cross-reference weeks of entries and spot patterns much faster than reading through them manually.\n\nFor the design: I'd set the assistant up to deliver the prep note to your inbox automatically, one hour before each session. You'd get a structured summary you can scan in two minutes instead of spending 30 minutes compiling it. Does that timing and format work for you?"],
            ['role' => 'user', 'content' => "One hour before would be perfect. And yes, a scannable summary is exactly what I need."],
            ['role' => 'assistant', 'content' => "Great. And should the assistant just deliver the note, or would you want it to flag anything urgent — like if a client's mood has dropped significantly or they've mentioned something concerning?"],
            ['role' => 'user', 'content' => "Yes, definitely flag anything concerning. If a client mentions feeling really low or anything about self-harm, I want that front and centre."],
            ['role' => 'assistant', 'content' => "Absolutely — mental health flags will be a priority alert at the top of every prep note. That's a firm rule.\n\nI've got everything I need. Let me put together your Playbook.\n\n---\n\n**1. Your Bottleneck — Pre-Session Prep Notes**\n\nBefore every coaching session you spend about 30 minutes reading through the client's journal entries in Practice Better and pulling together prep notes — dot points covering struggles, wins, trends, and topics to bring up. With 15 active clients seen weekly or fortnightly, that's five to eight hours a week of prep time. The work matters (your prep quality directly affects the session), but the volume is unsustainable.\n\n**2. Your Process Map**\n\n1. **Identify upcoming session** — check who's booked in next from the calendar. *(Automatic — pulled from calendar)*\n2. **Gather journal entries** — retrieve all entries from Practice Better since the last session. *(Automatic — date-range lookup)*\n3. **Read and analyse entries** — look for struggles, wins, mood patterns, recurring themes. *(Learnable — Session Prep Buddy learns what you consider noteworthy from your past prep notes)*\n4. **Spot trends** — flag anything mentioned three or more times, or notable shifts from previous weeks. *(Learnable from your historical notes)*\n5. **Identify unaddressed topics** — things the client has raised but haven't been discussed yet. *(Learnable — cross-reference with session history)*\n6. **Compile prep note** — format as structured dot points with your standard sections: priority alerts (if any), quick snapshot, struggles, wins, trends to watch, suggested talking points. *(Automatic)*\n7. **Deliver** — email the prep note to you one hour before the session. *(Automatic)*\n\n**3. How Your Assistant Works**\n\nSession Prep Buddy handles the full prep cycle — from pulling journal entries to delivering a structured briefing note to your inbox one hour before each session. It learns your prep style by reviewing your past notes and session histories: which patterns you flag, how you weight struggles versus wins, and what kind of trends you track. Over time it matches your prep voice closely.\n\nIt acts automatically on gathering data, spotting trends, and formatting the note. It flags anything concerning (self-harm, significant mood drops, mental health concerns) as a priority alert at the top of every note. It will never contact clients directly, never make diagnostic statements, and will always include entries from the period since the last completed session only.\n\nWhen it encounters a client situation it hasn't seen before, it asks you what to do and remembers your answer for next time.\n\n**4. Getting Started**\n\n1. Download the instruction file\n2. Open Claude CoWork\n3. Create a new project called \"Session Prep Buddy\"\n4. Upload the file to the project\n5. Type in \"Let's get started\"\n\n**First test task:** Pick your next scheduled client session. Let Session Prep Buddy pull their journal entries from the past week and generate a prep note. Compare it to what you would have written yourself and flag anything you'd adjust.\n\n**5. What Happens Next**\n\nWhen Session Prep Buddy first runs, it goes through a short onboarding process before doing real work. It will review three months of client journal entries to learn the typical content and themes, study your past prep notes to learn your structure and style, and cross-reference journals with session notes to understand which themes you actually discussed. It will ask you a couple of setup questions — like what subtle signs you watch for beyond the obvious flags, and whether dot points under standard headings is the right format.\n\nDuring the first week, expect to review each note closely and give feedback. By week two, the notes should feel like your own. You can refine anytime — just tell Session Prep Buddy what to change and it remembers.\n\n---\n\nAnd here are the instructions for your assistant. Save this as a markdown file and add it to your Claude CoWork project. When Session Prep Buddy first runs, it will go through a short onboarding process to learn how you work before it starts doing real tasks.\n\n<!-- INSTRUCTIONS_START -->\n\n# Session Prep Buddy — AI Assistant for Karen Whitfield\n\n## Role\nYou are Session Prep Buddy, an AI assistant for Karen Whitfield, a health and wellness coach. Your job is to prepare pre-session briefing notes by analysing client journal entries.\n\n## Business Context\nKaren is a health and wellness coach with 15 active clients seen weekly or fortnightly. She uses Practice Better for client management and journals, Google Calendar for scheduling, and Gmail for communication.\n\n## The Process You Handle\n1. **Check calendar** — identify the next scheduled coaching session. *Trigger: one hour before each session. Act automatically.*\n2. **Retrieve journal entries** — pull all entries from Practice Better since the client's last completed session. *Act automatically.*\n3. **Analyse entries** — identify struggles, wins, mood patterns, recurring themes, and unaddressed topics. *Act automatically, learning from Karen's past prep notes.*\n4. **Spot trends** — flag anything mentioned three or more times or notable shifts from previous weeks. *Act automatically.*\n5. **Compile prep note** — format as structured dot points: priority alerts, quick snapshot, struggles, wins, trends to watch, suggested talking points. *Act automatically.*\n6. **Deliver** — email the prep note to Karen. *Act automatically.*\n\n## How You Learn\n- Review Karen's past prep notes to learn her structure, what she highlights, and how she summarises.\n- Cross-reference journal entries with session notes to understand which themes Karen chose to discuss.\n- Track feedback on your notes over time — if Karen adjusts something, remember the preference.\n\n## Onboarding Sequence\n1. Confirm Practice Better, Google Calendar, and Gmail integrations are connected.\n2. Review the last 3 months of client journal entries to learn typical content, language, and themes.\n3. Review Karen's past prep notes (if available) to learn structure, emphasis, and summarisation style.\n4. Cross-reference journal entries with session notes to understand which journal themes Karen discussed.\n5. Ask Karen: \"What does a concerning journal entry look like to you beyond the obvious flags? Are there subtle signs you watch for?\"\n6. Ask Karen: \"Is dot points under standard headings the right format, or do you prefer something different?\"\n7. Summarise what you have learned and confirm with Karen before starting real work.\n\n## Rules\n- Always flag mentions of self-harm, significant mood drops, or mental health concerns as a priority alert at the top of the prep note.\n- Deliver the prep note exactly one hour before the scheduled session.\n- Include entries from the period since the last completed session only.\n- Never contact clients directly.\n- Never make diagnostic statements about a client's health.\n- Never skip or filter entries selectively.\n\n## Output Style\nStructured dot points. Sections: priority alerts (if any), quick snapshot, struggles, wins, trends to watch, suggested talking points. Clean, scannable, concise.\n\n## Defaults\nWhen you encounter a situation with no clear rule, ask Karen what to do. Suggest a sensible default, remember her answer, and apply it going forward.", 'is_deliverable' => true],
        ];
    }

    private function karenStep5Messages(): array
    {
        // Follow-up emails — full session through Step 5 (Launch)
        return [
            ['role' => 'assistant', 'content' => "Hey Karen! Welcome back — let's build another one.\n\nWhat process are we automating today?"],
            ['role' => 'user', 'content' => "Post-session follow-up emails. After every coaching session I write a follow-up with action items, resources, and a summary. It takes me 20 minutes per client and I always fall behind."],
            ['role' => 'assistant', 'content' => "Follow-up emails at 20 minutes each — and falling behind means clients are waiting longer for their action items. That's a great one to automate.\n\nYou're writing these after every session. Is it the same structure every time, or does it vary a lot between clients?"],
            ['role' => 'user', 'content' => "Same structure: recap of what we discussed, 3-4 action items, links to resources I mentioned, and when our next session is. I just personalise it for each client."],
            ['role' => 'assistant', 'content' => "Consistent structure with personalisation — perfect for an assistant. Let me map out the full process.\n\n**Your follow-up email process:**\n\n1. **Capture session notes** — you take dot-point notes during the session in a Google Doc. *(Manual — your input)*\n2. **Extract action items** — pull out the agreed tasks and next steps. *(Automatic — parsed from your notes)*\n3. **Match resources** — find the relevant recipes, articles, or exercises from your Google Drive library. *(Automatic — keyword matching against ~50 files)*\n4. **Look up next session** — check the calendar for when you're seeing this client next. *(Automatic)*\n5. **Draft the email** — structured with recap, action items, resources, next session, and a personal sign-off. *(Learnable — matches your tone from past emails)*\n6. **Present for review** — you approve or tweak before sending. *(Needs guidance)*\n\nDoes that cover it?"],
            ['role' => 'user', 'content' => "That's exactly it. The resource matching would save me the most time — I'm always digging through my Drive folder trying to find the right article."],
            ['role' => 'assistant', 'content' => "Your assistant will index your entire resource library and match automatically based on the topics in your session notes. No more searching.\n\nFor the design: the assistant takes your rough session notes, drafts a polished follow-up email in your voice, attaches the right resources, includes the next session date, and presents it for your review. Does that feel right?"],
            ['role' => 'user', 'content' => "Yes, perfect. I want to review before it sends — my clients are important to me and I want every email to feel personal."],
            ['role' => 'assistant', 'content' => "Absolutely — review before send. Any hard rules? Things the assistant should always or never include?"],
            ['role' => 'user', 'content' => "Always include a personal note at the end — something encouraging about their progress. Never include anything about billing or payments in a follow-up email."],
            ['role' => 'assistant', 'content' => "Encouraging personal note always, billing never. Clear rules.\n\nHere's your Playbook.\n\n---\n\n**1. Your Bottleneck — Post-Session Follow-Up Emails**\n\nAfter every coaching session you write a follow-up email with action items, resource links, and a personalised summary. At 20 minutes per client, it adds up fast — and when you fall behind, clients wait longer for their action items. The structure is consistent (recap, actions, resources, next session) but the personalisation is what makes each one feel like it came from you.\n\n---\n20 min — per follow-up email\nWeekly — per active client\n~3 hrs — per week on follow-ups\n\n**2. Your Process Map**\n\n1. **Capture session notes** — you take dot-point notes during the session in a Google Doc. *(Manual — your input)*\n2. **Extract action items** — pull out the agreed tasks and next steps. *(Automatic — parsed from your notes)*\n3. **Match resources** — find the relevant recipes, articles, or exercises from your Google Drive library. *(Automatic — keyword matching against your resource folder)*\n4. **Look up next session** — check the calendar for when you're seeing this client next. *(Automatic)*\n5. **Draft the email** — structured with recap, action items, resources, next session, and a personal encouraging sign-off. *(Learnable — matches your tone from past emails)*\n6. **Present for review** — you approve or tweak before sending. *(Always — firm boundary)*\n\n**3. How Your Assistant Works**\n\nFollow-Up Drafter takes your rough session notes and produces a polished follow-up email in your voice. It automatically extracts action items, matches resources from your Google Drive library by topic, pulls the next session date from your calendar, and drafts the complete email.\n\nIt learns your email voice by studying your past sent follow-ups, your tone, structure, the kind of personal notes you add, and how you reference resources. Each email should feel like it came directly from you, not from a system.\n\n📧 **Learns your voice** | Reviews your last 90 days of sent emails to match your exact tone and style\n📎 **Smart resource matching** | Searches your Drive library by topic and links the most relevant files\n📅 **Calendar-aware** | Pulls the next session date automatically so clients always know when you meet next\n✅ **Review before send** | Every email is presented for your approval, nothing goes out without your say-so\n\nIt always presents the draft for your review before sending. It will never include billing or pricing information. It will never invent action items that weren't in your session notes. When it encounters a new type of resource request or client situation, it asks you what to do and remembers your answer.\n\n**4. Getting Started**\n\n1. Download the instruction file. Use the \"Download Instructions\" button to save the .md file.\n2. Open Claude CoWork and create a new project called \"Follow-Up Drafter\". Upload the instruction file to the project.\n3. Connect your tools. Google Docs (session notes), Google Drive (resources), Gmail (drafts and sent history), and Google Calendar (session dates).\n4. Type \"Let's get started\". Follow-Up Drafter will run through its onboarding process to learn how you work.\n\n**First test task:** After your next coaching session, share your session notes with Follow-Up Drafter. Let it draft the follow-up email, match resources from your library, and include the next session date. Compare the draft to what you would have written yourself.\n\n**5. What Happens Next**\n\nWhen Follow-Up Drafter first runs, it goes through a short onboarding process. It will review three months of your sent follow-up emails to learn your writing style, structure, and personalisation patterns. It will index every file in your Google Drive resources folder by topic and keywords. It will study your session note format to understand how you capture action items.\n\nIt will ask you a couple of setup questions — whether any clients prefer a different communication style, and whether you have a preferred sign-off. During the first week, review each draft closely and give feedback on tone and content. By week two, the drafts should feel like your own writing. Refine anytime by telling Follow-Up Drafter what to change.\n\n---\n\nAnd here are the instructions for your assistant. Save this as a markdown file and add it to your Claude CoWork project. When Follow-Up Drafter first runs, it will go through a short onboarding process to learn how you work before it starts doing real tasks.\n\n<!-- INSTRUCTIONS_START -->\n\n# Follow-Up Drafter — AI Assistant for Karen Whitfield\n\n## Role\nYou are Follow-Up Drafter, an AI assistant for Karen Whitfield, a health and wellness coach. Your job is to draft post-session follow-up emails for Karen's coaching clients.\n\n## Business Context\nKaren is a health and wellness coach with active clients seen weekly or fortnightly. She takes session notes in Google Docs, stores client resources in Google Drive, uses Google Calendar for scheduling, and sends follow-ups via Gmail.\n\n## The Process You Handle\n1. **Read session notes** — Karen shares her dot-point notes from the session in a Google Doc. *Trigger: Karen shares the notes after a session. Act automatically.*\n2. **Extract action items** — parse the notes for agreed tasks and next steps. *Act automatically.*\n3. **Match resources** — search Karen's Google Drive resources folder for relevant articles, recipes, or exercises based on session topics. *Act automatically.*\n4. **Look up next session** — check Google Calendar for the client's next booking. *Act automatically.*\n5. **Draft the email** — compose a follow-up with recap (2-3 sentences, warm tone), numbered action items, resource links, next session date, and a personalised encouraging note. *Act automatically.*\n6. **Present for review** — show Karen the draft. Wait for approval before sending. *Always check first.*\n\n## How You Learn\n- Review Karen's past sent follow-up emails in Gmail to learn her tone, structure, personalisation patterns, and sign-off style.\n- Index all files in the Google Drive resources folder, tagging each by topic, type, and keywords.\n- Study Karen's session note format to understand how she captures action items and discussion points.\n- Track feedback over time — if Karen adjusts a draft, remember the preference.\n\n## Onboarding Sequence\n1. Confirm Google Docs, Google Drive, Gmail, and Google Calendar integrations are connected.\n2. Review the last 3 months of sent follow-up emails to learn writing style, structure, and personalisation patterns.\n3. Index all files in the Google Drive resources folder by topic, type, and keywords.\n4. Review Karen's session note format to understand how action items and discussion points are captured.\n5. Ask Karen: \"Are there clients who prefer a different communication style — more formal, shorter, or different tone?\"\n6. Ask Karen: \"Do you have a preferred sign-off or closing line you use consistently?\"\n7. Summarise what you have learned and confirm with Karen before starting real work.\n\n## Rules\n- Always include an encouraging personal note about the client's progress at the end of each email.\n- Never include billing, payment, or pricing information in follow-up emails.\n- Always present the draft for Karen's review before sending.\n- Match resource links to topics actually discussed — do not include irrelevant resources.\n- Never invent action items that were not in the session notes.\n- Keep the tone friendly, encouraging, and supportive.\n\n## Output Style\nEmail format: warm recap (2-3 sentences), numbered action items, resource links, next session date, personalised encouraging sign-off. Each email should feel like it came directly from Karen.\n\n## Defaults\nWhen you encounter a new type of resource request or client situation you have not seen before, ask Karen what to do. Suggest a sensible default, remember her answer, and apply it going forward.", 'is_deliverable' => true],
            ['role' => 'assistant', 'content' => "Your Playbook and assistant instructions are ready. There's a lot more we can dial in to make Follow-Up Drafter even better — things like tone of voice for different clients, how it handles edge cases, and resource matching preferences. Would you like to keep going?"],
            ['role' => 'user', 'content' => "I think I'm good for now. I want to try it out first and come back if I need to refine things."],
            ['role' => 'assistant', 'content' => "That's a great approach — try it in the real world and tune from there.\n\nCongratulations, Karen! Follow-Up Drafter is ready to go. Those 20-minute follow-up emails should take you about 2 minutes to review and send.\n\nYou've got 7 days of support starting now. Come back to this chat anytime during that window with questions about setting up your assistant, refining tone, adjusting rules, or anything else. After 7 days the chat will close, but your downloads stay available."],
        ];
    }

    // ───────────────────────────────────────────────────
    // Message sets for other users
    // ───────────────────────────────────────────────────

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
            ['role' => 'assistant', 'content' => "Here's your Playbook.\n\n---\n\n**1. Your Bottleneck — Weekly Meal Plan Creation**\n\nEvery week you build custom meal plans for your clients — each one tailored to their calories, macros, allergies, preferences, and seasonal availability. With a library of 200+ recipes and the constraint of reusing ingredients to keep shopping lists simple, each plan takes significant time. Multiply that across your client base and it becomes hours of repetitive but detail-heavy work every week.\n\n**2. Your Process Map**\n\n1. **Pull the client profile** — retrieve the dietary profile from Practice Better: calorie target, macro split, allergies, food preferences, and any dislikes noted from previous weeks. *(Automatic)*\n2. **Check previous feedback** — review any notes or feedback from the client about last week's plan. Flag meals they disliked and exclude those recipes. *(Automatic — cross-referenced with feedback log)*\n3. **Select recipes** — search the recipe library for meals that fit the client's profile, prioritising seasonal ingredients and ingredient reuse across the week. *(Automatic — learns your rotation patterns over time)*\n4. **Build the 7-day plan** — generate 5 meals per day (breakfast, morning snack, lunch, afternoon snack, dinner) meeting daily calorie and macro targets. *(Automatic)*\n5. **Generate shopping list** — consolidated list grouped by category (produce, protein, dairy, pantry) with quantities for the full week. *(Automatic)*\n6. **Format and present** — compile the plan and shopping list as a clean, scannable document. Present for your review before sending to the client. *(Always — you review first)*\n\n**3. How Your Assistant Works**\n\nMeal Plan Builder handles the full weekly planning cycle — from pulling client profiles to delivering a formatted plan ready for your review. It learns your recipe rotation patterns, your preferences for seasonal ingredients, and how you balance variety against shopping simplicity.\n\nIt acts automatically on profile retrieval, recipe selection, plan generation, and shopping list compilation. It always presents the finished plan for your review before anything goes to a client. It tracks client feedback (dislikes, preferences) and adjusts future plans automatically.\n\nIt will never ignore allergies or dietary restrictions, never recommend supplements or medications, and will never exceed or significantly undershoot calorie or macro targets.\n\n**4. Getting Started**\n\n1. Download the instruction file\n2. Open Claude CoWork\n3. Create a new project called \"Meal Plan Builder\"\n4. Upload the file to the project\n5. Type in \"Let's get started\"\n\n**First test task:** Pick one client and let Meal Plan Builder generate next week's plan. Compare it to what you would have built — check the macro targets, ingredient reuse, and seasonal choices. Flag anything you'd adjust.\n\n**5. What Happens Next**\n\nWhen Meal Plan Builder first runs, it goes through a short onboarding process. It will index your full recipe library (all 200+ recipes) by ingredients, macros, dietary tags, and season. It will review your past meal plans to learn your rotation patterns — how often you repeat recipes, how you balance variety, and how you handle ingredient reuse. It will study client feedback history to understand which meals landed well and which didn't.\n\nDuring the first week, review each plan closely and give feedback on recipe choices, variety, and structure. By week two, the plans should feel like your own. Refine anytime — tell Meal Plan Builder what to change and it remembers.\n\n---\n\nAnd here are the instructions for your assistant. Save this as a markdown file and add it to your Claude CoWork project. When Meal Plan Builder first runs, it will go through a short onboarding process to learn how you work before it starts doing real tasks.\n\n<!-- INSTRUCTIONS_START -->\n\n# Meal Plan Builder — AI Assistant for David Nguyen\n\n## Role\nYou are Meal Plan Builder, an AI assistant for David Nguyen, a nutritionist. Your job is to generate weekly meal plans customised to each client's dietary requirements, macros, and preferences.\n\n## Business Context\nDavid is a nutritionist who creates personalised weekly meal plans for his clients. He uses Practice Better for client management and dietary profiles, maintains a library of 200+ recipes, and delivers plans as formatted PDFs via email.\n\n## The Process You Handle\n1. **Pull client profile** — retrieve calorie target, macro split, allergies, food preferences, and dislikes from Practice Better. *Trigger: weekly, when preparing the next week's plans. Act automatically.*\n2. **Check previous feedback** — review client notes on last week's plan and exclude disliked meals. *Act automatically.*\n3. **Select recipes** — search the recipe library for fitting meals, prioritising seasonal ingredients and ingredient reuse. *Act automatically, learning David's rotation patterns.*\n4. **Build 7-day plan** — 5 meals per day meeting daily calorie and macro targets. *Act automatically.*\n5. **Generate shopping list** — consolidated by category with quantities. *Act automatically.*\n6. **Present for review** — show David the complete plan and shopping list. Wait for approval. *Always check first.*\n\n## How You Learn\n- Index the full recipe library by ingredients, macros, dietary tags, and season.\n- Review David's past meal plans to learn rotation patterns, variety balance, and ingredient reuse strategies.\n- Track client feedback over time to learn which meals land well and which don't.\n\n## Onboarding Sequence\n1. Confirm Practice Better and recipe library integrations are connected.\n2. Index all recipes by ingredients, macros, dietary tags, and seasonal availability.\n3. Review David's past meal plans to learn rotation patterns and variety preferences.\n4. Review client feedback history to understand meal preferences and dislikes.\n5. Ask David: \"How do you decide what's in season — do you follow a specific seasonal guide or local availability?\"\n6. Ask David: \"What's your ideal balance between variety and ingredient reuse across a week?\"\n7. Summarise what you have learned and confirm with David before generating real plans.\n\n## Rules\n- Never ignore allergies or dietary restrictions — these are absolute constraints.\n- Never recommend supplements or medications.\n- Never send a plan to a client without David's review and approval.\n- Never exceed or significantly undershoot calorie or macro targets.\n- Prioritise seasonal ingredients and ingredient reuse to simplify shopping.\n\n## Output Style\nClean, practical, easy to scan. Simple language — clients range from health-savvy to complete beginners. Formatted as a structured document: daily meals with recipes, then a consolidated shopping list by category.\n\n## Defaults\nWhen you encounter a dietary situation with no clear rule, ask David what to do. Suggest a sensible default, remember his answer, and apply it going forward.", 'is_deliverable' => true],
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
            ['role' => 'assistant', 'content' => "Here's your Playbook.\n\n---\n\n**1. Your Bottleneck — Consulting Proposal Drafting**\n\nAfter every initial client call you spend about three hours writing a 4-6 page proposal: executive summary, scope of work, timeline, pricing, and terms. You customise heavily from templates based on your call notes — freeform dot points on what they need, budget range, timeline, and constraints. With 40 past proposals in Google Drive and a growing client pipeline, this is a serious drain on your time.\n\n**2. Your Process Map**\n\n1. **Capture call notes** — you jot freeform dot points during the call in a Google Doc. *(Manual — your input)*\n2. **Extract key details** — identify client needs, budget indicators, timeline, constraints, and specific requests. *(Automatic — parsed from your notes)*\n3. **Select a template** — search past proposals in Google Drive for the closest match based on industry, service type, and scope. *(Automatic — learns your matching patterns over time)*\n4. **Draft the proposal** — generate a 4-6 page document: executive summary tailored to the client, detailed scope of work, realistic timeline, pricing based on your standard rates, and terms from the standard template. *(Learnable — matches your proposal voice from past examples)*\n5. **Present for review** — share the draft for your review and approval. *(Always — firm boundary)*\n\n**3. How Your Assistant Works**\n\nProposal Writer takes your freeform call notes and produces a polished consulting proposal in your voice. It searches your library of 40+ past proposals to find the closest structural match, then customises every section based on what was discussed in the call.\n\nIt learns your proposal voice by studying your past work — how you frame executive summaries, the level of detail in your scoping, and how you position pricing. It acts automatically on note parsing, template selection, and drafting. It always presents the finished proposal for your review before anything goes to a client.\n\nIt will never invent scope items that weren't discussed, never modify pricing without your explicit instruction, and will never share client information across proposals.\n\n**4. Getting Started**\n\n1. Download the instruction file\n2. Open Claude CoWork\n3. Create a new project called \"Proposal Writer\"\n4. Upload the file to the project\n5. Type in \"Let's get started\"\n\n**First test task:** After your next client call, share your call notes with Proposal Writer. Let it select a template, draft the proposal, and present it for review. Compare the output to what you would have written and flag any adjustments.\n\n**5. What Happens Next**\n\nWhen Proposal Writer first runs, it goes through a short onboarding process. It will review all 40+ past proposals in your Google Drive to learn your structure, voice, pricing patterns, and how you tailor content to different industries and scopes. It will study your call note format to understand how you capture key details.\n\nIt will ask you a couple of setup questions — like your current standard rates and whether there are any industries or project types that need special handling. During the first week, review each proposal closely and give feedback on tone, scoping detail, and pricing. By week two, the drafts should feel like your own. Refine anytime.\n\n---\n\nAnd here are the instructions for your assistant. Save this as a markdown file and add it to your Claude CoWork project. When Proposal Writer first runs, it will go through a short onboarding process to learn how you work before it starts doing real tasks.\n\n<!-- INSTRUCTIONS_START -->\n\n# Proposal Writer — AI Assistant for Margaret Thompson\n\n## Role\nYou are Proposal Writer, an AI assistant for Margaret Thompson, a consultant. Your job is to draft consulting proposals from brief call notes and past templates.\n\n## Business Context\nMargaret runs a small consulting firm. She writes 4-6 page proposals after initial client calls. She has 40+ past proposals organised by client in Google Drive, and takes freeform dot-point call notes in Google Docs.\n\n## The Process You Handle\n1. **Read call notes** — Margaret shares her dot-point notes from the initial client call. *Trigger: Margaret shares the notes. Act automatically.*\n2. **Extract key details** — identify client needs, budget range, timeline, constraints, and specific requests. *Act automatically.*\n3. **Select template** — search past proposals in Google Drive for the closest match by industry, service type, and scope. *Act automatically, learning Margaret's matching patterns.*\n4. **Draft proposal** — generate a 4-6 page document: executive summary, scope of work, timeline, pricing, terms. *Act automatically, matching Margaret's proposal voice.*\n5. **Present for review** — share the draft with Margaret. Wait for approval. *Always check first.*\n\n## How You Learn\n- Review all past proposals in Google Drive to learn structure, voice, pricing patterns, and industry-specific tailoring.\n- Study Margaret's call note format to understand how she captures key details.\n- Track feedback on drafts over time to refine tone, scoping detail, and pricing presentation.\n\n## Onboarding Sequence\n1. Confirm Google Drive, Google Docs, and Gmail integrations are connected.\n2. Review all past proposals in Google Drive. Learn structure, voice, pricing patterns, and industry-specific tailoring.\n3. Study Margaret's call note format and how she captures key details.\n4. Ask Margaret: \"What are your current standard rates and how do you adjust pricing based on scope?\"\n5. Ask Margaret: \"Are there industries or project types that need special handling in proposals?\"\n6. Summarise what you have learned and confirm with Margaret before drafting real proposals.\n\n## Rules\n- Never send proposals to clients without Margaret's approval.\n- Never invent scope items not discussed in the call.\n- Never discount or modify pricing without explicit instruction.\n- Never share client information across proposals.\n\n## Output Style\nProfessional, confident, clear. Match Margaret's existing proposal voice. 4-6 pages: executive summary, scope of work, timeline, pricing, terms.\n\n## Defaults\nWhen you encounter a proposal situation with no clear rule, ask Margaret what to do. Suggest a sensible default, remember her answer, and apply it going forward.", 'is_deliverable' => true],
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
