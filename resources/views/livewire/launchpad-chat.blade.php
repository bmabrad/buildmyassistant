<div
    x-data="{
        scrollToBottom(smooth) {
            this.$nextTick(() => {
                const container = this.$refs.chatContainer;
                if (container) container.scrollTo({ top: container.scrollHeight, behavior: smooth ? 'smooth' : 'instant' });
            });
        },
        init() {
            this.scrollToBottom(false);
        },
        handleKeydown(e) {
            if (e.key === 'Enter' && !e.ctrlKey && !e.shiftKey) {
                e.preventDefault();
                $wire.sendMessage();
            }
        }
    }"
    x-init="scrollToBottom(false)"
    @response-complete.window="scrollToBottom(true); $nextTick(() => $refs.messageInput?.focus())"
    @if ($needsGreeting) wire:init="generateGreeting" @endif
    class="chat-page"
>
    <div class="chat-two-col">
        {{-- Chat column --}}
        <div class="chat-main">
            {{-- Header --}}
            <div class="chat-header">
                <h1 style="font-size: 17px; font-weight: 500; color: white; line-height: 1.4; margin: 0;">
                    AI Assistant Launchpad
                </h1>
            </div>

            {{-- Messages --}}
            <div
                x-ref="chatContainer"
                class="chat-messages"
            >
                @foreach ($messages as $message)
                    <div style="display: flex; justify-content: {{ $message->role === 'user' ? 'flex-end' : 'flex-start' }};">
                        <div class="chat-bubble {{ $message->role === 'user' ? 'chat-bubble-user' : 'chat-bubble-assistant' }} {{ $message->is_instruction_sheet ? 'chat-bubble-instructions' : '' }}">
                            @if ($message->role === 'assistant')
                                <div class="prose">{!! Str::markdown($message->content) !!}</div>

                                @if ($message->is_instruction_sheet)
                                    <div class="instruction-actions">
                                        <button
                                            x-data="{ copied: false }"
                                            x-on:click="
                                                navigator.clipboard.writeText(@js($message->content));
                                                copied = true;
                                                setTimeout(() => copied = false, 2000);
                                            "
                                            x-text="copied ? 'Copied!' : 'Copy instructions'"
                                            class="instruction-btn instruction-btn-primary"
                                        ></button>
                                        <a
                                            href="/launchpad/{{ $task->token }}/instructions.txt"
                                            download
                                            class="instruction-btn instruction-btn-outline"
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
                        <div class="chat-bubble chat-bubble-assistant">
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
                        (function() {
                            const container = document.querySelector('[x-ref="chatContainer"]');
                            if (!container) return;
                            const observer = new MutationObserver(() => {
                                container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
                            });
                            observer.observe(container, { childList: true, subtree: true, characterData: true });
                            document.addEventListener('livewire:navigated', () => observer.disconnect(), { once: true });
                        })();
                    </script>
                @endif
            </div>

            {{-- Error message --}}
            @if ($error)
                <div class="chat-error">
                    {{ $error }}
                </div>
            @endif

            {{-- Input --}}
            <div class="chat-input-area">
                <form
                    wire:submit="sendMessage"
                    style="display: flex; gap: 8px; align-items: flex-end;"
                >
                    <textarea
                        x-ref="messageInput"
                        wire:model="input"
                        rows="2"
                        placeholder="Type your message..."
                        maxlength="5000"
                        @if ($isStreaming) disabled @endif
                        autofocus
                        class="chat-input"
                        x-on:keydown="handleKeydown($event)"
                    ></textarea>
                    <button
                        type="submit"
                        @if ($isStreaming) disabled @endif
                        class="chat-send-btn"
                        style="
                            cursor: {{ $isStreaming ? 'not-allowed' : 'pointer' }};
                            opacity: {{ $isStreaming ? '0.6' : '1' }};
                        "
                    >
                        Send
                    </button>
                </form>
            </div>
        </div>

        {{-- Instructions panel (right sidebar) --}}
        <div class="chat-sidebar">
            <h2 style="font-size: 1.125rem; font-weight: 500; color: var(--deep-slate); margin-bottom: 0.5rem;">How it works</h2>
            <p style="font-size: 0.875rem; color: var(--mid-blue); margin-bottom: 1.25rem; line-height: 1.6;">Your AI guide will walk you through building a custom assistant. Here is what to expect:</p>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div style="display: flex; gap: 0.625rem; align-items: flex-start;">
                    <span class="sidebar-step-num">1</span>
                    <p style="font-size: 0.875rem; color: var(--mid-blue); line-height: 1.5; margin: 0;">Tell your guide about your business and what is eating your time</p>
                </div>
                <div style="display: flex; gap: 0.625rem; align-items: flex-start;">
                    <span class="sidebar-step-num">2</span>
                    <p style="font-size: 0.875rem; color: var(--mid-blue); line-height: 1.5; margin: 0;">Answer questions about the process so the guide understands it inside and out</p>
                </div>
                <div style="display: flex; gap: 0.625rem; align-items: flex-start;">
                    <span class="sidebar-step-num">3</span>
                    <p style="font-size: 0.875rem; color: var(--mid-blue); line-height: 1.5; margin: 0;">Receive your instruction sheet with a system prompt ready to paste</p>
                </div>
                <div style="display: flex; gap: 0.625rem; align-items: flex-start;">
                    <span class="sidebar-step-num">4</span>
                    <p style="font-size: 0.875rem; color: var(--mid-blue); line-height: 1.5; margin: 0;">Optionally go deeper with more detailed configuration</p>
                </div>
                <div style="display: flex; gap: 0.625rem; align-items: flex-start;">
                    <span class="sidebar-step-num">5</span>
                    <p style="font-size: 0.875rem; color: var(--mid-blue); line-height: 1.5; margin: 0;">Copy or download your instructions and set up your assistant</p>
                </div>
            </div>

            <div class="sidebar-tips">
                <p style="font-size: 0.75rem; font-weight: 500; color: var(--deep-slate); margin-bottom: 0.375rem;">Tips</p>
                <ul style="font-size: 0.75rem; color: var(--mid-blue); line-height: 1.6; margin: 0; padding-left: 1rem;">
                    <li>Answer one question at a time</li>
                    <li>Be as specific as you can about your process</li>
                    <li>Your chat link stays active so you can come back anytime</li>
                </ul>
            </div>
        </div>
    </div>

    <style>
        :root {
            --deep-slate: #1E2A38;
            --mid-blue: #3D5A73;
            --sage-accent: #7AA08A;
            --soft-sage: #C8D8CC;
            --off-white: #F4F6F4;
        }

        .chat-page {
            min-height: 70vh;
            background: var(--off-white);
            display: flex;
            align-items: center;
            justify-content: center;
            padding-top: 1em;
            padding-bottom: 1em;
        }

        .chat-two-col {
            display: flex;
            flex-direction: row;
            max-width: 1100px;
            width: 100%;
            margin: 0 auto;
            gap: 2em;
            height: 80vh;
            height: 80dvh;
            padding: 0;
        }

        .chat-main {
            flex: 3;
            display: flex;
            flex-direction: column;
            height: 100%;
            min-width: 0;
            background: white;
            border-radius: 1rem;
            border: 1px solid var(--soft-sage);
            box-shadow: 0 4px 24px rgba(30, 42, 56, 0.08), 0 1px 3px rgba(30, 42, 56, 0.04);
            overflow: hidden;
        }

        .chat-sidebar {
            flex: 2;
            padding: 2em 1.5em 2em 0;
            overflow-y: auto;
        }

        .sidebar-step-num {
            flex-shrink: 0;
            width: 1.5rem;
            height: 1.5rem;
            background: var(--sage-accent);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .sidebar-tips {
            margin-top: 1.5rem;
            background: var(--off-white);
            border-radius: 0.5rem;
            padding: 1rem;
        }

        .chat-header {
            padding: 16px 20px;
            background: var(--sage-accent);
            display: flex;
            align-items: center;
            border-radius: 1rem 1rem 0 0;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1em 1.5em;
            display: flex;
            flex-direction: column;
            gap: 16px;
            background: var(--off-white);
            border-radius: 0.5rem;
            -webkit-overflow-scrolling: touch;
        }

        .chat-bubble {
            max-width: 80%;
            padding: 0.625rem 1rem;
            font-size: 0.875rem;
            line-height: 1.6;
            word-break: break-word;
            color: #fff;
        }

        .chat-bubble-user {
            background: #2563eb;
            border-radius: 1rem 1rem 0.25rem 1rem;
        }

        .chat-bubble-assistant {
            background: #3a3a3c;
            border-radius: 1rem 1rem 1rem 0.25rem;
        }

        .chat-bubble-instructions {
            border-left: 4px solid var(--sage-accent);
        }

        .instruction-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            flex-wrap: wrap;
        }

        .instruction-btn {
            padding: 8px 14px;
            border-radius: 0.5rem;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            border: none;
        }

        .instruction-btn-primary {
            background: var(--sage-accent);
            color: white;
        }

        .instruction-btn-outline {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .chat-error {
            padding: 10px 20px;
            background: #fef2f2;
            border-top: 1px solid #fecaca;
            color: #991b1b;
            font-size: 13px;
            line-height: 1.5;
        }

        .chat-input-area {
            padding: 16px 20px;
            border-top: 1px solid var(--soft-sage);
            background: white;
        }

        .chat-input {
            flex: 1;
            padding: 0.625rem 1rem;
            border: 1px solid #333;
            border-radius: 0.75rem;
            font-family: 'Inter', sans-serif;
            font-size: 16px;
            color: var(--mid-blue);
            background: var(--off-white);
            outline: none;
            transition: border-color 0.2s;
            resize: none;
        }

        .chat-input:focus {
            border-color: var(--sage-accent);
        }

        .chat-send-btn {
            padding: 0.625rem 1.25rem;
            background: var(--sage-accent);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-family: 'Inter', sans-serif;
            font-size: 0.875rem;
            font-weight: 500;
            transition: opacity 0.2s;
            white-space: nowrap;
        }

        .chat-send-btn:hover {
            background: #6b9079;
        }

        .typing-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #aaa;
            animation: typing 1.2s infinite;
        }

        @keyframes typing {
            0%, 60%, 100% { opacity: 0.3; }
            30% { opacity: 1; }
        }

        /* Prose inside dark bubbles */
        .prose h1 { font-size: 1.25rem; font-weight: 500; margin: 16px 0 8px; color: white; line-height: 1.3; }
        .prose h2 { font-size: 1.0625rem; font-weight: 500; margin: 14px 0 6px; color: white; line-height: 1.4; }
        .prose h3 { font-size: 0.9375rem; font-weight: 500; margin: 12px 0 4px; color: white; line-height: 1.4; }
        .prose p { margin: 8px 0; }
        .prose ul, .prose ol { margin: 8px 0; padding-left: 20px; }
        .prose li { margin: 4px 0; }
        .prose strong { font-weight: 500; color: white; }
        .prose code {
            background: rgba(255, 255, 255, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.8125rem;
        }
        .prose pre {
            background: rgba(0, 0, 0, 0.3);
            color: #e0e0e0;
            padding: 16px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 12px 0;
        }
        .prose pre code {
            background: none;
            padding: 0;
            color: inherit;
            font-size: 0.8125rem;
        }
        .prose blockquote {
            border-left: 3px solid var(--sage-accent);
            padding-left: 12px;
            margin: 8px 0;
            opacity: 0.85;
        }
        .prose a {
            color: var(--sage-accent);
            text-decoration: underline;
        }

        /* Mobile: single column, sidebar below chat */
        @media (max-width: 767px) {
            .chat-page {
                padding: 1em;
                height: auto;
                min-height: 100vh;
                min-height: 100dvh;
                display: block;
            }
            .chat-two-col {
                flex-direction: column;
                gap: 1.5em;
                height: auto;
                max-width: 100%;
            }
            .chat-main {
                height: 80dvh;
                height: 80vh;
                width: 100%;
            }
            .chat-sidebar {
                padding: 0 0.5em 2em;
            }
            .chat-messages { padding: 12px; gap: 12px; }
            .chat-bubble { max-width: 92%; padding: 10px 14px; }
            .chat-header { padding: 12px 16px; }
            .chat-input-area { padding: 12px; }
            .chat-input { padding: 10px 14px; min-width: 0; }
            .chat-send-btn { padding: 10px 16px; flex-shrink: 0; }
            .prose pre { font-size: 12px; padding: 12px; }
            .prose h1 { font-size: 1.125rem; }
            .prose h2 { font-size: 1rem; }
        }
    </style>
</div>
