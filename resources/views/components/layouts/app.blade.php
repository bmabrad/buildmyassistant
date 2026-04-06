<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Build My Assistant' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --deep-slate: #1E2A38;
            --mid-blue: #3D5A73;
            --sage-accent: #7AA08A;
            --soft-sage: #C8D8CC;
            --off-white: #F4F6F4;
        }

        body {
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-size: 15px;
            line-height: 1.7;
            color: var(--mid-blue);
            background: var(--off-white);
        }

        h1, h2, h3, h4, h5, h6 {
            color: var(--deep-slate);
            font-weight: 500;
        }
    </style>
    @livewireStyles
</head>
<body>
    {{ $slot }}
    @livewireScripts
</body>
</html>
