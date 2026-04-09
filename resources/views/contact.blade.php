<x-layouts.public :title="'Contact — Build My Assistant'" :description="'Get in touch with Build My Assistant.'">

    <section class="py-16">
        <div class="max-w-[720px] mx-auto px-6">
            <p class="text-xs font-medium uppercase tracking-wide text-sage mb-3">Contact</p>
            <h2 class="text-2xl font-medium text-slate leading-tight mb-4">Get in touch</h2>
            <p class="mb-4">Have a question or want to find out more? Send a message and we will get back to you.</p>

            @if(session('success'))
                <div class="bg-sage/10 border border-sage text-sage px-4 py-3 rounded-md text-sm mb-4">{{ session('success') }}</div>
            @endif

            <form action="{{ route('contact.store') }}" method="POST" class="max-w-[480px] mt-6">
                @csrf

                <div class="mb-5">
                    <label for="name" class="block font-medium text-sm text-slate mb-1">Name</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                        class="w-full px-3 py-2.5 border border-soft-sage rounded-md text-sm text-slate bg-off-white outline-none focus:border-sage">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-5">
                    <label for="email" class="block font-medium text-sm text-slate mb-1">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                        class="w-full px-3 py-2.5 border border-soft-sage rounded-md text-sm text-slate bg-off-white outline-none focus:border-sage">
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-5">
                    <label for="message" class="block font-medium text-sm text-slate mb-1">Message</label>
                    <textarea id="message" name="message" rows="6" required
                        class="w-full px-3 py-2.5 border border-soft-sage rounded-md text-sm text-slate bg-off-white outline-none focus:border-sage resize-y">{{ old('message') }}</textarea>
                    @error('message')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="px-6 py-2.5 bg-sage text-white rounded-md text-sm font-medium cursor-pointer hover:bg-sage-dark">Send message</button>
            </form>
        </div>
    </section>

</x-layouts.public>
