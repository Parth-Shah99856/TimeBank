@section('title', 'Initiate Service Request')

<x-app-layout>
    @php
        $serviceId = request('service_id');
        $service = $serviceId ? \App\Models\Service::with(['user', 'category'])->find($serviceId) : null;
        $userBalance = (float)Auth::user()->time_balance;
    @endphp

    <div class="max-w-3xl mx-auto py-4"
         x-data="{
             hourlyRate: {{ $service ? (float)$service->hourly_rate : 1.0 }},
             estimated_hours: {{ old('estimated_hours', 2) }},
             userBalance: {{ $userBalance }},
             get totalCost() { return this.estimated_hours * this.hourlyRate; },
             get projectedBalance() { return this.userBalance - this.totalCost; },
             get isAffordable() { return this.projectedBalance >= 0; }
         }">
        {{-- Back Link --}}
        <div class="mb-6">
            <a href="{{ $service ? route('services.show', $service->id) : route('services.index') }}"
               class="inline-flex items-center gap-1.5 font-label-caps text-xs text-on-surface-variant hover:text-secondary transition-colors">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                Back to Directory
            </a>
        </div>

        {{-- Header Section --}}
        <div class="mb-8">
            <h1 class="font-headline-lg text-3xl md:text-4xl text-on-surface font-bold mb-2">Service Request</h1>
            <p class="font-body-md text-sm text-on-surface-variant">
                Draft your proposal and estimate time requirements to initiate a temporal escrow contract.
            </p>
        </div>

        {{-- Service Target Card --}}
        @if($service)
            <div class="glass-card p-5 rounded-2xl mb-8 flex items-center justify-between border-secondary/20">
                <div class="flex items-center gap-4">
                    <x-avatar :user="$service->user" size="lg" />
                    <div>
                        <span class="font-label-caps text-[10px] text-secondary">REQUESTING SERVICE FROM</span>
                        <h3 class="font-headline-md text-base md:text-lg font-bold text-on-surface">{{ $service->user->name }}</h3>
                        <p class="font-mono-data text-xs text-on-surface-variant">Skill: {{ $service->title }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="font-label-caps text-[10px] text-on-surface-variant block">Rate</span>
                    <span class="font-display-lg text-xl font-bold text-secondary">{{ number_format($service->hourly_rate, 2) }}</span>
                    <span class="font-mono-data text-[10px] text-on-surface-variant">TC/hr</span>
                </div>
            </div>
        @endif

        {{-- Main Glass Form Card --}}
        <div class="glass-card p-6 md:p-8 rounded-2xl relative overflow-hidden">
            <form method="POST" action="{{ route('service-requests.store') }}" x-data="{ loading: false }" @submit="loading = true">
                @csrf
                <input type="hidden" name="service_id" value="{{ $service ? $service->id : '' }}">

                {{-- If no service preselected, allow choosing --}}
                @if(!$service)
                    <div class="mb-6">
                        <label for="service_id_select" class="stitch-label">SELECT TARGET SERVICE *</label>
                        @php $allServices = \App\Models\Service::with('user')->where('is_active', true)->get(); @endphp
                        <select id="service_id_select" name="service_id" required class="stitch-select"
                                @change="hourlyRate = parseFloat($event.target.selectedOptions[0].dataset.rate || 1)">
                            <option value="">Choose a service...</option>
                            @foreach($allServices as $s)
                                <option value="{{ $s->id }}" data-rate="{{ $s->hourly_rate }}">{{ $s->title }} (by {{ $s->user->name }} &bull; {{ $s->hourly_rate }} TC/hr)</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('service_id')" class="mt-1 text-xs text-error font-mono-data" />
                    </div>
                @endif

                {{-- Proposal Title --}}
                <div class="mb-6">
                    <label for="title" class="stitch-label">PROPOSAL TITLE *</label>
                    <input id="title" type="text" name="title" value="{{ old('title', $service ? 'Request: ' . $service->title : '') }}" required
                           class="stitch-input @error('title') border-error/50 @enderror"
                           placeholder="e.g., Code Review & Optimization Session">
                    <x-input-error :messages="$errors->get('title')" class="mt-1 text-xs text-error font-mono-data" />
                </div>

                {{-- Project Scope / Description --}}
                <div class="mb-6">
                    <label for="project_scope" class="stitch-label">PROJECT SCOPE *</label>
                    <textarea id="project_scope" name="project_scope" rows="5" required
                              class="stitch-textarea @error('project_scope') border-error/50 @enderror"
                              placeholder="Detail the specific tasks, deliverables, expectations, and communication channels for this exchange...">{{ old('project_scope') }}</textarea>
                    <x-input-error :messages="$errors->get('project_scope')" class="mt-1 text-xs text-error font-mono-data" />
                </div>

                {{-- Grid: Estimated Hours & Desired Deadline --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="estimated_hours" class="stitch-label">ESTIMATED HOURS (TIME CREDITS) *</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-secondary">
                                <span class="material-symbols-outlined text-[18px]">schedule</span>
                            </div>
                            <input id="estimated_hours" type="number" step="0.5" min="0.5" max="999" name="estimated_hours"
                                   x-model.number="estimated_hours" required
                                   class="stitch-input pl-10 pr-20 font-mono text-secondary font-bold text-base @error('estimated_hours') border-error/50 @enderror"
                                   placeholder="2.0">
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none font-label-caps text-xs text-on-surface-variant">
                                HRS
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('estimated_hours')" class="mt-1 text-xs text-error font-mono-data" />
                    </div>

                    <div>
                        <label for="desired_deadline" class="stitch-label">DESIRED DEADLINE (OPTIONAL)</label>
                        <input id="desired_deadline" type="date" name="desired_deadline" value="{{ old('desired_deadline') }}" min="{{ date('Y-m-d') }}"
                               class="stitch-input font-mono @error('desired_deadline') border-error/50 @enderror">
                        <x-input-error :messages="$errors->get('desired_deadline')" class="mt-1 text-xs text-error font-mono-data" />
                    </div>
                </div>

                {{-- Transaction Summary Card --}}
                <div class="glass-panel p-5 rounded-xl border border-white/10 mb-8 space-y-3 bg-surface-container-high/40">
                    <div class="flex items-center gap-2 font-label-caps text-xs text-on-surface-variant pb-2 border-b border-white/5">
                        <span class="material-symbols-outlined text-[16px] text-secondary">receipt</span>
                        TRANSACTION SUMMARY
                    </div>

                    <div class="flex items-center justify-between text-xs font-mono-data">
                        <span class="text-on-surface-variant">Available Balance</span>
                        <span class="text-on-surface">{{ number_format($userBalance, 2) }} TC</span>
                    </div>

                    <div class="flex items-center justify-between text-xs font-mono-data">
                        <span class="text-on-surface-variant">Estimated Escrow Cost</span>
                        <span class="text-error font-semibold">-<span x-text="totalCost.toFixed(2)"></span> TC</span>
                    </div>

                    <div class="flex items-center justify-between text-sm font-mono-data pt-2 border-t border-white/10">
                        <span class="text-primary font-bold">Projected Balance</span>
                        <span :class="isAffordable ? 'text-secondary font-bold' : 'text-error font-bold'"
                              x-text="projectedBalance.toFixed(2) + ' TC'"></span>
                    </div>

                    {{-- Insufficient balance warning --}}
                    <div x-show="!isAffordable" class="p-2.5 rounded-lg bg-error/10 border border-error/30 text-xs font-mono-data text-error flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px]">warning</span>
                        Insufficient time credit balance for this request.
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="{{ $service ? route('services.show', $service->id) : route('services.index') }}" class="btn-stitch-secondary text-xs justify-center py-3.5 sm:w-1/3">
                        CANCEL
                    </a>
                    <button type="submit" class="btn-stitch-primary text-xs justify-center py-3.5 sm:w-2/3 shadow-[0_0_16px_rgba(93,230,255,0.3)]"
                            :disabled="loading || !isAffordable">
                        <span x-show="!loading" class="inline-flex items-center gap-2">
                            SUBMIT REQUEST <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                        </span>
                        <span x-show="loading" class="inline-flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px] animate-spin">sync</span> SUBMITTING...
                        </span>
                    </button>
                </div>

                <p class="font-mono-data text-[10px] text-on-surface-variant/60 text-center mt-4">
                    By submitting, funds will be locked in escrow until the contract terms are fulfilled or mutually cancelled.
                </p>
            </form>
        </div>
    </div>
</x-app-layout>
