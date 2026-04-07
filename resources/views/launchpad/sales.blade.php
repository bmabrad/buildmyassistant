<x-layouts.public :title="'AI Assistant Launchpad — Build My Assistant'" :description="'Your custom AI assistant, built in a single session. A guided chat that helps you automate the process eating your time. $5 AUD.'">

    {{-- Hero (dark) --}}
    <section class="bg-slate text-white py-16 text-center">
        <div class="max-w-[720px] mx-auto px-6">
            <p class="text-xs font-medium uppercase tracking-wide text-sage mb-3">The AI Assistant Launchpad</p>
            <h1 class="text-4xl font-medium text-white leading-tight mb-4">Your custom AI assistant, built in a single session</h1>
            <p class="text-soft-sage text-base max-w-[540px] mx-auto mb-8">A guided chat that helps you automate the process eating your time. Walk away with a complete instruction sheet and a system prompt ready to paste. $5 AUD.</p>
            <p class="text-3xl font-medium text-sage mb-6">$5 AUD</p>
            @if($canQuickBuy)
                <a href="{{ route('dashboard.new-build') }}" class="inline-block px-8 py-3 bg-sage text-white rounded-md text-sm font-medium no-underline cursor-pointer hover:bg-sage-dark">Build my assistant — $5</a>
            @else
                <form action="{{ route('launchpad.checkout') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-8 py-3 bg-sage text-white rounded-md text-sm font-medium cursor-pointer hover:bg-sage-dark">Build my assistant — $5</button>
                </form>
            @endif
        </div>
    </section>

    {{-- How it works (white) --}}
    <section class="py-16">
        <div class="max-w-[720px] mx-auto px-6">
            <h2 class="text-2xl font-medium text-slate leading-tight mb-6 text-center">How it works</h2>
            <p class="text-mid-blue text-center max-w-[540px] mx-auto mb-10">A guided chat session walks you through building a custom AI assistant for one process. By the end you will have everything you need to get it running.</p>

            <div class="space-y-8">
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-8 h-8 bg-sage/10 text-sage rounded-full flex items-center justify-center text-sm font-medium">1</div>
                    <div>
                        <h3 class="text-base font-medium text-slate mb-1">Bottleneck Discovery</h3>
                        <p class="text-mid-blue text-sm">We find the process draining the most hours from your week. If you already know, we dig straight into it.</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-8 h-8 bg-sage/10 text-sage rounded-full flex items-center justify-center text-sm font-medium">2</div>
                    <div>
                        <h3 class="text-base font-medium text-slate mb-1">Process Map</h3>
                        <p class="text-mid-blue text-sm">We map out everything involved in that process so your assistant knows exactly what it needs to handle.</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-8 h-8 bg-sage/10 text-sage rounded-full flex items-center justify-center text-sm font-medium">3</div>
                    <div>
                        <h3 class="text-base font-medium text-slate mb-1">Assistant Design</h3>
                        <p class="text-mid-blue text-sm">Your guide designs a custom AI assistant around how you work. It learns your patterns from your existing data, and you set the rules.</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-8 h-8 bg-sage/10 text-sage rounded-full flex items-center justify-center text-sm font-medium">4</div>
                    <div>
                        <h3 class="text-base font-medium text-slate mb-1">Handover</h3>
                        <p class="text-mid-blue text-sm">You receive a complete instruction sheet: your assistant's name, what it handles, how it learns, your rules, a full system prompt ready to paste, and step-by-step setup instructions.</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-8 h-8 bg-sage/10 text-sage rounded-full flex items-center justify-center text-sm font-medium">5</div>
                    <div>
                        <h3 class="text-base font-medium text-slate mb-1">Launch</h3>
                        <p class="text-mid-blue text-sm">Your assistant is ready to go. And once you see what it can do, you will already be thinking about the next one.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- What you get (off-white) --}}
    <section class="bg-off-white py-16">
        <div class="max-w-[720px] mx-auto px-6">
            <h2 class="text-2xl font-medium text-slate leading-tight mb-6 text-center">What you get</h2>
            <ul class="space-y-3 max-w-[540px] mx-auto">
                <li class="flex gap-3 text-mid-blue text-sm">
                    <span class="text-sage flex-shrink-0">&#10003;</span>
                    A guided chat that finds the process eating your time
                </li>
                <li class="flex gap-3 text-mid-blue text-sm">
                    <span class="text-sage flex-shrink-0">&#10003;</span>
                    A full map of everything involved in that process
                </li>
                <li class="flex gap-3 text-mid-blue text-sm">
                    <span class="text-sage flex-shrink-0">&#10003;</span>
                    A custom AI assistant that learns your patterns and follows your rules
                </li>
                <li class="flex gap-3 text-mid-blue text-sm">
                    <span class="text-sage flex-shrink-0">&#10003;</span>
                    A system prompt ready to paste, written in plain language
                </li>
                <li class="flex gap-3 text-mid-blue text-sm">
                    <span class="text-sage flex-shrink-0">&#10003;</span>
                    Step-by-step setup instructions you can follow straight away
                </li>
                <li class="flex gap-3 text-mid-blue text-sm">
                    <span class="text-sage flex-shrink-0">&#10003;</span>
                    Option to go deeper with more detailed configuration
                </li>
            </ul>
        </div>
    </section>

    {{-- Bottom CTA (white) --}}
    <section class="py-16 text-center">
        <div class="max-w-[720px] mx-auto px-6">
            <h2 class="text-2xl font-medium text-slate leading-tight mb-4">Ready to get your time back?</h2>
            <p class="text-mid-blue mb-6">Five steps. One process. A custom assistant ready to launch.</p>
            <p class="text-3xl font-medium text-sage mb-6">$5 AUD</p>
            @if($canQuickBuy)
                <a href="{{ route('dashboard.new-build') }}" class="inline-block px-8 py-3 bg-sage text-white rounded-md text-sm font-medium no-underline cursor-pointer hover:bg-sage-dark">Build my assistant — $5</a>
            @else
                <form action="{{ route('launchpad.checkout') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-8 py-3 bg-sage text-white rounded-md text-sm font-medium cursor-pointer hover:bg-sage-dark">Build my assistant — $5</button>
                </form>
            @endif
        </div>
    </section>

    {{-- FAQ --}}
    <section class="bg-off-white py-16">
        <div class="max-w-[720px] mx-auto px-6">
            <h2 class="text-2xl font-medium text-slate leading-tight mb-8 text-center">Common questions</h2>
            <div class="space-y-6 max-w-[640px] mx-auto">
                <div>
                    <h3 class="text-base font-medium text-slate mb-1">What do I get for $5?</h3>
                    <p class="text-sm">A complete instruction sheet for a custom AI assistant tailored to your business. Includes a system prompt ready to paste and step-by-step setup instructions.</p>
                </div>
                <div>
                    <h3 class="text-base font-medium text-slate mb-1">Do I need a Claude or ChatGPT account?</h3>
                    <p class="text-sm">The instructions are written for Claude CoWork by default, but they work with other platforms too. If you want platform-specific instructions, you can ask for them during the session.</p>
                </div>
                <div>
                    <h3 class="text-base font-medium text-slate mb-1">What if I do not know which process to automate?</h3>
                    <p class="text-sm">No problem. Your guide will ask about your business and help you identify the best one.</p>
                </div>
                <div>
                    <h3 class="text-base font-medium text-slate mb-1">Can I come back for another process?</h3>
                    <p class="text-sm">Yes. Each $5 session covers one process. Come back anytime to build your next assistant.</p>
                </div>
                <div>
                    <h3 class="text-base font-medium text-slate mb-1">How long does the session take?</h3>
                    <p class="text-sm">Most sessions take 10 to 15 minutes. You can take as long as you need.</p>
                </div>
                <div>
                    <h3 class="text-base font-medium text-slate mb-1">Can I go back to my session later?</h3>
                    <p class="text-sm">Yes. Your chat link stays active. You can return anytime.</p>
                </div>
            </div>
        </div>
    </section>

</x-layouts.public>
