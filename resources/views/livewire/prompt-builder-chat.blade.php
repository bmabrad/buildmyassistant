<div class="flex flex-col bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700" style="height: calc(100vh - 14rem);">

    {{-- Messages area --}}
    <div
        class="flex-1 overflow-y-auto p-4 space-y-4"
        id="prompt-builder-messages"
    >
        {{-- Welcome message --}}
        @if (empty($messages))
            <div class="flex justify-start">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3 max-w-[85%] text-sm text-gray-600 dark:text-gray-300 shadow-sm">
                    <p>G'day Brad. I've got the current system prompt loaded. What would you like to change?</p>
                    <p class="mt-2 text-xs text-gray-400">You can ask me to show any segment, propose changes, add new rules, or adjust the tone. I'll show you the change before applying it.</p>
                </div>
            </div>
        @endif

        @foreach ($messages as $msg)
            <div class="flex {{ $msg['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                <div class="rounded-xl px-4 py-3 max-w-[85%] text-sm shadow-sm
                    {{ $msg['role'] === 'user'
                        ? 'text-white'
                        : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300' }}"
                    @if($msg['role'] === 'user') style="background-color: #1E2A38;" @endif
                >
                    @if ($msg['role'] === 'assistant')
                        <div class="prose prose-sm dark:prose-invert max-w-none [&>p]:mb-2 [&>p:last-child]:mb-0 [&>ul]:my-2 [&>ol]:my-2 [&>pre]:my-2 [&>pre]:bg-gray-50 [&>pre]:dark:bg-gray-900 [&>pre]:rounded-lg [&>pre]:p-3 [&>pre]:text-xs">
                            {!! \Illuminate\Support\Str::markdown($msg['content']) !!}
                        </div>
                    @else
                        {!! nl2br(e($msg['content'])) !!}
                    @endif
                </div>
            </div>
        @endforeach

        {{-- Loading indicator --}}
        @if ($isLoading)
            <div class="flex justify-start">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3 text-sm text-gray-400 shadow-sm">
                    <div class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Thinking...
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Update notifications --}}
    @if (! empty($recentUpdates))
        <div class="px-4 pb-2 space-y-2">
            @foreach ($recentUpdates as $update)
                <div class="flex items-center gap-2 bg-green-50 border border-green-200 text-green-800 rounded-lg px-3 py-2 text-sm dark:bg-green-900/20 dark:border-green-800 dark:text-green-300">
                    <x-heroicon-o-check-circle class="w-4 h-4 flex-shrink-0" />
                    <span>{{ $update }}</span>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Input area --}}
    <div class="border-t border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800 rounded-b-xl">
        <form wire:submit="sendMessage" class="flex gap-3 items-end">
            <textarea
                wire:model="input"
                rows="2"
                placeholder="Tell me what to change..."
                class="flex-1 resize-none border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-3 text-sm bg-white dark:bg-gray-900 dark:text-gray-200 focus:ring-2 focus:border-transparent outline-none"
                style="focus:ring-color: #7AA08A;"
                @keydown.enter.prevent="if (!event.shiftKey) { $wire.sendMessage(); }"
                @if($isLoading) disabled @endif
            ></textarea>
            <button
                type="submit"
                class="rounded-lg px-5 py-3 text-sm font-medium text-white transition"
                style="background-color: {{ $isLoading ? '#a3b8ab' : '#7AA08A' }}; {{ $isLoading ? 'cursor: not-allowed;' : 'cursor: pointer;' }}"
                @if($isLoading) disabled @endif
            >
                {{ $isLoading ? 'Thinking...' : 'Send' }}
            </button>
        </form>
    </div>

    @script
    <script>
        // Auto-scroll after Livewire updates
        $wire.hook('morph.updated', ({ el }) => {
            const container = document.getElementById('prompt-builder-messages');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        });
    </script>
    @endscript
</div>
