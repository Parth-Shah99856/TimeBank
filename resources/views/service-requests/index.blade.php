@section('title', 'My Requests & Engagements')

<x-app-layout>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="font-headline-lg text-2xl md:text-3xl text-on-surface font-bold">My Requests & Exchanges</h1>
            <p class="font-body-md text-xs md:text-sm text-on-surface-variant mt-1">Track and manage active temporal lifecycle contracts</p>
        </div>
        <a href="{{ route('services.index') }}" class="btn-stitch-primary text-xs self-start sm:self-auto py-2.5 px-4 shadow-[0_0_12px_rgba(93,230,255,0.25)]">
            <span class="material-symbols-outlined text-[16px]">add</span> New Request
        </a>
    </div>

    @php
        $sentRequests = Auth::user()->requestedServiceRequests()->with(['service', 'provider', 'review'])->latest()->get();
        $receivedRequests = Auth::user()->providedServiceRequests()->with(['service', 'requester', 'review'])->latest()->get();
    @endphp

    <div x-data="{
        tab: 'sent',
        reviewModal: false,
        otpModal: false,
        activeRequestId: null,
        activeRequestTitle: '',
        activeCredits: '0.00',
        activeProviderName: '',
        otpSending: false,
        otpSent: false,
        otpCode: '',
        otpError: '',
        openOtpModal(id, title, credits, provider) {
            this.activeRequestId = id;
            this.activeRequestTitle = title;
            this.activeCredits = credits;
            this.activeProviderName = provider;
            this.otpSent = false;
            this.otpCode = '';
            this.otpError = '';
            this.otpModal = true;
            this.sendOtp();
        },
        async sendOtp() {
            this.otpSending = true;
            this.otpError = '';
            try {
                const res = await fetch(`/service-requests/${this.activeRequestId}/send-otp`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await res.json();
                if (res.ok) {
                    this.otpSent = true;
                } else {
                    this.otpError = data.message || 'Failed to dispatch verification code.';
                }
            } catch (e) {
                this.otpError = 'Network communication failure while sending code.';
            } finally {
                this.otpSending = false;
            }
        }
    }" class="space-y-6">
        {{-- Tabs Header --}}
        <div class="flex gap-4 border-b border-white/10 pb-4">
            <button @click="tab = 'sent'"
                    class="font-label-caps text-xs pb-2 px-3 transition-colors flex items-center gap-2"
                    :class="tab === 'sent' ? 'text-secondary border-b-2 border-secondary font-bold drop-shadow-[0_0_8px_rgba(93,230,255,0.4)]' : 'text-on-surface-variant hover:text-white'">
                <span>Sent Proposals</span>
                <span class="px-2 py-0.5 rounded-full bg-surface-container-high font-mono text-[10px]" x-text="'{{ $sentRequests->count() }}'"></span>
            </button>
            <button @click="tab = 'received'"
                    class="font-label-caps text-xs pb-2 px-3 transition-colors flex items-center gap-2"
                    :class="tab === 'received' ? 'text-secondary border-b-2 border-secondary font-bold drop-shadow-[0_0_8px_rgba(93,230,255,0.4)]' : 'text-on-surface-variant hover:text-white'">
                <span>Received Requests</span>
                <span class="px-2 py-0.5 rounded-full bg-surface-container-high font-mono text-[10px]" x-text="'{{ $receivedRequests->count() }}'"></span>
            </button>
        </div>

        {{-- Sent Requests Tab --}}
        <div x-show="tab === 'sent'" class="space-y-4">
            @forelse($sentRequests as $req)
                <div class="glass-card p-6 rounded-xl flex flex-col md:flex-row md:items-center justify-between gap-4 glow-hover">
                    <div class="flex items-start gap-4">
                        <x-avatar :user="$req->provider" size="md" />
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="font-headline-md text-base font-bold text-on-surface">{{ $req->service->title ?? $req->title }}</h3>
                                <x-badge :variant="$req->status">{{ ucfirst(str_replace('_', ' ', $req->status)) }}</x-badge>
                            </div>
                            <p class="font-mono-data text-xs text-on-surface-variant">
                                Provider: <span class="text-primary font-semibold">{{ $req->provider->name }}</span> &bull;
                                Requested {{ $req->created_at->diffForHumans() }}
                            </p>
                            <p class="font-body-md text-xs text-on-surface-variant/80 mt-2 line-clamp-2 max-w-xl">
                                {{ $req->project_scope ?? $req->title }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between md:flex-col md:items-end gap-3 pt-4 md:pt-0 border-t md:border-t-0 border-white/10">
                        <div class="text-right">
                            <span class="font-mono-data text-base font-bold text-secondary">{{ number_format($req->total_credits ?? $req->estimated_hours ?? 0, 2) }} TC</span>
                        </div>

                        {{-- Actions depending on status --}}
                        <div class="flex items-center gap-2">
                            @if($req->status === 'pending')
                                <form method="POST" action="{{ route('service-requests.cancel', $req->id) }}" onsubmit="return confirm('Cancel this pending service proposal?')">
                                    @csrf
                                    <button type="submit" class="btn-stitch-danger text-xs py-1.5 px-3">
                                        Cancel
                                    </button>
                                </form>
                            @elseif($req->status === 'accepted')
                                <form method="POST" action="{{ route('service-requests.start', $req->id) }}">
                                    @csrf
                                    <button type="submit" class="btn-stitch-primary text-xs py-1.5 px-3">
                                        Start Work
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('service-requests.cancel', $req->id) }}" onsubmit="return confirm('Cancel this accepted exchange?')">
                                    @csrf
                                    <button type="submit" class="btn-stitch-danger text-xs py-1.5 px-3">
                                        Cancel
                                    </button>
                                </form>
                            @elseif($req->status === 'in_progress')
                                <button type="button"
                                        @click="openOtpModal({{ $req->id }}, '{{ addslashes($req->service->title ?? $req->title) }}', '{{ number_format($req->total_credits ?? $req->estimated_hours ?? 0, 2) }}', '{{ addslashes($req->provider->name) }}')"
                                        class="btn-stitch-primary text-xs py-1.5 px-3 flex items-center gap-1.5 shadow-[0_0_10px_rgba(93,230,255,0.25)]">
                                    <span class="material-symbols-outlined text-[15px]">verified_user</span>
                                    Confirm Completion
                                </button>
                                <form method="POST" action="{{ route('service-requests.dispute', $req->id) }}" onsubmit="return confirm('Flag this exchange as disputed for admin review?')">
                                    @csrf
                                    <button type="submit" class="btn-stitch-danger text-xs py-1.5 px-3">
                                        Dispute
                                    </button>
                                </form>
                            @elseif($req->status === 'completed')
                                @if(!$req->review)
                                    <button @click="activeRequestId = {{ $req->id }}; reviewModal = true" class="btn-stitch-secondary text-xs py-1.5 px-3">
                                        <span class="material-symbols-outlined text-[16px]">star</span> Leave Review
                                    </button>
                                @else
                                    <span class="font-mono-data text-xs text-tertiary flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1;">star</span>
                                        Reviewed ({{ $req->review->rating }}/5)
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <x-empty-state
                    title="No Sent Requests"
                    description="You haven't requested any services from other architects yet."
                    actionUrl="{{ route('services.index') }}"
                    actionLabel="Explore Available Skills" />
            @endforelse
        </div>

        {{-- Received Requests Tab --}}
        <div x-show="tab === 'received'" class="space-y-4" style="display: none;">
            @forelse($receivedRequests as $req)
                <div class="glass-card p-6 rounded-xl flex flex-col md:flex-row md:items-center justify-between gap-4 glow-hover">
                    <div class="flex items-start gap-4">
                        <x-avatar :user="$req->requester" size="md" />
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="font-headline-md text-base font-bold text-on-surface">{{ $req->service->title ?? $req->title }}</h3>
                                <x-badge :variant="$req->status">{{ ucfirst(str_replace('_', ' ', $req->status)) }}</x-badge>
                            </div>
                            <p class="font-mono-data text-xs text-on-surface-variant">
                                Requester: <span class="text-primary font-semibold">{{ $req->requester->name }}</span> &bull;
                                Received {{ $req->created_at->diffForHumans() }}
                            </p>
                            <p class="font-body-md text-xs text-on-surface-variant/80 mt-2 line-clamp-2 max-w-xl">
                                {{ $req->project_scope ?? $req->title }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between md:flex-col md:items-end gap-3 pt-4 md:pt-0 border-t md:border-t-0 border-white/10">
                        <div class="text-right">
                            <span class="font-mono-data text-base font-bold text-secondary">+{{ number_format($req->total_credits ?? $req->estimated_hours ?? 0, 2) }} TC</span>
                        </div>

                        {{-- Provider Actions --}}
                        <div class="flex items-center gap-2">
                            @if($req->status === 'pending')
                                <form method="POST" action="{{ route('service-requests.accept', $req->id) }}">
                                    @csrf
                                    <button type="submit" class="btn-stitch-primary text-xs py-1.5 px-3">
                                        Accept
                                    </button>
                                </form>
                            @elseif($req->status === 'accepted')
                                <form method="POST" action="{{ route('service-requests.start', $req->id) }}">
                                    @csrf
                                    <button type="submit" class="btn-stitch-primary text-xs py-1.5 px-3">
                                        Start Work
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('service-requests.dispute', $req->id) }}" onsubmit="return confirm('Flag this exchange as disputed?')">
                                    @csrf
                                    <button type="submit" class="btn-stitch-danger text-xs py-1.5 px-3">
                                        Dispute
                                    </button>
                                </form>
                            @elseif($req->status === 'in_progress')
                                <span class="font-mono-data text-xs text-secondary px-2.5 py-1 rounded bg-secondary/10 border border-secondary/20">Work In Progress</span>
                                <form method="POST" action="{{ route('service-requests.dispute', $req->id) }}" onsubmit="return confirm('Flag this exchange as disputed?')">
                                    @csrf
                                    <button type="submit" class="btn-stitch-danger text-xs py-1.5 px-3">
                                        Dispute
                                    </button>
                                </form>
                            @elseif($req->status === 'completed')
                                @if(!$req->review)
                                    <button @click="activeRequestId = {{ $req->id }}; reviewModal = true" class="btn-stitch-secondary text-xs py-1.5 px-3">
                                        <span class="material-symbols-outlined text-[16px]">star</span> Leave Review
                                    </button>
                                @else
                                    <span class="font-mono-data text-xs text-tertiary flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1;">star</span>
                                        Reviewed ({{ $req->review->rating }}/5)
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <x-empty-state
                    title="No Incoming Requests"
                    description="You haven't received any service proposals yet. Make sure your skills are listed publicly."
                    actionUrl="{{ route('services.create') }}"
                    actionLabel="Offer a Skill" />
            @endforelse
        </div>

        {{-- Review Modal --}}
        <div x-show="reviewModal" class="stitch-overlay" style="display: none;" @click.self="reviewModal = false">
            <div class="stitch-modal animate-fade-in-up">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/10">
                    <h3 class="font-headline-md text-base font-bold text-on-surface">Submit Exchange Review</h3>
                    <button @click="reviewModal = false" class="text-on-surface-variant hover:text-white">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <form :action="'/service-requests/' + activeRequestId + '/reviews'" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="rating" class="stitch-label">RATING (1 TO 5 STARS) *</label>
                        <select id="rating" name="rating" required class="stitch-select">
                            <option value="5">★★★★★ 5 - Exceptional Collaboration</option>
                            <option value="4">★★★★☆ 4 - High Quality Delivery</option>
                            <option value="3">★★★☆☆ 3 - Satisfactory</option>
                            <option value="2">★★☆☆☆ 2 - Below Expectations</option>
                            <option value="1">★☆☆☆☆ 1 - Unsatisfactory</option>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label for="comment" class="stitch-label">EXPERIENCE FEEDBACK</label>
                        <textarea id="comment" name="comment" rows="3" class="stitch-textarea" placeholder="Share constructive thoughts on technical execution and communication..."></textarea>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" @click="reviewModal = false" class="btn-stitch-secondary text-xs">CANCEL</button>
                        <button type="submit" class="btn-stitch-primary text-xs shadow-[0_0_12px_rgba(93,230,255,0.3)]">SUBMIT REVIEW</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- OTP Confirmation Modal --}}
        <div x-show="otpModal" class="stitch-overlay" style="display: none;" @click.self="otpModal = false" x-cloak>
            <div class="stitch-modal animate-fade-in-up max-w-md">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/10">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-secondary/15 border border-secondary/30 flex items-center justify-center text-secondary">
                            <span class="material-symbols-outlined text-[18px]">lock_reset</span>
                        </div>
                        <div>
                            <h3 class="font-headline-md text-base font-bold text-on-surface">Confirm Skill Exchange</h3>
                            <p class="font-mono-data text-[11px] text-on-surface-variant">Two-Factor Settlement Verification</p>
                        </div>
                    </div>
                    <button @click="otpModal = false" class="text-on-surface-variant hover:text-white">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                {{-- Exchange Context Card --}}
                <div class="p-3.5 rounded-lg bg-surface-container/60 border border-white/10 mb-5 space-y-1.5 font-mono-data text-xs">
                    <div class="flex justify-between text-on-surface-variant">
                        <span>Initiative / Skill:</span>
                        <span class="text-on-surface font-semibold max-w-[200px] truncate" x-text="activeRequestTitle"></span>
                    </div>
                    <div class="flex justify-between text-on-surface-variant">
                        <span>Provider:</span>
                        <span class="text-primary font-semibold" x-text="activeProviderName"></span>
                    </div>
                    <div class="flex justify-between text-on-surface-variant pt-1 border-t border-white/5">
                        <span>Temporal Transfer:</span>
                        <span class="text-secondary font-bold text-sm" x-text="activeCredits + ' TC'"></span>
                    </div>
                </div>

                {{-- Status Alerts --}}
                <div x-show="otpSending" class="p-3 rounded-lg bg-secondary/10 border border-secondary/30 mb-4 flex items-center gap-2 text-xs font-mono-data text-secondary">
                    <span class="material-symbols-outlined animate-spin text-[16px]">sync</span>
                    Dispatching 6-digit authorization code to your email...
                </div>

                <div x-show="otpSent && !otpSending" class="p-3 rounded-lg bg-tertiary/10 border border-tertiary/30 mb-4 flex items-center gap-2 text-xs font-mono-data text-tertiary">
                    <span class="material-symbols-outlined text-[16px]">mark_email_read</span>
                    Code sent to <span class="font-bold underline">{{ Auth::user()->email }}</span> (valid for 15m)
                </div>

                <div x-show="otpError" class="p-3 rounded-lg bg-error/10 border border-error/30 mb-4 flex items-center gap-2 text-xs font-mono-data text-error" x-text="otpError"></div>

                {{-- OTP Verification Form --}}
                <form :action="'/service-requests/' + activeRequestId + '/complete'" method="POST">
                    @csrf
                    <div class="mb-5">
                        <label for="otp_input" class="stitch-label text-[11px] text-center block mb-2">
                            ENTER 6-DIGIT AUTHORIZATION CODE *
                        </label>
                        <input id="otp_input"
                               type="text"
                               name="otp"
                               x-model="otpCode"
                               required
                               maxlength="6"
                               pattern="[0-9]{6}"
                               autocomplete="one-time-code"
                               inputmode="numeric"
                               placeholder="••••••"
                               class="stitch-input text-center text-2xl tracking-[0.5em] font-mono-data font-bold text-secondary bg-surface-container-highest/80 border-secondary/30 focus:border-secondary focus:ring-secondary py-3">
                        <p class="font-mono-data text-[10px] text-on-surface-variant/70 text-center mt-2">
                            Releasing escrow balance transfers credits immediately to the skill provider.
                        </p>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-white/10">
                        <button type="button"
                                @click="sendOtp()"
                                :disabled="otpSending"
                                class="text-xs font-mono-data text-secondary hover:text-secondary-fixed flex items-center gap-1 disabled:opacity-50">
                            <span class="material-symbols-outlined text-[14px]">refresh</span>
                            Resend Code
                        </button>

                        <div class="flex gap-2">
                            <button type="button" @click="otpModal = false" class="btn-stitch-secondary text-xs py-1.5 px-3">
                                CANCEL
                            </button>
                            <button type="submit"
                                    :disabled="otpCode.length !== 6"
                                    class="btn-stitch-primary text-xs py-1.5 px-4 shadow-[0_0_12px_rgba(93,230,255,0.3)] disabled:opacity-50 disabled:cursor-not-allowed">
                                VERIFY & COMPLETE
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
