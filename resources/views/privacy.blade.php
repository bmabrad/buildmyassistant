<x-layouts.public :title="'Privacy policy — Build My Assistant'">

    <section class="py-16">
        <div class="max-w-[720px] mx-auto px-6">
            <h1 class="text-4xl font-medium text-slate leading-tight mb-6">Privacy Policy</h1>
            <p class="text-sm text-mid-blue/70 mb-4">Last updated: {{ date('F j, Y') }}</p>

            <p class="mb-4">
                Build My Assistant ("we", "us", "our") is committed to protecting your privacy. This policy explains how we collect, use, and protect your personal information in accordance with the Australian Privacy Principles (APPs) under the Privacy Act 1988 (Cth).
            </p>

            <h2 class="text-2xl font-medium text-slate leading-tight mt-8 mb-4">What we collect</h2>
            <p class="mb-4">
                When you use the AI Assistant Launchpad, we collect:
            </p>
            <ul class="mb-4 pl-5 list-disc">
                <li>Your name and email address (provided during Stripe checkout)</li>
                <li>Chat messages exchanged during your Launchpad session</li>
                <li>Payment information (processed by Stripe; we do not store card details)</li>
            </ul>

            <h2 class="text-2xl font-medium text-slate leading-tight mt-8 mb-4">Why we collect it</h2>
            <p class="mb-4">
                We collect your information to:
            </p>
            <ul class="mb-4 pl-5 list-disc">
                <li>Deliver the Launchpad service (your guided chat session and instruction sheet)</li>
                <li>Personalise your assistant's instruction sheet with your name and details</li>
                <li>Send you your session link and any service-related communications</li>
                <li>Improve our service</li>
            </ul>

            <h2 class="text-2xl font-medium text-slate leading-tight mt-8 mb-4">How we store it</h2>
            <p class="mb-4">
                Your data is stored securely using encrypted connections and industry-standard security measures. Our application is hosted on infrastructure that complies with applicable data protection standards.
            </p>

            <h2 class="text-2xl font-medium text-slate leading-tight mt-8 mb-4">Who we share it with</h2>
            <p class="mb-4">
                We share your information only with the following third parties, solely to deliver our service:
            </p>
            <ul class="mb-4 pl-5 list-disc">
                <li><strong class="font-medium text-slate">Stripe</strong>, processes your payment. Stripe's privacy policy applies to payment data.</li>
                <li><strong class="font-medium text-slate">Anthropic</strong>, provides the AI that powers your chat session. Your chat messages are sent to Anthropic's API to generate responses.</li>
            </ul>
            <p class="mb-4">
                We do not sell your personal information to anyone.
            </p>

            <h2 class="text-2xl font-medium text-slate leading-tight mt-8 mb-4">Your rights</h2>
            <p class="mb-4">
                Under the Australian Privacy Principles, you have the right to:
            </p>
            <ul class="mb-4 pl-5 list-disc">
                <li>Access the personal information we hold about you</li>
                <li>Request correction of inaccurate information</li>
                <li>Request deletion of your personal information</li>
            </ul>
            <p class="mb-4">
                To exercise any of these rights, contact us at <a href="mailto:hello@buildmyassistant.co" class="text-sage hover:underline">hello@buildmyassistant.co</a>.
            </p>

            <h2 class="text-2xl font-medium text-slate leading-tight mt-8 mb-4">Contact</h2>
            <p>
                For privacy enquiries, contact us at <a href="mailto:hello@buildmyassistant.co" class="text-sage hover:underline">hello@buildmyassistant.co</a>.
            </p>
        </div>
    </section>

</x-layouts.public>
