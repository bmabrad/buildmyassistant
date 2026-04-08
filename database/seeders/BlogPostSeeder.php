<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;

class BlogPostSeeder extends Seeder
{
    public function run(): void
    {
        $posts = [
            [
                'title' => 'Why Every Coach Needs an AI Assistant in 2026',
                'slug' => 'why-every-coach-needs-ai-assistant-2026',
                'excerpt' => 'AI assistants aren\'t replacing coaches — they\'re freeing them up to do the work that actually matters.',
                'content' => <<<'MD'
If you're a coach or consultant still doing everything manually, you're leaving hours on the table every week. The good news? You don't need to become a tech expert to fix that.

## The time trap

Most coaches spend 30–40% of their working hours on repetitive tasks: onboarding questionnaires, follow-up emails, session prep, and client check-ins. These are important, but they don't require *you* to do them every single time.

## What an AI assistant actually does

An AI assistant handles the process — not the relationship. It follows your method, uses your language, and delivers your frameworks. You stay in control of the quality; the assistant handles the volume.

Think of it like hiring a junior team member who never sleeps, never forgets a step, and works exactly the way you've trained them.

## Getting started is easier than you think

You don't need to write code or learn prompt engineering. The AI Assistant Launchpad walks you through the entire process in a single guided session. You describe your process, and the Launchpad builds the assistant for you.

## The bottom line

Coaches who adopt AI assistants aren't replacing themselves — they're multiplying their impact. And the ones who start now will have a serious advantage over those who wait.
MD,
                'featured_image' => 'https://picsum.photos/seed/coach-ai/1200/630',
                'meta_title' => 'Why Every Coach Needs an AI Assistant in 2026',
                'meta_description' => 'AI assistants help coaches reclaim hours each week by handling repetitive processes — without losing the personal touch.',
                'published' => true,
                'published_at' => now()->subDays(2),
            ],
            [
                'title' => '5 Processes You Can Hand Off to an AI Assistant Today',
                'slug' => '5-processes-hand-off-ai-assistant',
                'excerpt' => 'Not sure where to start with AI? These five common processes are perfect candidates for automation.',
                'content' => <<<'MD'
You don't need to automate everything at once. Start with one process that eats your time and go from there. Here are five that work brilliantly as AI assistants.

## 1. Client onboarding

An AI assistant can walk new clients through your intake process — collecting information, setting expectations, and delivering your welcome materials — all without you lifting a finger.

## 2. Discovery calls prep

Instead of manually reviewing notes before each call, an AI assistant can summarise what it knows about a prospect and prepare talking points based on your sales framework.

## 3. Session follow-ups

Post-session summaries, action items, and accountability check-ins can all be handled by an assistant that knows your methodology.

## 4. FAQ handling

How many times do you answer the same five questions? An AI assistant trained on your business can handle these instantly, in your voice.

## 5. Content repurposing

Feed your assistant a podcast transcript or workshop recording and let it draft social posts, email newsletters, or blog outlines based on your original content.

## Start with one

Pick the process that frustrates you most, and build an assistant for it. That's exactly what the Launchpad is designed to help you do.
MD,
                'featured_image' => 'https://picsum.photos/seed/five-processes/1200/630',
                'meta_title' => '5 Processes You Can Automate with an AI Assistant',
                'meta_description' => 'Five common coaching and consulting processes that are perfect candidates for AI assistant automation.',
                'published' => true,
                'published_at' => now()->subDays(5),
            ],
            [
                'title' => 'AI Won\'t Replace You — But It Will Replace Your Busywork',
                'slug' => 'ai-wont-replace-you-but-will-replace-busywork',
                'excerpt' => 'The fear that AI will take your job is understandable. The reality is much more practical — and much more useful.',
                'content' => <<<'MD'
Let's address the elephant in the room: no, AI is not going to replace coaches and consultants. But it *is* going to change how the best ones operate.

## The real threat isn't AI

The real threat is spending so much time on admin that you burn out, under-deliver, or can't take on new clients. That's the problem AI solves.

## What AI is actually good at

AI excels at structured, repeatable tasks. Things like:

- Following a script or framework step by step
- Asking the right questions in the right order
- Summarising information into a useful format
- Delivering consistent quality at any hour of the day

## What AI is terrible at

AI can't read the room. It doesn't pick up on the subtle shift in someone's tone that tells you they're holding something back. It doesn't have 15 years of experience to draw on when a client's situation doesn't fit the textbook.

That's your job. And it always will be.

## The smart play

Use AI for the 80% that's process. Save yourself for the 20% that's judgement, empathy, and expertise. Your clients get a better experience, and you get your time back.
MD,
                'featured_image' => 'https://picsum.photos/seed/ai-busywork/1200/630',
                'meta_title' => 'AI Won\'t Replace You — But It Will Replace Your Busywork',
                'meta_description' => 'AI isn\'t coming for coaching jobs — it\'s coming for the admin work that burns coaches out.',
                'published' => true,
                'published_at' => now()->subDays(9),
            ],
            [
                'title' => 'How to Write Instructions Your AI Assistant Will Actually Follow',
                'slug' => 'write-instructions-ai-assistant-will-follow',
                'excerpt' => 'The secret to a good AI assistant isn\'t the technology — it\'s the clarity of your instructions.',
                'content' => <<<'MD'
Most people who are disappointed with AI made the same mistake: they gave it vague instructions and expected specific results. Here's how to do it properly.

## Think like a trainer, not a user

When you train a new team member, you don't say "handle client onboarding." You walk them through each step, explain what good looks like, and give them examples. AI works the same way.

## The framework

Good AI instructions follow a simple pattern:

1. **Role** — Tell the assistant who it is and what it's here to do
2. **Process** — Walk it through the steps in order
3. **Guardrails** — Tell it what *not* to do
4. **Tone** — Give it examples of how you communicate
5. **Output** — Describe what the final result should look like

## Be specific about tone

"Be professional" means nothing to an AI. Instead, try: "Write in a warm, direct tone. Use short sentences. Avoid jargon. Sound like a trusted adviser, not a corporate handbook."

## Test and refine

Your first version won't be perfect. Run through your assistant as if you were a client. Where does it stumble? Where does it sound robotic? Adjust the instructions and try again.

## The Launchpad advantage

The AI Assistant Launchpad guides you through this entire process with structured questions. You don't need to know prompt engineering — you just need to know your own process.
MD,
                'featured_image' => 'https://picsum.photos/seed/instructions/1200/630',
                'meta_title' => 'How to Write Instructions Your AI Assistant Will Follow',
                'meta_description' => 'Learn the simple framework for writing clear AI assistant instructions that deliver consistent, quality results.',
                'published' => true,
                'published_at' => now()->subDays(14),
            ],
            [
                'title' => 'The $7 Experiment: Building Your First AI Assistant',
                'slug' => 'seven-dollar-experiment-first-ai-assistant',
                'excerpt' => 'For less than the price of a coffee, you can build a custom AI assistant that saves you hours every week.',
                'content' => <<<'MD'
Most AI tools cost $20–50 per month. Most AI consultants charge thousands. What if you could build your first AI assistant for $7?

## Why we priced it at $7

We wanted to remove every barrier to getting started. The AI Assistant Launchpad costs $7 AUD — not because it's basic, but because we believe the biggest hurdle for most coaches isn't money. It's clarity.

## What you get

In a single guided session, you'll work through your process with an AI guide that helps you:

- Identify the process that's eating your time
- Break it down into clear, repeatable steps
- Define the tone and boundaries for your assistant
- Generate a complete instruction sheet you can use immediately

## What happens after

You walk away with a ready-to-use instruction sheet for your AI assistant. Paste it into ChatGPT, Claude, or any AI tool and you've got a working assistant that follows your method.

Plus, you get 7 days of post-Playbook support to refine and adjust your instructions.

## The real value

The Launchpad doesn't just give you an assistant — it gives you clarity about your own process. Many coaches tell us that's worth more than the assistant itself.
MD,
                'featured_image' => 'https://picsum.photos/seed/experiment/1200/630',
                'meta_title' => 'Build Your First AI Assistant for $7 AUD',
                'meta_description' => 'The AI Assistant Launchpad helps coaches build a custom AI assistant in one guided session for just $7 AUD.',
                'published' => true,
                'published_at' => now()->subDays(20),
            ],
            [
                'title' => 'What Makes a Good AI Use Case (And What Doesn\'t)',
                'slug' => 'what-makes-good-ai-use-case',
                'excerpt' => 'Not everything should be handed to AI. Here\'s how to tell the difference between a great use case and a waste of time.',
                'content' => <<<'MD'
AI is powerful, but it's not magic. Knowing when to use it — and when not to — is the difference between saving hours and creating new problems.

## Great AI use cases share three traits

### 1. The process is repeatable

If you do it roughly the same way every time, AI can learn it. Client intake, session prep, follow-up emails — these all follow a pattern.

### 2. The stakes are moderate

AI works best when a mistake is fixable. Don't hand it your highest-stakes client conversations. Do hand it your session summaries and admin workflows.

### 3. The output is structured

If the result has a clear format — a filled-in template, a set of questions, a summary with action items — AI will nail it. If the result requires creative judgement, you'll want to stay involved.

## Bad AI use cases

- **Anything requiring real-time emotional intelligence.** AI can't read body language or pick up on what someone isn't saying.
- **High-stakes decisions.** Don't let AI decide which clients to take on or how to price a premium offer.
- **Creative strategy.** AI can help brainstorm, but the strategic thinking should be yours.

## The sweet spot

The best AI assistants handle the structured middle — not the creative beginning or the nuanced end. Find your structured middle, and you've found your use case.
MD,
                'featured_image' => 'https://picsum.photos/seed/use-case/1200/630',
                'meta_title' => 'What Makes a Good AI Use Case for Coaches',
                'meta_description' => 'Learn the three traits of a great AI use case and avoid wasting time on tasks AI isn\'t suited for.',
                'published' => true,
                'published_at' => now()->subDays(27),
            ],
        ];

        foreach ($posts as $post) {
            Article::create($post);
        }
    }
}
