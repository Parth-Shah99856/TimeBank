<!DOCTYPE html>
<html class="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="auth-check" content="{{ Auth::check() ? '1' : '0' }}">
    <title>TIMEBANK — Give Time. Gain Skills. Build Ideas.</title>
    <meta name="description" content="Join a decentralized network where your hours are the currency. Trade your expertise for the skills you need to bring your vision to life.">

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&family=Geist:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-on-background antialiased min-h-screen flex flex-col pt-16 md:pt-20 pb-20 md:pb-0 overflow-x-hidden" x-data="{ mobileMenu: false }">

    {{-- Top App Bar --}}
    <header class="fixed top-0 w-full z-50 bg-surface-container/60 backdrop-blur-lg border-b border-white/10 shadow-[0_0_16px_rgba(190,198,224,0.06)]">
        <div class="max-w-container-max mx-auto w-full px-margin-mobile md:px-margin-desktop h-16 md:h-20 flex items-center justify-between">
            <x-application-logo />

            <div class="hidden md:flex items-center space-x-8">
                <a href="#architecture" class="font-label-caps text-xs text-on-surface-variant hover:text-secondary transition-colors">Architecture</a>
                <a href="{{ route('services.index') }}" class="font-label-caps text-xs text-on-surface-variant hover:text-secondary transition-colors">Explore Skills</a>
                <a href="{{ route('ideas.index') }}" class="font-label-caps text-xs text-on-surface-variant hover:text-secondary transition-colors">IdeaVault</a>
            </div>

            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-stitch-primary text-xs py-2.5 px-5">
                        <span class="material-symbols-outlined text-[16px]">dashboard</span> Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-stitch-ghost text-xs">Sign In</a>
                    <a href="{{ route('register') }}" class="btn-stitch-primary text-xs py-2.5 px-5">
                        Get Started <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                    </a>
                @endauth
            </div>
        </div>
    </header>

    {{-- ============ HERO SECTION ============ --}}
    <section class="relative pt-12 pb-20 md:pt-20 md:pb-28 overflow-hidden">
        {{-- Background Glows --}}
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-secondary/10 rounded-full blur-[140px] pointer-events-none"></div>

        <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop relative z-10">
            <div class="max-w-3xl">
                {{-- Future of Exchange Pill --}}
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-surface-container-high border border-secondary/30 font-label-caps text-xs text-secondary mb-8 animate-fade-in shadow-[0_0_12px_rgba(93,230,255,0.15)]">
                    <span class="w-2 h-2 rounded-full bg-secondary shadow-[0_0_8px_rgba(93,230,255,1)]"></span>
                    The Future of Exchange
                </div>

                {{-- Hero Headline --}}
                <h1 class="font-headline text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-extrabold tracking-tight text-on-surface leading-[1.1] mb-6 animate-fade-in-up">
                    Give Time.<br>
                    Gain Skills.<br>
                    <span class="text-secondary drop-shadow-[0_0_24px_rgba(93,230,255,0.5)]">Build Ideas.</span>
                </h1>

                {{-- Hero Subtext --}}
                <p class="font-body-lg text-base md:text-lg text-on-surface-variant max-w-xl mb-10 leading-relaxed animate-fade-in-up" style="animation-delay: 0.1s;">
                    Join a decentralized network where your hours are the currency. Trade your expertise for the skills you need to bring your vision to life.
                </p>

                {{-- CTA Buttons --}}
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 mb-14 animate-fade-in-up" style="animation-delay: 0.2s;">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-stitch-primary text-sm py-4 px-8 shadow-[0_0_20px_rgba(93,230,255,0.4)]">
                            Launch Terminal <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="btn-stitch-primary text-sm py-4 px-8 shadow-[0_0_20px_rgba(93,230,255,0.4)]">
                            Get Started <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                        </a>
                    @endauth
                    <a href="{{ route('services.index') }}" class="btn-stitch-secondary text-sm py-4 px-8">
                        Explore Network
                    </a>
                </div>

                {{-- Social Proof / Trust Banner --}}
                <div class="flex items-center gap-4 pt-6 border-t border-white/10 max-w-md animate-fade-in-up" style="animation-delay: 0.3s;">
                    <div class="flex items-center -space-x-2">
                        <div class="w-9 h-9 rounded-full bg-surface-container-high border-2 border-background flex items-center justify-center font-bold text-secondary font-mono text-xs">E</div>
                        <div class="w-9 h-9 rounded-full bg-primary-container border-2 border-background flex items-center justify-center font-bold text-tertiary font-mono text-xs">M</div>
                        <div class="w-9 h-9 rounded-full bg-surface-container border-2 border-background flex items-center justify-center font-bold text-primary font-mono text-xs">S</div>
                        <div class="w-9 h-9 rounded-full bg-surface-variant border-2 border-background flex items-center justify-center font-mono text-[10px] text-on-surface-variant font-bold">+2k</div>
                    </div>
                    <div>
                        <h4 class="font-headline text-sm font-semibold text-on-surface">Trusted by creators</h4>
                        <p class="font-body-md text-xs text-on-surface-variant">Exchanging over 50,000 hours</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Interactive Visual: Temporal Orbital Clock Widget --}}
        <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop mt-16 md:mt-24">
            <div class="glass-card rounded-2xl p-8 md:p-14 relative overflow-hidden flex items-center justify-center min-h-[380px]">
                {{-- Concentric Orbital Rings --}}
                <div class="relative w-72 h-72 md:w-96 md:h-96 flex items-center justify-center">
                    {{-- Outer Ring --}}
                    <div class="absolute inset-0 rounded-full border border-white/10 border-dashed animate-[spin_60s_linear_infinite]"></div>
                    {{-- Middle Ring --}}
                    <div class="absolute inset-8 rounded-full border border-secondary/20 shadow-[0_0_24px_rgba(93,230,255,0.1)] animate-[spin_40s_linear_infinite_reverse]"></div>
                    {{-- Inner Core --}}
                    <div class="w-24 h-24 rounded-full bg-secondary/15 border border-secondary/50 shadow-[0_0_32px_rgba(93,230,255,0.4)] flex items-center justify-center">
                        <span class="material-symbols-outlined text-secondary text-[40px] animate-pulse" style="font-variation-settings: 'FILL' 1;">schedule</span>
                    </div>

                    {{-- Orbiting Nodes --}}
                    <div class="absolute top-6 left-6 glass-card px-3 py-1.5 rounded-lg border border-secondary/40 flex items-center gap-1.5 font-mono-data text-xs text-secondary shadow-[0_0_12px_rgba(93,230,255,0.3)]">
                        <span class="material-symbols-outlined text-[14px]">code</span>
                        <span>+2.0 Hrs</span>
                    </div>

                    <div class="absolute bottom-8 right-8 glass-card px-3 py-1.5 rounded-lg border border-tertiary/40 flex items-center gap-1.5 font-mono-data text-xs text-tertiary shadow-[0_0_12px_rgba(249,189,34,0.3)]">
                        <span class="material-symbols-outlined text-[14px]">design_services</span>
                        <span>-1.5 Hrs</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ ARCHITECTURE OF EXCHANGE ============ --}}
    <section id="architecture" class="py-16 md:py-24 bg-surface-container-lowest/50 border-t border-white/10 relative">
        <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop">
            <div class="max-w-2xl mb-14">
                <h2 class="font-headline-lg text-3xl md:text-4xl text-on-surface font-bold mb-3">
                    The Architecture of Exchange
                </h2>
                <p class="font-body-md text-on-surface-variant">
                    A calibrated ecosystem designed for high-value transactional networking. Your time is tracked, verified, and secured.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
                {{-- Node 1: Secure Time Vault --}}
                <div class="glass-card p-8 rounded-2xl glow-hover relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-12 h-12 rounded-xl bg-surface-container-high border border-white/5 flex items-center justify-center text-secondary">
                            <span class="material-symbols-outlined text-[24px]">account_balance_wallet</span>
                        </div>
                        <span class="font-mono-data text-xs text-on-surface-variant/60">NODE_01</span>
                    </div>
                    <h3 class="font-headline-md text-xl font-bold text-on-surface mb-2 group-hover:text-secondary transition-colors">
                        Secure Time Vault
                    </h3>
                    <p class="font-body-md text-sm text-on-surface-variant leading-relaxed">
                        Every hour contributed is cryptographically logged in your personal vault, ensuring immutable proof of value and instant transferability.
                    </p>
                </div>

                {{-- Direct Exchange --}}
                <div class="glass-card p-8 rounded-2xl glow-hover relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-12 h-12 rounded-xl bg-surface-container-high border border-white/5 flex items-center justify-center text-secondary">
                            <span class="material-symbols-outlined text-[24px]">sync_alt</span>
                        </div>
                        <span class="font-mono-data text-xs text-on-surface-variant/60">EXCHANGE_P2P</span>
                    </div>
                    <h3 class="font-headline-md text-xl font-bold text-on-surface mb-2 group-hover:text-secondary transition-colors">
                        Direct Exchange
                    </h3>
                    <p class="font-body-md text-sm text-on-surface-variant leading-relaxed">
                        Frictionless peer-to-peer skill swapping without intermediary fees or artificial valuation. 1 Hour = 1 Hour standard.
                    </p>
                </div>

                {{-- Skill Radar --}}
                <div class="glass-card p-8 rounded-2xl glow-hover relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-12 h-12 rounded-xl bg-surface-container-high border border-white/5 flex items-center justify-center text-secondary">
                            <span class="material-symbols-outlined text-[24px]">radar</span>
                        </div>
                        <span class="font-mono-data text-xs text-on-surface-variant/60">VECTORS</span>
                    </div>
                    <h3 class="font-headline-md text-xl font-bold text-on-surface mb-2 group-hover:text-secondary transition-colors">
                        Skill Radar & Reputation
                    </h3>
                    <p class="font-body-md text-sm text-on-surface-variant leading-relaxed">
                        Precision matching based on verified competency vectors, peer reviews, and chronological reliability scores.
                    </p>
                </div>

                {{-- Node 2: Syndicate Collaboration --}}
                <div class="glass-card p-8 rounded-2xl glow-hover relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-12 h-12 rounded-xl bg-surface-container-high border border-white/5 flex items-center justify-center text-secondary">
                            <span class="material-symbols-outlined text-[24px]">hub</span>
                        </div>
                        <span class="font-mono-data text-xs text-on-surface-variant/60">NODE_02</span>
                    </div>
                    <h3 class="font-headline-md text-xl font-bold text-on-surface mb-2 group-hover:text-secondary transition-colors">
                        Syndicate Collaboration
                    </h3>
                    <p class="font-body-md text-sm text-on-surface-variant leading-relaxed">
                        Pool hours with other architects in the IdeaVault to fund large-scale community initiatives, software projects, and social innovations.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ BOTTOM FOOTER ============ --}}
    <footer class="border-t border-white/10 bg-surface-container-lowest py-10">
        <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop flex flex-col sm:flex-row items-center justify-between gap-4">
            <x-application-logo />
            <p class="font-mono-data text-xs text-on-surface-variant/60 text-center">
                &copy; {{ date('Y') }} TIMEBANK PROTOCOL &bull; DISTRIBUTED TEMPORAL NETWORK
            </p>
            <div class="flex items-center gap-4 font-label-caps text-xs text-on-surface-variant">
                <a href="{{ route('services.index') }}" class="hover:text-secondary">Explore</a>
                <a href="{{ route('ideas.index') }}" class="hover:text-secondary">Vault</a>
                <a href="{{ route('login') }}" class="hover:text-secondary">Sign In</a>
            </div>
        </div>
    </footer>

    {{-- Mobile Bottom Nav --}}
    <x-bottom-nav />
</body>
</html>
