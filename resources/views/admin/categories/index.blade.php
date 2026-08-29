@section('title', 'Category Domain Management')

<x-app-layout>
    @php
        $categories = \App\Models\Category::withCount(['services', 'ideas'])->get();
    @endphp

    <div class="max-w-5xl mx-auto space-y-6" x-data="{ createModal: false, editModal: false, editCategory: { id: '', name: '', description: '' } }">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <a href="{{ route('admin.index') }}" class="inline-flex items-center gap-1 font-label-caps text-xs text-on-surface-variant hover:text-secondary mb-2">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span> Back to Platform Control
                </a>
                <h1 class="font-headline-lg text-2xl md:text-3xl text-on-surface font-bold">Category Domains</h1>
                <p class="font-body-md text-xs md:text-sm text-on-surface-variant">Configure taxonomy nodes for services and IdeaVault initiatives</p>
            </div>

            <button @click="createModal = true" class="btn-stitch-primary text-xs py-2.5 px-4 shadow-[0_0_12px_rgba(93,230,255,0.3)]">
                <span class="material-symbols-outlined text-[16px]">add</span> New Category
            </button>
        </div>

        {{-- Categories Table Card --}}
        <div class="glass-card rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left font-body-md text-sm">
                    <thead class="bg-surface-container-high/60 border-b border-white/10 font-label-caps text-[11px] text-on-surface-variant">
                        <tr>
                            <th class="px-6 py-4">ID</th>
                            <th class="px-6 py-4">Domain Name</th>
                            <th class="px-6 py-4">Description</th>
                            <th class="px-6 py-4">Active Services</th>
                            <th class="px-6 py-4">Initiatives</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 font-mono-data text-xs">
                        @forelse($categories as $cat)
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4 text-on-surface-variant">{{ $cat->id }}</td>
                                <td class="px-6 py-4 font-headline text-sm font-semibold text-on-surface">{{ $cat->name }}</td>
                                <td class="px-6 py-4 text-on-surface-variant max-w-xs truncate font-body-md text-xs">{{ $cat->description ?? '—' }}</td>
                                <td class="px-6 py-4 text-secondary font-bold">{{ $cat->services_count }}</td>
                                <td class="px-6 py-4 text-tertiary font-bold">{{ $cat->ideas_count }}</td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button @click="editCategory = { id: '{{ $cat->id }}', name: '{{ addslashes($cat->name) }}', description: '{{ addslashes($cat->description ?? '') }}' }; editModal = true;"
                                            class="text-on-surface-variant hover:text-secondary p-1">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                    <form method="POST" action="{{ route('categories.destroy', $cat->id) }}" class="inline-block" onsubmit="return confirm('Delete category {{ $cat->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-on-surface-variant hover:text-error p-1">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-on-surface-variant">No categories initialized yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Create Category Modal --}}
        <div x-show="createModal" class="stitch-overlay" style="display: none;" @click.self="createModal = false">
            <div class="stitch-modal animate-fade-in-up">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/10">
                    <h3 class="font-headline-md text-base font-bold text-on-surface">New Category Domain</h3>
                    <button @click="createModal = false" class="text-on-surface-variant hover:text-white">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('categories.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label for="name" class="stitch-label">NAME *</label>
                        <input id="name" type="text" name="name" required class="stitch-input" placeholder="e.g., Quantum Architecture">
                    </div>
                    <div class="mb-6">
                        <label for="description" class="stitch-label">DESCRIPTION</label>
                        <textarea id="description" name="description" rows="3" class="stitch-textarea" placeholder="Sector summary..."></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="createModal = false" class="btn-stitch-secondary text-xs">CANCEL</button>
                        <button type="submit" class="btn-stitch-primary text-xs">CREATE DOMAIN</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Edit Category Modal --}}
        <div x-show="editModal" class="stitch-overlay" style="display: none;" @click.self="editModal = false">
            <div class="stitch-modal animate-fade-in-up">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/10">
                    <h3 class="font-headline-md text-base font-bold text-on-surface">Edit Category Domain</h3>
                    <button @click="editModal = false" class="text-on-surface-variant hover:text-white">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>
                <form :action="'/categories/' + editCategory.id" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="mb-4">
                        <label for="edit_name" class="stitch-label">NAME *</label>
                        <input id="edit_name" type="text" name="name" x-model="editCategory.name" required class="stitch-input">
                    </div>
                    <div class="mb-6">
                        <label for="edit_description" class="stitch-label">DESCRIPTION</label>
                        <textarea id="edit_description" name="description" rows="3" x-model="editCategory.description" class="stitch-textarea"></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="editModal = false" class="btn-stitch-secondary text-xs">CANCEL</button>
                        <button type="submit" class="btn-stitch-primary text-xs">UPDATE DOMAIN</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
