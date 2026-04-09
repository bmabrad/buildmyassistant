<x-layouts.public :title="'Build My Assistant — Custom AI assistants for your business'">

    {{-- Hero (dark) --}}
    <section class="bg-slate text-white py-16 text-center">
        <div class="max-w-[720px] mx-auto px-6">
            <h1 class="text-4xl font-medium text-white leading-tight mb-4">Stop Doing the Work AI Should Be Doing for You</h1>
            <p class="text-soft-sage text-base max-w-[540px] mx-auto mb-8">You run your own business. You are good at what you do. But too much of your week goes to work that could be handled by a well-built AI assistant.</p>
            <a href="/launchpad" class="inline-block px-8 py-3 bg-sage text-white rounded-md text-sm font-medium no-underline hover:bg-sage-dark">See how it works</a>
        </div>
    </section>

    {{-- The Problem (white) --}}
    <section class="py-16">
        <div class="max-w-[720px] mx-auto px-6">
            <h2 class="text-2xl font-medium text-slate leading-tight mb-6 text-center">Sound familiar?</h2>
            <div class="max-w-[580px] mx-auto">
                <p class="mb-4">You know there are tasks you should automate. You have probably tried. But every time you sit down to set something up, you end up down a rabbit hole of options, prompts, and tools that do not quite do what you need. So you close the tab and go back to doing it yourself.</p>
                <p>The tasks pile up. The ones you keep putting off start to weigh on you. Not because they are hard, but because you know you should have sorted them out by now.</p>
            </div>
        </div>
    </section>

    {{-- The Idea (off-white) --}}
    <section class="bg-off-white py-16">
        <div class="max-w-[720px] mx-auto px-6">
            <h2 class="text-2xl font-medium text-slate leading-tight mb-6 text-center">We turn your messy processes into AI assistants that handle them for you</h2>
            <div class="max-w-[580px] mx-auto">
                <p class="mb-4">That is what Build My Assistant does. You tell us which process is draining your time, we map it out, design a custom AI assistant around it, and hand you everything you need to get it running.</p>
                <p>No learning curve. No figuring it out yourself. Just a working assistant, built for your business.</p>
            </div>
        </div>
    </section>

    {{-- How It Works (white) --}}
    <section class="py-16">
        <div class="max-w-[720px] mx-auto px-6">
            <h2 class="text-2xl font-medium text-slate leading-tight mb-6 text-center">How it works</h2>

            <div class="space-y-8 max-w-[580px] mx-auto">
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-8 h-8 bg-sage/10 text-sage rounded-full flex items-center justify-center text-sm font-medium">1</div>
                    <div>
                        <h3 class="text-base font-medium text-slate mb-1">Bottleneck Discovery</h3>
                        <p class="text-mid-blue text-sm">A guided session finds the process draining the most time from your week.</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-8 h-8 bg-sage/10 text-sage rounded-full flex items-center justify-center text-sm font-medium">2</div>
                    <div>
                        <h3 class="text-base font-medium text-slate mb-1">Process Map</h3>
                        <p class="text-mid-blue text-sm">Your guide helps you map out every step, input, and decision involved.</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-8 h-8 bg-sage/10 text-sage rounded-full flex items-center justify-center text-sm font-medium">3</div>
                    <div>
                        <h3 class="text-base font-medium text-slate mb-1">Your Custom Assistant Playbook</h3>
                        <p class="text-mid-blue text-sm">You walk away with a personalised Playbook: your assistant, your rules, a system prompt ready to paste, and step-by-step setup instructions.</p>
                    </div>
                </div>
            </div>

            <div class="text-center mt-10">
                <a href="/launchpad" class="inline-block px-8 py-3 bg-sage text-white rounded-md text-sm font-medium no-underline hover:bg-sage-dark">Build my assistant</a>
            </div>
        </div>
    </section>

    {{-- Social Proof Placeholder (off-white) --}}
    @php
        $hasTestimonials = false; // Set to true when testimonials are available
    @endphp
    @if($hasTestimonials)
    <section class="bg-off-white py-16">
        <div class="max-w-[720px] mx-auto px-6">
            <h2 class="text-2xl font-medium text-slate leading-tight mb-8 text-center">What people are saying</h2>
            <div class="grid md:grid-cols-2 gap-6">
                {{-- Testimonial cards will go here --}}
            </div>
        </div>
    </section>
    @endif

    {{-- Latest Articles --}}
    @if($articles->count())
    <section class="bg-off-white py-16">
        <div class="max-w-[720px] mx-auto px-6">
            <h2 class="text-2xl font-medium text-slate leading-tight mb-8 text-center">Learn more</h2>
            <div class="space-y-6">
                @foreach($articles as $article)
                    <article class="bg-white border border-soft-sage rounded-lg p-6">
                        <a href="/articles/{{ $article->slug }}" class="no-underline">
                            <h3 class="text-lg font-medium text-slate mb-2">{{ $article->title }}</h3>
                        </a>
                        @if($article->excerpt)
                            <p class="text-sm text-mid-blue mb-3">{{ $article->excerpt }}</p>
                        @endif
                        <a href="/articles/{{ $article->slug }}" class="text-sage font-medium text-sm">Read more &rarr;</a>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Bottom CTA (white or off-white depending on articles) --}}
    <section class="{{ $articles->count() ? 'py-16' : 'bg-off-white py-16' }} text-center">
        <div class="max-w-[720px] mx-auto px-6">
            <h2 class="text-2xl font-medium text-slate leading-tight mb-4">Ready to get your time back?</h2>
            <p class="text-mid-blue mb-8">One guided session. One custom assistant. Built for your business.</p>
            <a href="/launchpad" class="inline-block px-8 py-3 bg-sage text-white rounded-md text-sm font-medium no-underline hover:bg-sage-dark">See how it works</a>
        </div>
    </section>

</x-layouts.public>
