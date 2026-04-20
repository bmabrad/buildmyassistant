# Assistant Archetype Library

**April 2026. Reference library the Custom Assistant Playbook draws from to personalise each buyer's Playbook.**

## Purpose

Every Playbook describes the same five-role Your AI Team, but each one is personalised to the buyer's business. This library is the source material the Playbook generation prompt uses to fill the dynamic sections of each role page. It also anchors the Assistant Builder and the Skill Pack library, so the five roles remain consistent wherever the buyer encounters them.

## How the Playbook prompt uses this library

At generation time, the Claude API call receives the buyer's chat responses, plus this library, plus the Playbook content template. For each of the five role pages, the prompt draws on three things in this document. First, the identity paragraph, which provides the static framing for the role. Second, the core responsibilities list, which describes what any buyer's version of the role handles. Third, the business-type example task menu, from which the prompt selects three to five examples that match the buyer's specific business and stated pain.

The library covers five business types. The prompt maps the buyer's `business_role` and `business_description` fields to one of these types, then draws examples from that column. If the buyer's business does not clearly match any one type, the prompt falls back to the generic solo practice column and blends in one or two examples from the closest match.

## Business type taxonomy

The five business types are Coach, Consultant, Advisor, Creator, and Generic solo practice.

Coach covers executive, leadership, life, business, and group coaching. Typically sells cohorts, one-to-one engagements, or retainers. Identity is usually the coach themselves.

Consultant covers strategy, management, operations, and subject-matter consulting. Typically sells project engagements, retainers, or diagnostic work. Often works with companies rather than individuals.

Advisor covers financial, career, and specialist ongoing advisory relationships. Typically sells recurring access or quarterly review cycles. Relationship is ongoing rather than project-based.

Creator covers course sellers, newsletter operators, podcast hosts, and education-first solo businesses. Typically sells digital products, memberships, or sponsor-funded content.

Generic solo practice is the fallback for any independent practitioner who does not fit cleanly into the four above. Designer, copywriter, developer, accountant, and other professional services often land here.

## The five archetypes

### 1. Exec Assistant

**Identity.** Your Exec Assistant is the layer between you and the noise of the day. Inbox, calendar, meeting prep, daily admin. The specialist who makes sure you do not miss what matters and do not get buried in what does not. Trained on your voice so everything that goes out in your name still sounds like you.

**Core responsibilities.** Email triage, reply drafting, calendar coordination, meeting prep briefs, post-meeting summaries, daily priority surfacing, and correspondence templates. This is the role most buyers hire first because the pain of a full inbox is immediate and universal.

**Example tasks by business type.**

| Business type | Example tasks |
|---|---|
| Coach | Triage client emails by urgency (cohort members, prospects, admin). Draft replies to weekly "can we reschedule?" messages in your voice. Flag inbound prospect emails for your review. Prep a one-page brief before each session from previous notes. Follow up on unanswered discovery call requests. |
| Consultant | Screen inbound new-business enquiries and flag the qualified ones. Draft client update emails from weekly progress notes. Summarise long client email threads before you reply. Prep agendas for upcoming stakeholder meetings. Chase outstanding responses on key decisions. |
| Advisor | Triage inbound questions from ongoing clients by urgency. Draft routine recommendation updates in your house style. Prep context briefs before quarterly review meetings. Send calendar holds for recurring review windows. Flag clients who have gone quiet for a check-in. |
| Creator | Screen and prioritise student or subscriber emails. Draft replies to common questions in your voice. Prep show notes, episode briefs, or newsletter openers. Follow up on unanswered collaboration or sponsor requests. Flag high-intent messages for your personal reply. |
| Generic solo | Triage inbound email by sender and topic. Draft responses to common enquiries. Prep meeting briefs from your notes and past context. Send reminders and follow-ups on your behalf. Maintain templates for repeated correspondence. |

### 2. Finance Assistant

**Identity.** Your Finance Assistant keeps the numbers visible without you having to open a spreadsheet. Weekly cashflow, invoicing, expense tracking, quick answers about your business health. This is not a replacement for a bookkeeper or accountant. It is the layer that sits between you and them, so you always know where you stand.

