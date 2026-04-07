<x-layouts.public>
    <x-slot:title>Settings — Build My Assistant</x-slot:title>

    <section class="bg-off-white py-12" style="padding-top: 1em; padding-bottom: 1em;">
        <div>

            <h1 class="text-[22px] font-medium text-slate leading-[1.3] mb-8">Settings</h1>

            @if(session('success'))
                <div class="bg-sage/10 border border-sage/30 text-sage rounded-md px-4 py-3 mb-6 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white border border-soft-sage rounded-lg p-6 max-w-md">
                <form method="POST" action="{{ route('settings.update') }}">
                    @csrf

                    <div class="mb-4">
                        <label for="first_name" class="block text-sm font-medium text-slate mb-1">First name</label>
                        <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" class="w-full px-3 py-2 bg-white border border-soft-sage rounded-md text-sm text-slate focus:outline-none focus:ring-1 focus:ring-sage focus:border-sage" required>
                        @error('first_name')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="last_name" class="block text-sm font-medium text-slate mb-1">Last name</label>
                        <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" class="w-full px-3 py-2 bg-white border border-soft-sage rounded-md text-sm text-slate focus:outline-none focus:ring-1 focus:ring-sage focus:border-sage">
                        @error('last_name')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="email" class="block text-sm font-medium text-slate mb-1">Email address</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" class="w-full px-3 py-2 bg-white border border-soft-sage rounded-md text-sm text-slate focus:outline-none focus:ring-1 focus:ring-sage focus:border-sage" required>
                        @error('email')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        @if($user->pending_email)
                            <div class="mt-2 px-3 py-2 bg-off-white border border-soft-sage/50 rounded-md">
                                <p class="text-xs text-mid-blue">
                                    Waiting for confirmation of <strong>{{ $user->pending_email }}</strong>.
                                    Check your inbox for the verification link.
                                </p>
                                <form method="POST" action="{{ route('settings.cancel-email') }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs text-sage underline hover:opacity-80 mt-1">Cancel change</button>
                                </form>
                            </div>
                        @endif
                    </div>

                    <button type="submit" class="px-6 py-2.5 bg-sage text-white rounded-md text-sm font-medium hover:opacity-90 transition-opacity">
                        Save changes
                    </button>
                </form>
            </div>

        </div>
    </section>
</x-layouts.public>
