<x-layouts.public>
    <x-slot:title>Log in — Build My Assistant</x-slot:title>

    <section class="bg-off-white py-16">
        <div class="dialog-box">
            <h1 class="text-[22px] font-medium text-slate leading-[1.3] mb-2">Log in</h1>
            <p class="text-mid-blue mb-8">Access your dashboard and assistant builds.</p>

            @if(session('magic_link_sent'))
                <div class="bg-soft-sage/40 border border-sage rounded-md p-4 mb-6">
                    <p class="text-slate text-sm font-medium mb-1">Check your inbox</p>
                    <p class="text-mid-blue text-sm">We sent a login link to your email. It expires in 15 minutes.</p>
                </div>
            @endif

            {{-- Password login form --}}
            <form method="POST" action="/login" class="mb-6">
                @csrf

                <div class="mb-4">
                    <label for="email" class="block text-[11px] font-medium text-mid-blue uppercase tracking-[0.06em] mb-1">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        class="w-full px-3 py-2 border border-soft-sage rounded-md text-[15px] text-slate bg-white focus:outline-none focus:border-sage focus:ring-1 focus:ring-sage"
                    >
                    @error('email')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password" class="block text-[11px] font-medium text-mid-blue uppercase tracking-[0.06em] mb-1">Password <span class="normal-case tracking-normal font-normal">(optional)</span></label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="w-full px-3 py-2 border border-soft-sage rounded-md text-[15px] text-slate bg-white focus:outline-none focus:border-sage focus:ring-1 focus:ring-sage"
                    >
                    @error('password')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="w-full bg-sage text-white text-[15px] font-medium py-2.5 rounded-md hover:opacity-90 transition-opacity">
                    Log in
                </button>
            </form>

            {{-- Divider --}}
            <div class="flex items-center gap-4 mb-6">
                <div class="flex-1 h-px bg-soft-sage"></div>
                <span class="text-mid-blue text-xs">or</span>
                <div class="flex-1 h-px bg-soft-sage"></div>
            </div>

            {{-- Magic link form --}}
            <form method="POST" action="/login/magic">
                @csrf

                <input type="hidden" name="email" value="{{ old('email') }}">

                <button type="submit" class="w-full border border-sage text-sage text-[15px] font-medium py-2.5 rounded-md hover:bg-sage hover:text-white transition-colors">
                    Send me a magic link
                </button>

                <p class="text-mid-blue text-xs mt-2 text-center">We will email you a link to log in instantly, no password needed.</p>
            </form>
        </div>
    </section>
</x-layouts.public>
