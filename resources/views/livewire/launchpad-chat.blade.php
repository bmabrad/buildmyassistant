<div>
<section class="bg-off-white py-12 md:py-16">
<div class="max-w-[640px] mx-auto px-6">
    <div class="text-center mb-8">
        <h1 class="text-[22px] md:text-3xl font-medium text-slate leading-[1.3] mb-2">Your starter assistant, five minutes from now</h1>
        <p class="text-mid-blue">Answer ten short questions. Get a working starter prompt in your inbox.</p>
    </div>
    <div class="bg-white border border-soft-sage rounded-lg p-6 md:p-8 shadow-sm">

        @if ($step === 0)
            <div class="space-y-4">
                <p class="text-[17px] font-medium text-slate leading-[1.4]">Hey. What would you do with an extra ten hours a week?</p>
                <p>I am going to ask you ten quick questions about your business, the tools you use, and the work that eats your time. Five to seven minutes. At the end, I will generate a starter prompt for your first AI assistant, shaped around what you told me. It lands in your inbox within ten minutes, ready to paste into your tool of choice.</p>
                <p>Sound good? Let's go.</p>
                <button type="button" wire:click="start" class="inline-block px-8 py-3 bg-sage text-white rounded-md text-sm font-medium hover:opacity-90 transition-opacity">
                    Start
                </button>
            </div>

        @elseif ($step === 1)
            <form wire:submit="submitStep" class="space-y-4">
                <p class="text-[17px] font-medium text-slate leading-[1.4]">First up, what is your name?</p>
                <input type="text" wire:model="buyerName" placeholder="First name is fine." autofocus
                    class="w-full px-4 py-3 border border-soft-sage rounded-md text-[15px] focus:outline-none focus:border-sage" />
                @error('buyerName') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                <button type="submit" class="px-6 py-2.5 bg-sage text-white rounded-md text-sm font-medium hover:opacity-90">Next</button>
            </form>

        @elseif ($step === 2)
            <form wire:submit="submitStep" class="space-y-4">
                <p class="text-mid-blue">Nice to meet you, {{ $buyerName }}.</p>
                <p class="text-[17px] font-medium text-slate leading-[1.4]">In one sentence, what does your business do, and who is it for?</p>
                <textarea wire:model="businessDescription" rows="2" placeholder="e.g. Leadership coaching for mid-career execs in finance and tech." autofocus
                    class="w-full px-4 py-3 border border-soft-sage rounded-md text-[15px] focus:outline-none focus:border-sage"></textarea>
                @error('businessDescription') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                <button type="submit" class="px-6 py-2.5 bg-sage text-white rounded-md text-sm font-medium hover:opacity-90">Next</button>
            </form>

        @elseif ($step === 3)
            <div class="space-y-4">
                <p class="text-mid-blue">Got it.</p>
                <p class="text-[17px] font-medium text-slate leading-[1.4]">Which AI tool do you use most often? If you use a few, pick the one you are on daily. If you have not really used AI yet, that is fine too.</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach ($toolOptions as $tool)
                        <button type="button" wire:click="chooseTool('{{ $tool }}')"
                            class="px-4 py-3 border border-soft-sage rounded-md text-[14px] font-medium text-slate hover:bg-off-white hover:border-sage transition-colors">
                            {{ $tool }}
                        </button>
                    @endforeach
                </div>
            </div>

        @elseif ($step === 4)
            <form wire:submit="submitStep" class="space-y-4">
                <p class="text-mid-blue">Good.</p>
                <p class="text-[17px] font-medium text-slate leading-[1.4]">And how would you describe your own role? Coach, consultant, advisor, designer, creator, something else?</p>
                <input type="text" wire:model="businessRole" placeholder="e.g. Executive coach." autofocus
                    class="w-full px-4 py-3 border border-soft-sage rounded-md text-[15px] focus:outline-none focus:border-sage" />
                @error('businessRole') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                <button type="submit" class="px-6 py-2.5 bg-sage text-white rounded-md text-sm font-medium hover:opacity-90">Next</button>
            </form>

        @elseif ($step === 5)
            <form wire:submit="submitStep" class="space-y-4">
                <p class="text-mid-blue">Makes sense.</p>
                <p class="text-[17px] font-medium text-slate leading-[1.4]">Who are your clients, specifically? The more specific you are, the better your starter prompt will be.</p>
                <textarea wire:model="whoTheyServe" rows="2" placeholder="e.g. Directors and VPs navigating a step up into exec roles." autofocus
                    class="w-full px-4 py-3 border border-soft-sage rounded-md text-[15px] focus:outline-none focus:border-sage"></textarea>
                @error('whoTheyServe') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                <button type="submit" class="px-6 py-2.5 bg-sage text-white rounded-md text-sm font-medium hover:opacity-90">Next</button>
            </form>

        @elseif ($step === 6)
            <form wire:submit="submitStep" class="space-y-4">
                <p class="text-mid-blue">Good. Now the fun bit. Where your time actually goes.</p>
                <p class="text-[17px] font-medium text-slate leading-[1.4]">Which of these eats the most time in a typical week? Tick any that apply, and add anything I missed.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach ($timeDrainOptions as $option)
                        <label class="flex items-center gap-2 p-2 cursor-pointer hover:bg-off-white rounded">
                            <input type="checkbox" wire:model="timeDrainChecks" value="{{ $option }}" class="rounded border-soft-sage text-sage focus:ring-sage" />
                            <span class="text-[14px] text-slate">{{ $option }}</span>
                        </label>
                    @endforeach
                </div>
                <div>
                    <label class="text-[12px] font-medium text-mid-blue uppercase tracking-wider">Anything else?</label>
                    <input type="text" wire:model="timeDrainOther" placeholder="Optional."
                        class="w-full px-4 py-3 border border-soft-sage rounded-md text-[15px] focus:outline-none focus:border-sage mt-1" />
                </div>
                @error('timeDrainChecks') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                <button type="submit" class="px-6 py-2.5 bg-sage text-white rounded-md text-sm font-medium hover:opacity-90">Next</button>
            </form>

        @elseif ($step === 7)
            <form wire:submit="submitStep" class="space-y-4">
                <p class="text-mid-blue">Right, that tells me a lot.</p>
                <p class="text-[17px] font-medium text-slate leading-[1.4]">What kind of work do you put off because it feels tedious or low value?</p>
                <textarea wire:model="tediousWork" rows="3" placeholder="e.g. Writing session notes, chasing late invoices, drafting the same follow-up emails over and over." autofocus
                    class="w-full px-4 py-3 border border-soft-sage rounded-md text-[15px] focus:outline-none focus:border-sage"></textarea>
                @error('tediousWork') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                <button type="submit" class="px-6 py-2.5 bg-sage text-white rounded-md text-sm font-medium hover:opacity-90">Next</button>
            </form>

        @elseif ($step === 8)
            <form wire:submit="submitStep" class="space-y-4">
                <p class="text-mid-blue">Okay, last one on the pain.</p>
                <p class="text-[17px] font-medium text-slate leading-[1.4]">If you could hand one task off to a reliable assistant tomorrow morning, what would it be?</p>
                <textarea wire:model="oneHandoffToday" rows="2" placeholder="e.g. Drafting the follow-up emails I send after every client session." autofocus
                    class="w-full px-4 py-3 border border-soft-sage rounded-md text-[15px] focus:outline-none focus:border-sage"></textarea>
                @error('oneHandoffToday') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                <button type="submit" class="px-6 py-2.5 bg-sage text-white rounded-md text-sm font-medium hover:opacity-90">Next</button>
            </form>

        @elseif ($step === 9)
            <div class="space-y-4">
                <p class="text-mid-blue">Nearly there.</p>
                <p class="text-[17px] font-medium text-slate leading-[1.4]">Quick one. How much do you use AI in your work already?</p>
                <div class="flex flex-col sm:flex-row gap-2">
                    @foreach ($aiUsageOptions as $value => $label)
                        <button type="button" wire:click="chooseAiUsage('{{ $value }}')"
                            class="flex-1 px-4 py-3 border border-soft-sage rounded-md text-[14px] text-slate hover:bg-off-white hover:border-sage transition-colors">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
                <button type="button" wire:click="skipAiUsage" class="text-sage text-sm underline hover:no-underline">Skip this.</button>
            </div>

        @elseif ($step === 10)
            <form wire:submit="submitStep" class="space-y-4">
                <p class="text-mid-blue">That is everything I need.</p>
                <p class="text-[17px] font-medium text-slate leading-[1.4]">Last step. Where should I send your starter prompt? It will land in your inbox within ten minutes.</p>
                <input type="email" wire:model="buyerEmail" placeholder="you@yourbusiness.com" autofocus
                    class="w-full px-4 py-3 border border-soft-sage rounded-md text-[15px] focus:outline-none focus:border-sage" />
                @error('buyerEmail') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                <button type="submit" class="px-6 py-2.5 bg-sage text-white rounded-md text-sm font-medium hover:opacity-90">Send my starter prompt</button>
            </form>

        @elseif ($step === 11)
            <div class="space-y-4">
                <p class="text-[17px] font-medium text-slate leading-[1.4]">Thanks, {{ $buyerName }}. Your starter prompt is being generated now. It will be in your inbox within ten minutes, with the full Launchpad PDF attached.</p>
                <p>While you wait, have a look at how the five assistants work together, or come back here once your starter lands.</p>
                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <a href="/#five-assistants" class="inline-block px-6 py-2.5 border border-sage text-sage rounded-md text-sm font-medium text-center hover:bg-sage hover:text-white transition-colors">
                        How the five assistants work
                    </a>
                    <a href="/" class="inline-block px-6 py-2.5 text-mid-blue text-sm font-medium text-center hover:text-slate">
                        Close chat
                    </a>
                </div>
            </div>
        @endif

    </div>
</div>
</section>
</div>
