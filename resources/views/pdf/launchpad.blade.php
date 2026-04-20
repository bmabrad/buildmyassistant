<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Your Launchpad</title>
    <style>
        @page { margin: 24mm 20mm 24mm 20mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #3D5A73; font-size: 11pt; line-height: 1.6; }
        h1 { color: #1E2A38; font-size: 22pt; line-height: 1.2; font-weight: 500; margin: 0 0 8pt 0; }
        h2 { color: #1E2A38; font-size: 15pt; line-height: 1.3; font-weight: 500; margin: 18pt 0 8pt 0; }
        h3 { color: #1E2A38; font-size: 12pt; font-weight: 500; margin: 10pt 0 4pt 0; }
        p { margin: 0 0 8pt 0; }
        .slate { color: #1E2A38; }
        .sage { color: #7AA08A; }
        .mid { color: #3D5A73; }
        .soft { color: #C8D8CC; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .cover { text-align: center; padding-top: 50pt; }
        .cover .wordmark { font-size: 12pt; color: #1E2A38; }
        .cover .wordmark .dot { color: #7AA08A; }
        .cover h1 { margin-top: 40pt; }
        .cover .subtitle { margin-top: 10pt; font-size: 13pt; color: #3D5A73; }
        .cover .meta { margin-top: 30pt; font-size: 10pt; color: #3D5A73; }
        .callout { background: #F4F6F4; border-left: 3pt solid #7AA08A; padding: 12pt 14pt; margin-top: 40pt; font-size: 11pt; color: #1E2A38; }
        .prompt-block { background: #F4F6F4; border: 1pt solid #C8D8CC; border-radius: 4pt; padding: 14pt; font-family: DejaVu Sans Mono, monospace; font-size: 9pt; line-height: 1.5; white-space: pre-wrap; color: #1E2A38; margin: 10pt 0; }
        ol { padding-left: 18pt; }
        ol li { margin-bottom: 6pt; }
        .role-row { padding: 10pt 0; border-bottom: 1pt solid #C8D8CC; }
        .role-row:last-child { border-bottom: 0; }
        .role-row .name { color: #1E2A38; font-weight: 500; }
        .role-row .pick-tag { color: #7AA08A; font-size: 9pt; font-weight: 500; }
        .cta-block { margin-top: 14pt; padding: 14pt; border: 1pt solid #C8D8CC; border-radius: 4pt; }
        .cta-block h3 { margin-top: 0; }
        .closing { margin-top: 30pt; padding-top: 14pt; border-top: 1pt solid #C8D8CC; color: #3D5A73; font-size: 10pt; }
    </style>
</head>
<body>

<div class="page cover">
    <div class="wordmark">Build My Assistant<span class="dot">.co</span></div>
    <h1>Your Launchpad</h1>
    <p class="subtitle">{{ $output['cover']['subtitle'] ?? 'A starter prompt for your first AI assistant.' }}</p>
    <p class="meta">For {{ $lead->buyer_name ?? 'you' }} &middot; {{ $deliveredAt->format('j M Y') }}</p>
    <div class="callout">
        {{ $output['cover']['time_savings_line'] ?? 'Most solo practitioners recover several hours a week once their first assistant is running.' }}
    </div>
</div>

<div class="page">
    <h2>Your first assistant</h2>
    <p>{{ $output['your_first_assistant']['remit_paragraph'] ?? '' }}</p>
    <p>{{ $output['your_first_assistant']['pain_paragraph'] ?? '' }}</p>
    <p>{{ $output['your_first_assistant']['time_savings_paragraph'] ?? '' }}</p>
</div>

<div class="page">
    <h2>The starter prompt</h2>
    <p>Paste this into {{ $lead->tools_used ?: 'Claude CoWork' }}.</p>
    <div class="prompt-block">{{ $output['starter_prompt'] ?? '' }}</div>

    <h3>Install steps</h3>
    <ol>
        @foreach (($output['install_steps'] ?? []) as $step)
            <li>{{ $step }}</li>
        @endforeach
    </ol>
</div>

<div class="page">
    <h2>How to get the most out of it</h2>
    <h3>Onboard it with your own context</h3>
    <p>Tell the assistant about your clients, your voice, and your workflow. The more context you give it in the first hour, the better it gets at sounding like you.</p>
    <h3>Use it daily for the first week</h3>
    <p>Give it the real work, not test prompts. If it gets something wrong, tell it so it can adjust.</p>
    <h3>What to expect</h3>
    <p>Expect noticeable savings on repetitive work by the end of week one. Expect meaningful savings by the end of week three as it learns your patterns.</p>
</div>

<div class="page">
    <h2>The other four assistants</h2>
    @php $pickedRole = $output['assistant_pick']['role'] ?? null; @endphp
    @php
        $allRoles = [
            'Exec' => ['Exec Assistant', 'Handles inbox, calendar, and daily admin.'],
            'Finance' => ['Finance Assistant', 'Handles the numbers. Invoicing, cashflow, expenses.'],
            'Sales' => ['Sales Assistant', 'Handles leads and pipeline. Research, proposals, follow-up.'],
            'Marketing' => ['Marketing Assistant', 'Handles content and promotion. Newsletters, posts, copy.'],
            'Operations' => ['Operations Assistant', 'Handles delivery. Onboarding, SOPs, client workflow.'],
        ];
    @endphp
    @foreach ($allRoles as $key => $info)
        @php
            $match = collect($output['other_four'] ?? [])->firstWhere('role', $key);
            $isPicked = $key === $pickedRole;
        @endphp
        <div class="role-row">
            <p>
                <span class="name">{{ $info[0] }}.</span>
                {{ $match['one_line_remit'] ?? $info[1] }}
                @if ($match['typical_pain'] ?? null)
                    Most {{ $output['business_type'] ?? 'solo practitioners' }} start here when {{ rtrim($match['typical_pain'], '.') }}.
                @endif
                @if ($isPicked) <span class="pick-tag">(your first pick)</span> @endif
            </p>
        </div>
    @endforeach
    <p style="margin-top:14pt; color:#3D5A73; font-style: italic;">Start with one. Add the rest as the pain shifts.</p>
</div>

<div class="page">
    <h2>What is next</h2>

    <div class="cta-block">
        <h3>Build your custom assistant, $7</h3>
        <p>This starter works. The version built for your specific business, voice, and clients works better. Assistant Builder generates it in a single guided flow and hands you the full build.</p>
        <p class="sage">Start building &rsaquo;</p>
    </div>

    <div class="cta-block">
        <h3>Add automation with an Autopilot Pack</h3>
        <p>Once your assistant is running, an Autopilot Pack adds Make.com automation so the work happens without you triggering it. One pack per assistant type.</p>
        <p class="sage">See the packs &rsaquo;</p>
    </div>

    <div class="cta-block">
        <h3>Get us to install it for you, $390</h3>
        <p>Done-for-you. 30-minute session. We install an Autopilot Pack and connect the automation end to end.</p>
        <p class="sage">Book a Done-for-you Install &rsaquo;</p>
    </div>

    <p class="closing">
        Every option includes seven days of support after setup.<br>
        <br>
        Build My Assistant.co &middot; hello@buildmyassistant.co
    </p>
</div>

@if (! empty($output['no_ai_appendix']))
<div class="page">
    <h2>Appendix. Getting started with Claude CoWork</h2>
    <p style="white-space: pre-wrap;">{{ $output['no_ai_appendix'] }}</p>
</div>
@endif

</body>
</html>
