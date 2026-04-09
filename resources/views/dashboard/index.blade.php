<x-layouts.public>
    <x-slot:title>Dashboard — Build My Assistant</x-slot:title>

    <section class="bg-off-white py-12" style="padding-top: 1em; padding-bottom: 1em;">
        <div>

            {{-- Header --}}
            <div class="flex items-center justify-between mb-8 flex-wrap gap-4">
                <h1 class="text-[22px] font-medium text-slate leading-[1.3]">Welcome back, {{ $user->first_name ?? explode(' ', $user->name)[0] }}</h1>
                <a href="/dashboard/new-build" class="inline-block px-6 py-2.5 bg-sage text-white rounded-md text-sm font-medium no-underline hover:opacity-90 transition-opacity">
                    Build another assistant
                </a>
            </div>

            {{-- Builds --}}
            <h2 class="text-[17px] font-medium text-slate leading-[1.4] mb-4">Your assistants</h2>

            @if($tasks->isEmpty())
                <div class="bg-white border border-soft-sage rounded-lg p-8 text-center mb-8">
                    <p class="text-mid-blue mb-4">You do not have any builds yet.</p>
                    <a href="/dashboard/new-build" class="inline-block px-6 py-2.5 bg-sage text-white rounded-md text-sm font-medium no-underline hover:opacity-90 transition-opacity">
                        Build your first assistant
                    </a>
                </div>
            @else
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem;">
                    @foreach($tasks as $task)
                        <div class="bg-white border border-soft-sage rounded-lg p-5 flex flex-col justify-between">
                            <div>
                                <h3 class="text-[15px] font-medium text-slate truncate">
                                    {{ $task->assistant_name ?? 'Untitled assistant' }}
                                </h3>
                                @if($task->bottleneck_summary)
                                    <p class="text-mid-blue text-sm mt-1 line-clamp-2">{{ $task->bottleneck_summary }}</p>
                                @endif
                                <p class="text-mid-blue text-xs mt-2">
                                    {{ $task->status === 'completed' ? 'Completed' : 'In progress' }}
                                    <span class="text-soft-sage mx-1">&middot;</span>
                                    {{ $task->created_at->format('j M Y') }}
                                </p>
                            </div>
                            <a href="{{ route('launchpad.chat', $task->token) }}" class="mt-3 inline-block text-center px-4 py-2 border border-sage text-sage rounded-md text-sm font-medium no-underline hover:bg-sage hover:text-white transition-colors">
                                {{ $task->status === 'completed' ? 'View' : 'Continue' }}
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </section>
</x-layouts.public>
