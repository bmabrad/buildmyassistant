<x-layouts.public :title="'Privacy policy — Build My Assistant'">

    <div class="section">
        <div class="container" style="max-width: 640px;">
            <h1 style="margin-bottom: 24px;">Privacy policy</h1>
            <p style="margin-bottom: 8px; font-size: 14px; opacity: 0.7;">Last updated: {{ date('F j, Y') }}</p>

            <p style="margin-bottom: 16px;">
                Build My Assistant ("we", "us", "our") is committed to protecting your privacy. This policy explains how we collect, use, and protect your personal information in accordance with the Australian Privacy Principles (APPs) under the Privacy Act 1988 (Cth).
            </p>

            <h2 style="margin-top: 32px;">What we collect</h2>
            <p style="margin-bottom: 16px;">
                When you use the AI Assistant Launchpad, we collect:
            </p>
            <ul style="margin-bottom: 16px; padding-left: 20px;">
                <li>Your name and email address (provided during Stripe checkout)</li>
                <li>Chat messages exchanged during your Launchpad session</li>
                <li>Payment information (processed by Stripe; we do not store card details)</li>
            </ul>

            <h2 style="margin-top: 32px;">Why we collect it</h2>
            <p style="margin-bottom: 16px;">
                We collect your information to:
            </p>
            <ul style="margin-bottom: 16px; padding-left: 20px;">
                <li>Deliver the Launchpad service (your guided chat session and instruction sheet)</li>
                <li>Personalise your assistant's instruction sheet with your name and details</li>
                <li>Send you your session link and any service-related communications</li>
                <li>Improve our service</li>
            </ul>

            <h2 style="margin-top: 32px;">How we store it</h2>
            <p style="margin-bottom: 16px;">
                Your data is stored securely using encrypted connections and industry-standard security measures. Our application is hosted on infrastructure that complies with applicable data protection standards.
            </p>

            <h2 style="margin-top: 32px;">Who we share it with</h2>
            <p style="margin-bottom: 16px;">
                We share your information only with the following third parties, solely to deliver our service:
            </p>
            <ul style="margin-bottom: 16px; padding-left: 20px;">
                <li><strong style="font-weight: 500;">Stripe</strong> — processes your payment. Stripe's privacy policy applies to payment data.</li>
                <li><strong style="font-weight: 500;">Anthropic</strong> — provides the AI that powers your chat session. Your chat messages are sent to Anthropic's API to generate responses.</li>
            </ul>
            <p style="margin-bottom: 16px;">
                We do not sell your personal information to anyone.
            </p>

            <h2 style="margin-top: 32px;">Your rights</h2>
            <p style="margin-bottom: 16px;">
                Under the Australian Privacy Principles, you have the right to:
            </p>
            <ul style="margin-bottom: 16px; padding-left: 20px;">
                <li>Access the personal information we hold about you</li>
                <li>Request correction of inaccurate information</li>
                <li>Request deletion of your personal information</li>
            </ul>
            <p style="margin-bottom: 16px;">
                To exercise any of these rights, contact us at <a href="mailto:hello@buildmyassistant.co">hello@buildmyassistant.co</a>.
            </p>

            <h2 style="margin-top: 32px;">Contact</h2>
            <p>
                For privacy enquiries, contact us at <a href="mailto:hello@buildmyassistant.co">hello@buildmyassistant.co</a>.
            </p>
        </div>
    </div>

</x-layouts.public>
