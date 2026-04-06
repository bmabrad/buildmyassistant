<x-layouts.public :title="'AI Assistant Launchpad — Build My Assistant'" :description="'A guided chat session that builds you a custom AI assistant for $5 AUD. One process per session. Walk away with clear instructions to get it running.'">

    {{-- Hero --}}
    <div class="section" style="text-align: center; padding: 80px 0 60px;">
        <div class="container">
            <h1 style="max-width: 600px; margin: 0 auto 16px;">Automate the process eating your time</h1>
            <p style="max-width: 560px; margin: 0 auto 32px; font-size: 17px;">
                A guided chat session that builds you a custom AI assistant for $5 AUD. One process per session. Walk away with clear instructions to get it running.
            </p>
            <form method="POST" action="{{ route('launchpad.checkout') }}">
                @csrf
                <button type="submit" class="btn" style="font-size: 17px; padding: 16px 32px;">Build my assistant &mdash; $5</button>
            </form>
        </div>
    </div>

    {{-- How it works --}}
    <div class="section section-alt">
        <div class="container">
            <div class="section-header">
                <h2>How it works</h2>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 32px; max-width: 800px; margin: 0 auto;">
                <div>
                    <h3 style="margin-bottom: 8px;">1. Pay $5 and start your session</h3>
                    <p style="font-size: 15px;">You get a private chat with your AI guide straight away. No account needed.</p>
                </div>
                <div>
                    <h3 style="margin-bottom: 8px;">2. Tell us what is eating your time</h3>
                    <p style="font-size: 15px;">Your guide asks about your business and the process you want to automate. If you are not sure which one, they will help you figure it out.</p>
                </div>
                <div>
                    <h3 style="margin-bottom: 8px;">3. Get your custom assistant</h3>
                    <p style="font-size: 15px;">Your guide builds a complete instruction sheet: the assistant's name, what it does, a system prompt ready to paste, and step-by-step setup instructions.</p>
                </div>
                <div>
                    <h3 style="margin-bottom: 8px;">4. Set it up and start saving time</h3>
                    <p style="font-size: 15px;">Follow the instructions to get your assistant running. Come back anytime for another session.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- What you get --}}
    <div class="section">
        <div class="container">
            <div class="section-header">
                <h2>What you get</h2>
            </div>
            <ul style="max-width: 600px; margin: 0 auto; list-style: none; display: flex; flex-direction: column; gap: 14px;">
                <li style="padding-left: 24px; position: relative;"><span style="position: absolute; left: 0; color: var(--sage-accent);">&check;</span> A guided chat session that digs into your biggest time drain</li>
                <li style="padding-left: 24px; position: relative;"><span style="position: absolute; left: 0; color: var(--sage-accent);">&check;</span> A full breakdown of everything involved in that process</li>
                <li style="padding-left: 24px; position: relative;"><span style="position: absolute; left: 0; color: var(--sage-accent);">&check;</span> A custom AI assistant built for your specific business and task</li>
                <li style="padding-left: 24px; position: relative;"><span style="position: absolute; left: 0; color: var(--sage-accent);">&check;</span> Your assistant gets a human name and is tailored to your way of working</li>
                <li style="padding-left: 24px; position: relative;"><span style="position: absolute; left: 0; color: var(--sage-accent);">&check;</span> Step-by-step setup instructions you can follow straight away</li>
                <li style="padding-left: 24px; position: relative;"><span style="position: absolute; left: 0; color: var(--sage-accent);">&check;</span> Option to go deeper with more detailed configuration</li>
            </ul>
        </div>
    </div>

    {{-- Price --}}
    <div class="section section-alt" style="text-align: center;">
        <div class="container">
            <h2>$5 AUD per session. One process, one assistant.</h2>
            <p style="margin-bottom: 24px;">Come back anytime to build your next one.</p>
            <form method="POST" action="{{ route('launchpad.checkout') }}">
                @csrf
                <button type="submit" class="btn" style="font-size: 17px; padding: 16px 32px;">Build my assistant &mdash; $5</button>
            </form>
        </div>
    </div>

    {{-- FAQ --}}
    <div class="section">
        <div class="container">
            <div class="section-header">
                <h2>Common questions</h2>
            </div>
            <div style="max-width: 640px; margin: 0 auto; display: flex; flex-direction: column; gap: 24px;">
                <div>
                    <h3 style="margin-bottom: 4px;">What do I get for $5?</h3>
                    <p style="font-size: 15px;">A complete instruction sheet for a custom AI assistant tailored to your business. Includes a system prompt ready to paste and step-by-step setup instructions.</p>
                </div>
                <div>
                    <h3 style="margin-bottom: 4px;">Do I need a Claude or ChatGPT account?</h3>
                    <p style="font-size: 15px;">The instructions are written for Claude CoWork by default, but they work with other platforms too. If you want platform-specific instructions, you can ask for them during the session.</p>
                </div>
                <div>
                    <h3 style="margin-bottom: 4px;">What if I do not know which process to automate?</h3>
                    <p style="font-size: 15px;">No problem. Your guide will ask about your business and help you identify the best one.</p>
                </div>
                <div>
                    <h3 style="margin-bottom: 4px;">Can I come back for another process?</h3>
                    <p style="font-size: 15px;">Yes. Each $5 session covers one process. Come back anytime to build your next assistant.</p>
                </div>
                <div>
                    <h3 style="margin-bottom: 4px;">How long does the session take?</h3>
                    <p style="font-size: 15px;">Most sessions take 10 to 15 minutes. You can take as long as you need.</p>
                </div>
                <div>
                    <h3 style="margin-bottom: 4px;">Can I go back to my session later?</h3>
                    <p style="font-size: 15px;">Yes. Your chat link stays active. You can return anytime.</p>
                </div>
            </div>
        </div>
    </div>

</x-layouts.public>
