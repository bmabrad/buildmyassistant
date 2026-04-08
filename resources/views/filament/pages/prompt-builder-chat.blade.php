<div style="display: flex; gap: 1.5rem; width: 100%; max-width: 100%;">

    {{-- Left: Chat --}}
    <div style="flex: 1; min-width: 0;">
        <div style="display: flex; flex-direction: column; background: #f9fafb; border-radius: 0.75rem; border: 1px solid #e5e7eb; height: calc(100vh - 14rem);">

            {{-- Messages area --}}
            <div id="prompt-builder-messages" style="flex: 1; overflow-y: auto; padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                @if (empty($this->messages))
                    <div style="display: flex; justify-content: flex-start;">
                        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 0.75rem 1rem; max-width: 85%; font-size: 0.875rem; color: #3D5A73; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            <p style="margin: 0;">G'day Brad. I've got the current system prompt loaded. What would you like to change?</p>
                            <p style="margin: 0.5rem 0 0 0; font-size: 0.75rem; color: #94a3b8;">You can ask me to show any segment, propose changes, add new rules, or adjust the tone. I'll show you the change before applying it.</p>
                        </div>
                    </div>
                @endif

                @foreach ($this->messages as $msg)
                    <div style="display: flex; justify-content: {{ $msg['role'] === 'user' ? 'flex-end' : 'flex-start' }};">
                        <div style="border-radius: 0.75rem; padding: 0.75rem 1rem; max-width: 85%; font-size: 0.875rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
                            {{ $msg['role'] === 'user'
                                ? 'background: #1E2A38; color: white;'
                                : 'background: white; border: 1px solid #e5e7eb; color: #3D5A73;' }}">
                            @if ($msg['role'] === 'assistant')
                                <div class="prose prose-sm" style="max-width: none; font-size: 0.875rem; line-height: 1.6;">
                                    {!! \Illuminate\Support\Str::markdown($msg['content']) !!}
                                </div>
                            @else
                                {!! nl2br(e($msg['content'])) !!}
                            @endif
                        </div>
                    </div>
                @endforeach

                @if ($this->isLoading)
                    <div style="display: flex; justify-content: flex-start;">
                        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 0.75rem 1rem; font-size: 0.875rem; color: #94a3b8; box-shadow: 0 1px 2px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 0.5rem;">
                            <svg style="width: 1rem; height: 1rem; animation: spin 1s linear infinite;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Thinking...
                        </div>
                    </div>
                @endif
            </div>

            {{-- Update notifications --}}
            @if (! empty($this->recentUpdates))
                <div style="padding: 0 1rem 0.5rem; display: flex; flex-direction: column; gap: 0.5rem;">
                    @foreach ($this->recentUpdates as $update)
                        <div style="display: flex; align-items: center; gap: 0.5rem; background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.875rem;">
                            <svg style="width: 1rem; height: 1rem; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>{{ $update }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Input area --}}
            <div style="border-top: 1px solid #e5e7eb; padding: 1rem; background: white; border-radius: 0 0 0.75rem 0.75rem;">
                <form wire:submit="sendMessage" style="display: flex; gap: 0.75rem; align-items: flex-end;">
                    <textarea
                        wire:model="input"
                        rows="2"
                        placeholder="Tell me what to change..."
                        style="flex: 1; resize: none; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.75rem 1rem; font-size: 0.875rem; font-family: inherit; outline: none;"
                        @keydown.enter.prevent="if (!event.shiftKey) { $wire.sendMessage(); }"
                    ></textarea>
                    <button
                        type="submit"
                        style="background-color: #7AA08A; color: white; padding: 0.75rem 1.25rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; border: none; cursor: pointer;"
                    >
                        Send
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Right: Current prompt segments --}}
    <div style="width: 22rem; flex-shrink: 0;">
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; height: calc(100vh - 14rem); overflow-y: auto;">
            <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; background: white; border-radius: 0.75rem 0.75rem 0 0; z-index: 1;">
                <h3 style="margin: 0; font-size: 0.875rem; font-weight: 600; color: #1E2A38;">Current Prompt Segments</h3>
                <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #94a3b8;">{{ $this->getSegments()->count() }} active segments</p>
            </div>
            <div style="padding: 0.5rem;">
                @foreach ($this->getSegments() as $segment)
                    <details style="margin-bottom: 2px;">
                        <summary style="padding: 0.5rem 0.75rem; font-size: 0.8125rem; cursor: pointer; border-radius: 0.375rem; color: #1E2A38; display: flex; align-items: center; gap: 0.5rem; user-select: none;"
                            onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                            <span style="display: inline-block; padding: 0.125rem 0.375rem; border-radius: 0.25rem; font-size: 0.625rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;
                                {{ $segment->category === 'step'
                                    ? 'background: #dbeafe; color: #1e40af;'
                                    : ($segment->category === 'base'
                                        ? 'background: #f3e8ff; color: #6b21a8;'
                                        : 'background: #fef3c7; color: #92400e;') }}">
                                {{ $segment->category === 'step' ? 'S' . $segment->step_number : substr($segment->category, 0, 1) }}
                            </span>
                            <span style="font-weight: 500;">{{ $segment->label }}</span>
                        </summary>
                        <div style="padding: 0.5rem 0.75rem 0.75rem 2.25rem; font-size: 0.75rem; color: #3D5A73; line-height: 1.6; white-space: pre-wrap; font-family: 'SF Mono', 'Cascadia Code', 'Fira Code', monospace; background: #f9fafb; border-radius: 0 0 0.375rem 0.375rem; margin: 0 0.25rem 0.25rem; max-height: 20rem; overflow-y: auto;">{{ \Illuminate\Support\Str::limit($segment->content, 1500) }}</div>
                    </details>
                @endforeach
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes spin { to { transform: rotate(360deg); } }
    .prose p { margin-bottom: 0.5rem; }
    .prose p:last-child { margin-bottom: 0; }
    .prose ul, .prose ol { margin: 0.5rem 0; padding-left: 1.5rem; }
    .prose pre { background: #f4f6f4; border-radius: 0.5rem; padding: 0.75rem; font-size: 0.75rem; margin: 0.5rem 0; overflow-x: auto; }
    .prose code { background: #f1f5f9; padding: 0.125rem 0.25rem; border-radius: 0.25rem; font-size: 0.75rem; }
    .prose pre code { background: none; padding: 0; }
    details summary::-webkit-details-marker { display: none; }
    details summary::marker { font-size: 0.75rem; }
</style>
