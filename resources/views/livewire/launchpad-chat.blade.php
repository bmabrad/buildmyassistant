<div
    x-data="{
        scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.chatContainer;
                if (container) container.scrollTop = container.scrollHeight;
            });
        },
        init() {
            this.scrollToBottom();
        }
    }"
    x-init="scrollToBottom()"
    @response-complete.window="scrollToBottom(); $nextTick(() => $refs.messageInput?.focus())"
    style="display: flex; flex-direction: column; height: 100vh; max-width: 800px; margin: 0 auto; background: var(--off-white);"
>
    {{-- Header --}}
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--soft-sage); background: white; display: flex; justify-content: space-between; align-items: center;">
        <h1 style="font-size: 17px; font-weight: 500; color: var(--deep-slate); line-height: 1.4;">
            AI Assistant Launchpad
        </h1>
        <a
            href="/launchpad/{{ $task->token }}/chat.txt"
            download
            style="font-size: 12px; color: var(--mid-blue); text-decoration: none; opacity: 0.7;"
        >Download chat</a>
    </div>

    {{-- Messages --}}
    <div
        x-ref="chatContainer"
        style="flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 16px;"
    >
        @foreach ($messages as $message)
            <div style="display: flex; justify-content: {{ $message->role === 'user' ? 'flex-end' : 'flex-start' }};">
                <div style="
                    max-width: 85%;
                    padding: 12px 16px;
                    border-radius: 12px;
                    {{ $message->role === 'user'
                        ? 'background: var(--deep-slate); color: white;'
                        : 'background: white; border: 1px solid var(--soft-sage); color: var(--mid-blue);' }}
                    {{ $message->is_instruction_sheet ? 'border-left: 4px solid var(--sage-accent); background: #f8faf8;' : '' }}
                    font-size: 15px;
                    line-height: 1.7;
                ">
                    @if ($message->role === 'assistant')
                        <div class="prose">{!! Str::markdown($message->content) !!}</div>

                        @if ($message->is_instruction_sheet)
                            <div style="display: flex; gap: 8px; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--soft-sage);">
                                <button
                                    x-data="{ copied: false }"
                                    x-on:click="
                                        navigator.clipboard.writeText(@js($message->content));
                                        copied = true;
                                        setTimeout(() => copied = false, 2000);
                                    "
                                    x-text="copied ? 'Copied!' : 'Copy instructions'"
                                    style="
                                        padding: 8px 14px;
                                        background: var(--sage-accent);
                                        color: white;
                                        border: none;
                                        border-radius: 6px;
                                        font-family: 'Inter', sans-serif;
                                        font-size: 13px;
                                        font-weight: 500;
                                        cursor: pointer;
                                    "
                                ></button>
                                <a
                                    href="/launchpad/{{ $task->token }}/instructions.txt"
                                    download
                                    style="
                                        padding: 8px 14px;
                                        background: white;
                                        color: var(--mid-blue);
                                        border: 1px solid var(--soft-sage);
                                        border-radius: 6px;
                                        font-family: 'Inter', sans-serif;
                                        font-size: 13px;
                                        font-weight: 500;
                                        text-decoration: none;
                                        cursor: pointer;
                                    "
                                >Download</a>
                            </div>
                        @endif
                    @else
                        {{ $message->content }}
                    @endif
                </div>
            </div>
        @endforeach

        {{-- Streaming response --}}
        @if ($isStreaming)
            <div style="display: flex; justify-content: flex-start;">
                <div style="
                    max-width: 85%;
                    padding: 12px 16px;
                    border-radius: 12px;
                    background: white;
                    border: 1px solid var(--soft-sage);
                    color: var(--mid-blue);
                    font-size: 15px;
                    line-height: 1.7;
                ">
                    <div class="prose" wire:stream="streamed-response">
                        <span style="display: inline-flex; gap: 4px; align-items: center; padding: 4px 0;">
                            <span class="typing-dot" style="animation-delay: 0s;"></span>
                            <span class="typing-dot" style="animation-delay: 0.2s;"></span>
                            <span class="typing-dot" style="animation-delay: 0.4s;"></span>
                        </span>
                    </div>
                </div>
            </div>

            <script>
                // Auto-scroll during streaming
                (function() {
                    const container = document.querySelector('[x-ref="chatContainer"]');
                    if (!container) return;
                    const observer = new MutationObserver(() => {
                        container.scrollTop = container.scrollHeight;
                    });
                    observer.observe(container, { childList: true, subtree: true, characterData: true });
                    document.addEventListener('livewire:navigated', () => observer.disconnect(), { once: true });
                })();
            </script>
        @endif
    </div>

    {{-- Input --}}
    <div style="padding: 16px 20px; border-top: 1px solid var(--soft-sage); background: white;">
        <form
            wire:submit="sendMessage"
            style="display: flex; gap: 8px; align-items: flex-end;"
        >
            <input
                x-ref="messageInput"
                wire:model="input"
                type="text"
                placeholder="Type your message..."
                @if ($isStreaming) disabled @endif
                autofocus
                style="
                    flex: 1;
                    padding: 12px 16px;
                    border: 1px solid var(--soft-sage);
                    border-radius: 8px;
                    font-family: 'Inter', sans-serif;
                    font-size: 15px;
                    color: var(--mid-blue);
                    background: var(--off-white);
                    outline: none;
                    transition: border-color 0.2s;
                "
                onfocus="this.style.borderColor='var(--sage-accent)'"
                onblur="this.style.borderColor='var(--soft-sage)'"
            >
            <button
                type="submit"
                @if ($isStreaming) disabled @endif
                style="
                    padding: 12px 20px;
                    background: var(--sage-accent);
                    color: white;
                    border: none;
                    border-radius: 8px;
                    font-family: 'Inter', sans-serif;
                    font-size: 15px;
                    font-weight: 500;
                    cursor: {{ $isStreaming ? 'not-allowed' : 'pointer' }};
                    opacity: {{ $isStreaming ? '0.6' : '1' }};
                    transition: opacity 0.2s;
                "
            >
                Send
            </button>
        </form>
    </div>

    <style>
        .typing-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--sage-accent);
            animation: typing 1.2s infinite;
        }

        @keyframes typing {
            0%, 60%, 100% { opacity: 0.3; }
            30% { opacity: 1; }
        }

        .prose h1 { font-size: 22px; font-weight: 500; margin: 16px 0 8px; color: var(--deep-slate); line-height: 1.3; }
        .prose h2 { font-size: 17px; font-weight: 500; margin: 14px 0 6px; color: var(--deep-slate); line-height: 1.4; }
        .prose h3 { font-size: 15px; font-weight: 500; margin: 12px 0 4px; color: var(--deep-slate); line-height: 1.4; }
        .prose p { margin: 8px 0; }
        .prose ul, .prose ol { margin: 8px 0; padding-left: 20px; }
        .prose li { margin: 4px 0; }
        .prose strong { font-weight: 500; color: var(--deep-slate); }
        .prose code {
            background: var(--off-white);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 13px;
        }
        .prose pre {
            background: var(--deep-slate);
            color: var(--off-white);
            padding: 16px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 12px 0;
        }
        .prose pre code {
            background: none;
            padding: 0;
            color: inherit;
            font-size: 13px;
        }
        .prose blockquote {
            border-left: 3px solid var(--sage-accent);
            padding-left: 12px;
            margin: 8px 0;
            color: var(--mid-blue);
        }

        @media (max-width: 640px) {
            .prose pre { font-size: 12px; padding: 12px; }
        }
    </style>
</div>
