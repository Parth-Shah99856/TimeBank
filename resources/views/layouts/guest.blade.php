<!DOCTYPE html>
<html class="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'TIMEBANK') }} — @yield('title', 'Secure Access')</title>

        <!-- Fonts & Icons -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&family=Geist:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

        <!-- Vite Assets -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-background text-on-background antialiased min-h-screen flex flex-col justify-center items-center p-4 relative overflow-x-hidden">
        {{-- Background Glow Orbs --}}
        <div class="fixed inset-0 pointer-events-none overflow-hidden">
            <div class="absolute -top-40 -left-40 w-96 h-96 bg-secondary/10 rounded-full blur-[120px]"></div>
            <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-secondary/5 rounded-full blur-[140px]"></div>
        </div>

        {{-- Top Brand Header --}}
        <div class="relative z-10 mb-8 text-center animate-fade-in">
            <a href="/" class="inline-flex flex-col items-center gap-2 group">
                <div class="w-12 h-12 rounded-xl bg-secondary/15 border border-secondary/40 flex items-center justify-center text-secondary group-hover:shadow-[0_0_20px_rgba(93,230,255,0.5)] transition-all duration-300">
                    <span class="material-symbols-outlined text-[28px]" style="font-variation-settings: 'FILL' 1;">schedule</span>
                </div>
                <span class="font-headline text-2xl font-bold tracking-tighter text-primary">
                    TIMEBANK
                </span>
            </a>
            <p class="font-body-md text-xs text-on-surface-variant mt-1 tracking-wide">
                Secure access to your temporal assets.
            </p>
        </div>

        {{-- Main Glass Auth Container --}}
        <div class="relative z-10 w-full max-w-md animate-fade-in-up">
            <div class="glass-card p-6 sm:p-8 rounded-2xl shadow-2xl border border-white/10 relative overflow-hidden">
                {{ $slot }}
            </div>

            {{-- Footer Note --}}
            <p class="text-center font-mono-data text-xs text-on-surface-variant/60 mt-6">
                &copy; {{ date('Y') }} TIMEBANK PROTOCOL &bull; ALL RIGHTS RESERVED
            </p>
        </div>
    </body>
</html>
