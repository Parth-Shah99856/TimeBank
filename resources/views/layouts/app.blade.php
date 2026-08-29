<!DOCTYPE html>
<html class="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="auth-check" content="{{ Auth::check() ? '1' : '0' }}">

        <title>{{ config('app.name', 'TIMEBANK') }} — @yield('title', 'Decentralized Exchange')</title>
        <meta name="description" content="@yield('meta_description', 'TIMEBANK — A community where time is the currency. Give Time. Gain Skills. Build Ideas.')">

        <!-- Fonts & Icons -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&family=Geist:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

        <!-- Vite Assets -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-background text-on-background antialiased min-h-screen flex flex-col pt-16 md:pt-20 pb-20 md:pb-0 overflow-x-hidden" x-data="{ userMenu: false, mobileDrawer: false }">

        {{-- ============ STITCH TOP APP BAR ============ --}}
        <header class="fixed top-0 w-full z-50 bg-surface-container/60 backdrop-blur-lg border-b border-white/10 shadow-[0_0_16px_rgba(190,198,224,0.06)]">
            <div class="max-w-container-max mx-auto w-full px-margin-mobile md:px-margin-desktop h-16 md:h-20 flex items-center justify-between">

                {{-- Left: Mobile Hamburger & Logo --}}
                <div class="flex items-center gap-3">
                    <button @click="mobileDrawer = !mobileDrawer"
                            class="md:hidden text-primary hover:text-secondary p-1.5 rounded-lg hover:bg-white/5 transition-colors focus:outline-none">
                        <span class="material-symbols-outlined text-[24px]">menu</span>
                    </button>
                    <x-application-logo />
                </div>

                {{-- Center: Desktop Navigation Links --}}
                @include('layouts.navigation')

                {{-- Right: User Info & Notification Bell --}}
                <div class="flex items-center gap-3 md:gap-4">
                    {{-- Time Balance Badge (Desktop) --}}
                    @auth
                    <a href="{{ route('transactions.index') }}"
                       class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-secondary/10 border border-secondary/30 text-secondary hover:bg-secondary/20 transition-all duration-200 group">
                        <span class="material-symbols-outlined text-[16px] text-secondary group-hover:scale-110 transition-transform" style="font-variation-settings: 'FILL' 1;">schedule</span>
                        <span class="font-mono-data text-xs font-semibold">{{ number_format(Auth::user()->time_balance, 2) }}</span>
                        <span class="font-label-caps text-[10px] opacity-80">TC</span>
                    </a>
                    @endauth

                    {{-- Notification Bell --}}
                    <a href="{{ route('notifications.index') }}"
                       class="relative p-2 rounded-lg text-primary hover:text-secondary hover:bg-white/5 transition-colors"
                       title="Notifications">
                        <span class="material-symbols-outlined text-[22px]">notifications</span>
                        <span x-show="$store.notifications.unreadCount > 0"
                              x-text="$store.notifications.unreadCount"
                              class="absolute top-1 right-1 flex items-center justify-center min-w-[16px] h-[16px] px-1 text-[9px] font-bold text-background bg-secondary rounded-full shadow-[0_0_8px_rgba(93,230,255,0.8)]"
                              style="display: none;"></span>
                    </a>

                    {{-- User Profile / Dropdown --}}
                    @auth
                    <div class="relative">
                        <button @click="userMenu = !userMenu"
                                @click.outside="userMenu = false"
                                class="flex items-center gap-2 p-1 rounded-full border border-white/10 hover:border-secondary/40 transition-colors focus:outline-none">
                            <x-avatar :user="Auth::user()" size="sm" />
                        </button>

                        {{-- Dropdown Card --}}
                        <div x-show="userMenu"
                             x-transition:enter="transition ease-out duration-150 transform"
                             x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100 transform"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-56 glass-card p-2 rounded-xl shadow-2xl border border-white/15 z-50 divide-y divide-white/10"
                             style="display: none;">
                            <div class="px-3 py-2">
                                <p class="text-sm font-semibold text-on-surface truncate">{{ Auth::user()->name }}</p>
                                <p class="font-mono-data text-xs text-on-surface-variant truncate">{{ Auth::user()->email }}</p>
                                <div class="mt-2 pt-2 border-t border-white/5 flex items-center justify-between text-xs">
                                    <span class="text-on-surface-variant font-label-caps text-[10px]">Balance:</span>
                                    <span class="text-secondary font-mono font-bold">{{ number_format(Auth::user()->time_balance, 2) }} TC</span>
                                </div>
                            </div>
                            <div class="py-1">
                                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-3 py-2 text-xs font-label-caps text-on-surface-variant hover:text-secondary hover:bg-white/5 rounded-lg transition-colors">
                                    <span class="material-symbols-outlined text-[16px]">person</span> Profile
                                </a>
                                <a href="{{ route('services.create') }}" class="flex items-center gap-2.5 px-3 py-2 text-xs font-label-caps text-on-surface-variant hover:text-secondary hover:bg-white/5 rounded-lg transition-colors">
                                    <span class="material-symbols-outlined text-[16px]">add_circle</span> Offer Skill
                                </a>
                                <a href="{{ route('ideas.create') }}" class="flex items-center gap-2.5 px-3 py-2 text-xs font-label-caps text-on-surface-variant hover:text-secondary hover:bg-white/5 rounded-lg transition-colors">
                                    <span class="material-symbols-outlined text-[16px]">lightbulb</span> Post Initiative
                                </a>
                                <a href="{{ route('service-requests.index') }}" class="flex items-center gap-2.5 px-3 py-2 text-xs font-label-caps text-on-surface-variant hover:text-secondary hover:bg-white/5 rounded-lg transition-colors">
                                    <span class="material-symbols-outlined text-[16px]">sync_alt</span> My Requests
                                </a>
                            </div>
                            <div class="py-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-label-caps text-error hover:bg-error/10 rounded-lg transition-colors">
                                        <span class="material-symbols-outlined text-[16px]">logout</span> Disconnect
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="flex items-center gap-2">
                        <a href="{{ route('login') }}" class="btn-stitch-ghost text-xs">Sign In</a>
                        <a href="{{ route('register') }}" class="btn-stitch-primary text-xs py-2 px-4">Get Started</a>
                    </div>
                    @endauth
                </div>
            </div>
        </header>

        {{-- ============ MOBILE DRAWER MENU ============ --}}
        <div x-show="mobileDrawer"
             x-transition:enter="transition-opacity ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileDrawer = false"
             class="fixed inset-0 bg-background/80 backdrop-blur-sm z-50 md:hidden"
             style="display: none;"></div>

        <aside x-show="mobileDrawer"
               x-transition:enter="transition ease-out duration-300 transform"
               x-transition:enter-start="-translate-x-full"
               x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in duration-200 transform"
               x-transition:leave-start="translate-x-0"
               x-transition:leave-end="-translate-x-full"
               class="fixed inset-y-0 left-0 w-72 bg-surface-container-high border-r border-white/10 z-50 md:hidden flex flex-col p-6 shadow-2xl"
               style="display: none;">
            <div class="flex items-center justify-between pb-6 border-b border-white/10">
                <x-application-logo />
                <button @click="mobileDrawer = false" class="text-on-surface-variant hover:text-white p-1">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <nav class="flex-1 py-6 space-y-2 overflow-y-auto">
                @auth
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-label-caps text-on-surface hover:bg-white/5 hover:text-secondary">
                        <span class="material-symbols-outlined text-[20px]">home</span> Dashboard
                    </a>
                    <a href="{{ route('services.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-label-caps text-on-surface hover:bg-white/5 hover:text-secondary">
                        <span class="material-symbols-outlined text-[20px]">explore</span> Browse Services
                    </a>
                    <a href="{{ route('services.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-label-caps text-on-surface hover:bg-white/5 hover:text-secondary">
                        <span class="material-symbols-outlined text-[20px]">add_circle</span> Offer a Skill
                    </a>
                    <a href="{{ route('ideas.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-label-caps text-on-surface hover:bg-white/5 hover:text-secondary">
                        <span class="material-symbols-outlined text-[20px]">account_balance_wallet</span> IdeaVault
                    </a>
                    <a href="{{ route('service-requests.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-label-caps text-on-surface hover:bg-white/5 hover:text-secondary">
                        <span class="material-symbols-outlined text-[20px]">sync_alt</span> My Requests
                    </a>
                    <a href="{{ route('transactions.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-label-caps text-on-surface hover:bg-white/5 hover:text-secondary">
                        <span class="material-symbols-outlined text-[20px]">receipt_long</span> Ledger / Wallet
                    </a>
                    <a href="{{ route('leaderboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-label-caps text-on-surface hover:bg-white/5 hover:text-tertiary">
                        <span class="material-symbols-outlined text-[20px] text-tertiary">military_tech</span> Leaderboard
                    </a>
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-label-caps text-on-surface hover:bg-white/5 hover:text-secondary">
                        <span class="material-symbols-outlined text-[20px]">person</span> My Profile
                    </a>
                    @if(Auth::user()->isAdmin())
                    <a href="{{ route('admin.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-label-caps text-tertiary hover:bg-white/5">
                        <span class="material-symbols-outlined text-[20px]">shield</span> Admin Platform Control
                    </a>
                    @endif
                @else
                    <a href="{{ route('home') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-label-caps text-on-surface hover:bg-white/5 hover:text-secondary">
                        <span class="material-symbols-outlined text-[20px]">home</span> Home
                    </a>
                    <a href="{{ route('services.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-label-caps text-on-surface hover:bg-white/5 hover:text-secondary">
                        <span class="material-symbols-outlined text-[20px]">explore</span> Browse Services
                    </a>
                    <a href="{{ route('ideas.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-label-caps text-on-surface hover:bg-white/5 hover:text-secondary">
                        <span class="material-symbols-outlined text-[20px]">account_balance_wallet</span> IdeaVault
                    </a>
                    <a href="{{ route('leaderboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-label-caps text-on-surface hover:bg-white/5 hover:text-tertiary">
                        <span class="material-symbols-outlined text-[20px] text-tertiary">military_tech</span> Leaderboard
                    </a>
                    <div class="pt-4 border-t border-white/10 space-y-2">
                        <a href="{{ route('login') }}" class="w-full btn-stitch-ghost text-xs justify-center py-2.5">
                            <span class="material-symbols-outlined text-[16px]">login</span> Sign In
                        </a>
                        <a href="{{ route('register') }}" class="w-full btn-stitch-primary text-xs justify-center py-2.5">
                            Get Started <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                        </a>
                    </div>
                @endauth
            </nav>

            @auth
            <div class="pt-4 border-t border-white/10">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full btn-stitch-danger text-xs">
                        <span class="material-symbols-outlined text-[16px]">logout</span> Disconnect
                    </button>
                </form>
            </div>
            @endauth
        </aside>

        {{-- Flash Messages --}}
        @if (session('status'))
            @php
                $statusMap = [
                    'service-request-created' => 'Service request submitted successfully. Awaiting provider acceptance.',
                    'service-request-accepted' => 'Service request accepted. You can now start work when ready.',
                    'service-request-started' => 'Work started! The exchange is now in progress.',
                    'service-request-cancelled' => 'Service request cancelled successfully.',
                    'service-request-completed' => 'Exchange completed! Time credits transferred.',
                    'service-request-disputed' => 'Dispute filed. An admin will review and resolve this exchange.',
                    'review-created' => 'Review submitted successfully. Thank you for your feedback.',
                    'profile-updated' => 'Profile updated successfully.',
                    'password-updated' => 'Password updated successfully.',
                    'verification-link-sent' => 'A new verification link has been sent to your email address.',
                ];
                $displayStatus = $statusMap[session('status')] ?? session('status');
            @endphp
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                 x-transition
                 class="max-w-container-max mx-auto w-full px-margin-mobile md:px-margin-desktop mt-4">
                <div class="glass-card px-4 py-3 border-secondary/40 bg-secondary/10 text-secondary rounded-xl flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">check_circle</span>
                        <span>{{ $displayStatus }}</span>
                    </div>
                    <button @click="show = false" class="text-secondary/60 hover:text-secondary p-1">
                        <span class="material-symbols-outlined text-[16px]">close</span>
                    </button>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
                 x-transition
                 class="max-w-container-max mx-auto w-full px-margin-mobile md:px-margin-desktop mt-4">
                <div class="glass-card px-4 py-3 border-error/40 bg-error/10 text-error rounded-xl flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">error</span>
                        <span>{{ session('error') }}</span>
                    </div>
                    <button @click="show = false" class="text-error/60 hover:text-error">
                        <span class="material-symbols-outlined text-[16px]">close</span>
                    </button>
                </div>
            </div>
        @endif

        {{-- ============ MAIN CANVAS ============ --}}
        <main class="flex-1 max-w-container-max mx-auto w-full px-margin-mobile md:px-margin-desktop py-6 md:py-8">
            {{ $slot }}
        </main>

        {{-- ============ BOTTOM NAV (MOBILE) ============ --}}
        <x-bottom-nav />

        {{-- ============ TOAST CONTAINER ============ --}}
        <div x-data class="fixed top-20 right-4 z-[100] space-y-2 pointer-events-none">
            <template x-for="toast in $store.toast.items" :key="toast.id">
                <div x-show="toast.visible"
                     x-transition:enter="transition ease-out duration-300 transform"
                     x-transition:enter-start="opacity-0 translate-x-8 scale-95"
                     x-transition:enter-end="opacity-100 translate-x-0 scale-100"
                     x-transition:leave="transition ease-in duration-200 transform"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 translate-x-8 scale-95"
                     :class="{
                         'bg-surface-container-high/90 text-secondary border-secondary/40 shadow-[0_0_16px_rgba(93,230,255,0.3)]': toast.type === 'success',
                         'bg-surface-container-high/90 text-error border-error/40 shadow-[0_0_16px_rgba(255,180,171,0.3)]': toast.type === 'error',
                         'bg-surface-container-high/90 text-primary border-white/20': toast.type === 'info',
                         'bg-surface-container-high/90 text-tertiary border-tertiary/40': toast.type === 'warning',
                     }"
                     class="glass-card flex items-center gap-3 px-5 py-3.5 rounded-xl text-sm font-medium border pointer-events-auto cursor-pointer min-w-[280px]"
                     @click="$store.toast.dismiss(toast.id)">
                    <span class="material-symbols-outlined text-[20px]"
                          :class="{
                              'text-secondary': toast.type === 'success',
                              'text-error': toast.type === 'error',
                              'text-tertiary': toast.type === 'warning',
                              'text-primary': toast.type === 'info',
                          }"
                          x-text="toast.type === 'success' ? 'check_circle' : (toast.type === 'error' ? 'error' : 'info')"></span>
                    <span class="font-body-md text-sm text-on-surface" x-text="toast.message"></span>
                </div>
            </template>
        </div>
    </body>
</html>
