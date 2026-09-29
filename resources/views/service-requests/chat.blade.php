@section('title', 'Exchange Transmission Channel')

<x-app-layout>
    <div class="max-w-5xl mx-auto space-y-4"
         x-data="{
             messages: @js($messages->map(fn($m) => [
                 'id' => $m->id,
                 'sender_id' => $m->sender_id,
                 'sender_name' => $m->sender->name ?? 'User',
                 'sender_avatar' => $m->sender->avatar_url ?? null,
                 'content' => $m->content,
                 'created_at' => $m->created_at->format('M j, g:i A'),
                 'is_me' => $m->sender_id === Auth::id(),
                 'is_read' => $m->read_at !== null
             ])),
             newMessage: '',
             isSending: false,
             isPolling: false,
             pollIntervalMs: 2500,
             pollTimer: null,
             lastMessageId: {{ $messages->max('id') ?? 0 }},
             scrollToBottom() {
                 this.$nextTick(() => {
                     const el = document.getElementById('chat-messages-container');
                     if (el) el.scrollTop = el.scrollHeight;
                 });
             },
             init() {
                 if (this.messages.length > 0) {
                     const maxId = Math.max(...this.messages.map(m => m.id));
                     this.lastMessageId = Math.max(this.lastMessageId, maxId);
                 }
                 this.scrollToBottom();
                 this.startPolling(2500);

                 const onVisibilityChange = () => {
                     if (document.hidden) {
                         // Backgrounded: throttle down to 10s to conserve system resources
                         this.startPolling(10000);
                     } else {
                         // Active foreground: immediate check and restore 2.5s cadence
                         this.pollNewMessages();
                         this.startPolling(2500);
                     }
                 };

                 const onUnload = () => this.stopPolling();

                 document.addEventListener('visibilitychange', onVisibilityChange);
                 window.addEventListener('beforeunload', onUnload);
                 window.addEventListener('pagehide', onUnload);
             },
             destroy() {
                 this.stopPolling();
             },
             startPolling(intervalMs = 2500) {
                 this.stopPolling();
                 this.pollIntervalMs = intervalMs;
                 this.pollTimer = setInterval(() => {
                     this.pollNewMessages();
                 }, this.pollIntervalMs);
             },
             stopPolling() {
                 if (this.pollTimer) {
                     clearInterval(this.pollTimer);
                     this.pollTimer = null;
                 }
             },
             async pollNewMessages() {
                 if (this.isPolling) return;
                 this.isPolling = true;

                 try {
                     const url = '{{ route('service-requests.chat', $serviceRequest) }}?after_id=' + this.lastMessageId;
                     const response = await fetch(url, {
                         method: 'GET',
                         headers: {
                             'Accept': 'application/json',
                             'X-Requested-With': 'XMLHttpRequest'
                         }
                     });

                     if (response && response.ok) {
                         const data = await response.json();

                         // Synchronize read receipts on our own messages if read by partner
                         if (data.last_read_id) {
                             this.messages.forEach(m => {
                                 if (m.is_me && m.id <= data.last_read_id) {
                                     m.is_read = true;
                                 }
                             });
                         }

                         // Append newly arrived messages
                         if (Array.isArray(data.messages) && data.messages.length > 0) {
                             const el = document.getElementById('chat-messages-container');
                             const wasNearBottom = el
                                 ? (el.scrollHeight - el.scrollTop - el.clientHeight < 120)
                                 : true;

                             let hasNew = false;
                             data.messages.forEach(msg => {
                                 if (!this.messages.some(m => m.id === msg.id)) {
                                     this.messages.push({
                                         id: msg.id,
                                         sender_id: msg.sender_id,
                                         sender_name: msg.sender_name,
                                         sender_avatar: msg.sender_avatar,
                                         content: msg.content,
                                         created_at: msg.created_at,
                                         is_me: msg.is_me,
                                         is_read: msg.is_read
                                     });
                                     this.lastMessageId = Math.max(this.lastMessageId, msg.id);
                                     hasNew = true;
                                 }
                             });

                             if (hasNew && wasNearBottom) {
                                 this.scrollToBottom();
                             }
                         }
                     }
                 } catch (e) {
                     // Silently tolerate background polling interruptions without disrupting chat UI
                 } finally {
                     this.isPolling = false;
                 }
             },
             async sendMessage() {
                 const text = this.newMessage.trim();
                 if (!text || this.isSending) return;
                 if (text.length > 2000) return;

                 this.isSending = true;
                 try {
                     const response = await fetch('{{ route('service-requests.messages.store', $serviceRequest) }}', {
                         method: 'POST',
                         headers: {
                             'Content-Type': 'application/json',
                             'Accept': 'application/json',
                             'X-CSRF-TOKEN': '{{ csrf_token() }}',
                             'X-Requested-With': 'XMLHttpRequest'
                         },
                         body: JSON.stringify({ content: text })
                     });

                     if (response.ok) {
                         const data = await response.json();
                         const newMsg = {
                             id: data.id,
                             sender_id: data.sender_id,
                             sender_name: data.sender ? data.sender.name : '{{ Auth::user()->name }}',
                             sender_avatar: data.sender ? data.sender.avatar_url : null,
                             content: data.content,
                             created_at: 'Just now',
                             is_me: true,
                             is_read: false
                         };
                         if (!this.messages.some(m => m.id === newMsg.id)) {
                             this.messages.push(newMsg);
                         }
                         this.lastMessageId = Math.max(this.lastMessageId, data.id);
                         this.newMessage = '';
                         this.scrollToBottom();
                     } else {
                         const err = await response.json().catch(() => ({}));
                         alert(err.message || 'Failed to transmit message.');
                     }
                 } catch (e) {
                     alert('Network communication error.');
                 } finally {
                     this.isSending = false;
                     this.$nextTick(() => {
                         if (this.$refs.msgInput) {
                             this.$refs.msgInput.focus();
                         }
                     });
                 }
             }
         }">

        {{-- Channel Header Card --}}
        <div class="glass-card p-4 md:p-5 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-4 border border-white/10 shadow-lg">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('service-requests.index') }}"
                   class="w-9 h-9 rounded-xl bg-surface-container-highest/80 hover:bg-white/10 flex items-center justify-center text-on-surface-variant hover:text-white transition-colors flex-shrink-0"
                   title="Back to Exchanges">
                    <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                </a>

                <div class="flex items-center gap-3">
                    <x-avatar :user="$partner" size="md" />
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="font-headline text-base font-bold text-on-surface">{{ $partner->name }}</h2>
                            <span class="font-mono-data text-[10px] px-2 py-0.5 rounded-full bg-secondary/15 text-secondary border border-secondary/30">
                                {{ Auth::id() === $serviceRequest->requester_id ? 'Provider' : 'Requester' }}
                            </span>
                            <span class="inline-flex items-center gap-1 font-mono-data text-[10px] text-secondary/80">
                                <span class="w-1.5 h-1.5 rounded-full bg-secondary animate-pulse"></span>
                                LIVE
                            </span>
                        </div>
                        <p class="font-mono-data text-xs text-on-surface-variant truncate max-w-xs md:max-w-md">
                            Exchange: <span class="text-on-surface font-semibold">{{ $serviceRequest->title }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-3 md:pt-0 border-t md:border-t-0 border-white/10">
                <div class="text-left md:text-right font-mono-data">
                    <span class="text-xs text-on-surface-variant block">Budget Escrow</span>
                    <span class="text-sm font-bold text-secondary">{{ number_format($serviceRequest->total_credits ?? $serviceRequest->estimated_hours ?? 0, 2) }} TC</span>
                </div>
                <x-badge :variant="$serviceRequest->status">{{ ucfirst(str_replace('_', ' ', $serviceRequest->status)) }}</x-badge>
            </div>
        </div>

        {{-- Chat Box --}}
        <div class="glass-card rounded-2xl border border-white/10 flex flex-col h-[580px] overflow-hidden shadow-2xl relative">
            {{-- Messages Timeline --}}
            <div id="chat-messages-container"
                 class="flex-1 p-4 md:p-6 overflow-y-auto space-y-4 scroll-smooth">
                {{-- Exchange Kickoff Banner --}}
                <div class="text-center py-3">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-surface-container/60 border border-white/10 text-on-surface-variant text-[11px] font-mono-data">
                        <span class="material-symbols-outlined text-[14px] text-secondary">encrypted</span>
                        Secure peer-to-peer exchange channel established &bull; {{ $serviceRequest->created_at->format('M j, Y') }}
                    </div>
                </div>

                {{-- Empty Conversation State --}}
                <template x-if="messages.length === 0">
                    <div class="text-center py-16 space-y-2">
                        <div class="w-12 h-12 rounded-2xl bg-secondary/10 border border-secondary/25 flex items-center justify-center text-secondary mx-auto">
                            <span class="material-symbols-outlined text-[24px]">chat_bubble</span>
                        </div>
                        <h4 class="font-headline text-sm font-semibold text-on-surface">No Transmissions Yet</h4>
                        <p class="font-body-md text-xs text-on-surface-variant max-w-sm mx-auto">
                            Send the first message to synchronize expectations, project scope, and timeline deliverables.
                        </p>
                    </div>
                </template>

                {{-- Messages List --}}
                <template x-for="msg in messages" :key="msg.id">
                    <div :class="msg.is_me ? 'flex justify-end' : 'flex justify-start'" class="animate-fade-in-up">
                        <div class="flex items-end gap-2 max-w-[85%] sm:max-w-[70%]"
                             :class="msg.is_me ? 'flex-row-reverse' : 'flex-row'">

                            {{-- Avatar for incoming --}}
                            <template x-if="!msg.is_me">
                                <div class="w-7 h-7 rounded-lg bg-surface-container-highest border border-white/10 flex items-center justify-center text-xs font-bold text-secondary flex-shrink-0 overflow-hidden"
                                     x-text="msg.sender_name.charAt(0)"></div>
                            </template>

                            {{-- Message Bubble --}}
                            <div class="space-y-1">
                                <div class="p-3.5 rounded-2xl shadow-sm text-xs leading-relaxed break-words"
                                     :class="msg.is_me
                                        ? 'bg-secondary/20 border border-secondary/40 text-on-surface rounded-br-none shadow-[0_0_12px_rgba(93,230,255,0.15)]'
                                        : 'bg-surface-container-high/80 border border-white/10 text-on-surface rounded-bl-none'">
                                    <p class="whitespace-pre-wrap font-body-md" x-text="msg.content"></p>
                                </div>

                                {{-- Timestamp & Read Receipt --}}
                                <div class="flex items-center gap-1.5 px-1 font-mono-data text-[10px] text-on-surface-variant/60"
                                     :class="msg.is_me ? 'justify-end' : 'justify-start'">
                                    <span x-text="msg.created_at"></span>
                                    <template x-if="msg.is_me">
                                        <span class="material-symbols-outlined text-[13px]"
                                              :class="msg.is_read ? 'text-secondary' : 'text-on-surface-variant/40'"
                                              :title="msg.is_read ? 'Read' : 'Delivered'">
                                            done_all
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Message Input Bar --}}
            <div class="p-3 md:p-4 bg-surface-container/70 border-t border-white/10 backdrop-blur-md">
                <form @submit.prevent="sendMessage()" class="flex items-end gap-2.5">
                    @csrf
                    <div class="flex-1 relative">
                        <textarea
                            x-ref="msgInput"
                            x-model="newMessage"
                            @keydown.enter.exact.prevent="sendMessage()"
                            rows="2"
                            maxlength="2000"
                            required
                            placeholder="Type a message... (Press Enter to send, Shift+Enter for newline)"
                            class="stitch-textarea text-xs py-2.5 px-3.5 resize-none bg-surface-container-lowest/80 border-white/15 focus:border-secondary focus:ring-secondary leading-relaxed"></textarea>
                        <div class="absolute bottom-2 right-2 font-mono-data text-[9px] text-on-surface-variant/50"
                             x-text="newMessage.length + '/2000'"></div>
                    </div>

                    <button type="submit"
                            :disabled="!newMessage.trim() || isSending"
                            class="btn-stitch-primary px-4 py-3 text-xs flex items-center gap-1.5 shadow-[0_0_12px_rgba(93,230,255,0.3)] disabled:opacity-50 disabled:cursor-not-allowed flex-shrink-0 h-[48px]">
                        <span class="material-symbols-outlined text-[16px]" x-show="!isSending">send</span>
                        <span class="material-symbols-outlined text-[16px] animate-spin" x-show="isSending" style="display: none;">sync</span>
                        <span class="hidden sm:inline" x-text="isSending ? 'SENDING...' : 'SEND'"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
