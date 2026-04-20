<x-layouts.public>
    <x-slot:title>Dashboard — Build My Assistant</x-slot:title>

    <section class="bg-off-white py-12" style="padding-top: 1em; padding-bottom: 1em;">
        <div>
            <div class="flex items-center justify-between mb-8 flex-wrap gap-4">
                <h1 class="text-[22px] font-medium text-slate leading-[1.3]">Welcome back, {{ $user->first_name ?? explode(' ', $user->name)[0] }}</h1>
            </div>

            <div class="bg-white border border-soft-sage rounded-lg p-8 mb-8">
                <p class="text-mid-blue mb-4">Your account is active. Products you purchase will appear here.</p>
                <div class="flex gap-3">
                    <a href="{{ route('settings') }}" class="inline-block px-6 py-2.5 bg-sage text-white rounded-md text-sm font-medium no-underline hover:opacity-90 transition-opacity">
                        Account settings
                    </a>
                    <form method="POST" action="{{ route('dashboard.billing') }}">
                        @csrf
                        <button type="submit" class="inline-block px-6 py-2.5 border border-sage text-sage rounded-md text-sm font-medium hover:bg-sage hover:text-white transition-colors">
                            Manage billing
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
