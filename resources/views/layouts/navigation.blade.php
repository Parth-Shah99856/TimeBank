@php
    $isHome = request()->routeIs('dashboard') || request()->routeIs('home');
    $isExplore = request()->routeIs('services.*') || request()->routeIs('service-requests.*');
    $isVault = request()->routeIs('ideas.*') || request()->routeIs('projects.*');
    $isWallet = request()->routeIs('wallet') || request()->routeIs('transactions.*');
    $isLeaderboard = request()->routeIs('leaderboard');
    $isProfile = request()->routeIs('profile.*');
    $isAdmin = request()->routeIs('admin.*') || request()->routeIs('categories.*');
@endphp

{{-- Desktop Nav Links --}}
<nav class="hidden md:flex items-center space-x-6 lg:space-x-8">
    @auth
        <a href="{{ route('dashboard') }}"
           class="font-label-caps text-xs flex items-center gap-1.5 py-1 transition-all duration-200 {{ $isHome ? 'text-secondary drop-shadow-[0_0_8px_rgba(93,230,255,0.5)] border-b-2 border-secondary' : 'text-on-surface-variant hover:text-secondary' }}">
            <span class="material-symbols-outlined text-[18px] {{ $isHome ? 'fill' : '' }}" data-icon="home">home</span>
            Dashboard
        </a>
    @endauth

    <a href="{{ route('services.index') }}"
       class="font-label-caps text-xs flex items-center gap-1.5 py-1 transition-all duration-200 {{ $isExplore ? 'text-secondary drop-shadow-[0_0_8px_rgba(93,230,255,0.5)] border-b-2 border-secondary' : 'text-on-surface-variant hover:text-secondary' }}">
        <span class="material-symbols-outlined text-[18px] {{ $isExplore ? 'fill' : '' }}" data-icon="explore">explore</span>
        Explore
    </a>

    <a href="{{ route('ideas.index') }}"
       class="font-label-caps text-xs flex items-center gap-1.5 py-1 transition-all duration-200 {{ $isVault ? 'text-secondary drop-shadow-[0_0_8px_rgba(93,230,255,0.5)] border-b-2 border-secondary' : 'text-on-surface-variant hover:text-secondary' }}">
        <span class="material-symbols-outlined text-[18px] {{ $isVault ? 'fill' : '' }}" data-icon="account_balance_wallet">account_balance_wallet</span>
        IdeaVault
    </a>

    @auth
        <a href="{{ route('transactions.index') }}"
           class="font-label-caps text-xs flex items-center gap-1.5 py-1 transition-all duration-200 {{ $isWallet ? 'text-secondary drop-shadow-[0_0_8px_rgba(93,230,255,0.5)] border-b-2 border-secondary' : 'text-on-surface-variant hover:text-secondary' }}">
            <span class="material-symbols-outlined text-[18px] {{ $isWallet ? 'fill' : '' }}" data-icon="account_balance">account_balance</span>
            Ledger
        </a>
    @endauth

    <a href="{{ route('leaderboard') }}"
       class="font-label-caps text-xs flex items-center gap-1.5 py-1 transition-all duration-200 {{ $isLeaderboard ? 'text-tertiary drop-shadow-[0_0_8px_rgba(249,189,34,0.5)] border-b-2 border-tertiary' : 'text-on-surface-variant hover:text-tertiary' }}">
        <span class="material-symbols-outlined text-[18px] {{ $isLeaderboard ? 'fill' : '' }}" data-icon="military_tech">military_tech</span>
        Leaderboard
    </a>

    @auth
        <a href="{{ route('profile.edit') }}"
           class="font-label-caps text-xs flex items-center gap-1.5 py-1 transition-all duration-200 {{ $isProfile ? 'text-secondary drop-shadow-[0_0_8px_rgba(93,230,255,0.5)] border-b-2 border-secondary' : 'text-on-surface-variant hover:text-secondary' }}">
            <span class="material-symbols-outlined text-[18px] {{ $isProfile ? 'fill' : '' }}" data-icon="person">person</span>
            Profile
        </a>

        @if(Auth::user()->isAdmin())
        <a href="{{ route('admin.index') }}"
           class="font-label-caps text-xs flex items-center gap-1.5 py-1 transition-all duration-200 {{ $isAdmin ? 'text-tertiary drop-shadow-[0_0_8px_rgba(249,189,34,0.5)] border-b-2 border-tertiary' : 'text-on-surface-variant hover:text-tertiary' }}">
            <span class="material-symbols-outlined text-[18px] {{ $isAdmin ? 'fill' : '' }}" data-icon="shield">shield</span>
            Admin
        </a>
        @endif
    @endauth
</nav>
