<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Prompt Builder | Admin</title>
    @vite(['resources/css/app.css'])
    <style>
        :root {
            --deep-slate: #1E2A38;
            --mid-blue: #3D5A73;
            --sage: #7AA08A;
            --off-white: #F4F6F4;
        }
    </style>
</head>
<body style="background: var(--off-white); min-height: 100vh; display: flex; flex-direction: column; font-family: 'Inter', sans-serif;">

    {{-- Header --}}
    <header style="background: var(--deep-slate); color: white; padding: 1rem 1.5rem; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <h1 style="font-size: 1.125rem; font-weight: 500; margin: 0;">Prompt Builder</h1>
            <p style="font-size: 0.875rem; color: var(--mid-blue); margin: 0;">Chat to update the Launchpad system prompt</p>
        </div>
        <a href="/admin" style="font-size: 0.875rem; color: var(--mid-blue); text-decoration: none;">
            &larr; Back to Admin
        </a>
    </header>

    {{-- Chat container --}}
    <main style="flex: 1; display: flex; flex-direction: column; max-width: 48rem; margin: 0 auto; width: 100%; padding: 1.5rem 1rem;">

        {{-- Messages area --}}
        <div id="chat-messages" style="flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1rem; min-height: 400px; max-height: calc(100vh - 240px);">
            {{-- Welcome message --}}
            <div style="display: flex; justify-content: flex-start;">
                <div style="background: white; border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 0.75rem 1rem; max-width: 85%; font-size: 0.875rem; color: var(--mid-blue);">
                    <p style="margin: 0;">G'day Brad. I've got the current system prompt loaded. What would you like to change?</p>
                    <p style="margin-top: 0.5rem; color: #94a3b8; font-size: 0.75rem;">You can ask me to show any segment, propose changes, add new rules, or adjust the tone. I'll show you the change before applying it.</p>
                </div>
            </div>
        </div>

        {{-- Update notifications area --}}
        <div id="update-notifications" style="display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 0.5rem;"></div>

        {{-- Input area --}}
        <form id="chat-form" style="display: flex; gap: 0.75rem; align-items: flex-end;">
            <textarea
                id="chat-input"
                rows="2"
                placeholder="Tell me what to change..."
                style="flex: 1; resize: none; border: 1px solid #cbd5e1; border-radius: 0.5rem; padding: 0.75rem 1rem; font-size: 0.875rem; font-family: 'Inter', sans-serif; outline: none;"
            ></textarea>
            <button
                type="submit"
                id="send-btn"
                style="background: var(--sage); color: white; padding: 0.75rem 1.25rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; border: none; cursor: pointer;"
            >
                Send
            </button>
        </form>
    </main>

    <script>
        const messagesEl = document.getElementById('chat-messages');
        const form = document.getElementById('chat-form');
        const input = document.getElementById('chat-input');
        const sendBtn = document.getElementById('send-btn');
        const notificationsEl = document.getElementById('update-notifications');

        let chatHistory = [];

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const message = input.value.trim();
            if (!message) return;

            appendMessage('user', message);
            chatHistory.push({ role: 'user', content: message });

            input.value = '';
            input.style.height = 'auto';
            setLoading(true);

            try {
                const response = await fetch('{{ route("admin.prompt-builder.chat") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        message: message,
                        history: chatHistory.slice(0, -1),
                    }),
                });

                const data = await response.json();

                if (data.error) {
                    appendMessage('assistant', 'Something went wrong: ' + data.error);
                } else {
                    appendMessage('assistant', data.reply);
                    chatHistory.push({ role: 'assistant', content: data.reply });

                    if (data.updates_applied && data.updates_applied.length > 0) {
                        showUpdateNotifications(data.updates_applied);
                    }
                }
            } catch (err) {
                appendMessage('assistant', 'Network error. Check the console for details.');
                console.error(err);
            }

            setLoading(false);
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                form.dispatchEvent(new Event('submit'));
            }
        });

        input.addEventListener('input', () => {
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 150) + 'px';
        });

        function appendMessage(role, content) {
            const wrapper = document.createElement('div');
            wrapper.style.display = 'flex';
            wrapper.style.justifyContent = role === 'user' ? 'flex-end' : 'flex-start';

            const bubble = document.createElement('div');
            bubble.style.borderRadius = '0.5rem';
            bubble.style.padding = '0.75rem 1rem';
            bubble.style.maxWidth = '85%';
            bubble.style.fontSize = '0.875rem';

            if (role === 'user') {
                bubble.style.background = 'var(--deep-slate)';
                bubble.style.color = 'white';
                bubble.textContent = content;
            } else {
                bubble.style.background = 'white';
                bubble.style.border = '1px solid #e2e8f0';
                bubble.style.color = 'var(--mid-blue)';
                bubble.innerHTML = renderMarkdownLite(content);
            }

            wrapper.appendChild(bubble);
            messagesEl.appendChild(wrapper);
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }

        function renderMarkdownLite(text) {
            let html = text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            html = html.replace(/`([^`]+)`/g, '<code style="background: #f1f5f9; padding: 0.125rem 0.25rem; border-radius: 0.25rem; font-size: 0.75rem;">$1</code>');
            html = html.split('\n\n').map(p => `<p style="margin-bottom: 0.5rem;">${p}</p>`).join('');
            html = html.replace(/\n/g, '<br>');

            return html;
        }

        function showUpdateNotifications(updates) {
            updates.forEach(update => {
                const el = document.createElement('div');
                el.style.cssText = 'background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; border-radius: 0.5rem; padding: 0.5rem 1rem; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;';
                el.innerHTML = `
                    <svg style="width: 1rem; height: 1rem; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>${update}</span>
                `;
                notificationsEl.appendChild(el);

                setTimeout(() => {
                    el.style.transition = 'opacity 0.3s';
                    el.style.opacity = '0';
                    setTimeout(() => el.remove(), 300);
                }, 5000);
            });
        }

        function setLoading(loading) {
            sendBtn.disabled = loading;
            sendBtn.textContent = loading ? 'Thinking...' : 'Send';
            sendBtn.style.opacity = loading ? '0.5' : '1';
            sendBtn.style.cursor = loading ? 'not-allowed' : 'pointer';

            if (loading) {
                const indicator = document.createElement('div');
                indicator.id = 'typing-indicator';
                indicator.style.display = 'flex';
                indicator.style.justifyContent = 'flex-start';
                indicator.innerHTML = `
                    <div style="background: white; border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 0.75rem 1rem; font-size: 0.875rem; color: #94a3b8;">
                        Thinking...
                    </div>
                `;
                messagesEl.appendChild(indicator);
                messagesEl.scrollTop = messagesEl.scrollHeight;
            } else {
                document.getElementById('typing-indicator')?.remove();
            }
        }
    </script>
</body>
</html>
