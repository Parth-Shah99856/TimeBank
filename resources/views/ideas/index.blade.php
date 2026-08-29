@section('title', 'IdeaVault — Initiatives')

<x-app-layout>
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="font-headline-lg text-3xl md:text-4xl text-on-surface font-bold">Vault</h1>
            <p class="font-body-md text-xs md:text-sm text-on-surface-variant mt-1">
                Discover and collaborate on high-impact community initiatives. Invest your time where it matters.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('ideas.create') }}" class="btn-stitch-primary text-xs py-2.5 px-5 shadow-[0_0_16px_rgba(93,230,255,0.3)]">
                <span class="material-symbols-outlined text-[16px]">add</span> NEW INITIATIVE
            </a>
        </div>
    </div>

    {{-- Ideas Grid --}}
    @php
        $ideas = \App\Models\Idea::with(['user', 'category', 'collaborators'])->latest()->paginate(9);
    @endphp

    @if($ideas->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            @foreach($ideas as $idea)
                <x-idea-card :idea="$idea" />
            @endforeach
        </div>

        <div class="mt-8">
            {{ $ideas->links() }}
        </div>
    @else
        <x-empty-state
            title="No Initiatives in the Vault"
            description="The IdeaVault currently has no published proposals. Be the first architect to launch a decentralized initiative."
            actionUrl="{{ route('ideas.create') }}"
            actionLabel="Post New Initiative" />
    @endif
</x-app-layout>
