<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your AI Assistant Playbook is ready</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F4F6F4; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #3D5A73; font-size: 15px; line-height: 1.7;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #F4F6F4;">
        <tr>
            <td align="center" style="padding: 40px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 560px; background-color: #ffffff; border-radius: 8px; border: 1px solid #C8D8CC;">

                    {{-- Header --}}
                    <tr>
                        <td style="padding: 32px 32px 24px; border-bottom: 1px solid #C8D8CC;">
                            <span style="font-size: 17px; font-weight: 500; color: #1E2A38; letter-spacing: -0.02em;">Build My Assistant</span><span style="font-size: 17px; font-weight: 500; color: #7AA08A; letter-spacing: -0.02em;">.co</span>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 32px;">
                            <p style="margin: 0 0 20px; color: #1E2A38; font-size: 17px; font-weight: 500; line-height: 1.4;">
                                Hi {{ $buyerName }}, your Playbook is ready.
                            </p>

                            <p style="margin: 0 0 20px;">
                                Your AI assistant <strong style="color: #1E2A38; font-weight: 500;">{{ $assistantName }}</strong> is built and ready to go. It handles {{ $assistantHandles }}.
                            </p>

                            <p style="margin: 0 0 12px;">
                                You can download your Playbook and assistant instructions from the chat any time.
                            </p>

                            <p style="margin: 0 0 24px;">
                                You can view all your builds at any time from your <a href="{{ url('/dashboard') }}" style="color: #7AA08A; text-decoration: underline;">dashboard</a>.
                            </p>

                            {{-- CTA button --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 0 24px;">
                                <tr>
                                    <td style="background-color: #7AA08A; border-radius: 6px;">
                                        <a href="{{ $chatUrl }}" style="display: inline-block; padding: 12px 28px; color: #ffffff; font-size: 15px; font-weight: 500; text-decoration: none; letter-spacing: 0.01em;">View your Playbook</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0; font-size: 13px; color: #3D5A73;">
                                Or paste this link into your browser:<br>
                                <a href="{{ $chatUrl }}" style="color: #7AA08A; text-decoration: underline; word-break: break-all;">{{ $chatUrl }}</a>
                            </p>

                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding: 24px 32px; border-top: 1px solid #C8D8CC; font-size: 12px; color: #3D5A73; line-height: 1.5;">
                            <p style="margin: 0;">
                                Thanks for using the AI Assistant Launchpad.<br>
                                Build My Assistant<span style="color: #7AA08A;">.co</span>
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
