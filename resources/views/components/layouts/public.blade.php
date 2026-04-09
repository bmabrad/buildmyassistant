<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Build My Assistant' }}</title>
    <meta name="description" content="{{ $description ?? 'Custom AI assistants for your business. Built around how you actually work.' }}">

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="alternate" type="application/rss+xml" title="Build My Assistant Articles" href="/articles/feed">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])
    @livewireStyles
</head>
<body class="font-sans text-[15px] font-normal leading-[1.7] text-mid-blue bg-white">
    {{-- Nav --}}
    <div class="bg-slate">
    <nav class="max-w-[1000px] mx-auto px-6 py-4" x-data="{ mobileOpen: false }">
        <div class="flex items-center justify-between">
            <a href="/" class="text-lg font-medium text-white no-underline">Build My Assistant<span class="text-sage">.co</span></a>

            {{-- Mobile hamburger --}}
            <button class="md:hidden text-white" @click="mobileOpen = !mobileOpen" aria-label="Toggle navigation">
                <svg x-show="!mobileOpen" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                <svg x-show="mobileOpen" x-cloak class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>

            {{-- Desktop nav --}}
            <div class="hidden md:flex gap-6 items-center">
                <a href="/launchpad" class="text-soft-sage text-sm no-underline hover:text-white">Start Here</a>
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button @click="open = !open" class="text-soft-sage text-sm hover:text-white flex items-center gap-1">
                        Explore
                        <svg class="w-3.5 h-3.5 transition-transform" :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                    </button>
                    <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute right-0 mt-2 w-36 bg-slate border border-soft-sage/20 rounded-md shadow-lg py-1 z-50">
                        <a href="/about" class="block px-4 py-2 text-soft-sage text-sm no-underline hover:text-white hover:bg-white/5">About</a>
                        <a href="/articles" class="block px-4 py-2 text-soft-sage text-sm no-underline hover:text-white hover:bg-white/5">Articles</a>
                        <a href="/contact" class="block px-4 py-2 text-soft-sage text-sm no-underline hover:text-white hover:bg-white/5">Contact</a>
                    </div>
                </div>
                @auth
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button @click="open = !open" class="w-8 h-8 rounded-full bg-sage text-white text-xs font-medium flex items-center justify-center hover:opacity-90 transition-opacity" title="{{ auth()->user()->name }}">
                            @php
                                $nameParts = explode(' ', auth()->user()->name);
                                $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
                            @endphp
                            {{ $initials }}
                        </button>
                        <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute right-0 mt-2 w-36 bg-slate border border-soft-sage/20 rounded-md shadow-lg py-1 z-50" style="text-align: left;">
                            <a href="/dashboard" class="block px-4 py-2 text-soft-sage text-sm no-underline hover:text-white hover:bg-white/5">Dashboard</a>
                            <a href="/settings" class="block px-4 py-2 text-soft-sage text-sm no-underline hover:text-white hover:bg-white/5">Settings</a>
                            @if(session()->has('impersonating_from'))
                                <span class="block px-4 py-2 text-soft-sage/40 text-sm cursor-not-allowed" title="Not available while impersonating">Billing</span>
                            @else
                                <form method="POST" action="/dashboard/billing">
                                    @csrf
                                    <button type="submit" class="block w-full px-4 py-2 text-soft-sage text-sm hover:text-white hover:bg-white/5" style="text-align: left;">Billing</button>
                                </form>
                            @endif
                            <form method="POST" action="/logout">
                                @csrf
                                <button type="submit" class="block w-full px-4 py-2 text-soft-sage text-sm hover:text-white hover:bg-white/5" style="text-align: left;">Log out</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="/login" class="w-8 h-8 rounded-full border border-soft-sage/40 text-soft-sage flex items-center justify-center hover:text-white hover:border-white/40 transition-colors no-underline" title="Log in">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </a>
                @endauth
            </div>
        </div>

        {{-- Mobile nav --}}
        <div x-show="mobileOpen" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="md:hidden mt-4 pt-4 border-t border-soft-sage/20">
            <div class="flex flex-col gap-3">
                <a href="/launchpad" class="text-soft-sage text-sm no-underline hover:text-white">Start Here</a>
                <a href="/about" class="text-soft-sage text-sm no-underline hover:text-white">About</a>
                <a href="/articles" class="text-soft-sage text-sm no-underline hover:text-white">Articles</a>
                <a href="/contact" class="text-soft-sage text-sm no-underline hover:text-white">Contact</a>
                @auth
                    <div class="border-t border-soft-sage/20 pt-3 mt-1">
                        <a href="/dashboard" class="block text-soft-sage text-sm no-underline hover:text-white mb-3">Dashboard</a>
                        <a href="/settings" class="block text-soft-sage text-sm no-underline hover:text-white mb-3">Settings</a>
                        @if(session()->has('impersonating_from'))
                            <span class="block text-soft-sage/40 text-sm cursor-not-allowed mb-3" title="Not available while impersonating">Billing</span>
                        @else
                            <form method="POST" action="/dashboard/billing">
                                @csrf
                                <button type="submit" class="text-soft-sage text-sm hover:text-white mb-3">Billing</button>
                            </form>
                        @endif
                        <form method="POST" action="/logout">
                            @csrf
                            <button type="submit" class="text-soft-sage text-sm hover:text-white">Log out</button>
                        </form>
                    </div>
                @else
                    <a href="/login" class="text-soft-sage text-sm no-underline hover:text-white">Log in</a>
                @endauth
            </div>
        </div>
    </nav>
    </div>

    <main>
        {{ $slot }}
    </main>

    <style>
        main {
            min-height: calc(100vh - 120px);
        }
        main section > div {
            max-width: 1000px;
            margin-left: auto;
            margin-right: auto;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }
        .dialog-box {
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
    </style>

    {{-- Footer --}}
    <footer class="bg-slate py-6">
        <div class="max-w-[1000px] mx-auto px-6 flex items-center justify-between flex-wrap gap-4">
            <p class="text-soft-sage text-xs">Build My Assistant.co - &copy; {{ date('Y') }}</p>
            <div class="flex gap-4">
                <a href="/privacy" class="text-soft-sage text-xs no-underline hover:text-white">Privacy policy</a>
                <a href="/terms" class="text-soft-sage text-xs no-underline hover:text-white">Terms of use</a>
            </div>
        </div>
    </footer>
    @livewireScripts
</body>
</html>
