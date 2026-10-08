<div x-data="contabaiChat()" class="mx-auto max-w-3xl px-4 py-8">

    <!-- LIST (inbox) -->
    <div x-show="view === 'list'">
        <?php echo \Contabai\View::render('components.breadcrumb', ['crumbs' => [
            ['label' => __('Hub', 'contabai'), 'url' => home_url('/' . CONTABAI_ACCOUNT_PAGE_SLUG)],
            ['label' => __('Chats', 'contabai'), 'url' => null],
        ]]); ?>
        <h2 class="mb-6 text-2xl font-bold text-neutral-900"><?php echo esc_html__('Chats', 'contabai'); ?></h2>

        <div x-show="loading" class="py-16 text-center text-sm text-neutral-500"><?php echo esc_html__('Loading…', 'contabai'); ?></div>

        <template x-if="!loading && !loadFailed && chats.length === 0">
            <p class="rounded-xl border border-dashed border-neutral-300 py-16 text-center text-neutral-500"><?php echo esc_html__('No chats yet.', 'contabai'); ?></p>
        </template>

        <div x-show="!loading && chats.length" class="space-y-3">
            <template x-for="c in chats" x-bind:key="c.id">
                <button type="button" x-on:click="openThread(c.id)"
                        class="flex w-full items-center gap-3 rounded-lg border border-neutral-200 bg-white p-4 text-left transition hover:bg-neutral-50">
                    <span class="relative flex-none">
                        <img x-show="avatarUrl(c.counterpart)" x-bind:src="avatarUrl(c.counterpart)" x-bind:alt="c.counterpart.name" x-cloak
                             class="h-12 w-12 rounded-full bg-neutral-100 object-cover">
                        <span x-show="!avatarUrl(c.counterpart)" class="flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-400"><?php echo \Contabai\Heroicon::outline('user-circle', 'w-8 h-8'); ?></span>
                        <span x-show="c.counterpart.is_active" x-cloak class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white bg-green-500"></span>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center justify-between gap-2">
                            <span class="min-w-0 flex-1 truncate font-semibold text-neutral-900" x-text="c.counterpart.name"></span>
                            <span class="flex-none text-xs text-neutral-400" x-text="relTime(c.last_message ? c.last_message.created_at : c.updated_at)"></span>
                        </span>
                        <span class="mt-0.5 flex items-center justify-between gap-2">
                            <span class="min-w-0 flex-1 truncate text-sm text-neutral-500" x-text="preview(c.last_message)"></span>
                            <span x-show="c.unread_count" x-cloak class="contabai-accent-bg inline-flex flex-none items-center justify-center rounded-full px-2 py-0.5 text-xs font-semibold text-white" x-text="c.unread_count"></span>
                        </span>
                    </span>
                </button>
            </template>
        </div>

        <?php $pg_neutral = true; include __DIR__ . '/../components/pagination.php'; ?>
    </div>

    <!-- THREAD — full-viewport panel on mobile; a bounded, bordered card on desktop -->
    <div x-show="view === 'thread'" x-cloak class="fixed inset-0 z-40 flex justify-center bg-neutral-50 md:static! md:z-auto md:bg-transparent!">
        <div class="flex h-full w-full max-w-3xl flex-col bg-neutral-50 md:mx-auto md:h-[80vh]! md:overflow-hidden md:rounded-lg md:border md:border-neutral-200">
            <!-- top bar (back + host) -->
            <div class="flex flex-none items-center gap-3 border-b border-neutral-200 bg-white px-4 py-3">
                <button type="button" x-on:click="back()" title="<?php esc_attr_e('Chats', 'contabai'); ?>" aria-label="<?php esc_attr_e('Chats', 'contabai'); ?>"
                        class="flex-none rounded-md p-1 text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-900"><?php echo \Contabai\Heroicon::outline('arrow-left', 'w-5 h-5'); ?></button>
                <span class="relative flex-none">
                    <img x-show="avatarUrl(counterpart)" x-bind:src="avatarUrl(counterpart)" x-bind:alt="counterpart ? counterpart.name : ''" x-cloak
                         class="h-10 w-10 rounded-full bg-neutral-100 object-cover">
                    <span x-show="!avatarUrl(counterpart)" class="flex h-10 w-10 items-center justify-center rounded-full bg-neutral-100 text-neutral-400"><?php echo \Contabai\Heroicon::outline('user-circle', 'w-7 h-7'); ?></span>
                </span>
                <div class="min-w-0 flex-1">
                    <div class="truncate font-semibold text-neutral-900" x-text="counterpart ? counterpart.name : ''"></div>
                    <div class="truncate text-xs text-neutral-400" x-text="presence()"></div>
                </div>
            </div>

            <!-- loading -->
            <div x-show="loadingThread" class="flex flex-1 items-center justify-center text-sm text-neutral-500"><?php echo esc_html__('Loading…', 'contabai'); ?></div>

            <!-- messages (fills all remaining vertical space, scrolls) -->
            <div x-show="!loadingThread" x-ref="scroll" class="min-h-0 flex-1 space-y-3 overflow-y-auto px-4 py-5">
                <template x-if="messages.length === 0">
                    <p class="py-8 text-center text-sm text-neutral-400"><?php echo esc_html__('No messages yet. Say hello.', 'contabai'); ?></p>
                </template>
                <template x-for="m in messages" x-bind:key="m.id">
                    <?php include __DIR__ . '/../components/chat-message.php'; ?>
                </template>
            </div>

            <!-- compose pinned to the bottom (Enter sends, Shift+Enter = newline; no send button) -->
            <div x-show="!loadingThread" class="flex-none border-t border-neutral-200 bg-white p-3">
                <textarea x-model="body" rows="1" maxlength="2000"
                          x-on:keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); send(); }"
                          placeholder="<?php esc_attr_e('Press Enter to send · Shift+Enter for a new line', 'contabai'); ?>"
                          class="w-full resize-none rounded-lg border border-neutral-300 px-3 py-2 text-sm text-neutral-800 placeholder:text-neutral-400 focus:border-neutral-400 focus:outline-none"></textarea>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('alpine:init', function () {
    Alpine.data('contabaiChat', function () {
        return {
            endpoints: <?php echo wp_json_encode([
                'chats' => rest_url('contabai/v1/sanctum/guest/chats'),
                'chatBase' => rest_url('contabai/v1/sanctum/guest/chat/'),
                'replyBase' => rest_url('contabai/v1/sanctum/guest/chat/reply/'),
                'readBase' => rest_url('contabai/v1/sanctum/guest/chat/read/'),
            ]); ?>,
            labels: <?php echo wp_json_encode([
                'active' => __('Active now', 'contabai'),
                'lastSeen' => __('Last seen', 'contabai'),
                'sendError' => __('Could not send your message.', 'contabai'),
                'loadError' => __('Could not load the chat.', 'contabai'),
                'chatsError' => __('Could not load your chats.', 'contabai'),
                'hostUnavailable' => __('This host is not available for chat right now.', 'contabai'),
            ]); ?>,
            activeMs: 60000,
            view: 'list',
            loading: false,
            loadFailed: false,
            chats: [],
            currentPage: 1,
            lastPage: 1,
            total: 0,
            perPage: 4,
            loadingThread: false,
            chatId: null,
            counterpart: null,
            counterpartLastSeenAt: null,
            messages: [],
            lastId: 0,
            body: '',
            sending: false,
            init: function () {
                let self = this;
                self.loadChats(1);
                // The site-wide worker re-dispatches poll payloads as a 'chat-poll' window event; apply the ones for the open thread.
                window.addEventListener('chat-poll', function (e) {
                    if (e.detail && e.detail.chat_id === self.chatId) self.applyPoll(e.detail);
                });
            },
            loadChats: function (page) {
                let self = this;
                self.loading = true;
                self.loadFailed = false;
                let pageNumber = page || self.currentPage || 1;
                return fetch(self.endpoints.chats + '?page=' + pageNumber + '&per_page=' + self.perPage, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                    .then(function (r) { if (! r.ok) { let e = new Error('http'); e.status = r.status; throw e; } return r.json(); })
                    .then(function (res) {
                        let data = (res && res.data) || {};
                        self.chats = data.chats || [];
                        self.currentPage = data.current_page || 1;
                        self.lastPage = data.last_page || 1;
                        self.total = data.total || 0;
                        self.loading = false;
                    })
                    .catch(function (e) { self.loading = false; self.loadFailed = true; contabaiToast(contabaiStatusText(e.status, self.labels.chatsError), 'danger'); });
            },
            goTo: function (page) {
                if (page < 1 || page > this.lastPage) return;
                this.loadChats(page);
            },
            pages: function () {
                let last = this.lastPage, cur = this.currentPage, delta = 1, range = [];
                for (let pageNumber = 1; pageNumber <= last; pageNumber++) {
                    if (pageNumber === 1 || pageNumber === last || (pageNumber >= cur - delta && pageNumber <= cur + delta)) range.push(pageNumber);
                }
                let out = [], prev = 0;
                range.forEach(function (pageNumber) {
                    if (pageNumber - prev > 1) out.push({ ellipsis: true, key: 'e' + pageNumber });
                    out.push({ n: pageNumber, ellipsis: false, key: 'p' + pageNumber });
                    prev = pageNumber;
                });
                return out;
            },
            openThread: function (id) {
                let self = this;
                self.view = 'thread';
                self.chatId = id;
                self.counterpart = null;
                self.messages = [];
                self.lastId = 0;
                self.loadingThread = true;
                let opts = { credentials: 'same-origin', headers: { 'Accept': 'application/json' } };
                fetch(self.endpoints.chatBase + id, opts)
                    .then(function (r) { if (! r.ok) { let e = new Error('http'); e.status = r.status; throw e; } return r.json(); })
                    .then(function (res) {
                        let data = (res && res.data) || {};
                        self.counterpart = data.counterpart || null;
                        self.counterpartLastSeenAt = data.counterpart ? data.counterpart.last_seen_at : null;
                        // Load the WHOLE history: page 1 is the newest block; fetch the older pages
                        // (2..last_page) in parallel and merge, then sort oldest-first by id. Chats are
                        // rarely long, so the extra fetches only happen on the odd big thread.
                        let all = (data.messages || []).slice();
                        let last = (data.pagination && data.pagination.last_page) || 1;
                        let older = [];
                        for (let pageNumber = 2; pageNumber <= last; pageNumber++) {
                            older.push(fetch(self.endpoints.chatBase + id + '?page=' + pageNumber, opts).then(function (r) { if (! r.ok) { let e = new Error('http'); e.status = r.status; throw e; } return r.json(); }));
                        }
                        return Promise.all(older).then(function (pages) {
                            if (self.chatId !== id) return; // user opened another chat meanwhile
                            pages.forEach(function (res2) { let pageData = (res2 && res2.data) || {}; all = all.concat(pageData.messages || []); });
                            all.sort(function (a, b) { return a.id - b.id; });
                            self.messages = all;
                            self.lastId = all.length ? all[all.length - 1].id : 0;
                            self.loadingThread = false;
                            self.markRead();
                            self.scrollDown();
                            // Tell the background worker (if any) to poll this thread for new host messages.
                            if (window.chatWorker) { window.chatWorker.postMessage({ command: 'watch-thread', chatId: id, after: self.lastId }); }
                        });
                    })
                    .catch(function (e) {
                        if (self.chatId !== id) return;
                        self.loadingThread = false;
                        contabaiToast(contabaiStatusText(e.status, self.labels.loadError), 'danger');
                        self.back();
                    });
            },
            applyPoll: function (data) {
                let self = this;
                if (!data) return;
                self.counterpartLastSeenAt = data.counterpart_last_seen_at;
                let added = false;
                (data.messages || []).forEach(function (m) {
                    if (m.id > self.lastId) { self.messages.push(m); self.lastId = m.id; added = true; }
                });
                if (added) { self.markRead(); self.scrollDown(); }
            },
            send: function () {
                let self = this;
                let text = (self.body || '').trim();
                if (!text || !self.chatId || self.sending) return;
                self.sending = true;
                fetch(self.endpoints.replyBase + self.chatId, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ body: text }),
                })
                    .then(function (r) { return r.json().then(function (res) { return { ok: r.ok, status: r.status, res: res }; }); })
                    .then(function (out) {
                        self.sending = false;
                        if (!out.ok) {
                            if (out.status === 422) {
                                contabaiToast((out.res && out.res.errors) ? contabaiFieldText.body : self.labels.hostUnavailable, 'danger');
                            } else {
                                contabaiToast(contabaiStatusText(out.status, self.labels.sendError), 'danger');
                            }
                            return;
                        }
                        if ((self.body || '').trim() === text) { self.body = ''; }
                        let message = out.res && out.res.data;
                        if (message && message.id > self.lastId) {
                            self.messages.push(message);
                            self.lastId = message.id;
                            self.scrollDown();
                            // Advance the worker's cursor so it doesn't re-echo our just-sent message.
                            if (window.chatWorker) { window.chatWorker.postMessage({ command: 'set-cursor', after: self.lastId }); }
                        }
                    })
                    .catch(function () { self.sending = false; contabaiToast(self.labels.sendError, 'danger'); });
            },
            markRead: function () {
                let self = this;
                if (!self.chatId) return;
                fetch(self.endpoints.readBase + self.chatId, { method: 'POST', credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
            },
            back: function () {
                this.view = 'list';
                this.chatId = null;
                // Stop the worker's thread poll so it doesn't keep polling the chat we just left.
                if (window.chatWorker) { window.chatWorker.postMessage({ command: 'stop-thread' }); }
                this.loadChats(this.currentPage);
            },
            presence: function () {
                if (!this.counterpartLastSeenAt) return '';
                let lastSeenTime = Date.parse(this.counterpartLastSeenAt);
                if (!isNaN(lastSeenTime) && (Date.now() - lastSeenTime) < this.activeMs) return this.labels.active;
                return this.labels.lastSeen + ' ' + new Date(this.counterpartLastSeenAt).toLocaleString();
            },
            avatarUrl: function (counterpart) {
                return (counterpart && counterpart.avatar && counterpart.avatar['256x256']) || '';
            },
            preview: function (msg) {
                return (msg && msg.body) || '';
            },
            relTime: function (ts) {
                if (!ts) return '';
                let parsedTime = Date.parse(ts);
                if (isNaN(parsedTime)) return '';
                let diff = Math.floor((Date.now() - parsedTime) / 1000);
                if (diff < 60) return '<?php echo esc_js(__('now', 'contabai')); ?>';
                if (diff < 3600) return Math.floor(diff / 60) + '<?php echo esc_js(__('m', 'contabai')); ?>';
                if (diff < 86400) return Math.floor(diff / 3600) + '<?php echo esc_js(__('h', 'contabai')); ?>';
                return new Date(ts).toLocaleDateString();
            },
            scrollDown: function () {
                let self = this;
                this.$nextTick(function () { if (self.$refs.scroll) self.$refs.scroll.scrollTop = self.$refs.scroll.scrollHeight; });
            }
        };
    });
});
</script>
