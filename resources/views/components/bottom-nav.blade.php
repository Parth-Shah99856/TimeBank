@php
    $isHome = request()->routeIs('dashboard') || request()->routeIs('home');
    $isExplore = request()->routeIs('services.*') || request()->routeIs('service-requests.*');
    $isVault = request()->routeIs('ideas.*') || request()->routeIs('projects.*');
    $isWallet = request()->routeIs('wallet') || request()->routeIs('transactions.*');
    $isLeaderboard = request()->routeIs('leaderboard');
    $isProfile = request()->routeIs('profile.*');
    $isLogin = request()->routeIs('login');
@endphp

<nav class="md:hidden bg-surface-container/85 backdrop-blur-xl border-t border-white/10 fixed bottom-0 w-full z-50 rounded-t-xl shadow-[0_-4px_24px_rgba(0,203,230,0.1)] flex justify-around items-center h-20 pb-safe px-2">
    @auth
        {{-- Home / Dashboard --}}
        <a href="{{ route('dashboard') }}"
           class="flex flex-col items-center justify-center py-1 px-2 rounded-lg transition-all duration-200 w-16 {{ $isHome ? 'text-secondary drop-shadow-[0_0_8px_rgba(93,230,255,0.5)]' : 'text-on-surface-variant/70 hover:text-secondary' }}">
            <span class="material-symbols-outlined mb-1 {{ $isHome ? 'fill' : '' }}" data-icon="home">home</span>
            <span class="font-label-caps text-[10px]">Home</span>
        </a>

        {{-- Explore (Services) --}}
        <a href="{{ route('services.index') }}"
           class="flex flex-col items-center justify-center py-1 px-2 rounded-lg transition-all duration-200 w-16 {{ $isExplore ? 'text-secondary drop-shadow-[0_0_8px_rgba(93,230,255,0.5)]' : 'text-on-surface-variant/70 hover:text-secondary' }}">
            <span class="material-symbols-outlined mb-1 {{ $isExplore ? 'fill' : '' }}" data-icon="explore">explore</span>
            <span class="font-label-caps text-[10px]">Explore</span>
        </a>

        {{-- Vault (Ideas) --}}
        <a href="{{ route('ideas.index') }}"
           class="flex flex-col items-center justify-center py-1 px-2 rounded-lg transition-all duration-200 w-16 {{ $isVault ? 'text-secondary drop-shadow-[0_0_8px_rgba(93,230,255,0.5)]' : 'text-on-surface-variant/70 hover:text-secondary' }}">
            <span class="material-symbols-outlined mb-1 {{ $isVault ? 'fill' : '' }}" data-icon="account_balance_wallet">account_balance_wallet</span>
            <span class="font-label-caps text-[10px]">Vault</span>
        </a>

        {{-- Wallet --}}
        <a href="{{ route('transactions.index') }}"
           class="flex flex-col items-center justify-center py-1 px-2 rounded-lg transition-all duration-200 w-16 {{ $isWallet ? 'text-secondary drop-shadow-[0_0_8px_rgba(93,230,255,0.5)]' : 'text-on-surface-variant/70 hover:text-secondary' }}">
            <span class="material-symbols-outlined mb-1 {{ $isWallet ? 'fill' : '' }}" data-icon="account_balance">account_balance</span>
            <span class="font-label-caps text-[10px]">Wallet</span>
        </a>

        {{-- Profile --}}
        <a href="{{ route('profile.edit') }}"
           class="flex flex-col items-center justify-center py-1 px-2 rounded-lg transition-all duration-200 w-16 {{ $isProfile ? 'text-secondary drop-shadow-[0_0_8px_rgba(93,230,255,0.5)]' : 'text-on-surface-variant/70 hover:text-secondary' }}">
            <span class="material-symbols-outlined mb-1 {{ $isProfile ? 'fill' : '' }}" data-icon="person">person</span>
            <span class="font-label-caps text-[10px]">Profile</span>
        </a>
    @else
        {{-- Home (Landing) --}}
        <a href="{{ route('home') }}"
           class="flex flex-col items-center justify-center py-1 px-2 rounded-lg transition-all duration-200 w-16 {{ $isHome ? 'text-secondary drop-shadow-[0_0_8px_rgba(93,230,255,0.5)]' : 'text-on-surface-variant/70 hover:text-secondary' }}">
            <span class="material-symbols-outlined mb-1 {{ $isHome ? 'fill' : '' }}" data-icon="home">home</span>
            <span class="font-label-caps text-[10px]">Home</span>
        </a>

        {{-- Explore (Services) --}}
        <a href="{{ route('services.index') }}"
           class="flex flex-col items-center justify-center py-1 px-2 rounded-lg transition-all duration-200 w-16 {{ $isExplore ? 'text-secondary drop-shadow-[0_0_8px_rgba(93,230,255,0.5)]' : 'text-on-surface-variant/70 hover:text-secondary' }}">
            <span class="material-symbols-outlined mb-1 {{ $isExplore ? 'fill' : '' }}" data-icon="explore">explore</span>
            <span class="font-label-caps text-[10px]">Explore</span>
        </a>

        {{-- Vault (Ideas) --}}
        <a href="{{ route('ideas.index') }}"
           class="flex flex-col items-center justify-center py-1 px-2 rounded-lg transition-all duration-200 w-16 {{ $isVault ? 'text-secondary drop-shadow-[0_0_8px_rgba(93,230,255,0.5)]' : 'text-on-surface-variant/70 hover:text-secondary' }}">
            <span class="material-symbols-outlined mb-1 {{ $isVault ? 'fill' : '' }}" data-icon="account_balance_wallet">account_balance_wallet</span>
            <span class="font-label-caps text-[10px]">Vault</span>
        </a>

        {{-- Leaderboard --}}
        <a href="{{ route('leaderboard') }}"
           class="flex flex-col items-center justify-center py-1 px-2 rounded-lg transition-all duration-200 w-16 {{ $isLeaderboard ? 'text-tertiary drop-shadow-[0_0_8px_rgba(249,189,34,0.5)]' : 'text-on-surface-variant/70 hover:text-tertiary' }}">
            <span class="material-symbols-outlined mb-1 {{ $isLeaderboard ? 'fill' : '' }}" data-icon="military_tech">military_tech</span>
            <span class="font-label-caps text-[10px]">Ranks</span>
        </a>

        {{-- Sign In --}}
        <a href="{{ route('login') }}"
           class="flex flex-col items-center justify-center py-1 px-2 rounded-lg transition-all duration-200 w-16 {{ $isLogin ? 'text-secondary drop-shadow-[0_0_8px_rgba(93,230,255,0.5)]' : 'text-on-surface-variant/70 hover:text-secondary' }}">
            <span class="material-symbols-outlined mb-1 {{ $isLogin ? 'fill' : '' }}" data-icon="login">login</span>
            <span class="font-label-caps text-[10px]">Sign In</span>
        </a>
    @endauth
</nav>
