<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 25mm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11pt;
            color: #3D5A73;
            line-height: 1.7;
            margin: 0;
            padding: 0;
        }

        /* Header bar */
        .header-bar {
            background-color: #1E2A38;
            margin: -25mm -25mm 0 -25mm;
            padding: 18mm 25mm 14mm 25mm;
        }

        .header-bar img {
            height: 28px;
        }

        /* Page title */
        .page-title {
            font-size: 16pt;
            font-weight: 500;
            color: #1E2A38;
            margin: 20px 0 6px 0;
            padding: 0;
        }

        .buyer-name {
            font-size: 11pt;
            font-weight: 400;
            color: #3D5A73;
            margin: 0 0 2px 0;
        }

        .session-date {
            font-size: 9pt;
            font-weight: 400;
            color: #3D5A73;
            margin: 0 0 24px 0;
        }

        /* Section headings */
        .section-heading {
            font-size: 13pt;
            font-weight: 500;
            color: #1E2A38;
            margin: 24px 0 8px 0;
            padding-bottom: 4px;
            border-bottom: 2px solid #F4F6F4;
        }

        .section-number {
            color: #7AA08A;
            font-weight: 600;
        }

        /* Body text */
        p {
            margin: 6px 0;
            font-size: 11pt;
            line-height: 1.7;
        }

        strong {
            color: #1E2A38;
            font-weight: 500;
        }

        /* Lists */
        ol, ul {
            margin: 6px 0;
            padding-left: 24px;
        }

        li {
            margin: 4px 0;
            line-height: 1.6;
        }

        /* Code block (system prompt) */
        .code-block {
            background-color: #F4F6F4;
            border: 1px solid #C8D8CC;
            border-radius: 6px;
            padding: 14px 16px;
            font-family: 'Courier New', monospace;
            font-size: 9pt;
            line-height: 1.6;
            color: #1E2A38;
            margin: 10px 0;
            word-wrap: break-word;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: -15mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #3D5A73;
        }
    </style>
</head>
<body>
    {{-- Header bar with logo --}}
    <div class="header-bar">
        @if (file_exists($logoPath))
            <img src="{{ $logoPath }}" alt="Build My Assistant">
        @endif
    </div>

    {{-- Title block --}}
    <div class="page-title">Your AI Assistant Instruction Sheet</div>
    <div class="buyer-name">Built for {{ $buyerName }}</div>
    <div class="session-date">{{ $sessionDate }}</div>

    {{-- Sections --}}
    @foreach ($sections as $index => $section)
        <div class="section-heading">
            <span class="section-number">{{ $index + 1 }}.</span> {{ $section['title'] }}
        </div>
        <div class="section-content">
            {!! $section['html'] !!}
        </div>
    @endforeach

    {{-- Footer --}}
    <div class="footer">
        Built by Build My Assistant | buildmyassistant.co
    </div>
</body>
</html>
