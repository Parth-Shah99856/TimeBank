@section('title', 'My Skills & Offerings')

<x-app-layout>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="font-headline-lg text-2xl md:text-3xl text-on-surface font-bold">My Skills & Offerings</h1>
            <p class="font-body-md text-xs md:text-sm text-on-surface-variant mt-1">Manage your active service capabilities in the network</p>
        </div>
        <a href="{{ route('services.create') }}" class="btn-stitch-primary text-xs self-start sm:self-auto py-2.5 px-4 shadow-[0_0_12px_rgba(93,230,255,0.25)]">
            <span class="material-symbols-outlined text-[16px]">add</span> Offer New Skill
        </a>
    </div>

    @php
        $myServices = Auth::user()->services()->with('category')->latest()->get();
    @endphp

    <div x-data="{ editModal: false, editingService: null, editTitle: '', editDescription: '', editHourlyRate: '', editCategoryId: '', editIsActive: true }">

        @if($myServices->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($myServices as $service)
                    <div class="glass-card p-6 rounded-xl flex flex-col justify-between group glow-hover relative">
                        {{-- Active/Inactive badge --}}
                        <div class="absolute top-3 right-3">
                            @if($service->is_active)
                                <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 font-label-caps text-[9px] text-emerald-400">ACTIVE</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full bg-surface-container-high border border-white/10 font-label-caps text-[9px] text-on-surface-variant">INACTIVE</span>
                            @endif
                        </div>

                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <x-badge :variant="'cyan'">{{ $service->category->name ?? 'Skill' }}</x-badge>
                            </div>
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <h3 class="font-headline-md text-base font-semibold text-primary line-clamp-2 flex-1">
                                    {{ $service->title }}
                                </h3>
                                <span class="font-mono-data text-xs text-secondary font-bold whitespace-nowrap">{{ number_format($service->hourly_rate, 2) }} TC/hr</span>
                            </div>
                            <p class="font-body-md text-xs text-on-surface-variant line-clamp-3 mb-4">
                                {{ $service->description }}
                            </p>
                            @if(!empty($service->tags) && is_array($service->tags))
                                <div class="flex flex-wrap gap-1.5 mb-3">
                                    @foreach(array_slice($service->tags, 0, 3) as $tag)
                                        <span class="px-2 py-0.5 rounded bg-surface-container-high border border-white/5 font-mono-data text-[10px] text-on-surface-variant">{{ $tag }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="pt-4 border-t border-white/10 flex items-center justify-between gap-2">
                            <a href="{{ route('services.show', $service->id) }}" class="font-label-caps text-xs text-secondary hover:underline flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">visibility</span> View
                            </a>

                            <div class="flex items-center gap-2">
                                {{-- Edit Button --}}
                                <button
                                    @click="
                                        editingService = {{ $service->id }};
                                        editTitle = '{{ addslashes($service->title) }}';
                                        editDescription = '{{ addslashes($service->description) }}';
                                        editHourlyRate = '{{ $service->hourly_rate }}';
                                        editCategoryId = '{{ $service->category_id }}';
                                        editIsActive = {{ $service->is_active ? 'true' : 'false' }};
                                        editModal = true;
                                    "
                                    class="text-primary/70 hover:text-primary text-xs font-label-caps p-1 hover:bg-white/5 rounded-lg transition-colors"
                                    title="Edit Service"
                                >
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                </button>

                                {{-- Delete Button --}}
                                <button
                                    @click="
                                        if (confirm('Are you sure you want to retire this skill offering?')) {
                                            fetch('{{ route('services.destroy', $service->id) }}', {
                                                method: 'DELETE',
                                                headers: {
                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                    'Accept': 'application/json'
                                                }
                                            }).then(() => window.location.reload());
                                        }
                                    "
                                    class="text-error/70 hover:text-error text-xs font-label-caps p-1 hover:bg-error/5 rounded-lg transition-colors"
                                    title="Delete"
                                >
                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <x-empty-state
                title="No Skills Offered Yet"
                description="You haven't listed any skills in the temporal network. Start offering your expertise to earn time credits."
                actionUrl="{{ route('services.create') }}"
                actionLabel="Offer a Skill Now" />
        @endif

        {{-- Edit Service Modal --}}
        <div x-show="editModal" class="stitch-overlay" style="display: none;" @click.self="editModal = false">
            <div class="stitch-modal animate-fade-in-up max-w-lg w-full">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/10">
                    <h3 class="font-headline-md text-base font-bold text-on-surface">Edit Skill Offering</h3>
                    <button @click="editModal = false" class="text-on-surface-variant hover:text-white">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <form :action="'/services/' + editingService" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="mb-4">
                        <label class="stitch-label">SERVICE TITLE *</label>
                        <input type="text" name="title" x-model="editTitle" required class="stitch-input" placeholder="Service title...">
                    </div>

                    <div class="mb-4">
                        <label class="stitch-label">CATEGORY *</label>
                        @php $categories = \App\Models\Category::where('is_active', true)->get(); @endphp
                        <select name="category_id" x-model="editCategoryId" required class="stitch-select">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="stitch-label">DESCRIPTION *</label>
                        <textarea name="description" x-model="editDescription" rows="3" required class="stitch-textarea" placeholder="Describe your service..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="stitch-label">HOURLY RATE (TC/hr) *</label>
                        <input type="number" name="hourly_rate" x-model="editHourlyRate" step="0.25" min="0.25" required class="stitch-input font-mono text-secondary font-bold" placeholder="1.00">
                    </div>

                    <div class="mb-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" :checked="editIsActive" @change="editIsActive = $event.target.checked" class="w-4 h-4 rounded border-white/20 bg-surface-container">
                            <span class="font-label-caps text-xs text-on-surface-variant">Active — listed publicly in marketplace</span>
                        </label>
                    </div>

                    <div class="flex gap-3">
                        <button type="button" @click="editModal = false" class="btn-stitch-secondary w-1/3 text-xs">CANCEL</button>
                        <button type="submit" class="btn-stitch-primary w-2/3 text-xs shadow-[0_0_12px_rgba(93,230,255,0.3)]">SAVE CHANGES</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
