@section('title', 'Explore Services')

<x-app-layout>
    {{-- Header Section --}}
    <div class="mb-8 max-w-2xl">
        <h1 class="font-headline-lg text-3xl md:text-4xl text-on-surface font-bold mb-2">
            Explore Services
        </h1>
        <p class="font-body-md text-sm md:text-base text-on-surface-variant">
            Discover and connect with community members offering high-value skills in exchange for time credits.
        </p>
    </div>

    {{-- Search & Category Filter Bar --}}
    <div class="mb-8 space-y-4" x-data="{ activeCategory: '{{ request('category', 'all') }}', search: '{{ request('search', '') }}' }">
        {{-- Search Input --}}
        <form method="GET" action="{{ route('services.index') }}" class="relative max-w-3xl">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-on-surface-variant/60">
                <span class="material-symbols-outlined text-[20px]">search</span>
            </div>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search skills, services, or members..."
                   class="stitch-input pl-11 py-3.5 pr-28 rounded-xl shadow-[0_0_16px_rgba(190,198,224,0.04)]">
            <button type="submit" class="absolute inset-y-1.5 right-1.5 btn-stitch-secondary text-xs px-4 py-1.5 rounded-lg">
                Search
            </button>
        </form>

        {{-- Category Pills (Scrollable) --}}
        @php
            $categories = \App\Models\Category::all();
        @endphp
        <div class="flex items-center gap-2.5 overflow-x-auto pb-2 scrollbar-none">
            <a href="{{ route('services.index') }}"
               class="font-label-caps text-xs px-4 py-2 rounded-full border transition-all duration-200 whitespace-nowrap {{ !request('category') ? 'bg-secondary/15 text-secondary border-secondary/40 shadow-[0_0_10px_rgba(93,230,255,0.25)]' : 'bg-surface-container-high text-on-surface-variant border-white/10 hover:text-white hover:border-white/20' }}">
                All Categories
            </a>
            @foreach($categories as $cat)
                <a href="{{ route('services.index', ['category' => $cat->id]) }}"
                   class="font-label-caps text-xs px-4 py-2 rounded-full border transition-all duration-200 whitespace-nowrap {{ request('category') == $cat->id ? 'bg-secondary/15 text-secondary border-secondary/40 shadow-[0_0_10px_rgba(93,230,255,0.25)]' : 'bg-surface-container-high text-on-surface-variant border-white/10 hover:text-white hover:border-white/20' }}">
                    {{ $cat->name }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Services Grid --}}
    @php
        $servicesQuery = \App\Models\Service::with(['user', 'category'])
            ->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->where('is_active', true))
            ->latest();
        if(request('search')) {
            $servicesQuery->where(function($q) {
                $q->where('title', 'like', '%' . request('search') . '%')
                  ->orWhere('description', 'like', '%' . request('search') . '%');
            });
        }
        if(request('category')) {
            $servicesQuery->where('category_id', request('category'));
        }
        $services = $servicesQuery->paginate(9);
    @endphp

    @if($services->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            @foreach($services as $service)
                <x-service-card :service="$service" />
            @endforeach
        </div>

        <div class="mt-8">
            {{ $services->withQueryString()->links() }}
        </div>
    @else
        <x-empty-state
            title="No Services Found"
            description="Try adjusting your search criteria or browse another category."
            actionUrl="{{ route('services.create') }}"
            actionLabel="Offer a New Skill" />
    @endif
</x-app-layout>