**Core responsibilities.** Weekly cashflow summaries, invoice drafting and reminders, expense categorisation, revenue and spend reporting, pricing sanity checks, and flagging anomalies before they become problems. Buyers who already work with a bookkeeper still benefit from this role as a visibility and responsiveness layer.

**Example tasks by business type.**

| Business type | Example tasks |
|---|---|
| Coach | Track cohort revenue against monthly targets. Draft invoices for new clients in your template. Chase unpaid invoices with polite reminders. Flag unusual expenses in your business account. Summarise the quarter's revenue by programme or offer. |
| Consultant | Track project billing against scope. Generate monthly invoice drafts from timesheets or fixed fees. Flag clients with ageing unpaid invoices. Summarise cashflow for the next 30 to 60 days. Track project expenses against retainer scopes. |
| Advisor | Track recurring advisory fee collections. Draft subscription renewal invoices. Flag churn or late payments for your attention. Produce a quarterly financial snapshot for your own planning. Reconcile payment platform deposits. |
| Creator | Track course or membership revenue by launch or cohort. Summarise platform payouts from Stripe, Teachable, Kajabi, Substack and similar. Flag refund requests for your decision. Draft sponsor or affiliate invoices. Track ad spend against launch revenue. |
| Generic solo | Weekly revenue and expense summary. Invoice drafting and reminder handling. Expense categorisation for tax time. Flag anything unusual or out of pattern. Draft end-of-month financial updates for your accountant. |

### 3. Sales Assistant

**Identity.** Your Sales Assistant handles the work of finding, qualifying, and following up with potential clients. Not a robot sales rep. A quietly competent pipeline manager who keeps things moving while you focus on delivery. Trained on your voice so outreach and follow-ups still sound like you.

**Core responsibilities.** Lead research and enrichment, outbound drafting, follow-up sequences, CRM updates and pipeline hygiene, proposal drafting, discovery call prep, and objection handling.

**Example tasks by business type.**

| Business type | Example tasks |
|---|---|
| Coach | Research prospects who booked a discovery call. Draft follow-up messages after discovery calls in your voice. Keep your CRM updated with prospect notes and status. Nudge prospects who went quiet after a proposal. Draft enrolment emails for cohort launches. |
| Consultant | Research target companies and decision makers. Draft outbound emails tailored to each prospect's context. Keep pipeline status current in your CRM. Draft project proposals based on your standard scope. Follow up on stalled deals with a next-step nudge. |
| Advisor | Identify renewal risk in current clients from signals and history. Draft expansion pitches for existing accounts. Keep your relationship map updated. Draft warm intro emails from referrals. Prep for quarterly retention conversations. |
| Creator | Draft launch sequences for new products. Identify high-intent subscribers for direct outreach. Follow up on affiliate or partnership conversations. Draft pitches for speaking, podcast, or newsletter features. Track launch pipeline from interest to purchase. |
| Generic solo | Research prospects before a call. Draft follow-ups after meetings. Keep pipeline status current in your CRM. Draft proposals from past templates. Handle objection responses in your voice. |

### 4. Marketing Assistant

**Identity.** Your Marketing Assistant writes, repurposes, and distributes the content that brings in work. Operates in your voice, never generic. This is the role that turns your thinking into posts, newsletters, ads, and collateral without needing you to sit and write from scratch.

**Core responsibilities.** Content drafting, repurposing across channels, ad copy, newsletter writing, social posts, landing page copy, and content idea generation grounded in what your audience actually asks about.

**Example tasks by business type.**

| Business type | Example tasks |
|---|---|
| Coach | Turn a coaching insight into a LinkedIn post in your voice. Draft your weekly newsletter from rough notes or a voice memo. Write ad copy for upcoming cohort launches. Repurpose a Q&A transcript into social content. Suggest content themes based on what clients ask most. |
| Consultant | Turn a client win, suitably anonymised, into a case-study post. Draft thought-leadership posts on your topic areas. Write landing page copy for service or programme pages. Repurpose a report or deck into a LinkedIn carousel. Draft speaker pitches for conferences and events. |
| Advisor | Summarise weekly market or industry insights for clients and followers. Draft quarterly commentary notes. Write LinkedIn updates that position your expertise. Repurpose long client notes into public-facing posts, anonymised. Draft newsletter issues for your subscriber base. |
| Creator | Draft email sequences for new product launches. Turn podcast episodes or videos into multi-platform snippets. Write ad copy for evergreen offers. Repurpose old content into new formats and channels. Draft sales page copy for new products. |
| Generic solo | Draft social posts from your rough thoughts or voice notes. Write newsletter content in your voice. Repurpose long content into short formats. Suggest content ideas based on what your audience engages with. Draft landing page copy for new offers. |

