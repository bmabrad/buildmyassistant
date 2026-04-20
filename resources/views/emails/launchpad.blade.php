<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $subject }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #3D5A73; font-size: 15px; line-height: 1.6; margin: 0; padding: 24px; background: #ffffff; }
        .container { max-width: 640px; margin: 0 auto; }
        h1 { color: #1E2A38; font-size: 18px; font-weight: 500; margin: 0 0 16px 0; }
        p { margin: 0 0 14px 0; }
        .prompt { background: #F4F6F4; border: 1px solid #C8D8CC; border-radius: 4px; padding: 16px; font-family: Menlo, Monaco, Consolas, monospace; font-size: 13px; line-height: 1.5; white-space: pre-wrap; color: #1E2A38; margin: 16px 0; }
        ol { padding-left: 20px; margin: 0 0 14px 0; }
        ol li { margin-bottom: 6px; }
        .cta { display: inline-block; margin-top: 8px; padding: 10px 20px; background: #7AA08A; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: 500; font-size: 14px; }
        .signoff { margin-top: 20px; color: #3D5A73; }
        .footer { margin-top: 28px; padding-top: 14px; border-top: 1px solid #C8D8CC; color: #3D5A73; font-size: 12px; }
    </style>
</head>
<body>
<div class="container">
    <p>Hey {{ $lead->buyer_name }},</p>

    <p>{{ $output['email_body']['opening_line'] ?? '' }}</p>

    <div class="prompt">{{ $output['starter_prompt'] ?? '' }}</div>

    <ol>
        @foreach (($output['install_steps'] ?? []) as $step)
            <li>{{ $step }}</li>
        @endforeach
    </ol>

    <p>Give it a task. It will ask a couple of quick questions to understand your business, then get out of your way. You should feel the difference in your first week.</p>

    <p>{{ $output['email_body']['upgrade_line'] ?? '' }}</p>

    <p><a href="{{ $builderUrl }}" class="cta">Build my custom assistant</a></p>

    <p class="signoff">Your full Launchpad is attached as a PDF.<br>See you there,<br>Brad</p>

    <p class="footer">Build My Assistant.co &middot; hello@buildmyassistant.co</p>
</div>
</body>
</html>
