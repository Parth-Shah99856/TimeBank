@section('title', 'Offer a Skill')

<x-app-layout>
    <div class="max-w-2xl mx-auto py-4">
        {{-- Header Section --}}
        <div class="mb-8 text-center">
            <h1 class="font-headline-lg text-3xl md:text-4xl font-bold text-on-surface mb-2">Offer a Skill</h1>
            <p class="font-body-md text-sm text-on-surface-variant">
                Configure your service offering to begin earning time credits in the network.
            </p>
        </div>

        {{-- Main Glass Form Card --}}
        <div class="glass-card p-6 md:p-10 rounded-2xl relative overflow-hidden"
             x-data="{
                 loading: false,
                 tagInput: '{{ old('tags_raw', '') }}',
                 get tagsList() {
                     return this.tagInput.split(',').map(t => t.trim()).filter(t => t.length > 0);
                 }
             }">
            <form method="POST" action="{{ route('services.store') }}" @submit="loading = true">
                @csrf

                {{-- Service Title --}}
                <div class="mb-6">
                    <label for="title" class="stitch-label">SERVICE TITLE *</label>
                    <input id="title" type="text" name="title" value="{{ old('title') }}" required autofocus
                           class="stitch-input @error('title') border-error/50 @enderror"
                           placeholder="e.g., Advanced UI/UX Architecture & Systems">
                    <x-input-error :messages="$errors->get('title')" class="mt-1 text-xs text-error font-mono-data" />
                </div>

                {{-- Category Selection --}}
                <div class="mb-6">
                    <label for="category_id" class="stitch-label">CATEGORY *</label>
                    @php
                        $categories = \App\Models\Category::where('is_active', true)->get();
                    @endphp
                    <select id="category_id" name="category_id" required class="stitch-select @error('category_id') border-error/50 @enderror">
                        <option value="">Select an expertise domain...</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('category_id')" class="mt-1 text-xs text-error font-mono-data" />
                </div>

                {{-- Detailed Description --}}
                <div class="mb-6">
                    <label for="description" class="stitch-label">DETAILED DESCRIPTION *</label>
                    <textarea id="description" name="description" rows="5" required
                              class="stitch-textarea @error('description') border-error/50 @enderror"
                              placeholder="Describe the specific value you provide, deliverables, and what a typical collaboration session involves...">{{ old('description') }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-1 text-xs text-error font-mono-data" />
                </div>

                {{-- Hourly Credit Rate --}}
                <div class="mb-6">
                    <label for="hourly_rate" class="stitch-label">HOURLY CREDIT RATE *</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-secondary">
                            <span class="material-symbols-outlined text-[18px]">account_balance_wallet</span>
                        </div>
                        <input id="hourly_rate" type="number" step="0.25" min="0.25" name="hourly_rate" value="{{ old('hourly_rate', '1.00') }}" required
                               class="stitch-input pl-10 pr-20 font-mono text-secondary font-bold text-base @error('hourly_rate') border-error/50 @enderror"
                               placeholder="1.00">
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none font-label-caps text-xs text-on-surface-variant">
                            TC / HR
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('hourly_rate')" class="mt-1 text-xs text-error font-mono-data" />
                </div>

                {{-- Tags --}}
                <div class="mb-8">
                    <label for="tag_input_field" class="stitch-label">SKILL TAGS (COMMA SEPARATED)</label>
                    <input id="tag_input_field" type="text" x-model="tagInput"
                           class="stitch-input"
                           placeholder="React, TypeScript, Architecture, Cloud Infrastructure">
                    <p class="font-mono-data text-[10px] text-on-surface-variant/60 mt-1">Separate skills with commas (e.g. Design, Figma, Prototyping)</p>

                    {{-- Hidden Array Inputs --}}
                    <template x-for="(tag, index) in tagsList" :key="index">
                        <input type="hidden" name="tags[]" :value="tag">
                    </template>
                </div>

                {{-- Form Actions --}}
                <div class="flex flex-col sm:flex-row gap-4 pt-4 border-t border-white/10">
                    <a href="{{ route('services.index') }}" class="btn-stitch-secondary text-xs justify-center py-3.5 sm:w-1/3">
                        CANCEL
                    </a>
                    <button type="submit" class="btn-stitch-primary text-xs justify-center py-3.5 sm:w-2/3 shadow-[0_0_16px_rgba(93,230,255,0.3)]" :disabled="loading">
                        <span x-show="!loading" class="inline-flex items-center gap-2">
                            PUBLISH SKILL OFFERING <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                        </span>
                        <span x-show="loading" class="inline-flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px] animate-spin">sync</span> PUBLISHING...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
