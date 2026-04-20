<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Launchpad alert</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #1E2A38; font-size: 14px; line-height: 1.5; padding: 20px; }
        .container { max-width: 640px; margin: 0 auto; }
        h1 { font-size: 17px; font-weight: 500; margin: 0 0 12px 0; }
        table { border-collapse: collapse; width: 100%; margin-top: 12px; }
        th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #C8D8CC; vertical-align: top; }
        th { font-weight: 500; color: #3D5A73; width: 32%; }
        .mono { font-family: Menlo, Consolas, monospace; font-size: 12px; color: #1E2A38; white-space: pre-wrap; }
    </style>
</head>
<body>
<div class="container">
    <h1>Launchpad alert: {{ $reason }}</h1>
    <p>A Launchpad run needs a look. Details below.</p>
    <table>
        <tr><th>Lead ID</th><td>#{{ $lead->id }}</td></tr>
        <tr><th>Buyer</th><td>{{ $lead->buyer_name ?? '—' }}</td></tr>
        <tr><th>Email</th><td>{{ $lead->buyer_email }}</td></tr>
        <tr><th>Tool</th><td>{{ $lead->tools_used ?? '—' }}</td></tr>
        <tr><th>Business type</th><td>{{ $lead->business_type ?? '—' }}</td></tr>
        <tr><th>Assistant pick</th><td>{{ $lead->assistant_pick ?? '—' }}</td></tr>
        <tr><th>Lead status</th><td>{{ $lead->status }}</td></tr>
        @foreach ($context as $label => $value)
            <tr>
                <th>{{ \Illuminate\Support\Str::headline((string) $label) }}</th>
                <td class="mono">{{ is_scalar($value) ? (string) $value : json_encode($value, JSON_PRETTY_PRINT) }}</td>
            </tr>
        @endforeach
    </table>
    <p style="margin-top:20px;"><a href="{{ $adminUrl }}">Open in admin</a></p>
</div>
</body>
</html>
