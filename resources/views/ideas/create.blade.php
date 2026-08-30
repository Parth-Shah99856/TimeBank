@section('title', 'Post a New Initiative')

<x-app-layout>
    <div class="max-w-2xl mx-auto py-4">
        {{-- Header Section --}}
        <div class="mb-8 text-center">
            <h1 class="font-headline-lg text-3xl md:text-4xl font-bold text-on-surface mb-2">Post an Initiative</h1>
            <p class="font-body-md text-sm text-on-surface-variant">
                Publish a proposal to the IdeaVault to crowd-fund temporal contributions and recruit architects.
            </p>
        </div>

        {{-- Main Form Card --}}
        <div class="glass-card p-6 md:p-10 rounded-2xl relative overflow-hidden"
             x-data="{
                 loading: false,
                 skillsInput: '{{ old('skills_raw', '') }}',
                 get skillsList() {
                     return this.skillsInput.split(',').map(s => s.trim()).filter(s => s.length > 0);
                 }
             }">
            <form method="POST" action="{{ route('ideas.store') }}" @submit="loading = true">
                @csrf

                {{-- Initiative Title --}}
                <div class="mb-6">
                    <label for="title" class="stitch-label">INITIATIVE TITLE *</label>
                    <input id="title" type="text" name="title" value="{{ old('title') }}" required autofocus
                           class="stitch-input @error('title') border-error/50 @enderror"
                           placeholder="e.g., OpenMesh Decentralized Communication Grid">
                    <x-input-error :messages="$errors->get('title')" class="mt-1 text-xs text-error font-mono-data" />
                </div>

                {{-- Category --}}
                <div class="mb-6" x-data="{ selectedCategory: '{{ old('category_id', '') }}' }">
                    <label for="category_id" class="stitch-label">CATEGORY *</label>
                    @php $categories = \App\Models\Category::where('is_active', true)->orderBy('name')->get(); @endphp
                    <select id="category_id" name="category_id" x-model="selectedCategory" required class="stitch-select @error('category_id') border-error/50 @enderror @error('custom_category') border-error/50 @enderror">
                        <option value="">Select an initiative sector...</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                        <option value="custom" {{ old('category_id') === 'custom' ? 'selected' : '' }}>
                            ✨ Other / Custom Category...
                        </option>
                    </select>
                    <x-input-error :messages="$errors->get('category_id')" class="mt-1 text-xs text-error font-mono-data" />

                    {{-- Custom Category Input (shown when 'custom' is selected) --}}
                    <div x-show="selectedCategory === 'custom'" x-cloak class="mt-3 space-y-1.5 animate-fade-in-up">
                        <label for="custom_category" class="stitch-label text-[11px] text-secondary flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[14px]">edit</span>
                            ENTER CUSTOM CATEGORY NAME *
                        </label>
                        <input id="custom_category" type="text" name="custom_category" value="{{ old('custom_category') }}"
                               maxlength="100"
                               :required="selectedCategory === 'custom'"
                               class="stitch-input @error('custom_category') border-error/50 @enderror"
                               placeholder="e.g., Quantum Computing, Urban Permaculture, Biohacking">
                        <p class="font-mono-data text-[10px] text-on-surface-variant/70">A new initiative category will be registered for your proposal.</p>
                        <x-input-error :messages="$errors->get('custom_category')" class="mt-1 text-xs text-error font-mono-data" />
                    </div>
                </div>

                {{-- Mission Statement --}}
                <div class="mb-6">
                    <label for="mission_statement" class="stitch-label">MISSION STATEMENT & SCOPE *</label>
                    <textarea id="mission_statement" name="mission_statement" rows="5" required
                              class="stitch-textarea @error('mission_statement') border-error/50 @enderror"
                              placeholder="Detail the vision, objective, milestone stages, and societal impact of this initiative...">{{ old('mission_statement') }}</textarea>
                    <x-input-error :messages="$errors->get('mission_statement')" class="mt-1 text-xs text-error font-mono-data" />
                </div>

                {{-- Target Hours --}}
                <div class="mb-6">
                    <label for="target_hours" class="stitch-label">TARGET TIME BUDGET (HOURS) *</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-secondary">
                            <span class="material-symbols-outlined text-[18px]">schedule</span>
                        </div>
                        <input id="target_hours" type="number" step="10" min="1" max="10000" name="target_hours" value="{{ old('target_hours', '100') }}" required
                               class="stitch-input pl-10 pr-20 font-mono text-secondary font-bold text-base @error('target_hours') border-error/50 @enderror"
                               placeholder="100">
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none font-label-caps text-xs text-on-surface-variant">
                            HRS
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('target_hours')" class="mt-1 text-xs text-error font-mono-data" />
                </div>

                {{-- Required Skills --}}
                <div class="mb-8">
                    <label for="skills_input_field" class="stitch-label">REQUIRED EXPERTISE (COMMA SEPARATED)</label>
                    <input id="skills_input_field" type="text" x-model="skillsInput"
                           class="stitch-input"
                           placeholder="e.g., Rust, CAD Modeling, Botany, UI Design">
                    <p class="font-mono-data text-[10px] text-on-surface-variant/60 mt-1">Specify key skill vectors needed for contributors</p>

                    {{-- Hidden Array Inputs --}}
                    <template x-for="(skill, index) in skillsList" :key="index">
                        <input type="hidden" name="required_skills[]" :value="skill">
                    </template>
                </div>

                {{-- Form Actions --}}
                <div class="flex flex-col sm:flex-row gap-4 pt-4 border-t border-white/10">
                    <a href="{{ route('ideas.index') }}" class="btn-stitch-secondary text-xs justify-center py-3.5 sm:w-1/3">
                        CANCEL
                    </a>
                    <button type="submit" class="btn-stitch-primary text-xs justify-center py-3.5 sm:w-2/3 shadow-[0_0_16px_rgba(93,230,255,0.3)]" :disabled="loading">
                        <span x-show="!loading" class="inline-flex items-center gap-2">
                            PUBLISH INITIATIVE <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
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
