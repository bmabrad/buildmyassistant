<x-layouts.public>
    <x-slot:title>Confirm new build — Build My Assistant</x-slot:title>

    <section class="bg-off-white py-16">
        <div class="dialog-box">
            <h1 class="text-[22px] font-medium text-slate leading-[1.3] mb-2">Build another assistant</h1>
            <p class="text-mid-blue mb-8">Start a new guided session to build a custom AI assistant for another process.</p>

            @error('charge')
                <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
                    <p class="text-red-700 text-sm">{{ $message }}</p>
                    @if(session('show_fallback'))
                        <form method="POST" action="/launchpad/checkout" class="inline mt-2">
                            @csrf
                            <button type="submit" class="text-sage text-sm font-medium hover:underline bg-transparent border-none cursor-pointer p-0">
                                Pay with a new card instead
                            </button>
                        </form>
                    @endif
                </div>
            @enderror

            <div class="bg-white border border-soft-sage rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-slate font-medium">AI Assistant Launchpad</span>
                    <span class="text-slate font-medium">$7 AUD</span>
                </div>

                <p class="text-mid-blue text-sm mb-6">
                    Charging your saved {{ ucfirst($paymentMethod?->card?->brand ?? 'card') }}{{ $paymentMethod?->card?->last4 ? ' ending in ' . $paymentMethod->card->last4 : '' }}
                </p>

                <form method="POST" action="/dashboard/new-build">
                    @csrf
                    <button type="submit" class="w-full bg-sage text-white text-[15px] font-medium py-2.5 rounded-md hover:opacity-90 transition-opacity" style="margin-bottom: 1em;">
                        Confirm — $7 AUD
                    </button>
                </form>

                <p class="text-mid-blue text-xs mt-3 text-center">
                    <a href="/dashboard" class="text-sage hover:underline">Cancel</a>
                </p>
            </div>
        </div>
    </section>
</x-layouts.public>
