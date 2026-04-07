<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your AI Assistant Launchpad is ready to go</title>
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
                                Thanks for your purchase, {{ $buyerName }}.
                            </p>

                            <p style="margin: 0 0 20px;">
                                Your AI Assistant Launchpad session is ready. A guided chat will walk you through building a custom AI assistant for the process eating your time.
                            </p>

                            <p style="margin: 0 0 24px;">
                                You can start any time — your session won't expire.
                            </p>

                            {{-- CTA button --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 0 24px;">
                                <tr>
                                    <td style="background-color: #7AA08A; border-radius: 6px;">
                                        <a href="{{ $chatUrl }}" style="display: inline-block; padding: 12px 28px; color: #ffffff; font-size: 15px; font-weight: 500; text-decoration: none; letter-spacing: 0.01em;">Start your session</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0; font-size: 13px; color: #3D5A73;">
                                Or paste this link into your browser:<br>
                                <a href="{{ $chatUrl }}" style="color: #7AA08A; text-decoration: underline; word-break: break-all;">{{ $chatUrl }}</a>
                            </p>

                            @if($invoiceUrl)
                            <p style="margin: 28px 0 8px; color: #1E2A38; font-size: 15px; font-weight: 500; line-height: 1.4;">
                                Your invoice
                            </p>
                            <p style="margin: 0 0 12px; font-size: 13px; color: #3D5A73;">
                                Click below to view or download your invoice for this purchase.
                            </p>
                            <p style="margin: 0;">
                                <a href="{{ $invoiceUrl }}" style="color: #7AA08A; font-size: 13px; text-decoration: underline;">View your invoice</a>
                            </p>
                            @endif
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
