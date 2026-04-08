<x-layouts.public :title="'AI Assistant Launchpad — Build My Assistant'" :description="'Turn your messy, time-consuming processes into AI assistants that handle them for you. One guided session, one custom assistant.'">

    {{-- Hero (dark) --}}
    <section class="bg-slate text-white py-16 text-center">
        <div class="max-w-[720px] mx-auto px-6">
            <p class="text-xs font-medium uppercase tracking-wide text-sage mb-3">The AI Assistant Launchpad</p>
            <h1 class="text-4xl font-medium text-white leading-tight mb-4">Turn your messy, time-consuming processes into AI assistants that handle them for you</h1>
            <p class="text-soft-sage text-base max-w-[580px] mx-auto mb-8">You know the ones. The tasks you keep meaning to sort out but never get around to. In each guided session, we map out your process, design a custom assistant around it, and hand you everything you need to get it running.</p>
            @auth
                <a href="{{ route('dashboard.new-build') }}" class="inline-block px-8 py-3 bg-sage text-white rounded-md text-sm font-medium no-underline cursor-pointer hover:bg-sage-dark">Build my assistant</a>
            @else
                <form method="POST" action="{{ route('launchpad.checkout') }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-block px-8 py-3 bg-sage text-white rounded-md text-sm font-medium cursor-pointer hover:bg-sage-dark">Build my assistant</button>
                </form>
            @endauth
        </div>
    </section>

    {{-- The Idea (white) --}}
    <section class="py-16">
        <div class="max-w-[720px] mx-auto px-6">
            <h2 class="text-2xl font-medium text-slate leading-tight mb-6 text-center">One process. One session. One custom assistant.</h2>
            <div class="max-w-[580px] mx-auto">
                <p class="mb-4">You have a process in your business that takes too long, feels tedious, and keeps falling to the bottom of the list. Maybe it is client follow-ups. Maybe it is session prep. Maybe it is something you have been avoiding for months.</p>
                <p>The AI Assistant Launchpad takes that process and turns it into a fully designed AI assistant, built around how you actually work. You walk away with Your Custom Assistant Playbook, everything you need to set it up and start getting time back.</p>
            </div>
        </div>
    </section>

    {{-- The Method (off-white) --}}
    <section class="bg-off-white py-16">
        <div class="max-w-[720px] mx-auto px-6">
            <h2 class="text-2xl font-medium text-slate leading-tight mb-4 text-center">Why this works when other AI tools haven&rsquo;t</h2>
            <p class="text-mid-blue text-center max-w-[580px] mx-auto mb-4">Most AI tools give you a blank screen and expect you to figure it out. That is why you have tried before and it did not stick.</p>
            <p class="text-mid-blue text-center max-w-[580px] mx-auto mb-10">The Launchpad is different. Before your assistant is designed, your guide helps you map out your actual process. Every step, every decision, every input. Your assistant is engineered from that map. That is why it works first time.</p>

            <h3 class="text-lg font-medium text-slate mb-6 text-center">The 5 Steps</h3>
            <div class="space-y-8">
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-8 h-8 bg-sage/10 text-sage rounded-full flex items-center justify-center text-sm font-medium">1</div>
                    <div>
                        <h3 class="text-base font-medium text-slate mb-1">Bottleneck Discovery</h3>
                        <p class="text-mid-blue text-sm">We find the process draining the most time from your week. If you already know, we dig straight into it.</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-8 h-8 bg-sage/10 text-sage rounded-full flex items-center justify-center text-sm font-medium">2</div>
                    <div>
                        <h3 class="text-base font-medium text-slate mb-1">Process Map</h3>
                        <p class="text-mid-blue text-sm">Your guide helps you map out everything involved in that process. Every step, every input, every decision point. Nothing gets missed.</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-8 h-8 bg-sage/10 text-sage rounded-full flex items-center justify-center text-sm font-medium">3</div>
                    <div>
                        <h3 class="text-base font-medium text-slate mb-1">Assistant Design</h3>
                        <p class="text-mid-blue text-sm">Your custom AI assistant is designed around your Process Map. It learns your patterns, follows your rules, and fits the way you work.</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-8 h-8 bg-sage/10 text-sage rounded-full flex items-center justify-center text-sm font-medium">4</div>
                    <div>
                        <h3 class="text-base font-medium text-slate mb-1">Handover</h3>
                        <p class="text-mid-blue text-sm">You receive Your Custom Assistant Playbook: your assistant&rsquo;s name, what it handles, how it learns, your rules, a system prompt ready to paste, and step-by-step setup instructions.</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-8 h-8 bg-sage/10 text-sage rounded-full flex items-center justify-center text-sm font-medium">5</div>
                    <div>
                        <h3 class="text-base font-medium text-slate mb-1">Launch</h3>
                        <p class="text-mid-blue text-sm">Your assistant is ready to go. Follow the instructions and start getting time back. And if you need help along the way, your guide is available for 7 days to answer questions and help you fine-tune.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Value Stack (white) --}}
    <section class="py-16">
        <div class="max-w-[720px] mx-auto px-6">
            <h2 class="text-2xl font-medium text-slate leading-tight mb-8 text-center">What you get</h2>
            <div class="space-y-6 max-w-[580px] mx-auto">
                <div>
                    <h3 class="text-base font-medium text-slate mb-1">Your Custom Assistant Playbook</h3>
                    <p class="text-mid-blue text-sm">A personalised playbook built for your business, your rules, and your process. Not a template. Not generic. Yours.</p>
                </div>
                <div>
                    <h3 class="text-base font-medium text-slate mb-1">A detailed Process Map of your process</h3>
                    <p class="text-mid-blue text-sm">Every step, every input, every decision point mapped out so nothing gets missed. This alone would cost hundreds from a consultant.</p>
                </div>
                <div>
                    <h3 class="text-base font-medium text-slate mb-1">A system prompt engineered from your Process Map</h3>
                    <p class="text-mid-blue text-sm">Ready to paste into Claude. Written in plain language so you understand exactly what it does and can tweak it yourself.</p>
                </div>
                <div>
                    <h3 class="text-base font-medium text-slate mb-1">Step-by-step setup instructions</h3>
                    <p class="text-mid-blue text-sm">Follow them straight away. No technical knowledge needed.</p>
                </div>
                <div>
                    <h3 class="text-base font-medium text-slate mb-1">7 days of support after your session</h3>
                    <p class="text-mid-blue text-sm">Come back anytime within 7 days to ask questions, troubleshoot setup, or refine your assistant. Your guide is still there.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- The Close (off-white) --}}
    <section class="bg-off-white py-16 text-center">
        <div class="max-w-[720px] mx-auto px-6">
            <h2 class="text-2xl font-medium text-slate leading-tight mb-4">One process. One assistant. $7.</h2>
            <p class="text-mid-blue max-w-[540px] mx-auto mb-4">Every assistant is custom. Every Playbook is built for your business. Come back anytime for another session to tackle the next process.</p>
            <p class="text-mid-blue max-w-[540px] mx-auto mb-8">If it does not work, come back during your 7 days and we will help you get it right.</p>
            @auth
                <a href="{{ route('dashboard.new-build') }}" class="inline-block px-8 py-3 bg-sage text-white rounded-md text-sm font-medium no-underline cursor-pointer hover:bg-sage-dark">Build my assistant</a>
            @else
                <form method="POST" action="{{ route('launchpad.checkout') }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-block px-8 py-3 bg-sage text-white rounded-md text-sm font-medium cursor-pointer hover:bg-sage-dark">Build my assistant</button>
                </form>
            @endauth
        </div>
    </section>

    {{-- FAQ --}}
    <section class="py-16">
        <div class="max-w-[720px] mx-auto px-6">
            <h2 class="text-2xl font-medium text-slate leading-tight mb-8 text-center">Common questions</h2>
            <div class="space-y-6 max-w-[640px] mx-auto">
                <div>
                    <h3 class="text-base font-medium text-slate mb-1">What do I get for $7?</h3>
                    <p class="text-sm">Your Custom Assistant Playbook, a detailed Process Map of your process, a system prompt ready to paste, step-by-step setup instructions, and 7 days of support to help you get it running.</p>
                </div>
                <div>
                    <h3 class="text-base font-medium text-slate mb-1">Do I need a Claude or ChatGPT account?</h3>
                    <p class="text-sm">The instructions are written for Claude by default, but they work with other platforms too. If you use a different tool, your guide can tailor the instructions.</p>
                </div>
                <div>
                    <h3 class="text-base font-medium text-slate mb-1">What if I do not know which process to automate?</h3>
                    <p class="text-sm">No problem. Your guide will ask about your business and help you identify the best one.</p>
                </div>
                <div>
                    <h3 class="text-base font-medium text-slate mb-1">What if my process is not a good fit for AI?</h3>
                    <p class="text-sm">Your guide will catch that in the first step and help you find one that is. You will not waste your session on something that does not work.</p>
                </div>
                <div>
                    <h3 class="text-base font-medium text-slate mb-1">Can I come back for another process?</h3>
                    <p class="text-sm">Yes. Each session covers one process. Come back anytime to build your next assistant.</p>
                </div>
                <div>
                    <h3 class="text-base font-medium text-slate mb-1">How long does the session take?</h3>
                    <p class="text-sm">Most sessions take 10 to 15 minutes. You can take as long as you need.</p>
                </div>
                <div>
                    <h3 class="text-base font-medium text-slate mb-1">What happens after 7 days?</h3>
                    <p class="text-sm">Your support window closes and the chat is locked. You can still view your Playbook and everything your guide delivered.</p>
                </div>
            </div>
        </div>
    </section>

</x-layouts.public>