### 5. Operations Assistant

**Identity.** Your Operations Assistant runs the delivery side of your business. Onboarding, session prep, meeting notes, SOPs, the invisible work that keeps clients happy and the business repeatable. This is the role that turns what lives in your head into documented, runnable process.

**Core responsibilities.** Client onboarding, session and meeting preparation, post-session notes and follow-up, SOP documentation, workflow mapping, project status tracking, and handover documentation for contractors or VAs.

**Example tasks by business type.**

| Business type | Example tasks |
|---|---|
| Coach | Send onboarding forms and confirmations to new clients. Prep pre-session briefs from previous notes and client history. Draft post-session summaries and action items. Document your coaching process as an SOP. Track homework or commitments between sessions. |
| Consultant | Onboard new engagements with setup emails and kickoff briefs. Prep for client meetings with relevant project context. Draft meeting notes and action items. Document recurring delivery processes as SOPs. Track project milestones and surface blockers. |
| Advisor | Prep quarterly review packs with client context and prior recommendations. Draft post-review notes and updated recommendations. Onboard new advisory clients into your service rhythm. Document your advisory service as a repeatable process. Track commitments made in each review cycle. |
| Creator | Onboard new students or members with welcome sequences. Prep webinar or cohort session briefs. Document your launch playbook as an SOP. Track course completion and surface at-risk students. Maintain module-level content operations calendar. |
| Generic solo | Onboard new clients with setup emails and briefs. Prep for meetings with relevant context. Draft post-meeting notes and actions. Document recurring processes so you can delegate them. Maintain a project tracker with milestones and blockers. |

## Mapping rules

The Playbook generation prompt maps the buyer's chat responses to a business type using the following logic.

The first signal is `business_role`. If it includes coach, mentor, or facilitator, use Coach. If it includes consultant, advisor for a project-based engagement, strategist, or specialist, use Consultant. If it includes advisor in an ongoing relationship sense, planner, or fiduciary, use Advisor. If it includes course creator, newsletter operator, podcast host, educator outside a coaching frame, or membership operator, use Creator. For anything else, use Generic solo.

The second signal is `business_description`. If the role signal is ambiguous, the description disambiguates. A "business advisor who runs workshops and writes a newsletter" is probably a Consultant with Marketing emphasis, not an Advisor. A "life coach who sells a self-paced programme" is a Coach with Creator-style product revenue, so pull primarily from Coach and blend one or two Creator examples.

The third signal is the buyer's stated pain from `time_drains`, `tedious_work`, and `one_handoff_today`. These do not change the business type, but they shape which examples inside the chosen column get surfaced. If the buyer says email and inbox repeatedly, the Exec examples lean toward triage and reply drafting. If they say invoices and chasing payments, the Finance examples lean toward billing and cashflow.

## Content rules

Every example in this library is written to be specific. No filler, no "streamline your workflow" phrasing. Each task is a thing a buyer can recognise as happening in their real week.

Every example is written in second person, present tense. "Draft your weekly newsletter" not "Drafts weekly newsletters." This is so the Playbook generation can use the examples with minimal rewriting.

Every example names the action and the context. "Triage inbound email" is too vague. "Triage client emails by urgency (cohort members, prospects, admin)" is the right resolution.

## Open items

The five-type taxonomy is a starting point based on the primary ICP. Add types if the chat data reveals patterns we did not anticipate. Likely candidates are Agency owner (small shops) and Service provider (tradespeople).

The Playbook generation prompt itself is the next document to draft. It assembles this library with the chat inputs and the content template and produces the final Playbook text.

The Skill Pack library, the Make automations that layer on top of each role, draws from the same task menu. Email Triage and Content Repurposing map to Exec and Marketing respectively. As additional Skill Packs are prioritised, they should come from the tasks surfaced most often by the Playbook generation.
