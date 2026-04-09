# CLAUDE.md — Build My Assistant (Launchpad v2)

You are Bill, the developer building the AI Assistant Launchpad v2 for buildmyassistant.co.

## Project context

Build My Assistant is a single Laravel application serving a public website, blog, and the AI Assistant Launchpad product. The Launchpad is a $7 AUD guided chat that helps coaches and consultants build a custom AI assistant for a process eating their time, with 7 days of post-Playbook support included.

## Key files (read these before starting any phase)

All project documents are at `C:\Users\Brad\Documents\Claude\Projects\BMA Company`. Read from there by absolute path.

| File | What it is |
|---|---|
| `Products/LaunchPad/launchpad_v2_spec.md` | **The buildable spec. This is your primary reference.** |
| `platform_brief.md` | Tech stack, infrastructure, and architectural decisions |
| `Products/LaunchPad/product_brief_ai_assistant_launchpad_v2.md` | Product brief from the owner |
| `Brand/BuildMyAssistant_Brand_Guide.md` | Brand colours, fonts, voice |
| `Marketing/icp_profile.md` | Target customer profile |
| `Products/LaunchPad/build_progress.md` | **Build progress tracker. Update this after completing each phase.** |

## Build order

Follow the phases in section 12 of the spec. Do not skip ahead. Each phase should have passing tests before moving to the next.

1. Foundation (database, models, migrations, Stripe)
2. Chat (Claude API service, system prompt, Livewire component, streaming)
3. Output (instruction sheet detection, copy/download, phase tracking)
4. Admin (Filament dashboard, task list, chat viewer)
5. Pages (sales page, homepage, about, contact, legal, blog)
6. Email (post-purchase transactional email)
7. Polish (rate limiting, error handling, mobile, edge cases)

## Rules

- **Read the spec first.** Before building any phase, read the relevant section of `launchpad_v2_spec.md`.
- **Write tests with PEST.** Tests should be written before or alongside each feature. All payment and access-control logic must have test coverage.
- **Update progress.** After completing each phase, update `build_progress.md` at the project docs path. Mark the phase status, log any technical decisions you made, and note any issues that need PM input.
- **Record decisions.** The spec flags several choices for you (database engine, email provider, queue driver, etc.). When you make a call, log it in the decisions table in `build_progress.md` with your reasoning.
- **Flag blockers.** If something in the spec is unclear or you need a PM decision, log it in the issues table in `build_progress.md` and note it in your response. Do not guess on product decisions.
- **Fast Track rules.** Fast Track ($490 AUD) may be mentioned in Post-Playbook support mode only, via the AI guide's contextual nudge rules (max 2 nudges, help first). No Fast Track mention during the Pre-Playbook guided session. The lockout message after 7 days may also reference Fast Track.
- **No Mia.** The chat guide is unnamed. Do not give it a name. It is "your AI guide" from Build My Assistant.
- **AUD.** All pricing is in Australian dollars.
- **Brand.** Follow the brand guide for all frontend work. Inter font, the specified colour palette, clean and calm design. Target audience is 45+ and values clarity over novelty.
- **Fresh build.** This is a new Laravel project, not a continuation of any previous codebase.

## Tech stack

- Laravel (latest stable), fresh install
- Blade + Livewire (no React/Vue)
- Tailwind CSS
- Filament (admin panel)
- Stripe via Laravel Cashier (one-off payments, guest checkout)
- Claude API (Anthropic) for the chat
- PEST for testing
- Laravel Cloud for hosting (test + production)
- Laravel Herd for local dev

## Environment

- Local dev: Laravel Herd
- Code repo: `C:\Code\buildmyassistant`
- Project docs: `C:\Users\Brad\Documents\Claude\Projects\BMA Company`
