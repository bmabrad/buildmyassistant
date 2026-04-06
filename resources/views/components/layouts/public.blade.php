<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Build My Assistant' }}</title>
    <meta name="description" content="{{ $description ?? 'Custom AI assistants for coaches and consultants. Built around your business, your voice, and the way you work.' }}">

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="alternate" type="application/rss+xml" title="Build My Assistant Blog" href="/blog/feed">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap" rel="stylesheet">

    <style>
        :root {
            --deep-slate: #1E2A38;
            --mid-blue: #3D5A73;
            --sage-accent: #7AA08A;
            --soft-sage: #C8D8CC;
            --off-white: #F4F6F4;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            color: var(--mid-blue);
            background: white;
            line-height: 1.7;
            font-size: 16px;
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3, h4 {
            color: var(--deep-slate);
            font-weight: 500;
            line-height: 1.3;
        }

        h1 { font-size: 2.2rem; }
        h2 { font-size: 1.5rem; margin-bottom: 12px; }
        h3 { font-size: 1.1rem; }

        a { color: var(--sage-accent); text-decoration: none; }
        a:hover { text-decoration: underline; }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .btn {
            display: inline-block;
            padding: 14px 28px;
            background: var(--sage-accent);
            color: white;
            border: none;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        .btn:hover { opacity: 0.9; text-decoration: none; }

        .btn-outline {
            background: white;
            color: var(--sage-accent);
            border: 1px solid var(--sage-accent);
        }

        /* Header */
        .site-header {
            padding: 16px 0;
            border-bottom: 1px solid var(--soft-sage);
            background: white;
        }
        .site-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .site-header img { height: 32px; }
        .site-nav { display: flex; gap: 24px; align-items: center; }
        .site-nav a {
            color: var(--mid-blue);
            font-size: 15px;
            text-decoration: none;
        }
        .site-nav a:hover { color: var(--deep-slate); }
        .nav-toggle { display: none; background: none; border: none; cursor: pointer; padding: 4px; }
        .nav-toggle svg { width: 24px; height: 24px; stroke: var(--deep-slate); }

        /* Footer */
        .site-footer {
            padding: 40px 0;
            border-top: 1px solid var(--soft-sage);
            background: var(--off-white);
            margin-top: 60px;
        }
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        .footer-content img { height: 24px; }
        .footer-links { display: flex; gap: 20px; }
        .footer-links a { color: var(--mid-blue); font-size: 14px; }
        .footer-copy { font-size: 13px; color: var(--mid-blue); opacity: 0.7; width: 100%; text-align: center; margin-top: 16px; }

        /* Sections */
        .section { padding: 60px 0; }
        .section-alt { background: var(--off-white); }
        .section-header { text-align: center; margin-bottom: 40px; }
        .section-header p { margin-top: 12px; max-width: 600px; margin-left: auto; margin-right: auto; }

        @media (max-width: 640px) {
            h1 { font-size: 1.7rem; }
            h2 { font-size: 1.25rem; }
            .section { padding: 40px 0; }
            .site-nav { display: none; position: absolute; top: 64px; left: 0; right: 0; background: white; flex-direction: column; padding: 20px; gap: 16px; border-bottom: 1px solid var(--soft-sage); z-index: 50; }
            .site-nav.open { display: flex; }
            .nav-toggle { display: block; }
            .footer-content { flex-direction: column; text-align: center; }
            .footer-links { justify-content: center; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container" style="position: relative;">
            <a href="/"><img src="/images/logos/logo_long_dark_text.svg" alt="Build My Assistant"></a>
            <button class="nav-toggle" onclick="document.querySelector('.site-nav').classList.toggle('open')" aria-label="Toggle navigation">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <nav class="site-nav">
                <a href="/">Home</a>
                <a href="/launchpad">Launchpad</a>
                <a href="/blog">Blog</a>
                <a href="/about">About</a>
                <a href="/contact">Contact</a>
            </nav>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <img src="/images/logos/logo_long_dark_text.svg" alt="Build My Assistant">
                <div class="footer-links">
                    <a href="/privacy">Privacy</a>
                    <a href="/terms">Terms</a>
                    <a href="/contact">Contact</a>
                </div>
                <p class="footer-copy">&copy; {{ date('Y') }} Build My Assistant. Custom AI assistants for coaches and consultants.</p>
            </div>
        </div>
    </footer>
</body>
</html>
