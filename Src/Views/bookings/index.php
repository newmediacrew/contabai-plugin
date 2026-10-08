<div x-data="contabaiBookings()" class="mx-auto max-w-3xl px-4 py-8">

    <!-- LIST -->
    <div x-show="view === 'list'">
        <?php echo \Contabai\View::render('components.breadcrumb', ['crumbs' => [
            ['label' => __('Hub', 'contabai'), 'url' => home_url('/' . CONTABAI_ACCOUNT_PAGE_SLUG)],
            ['label' => __('My bookings', 'contabai'), 'url' => null],
        ]]); ?>
        <h2 class="mb-6 text-2xl font-bold text-neutral-900"><?php echo esc_html__('My bookings', 'contabai'); ?></h2>

        <div x-show="loading" class="py-16 text-center text-sm text-neutral-500"><?php echo esc_html__('Loading…', 'contabai'); ?></div>

        <template x-if="!loading && !loadFailed && bookings.length === 0">
            <p class="rounded-xl border border-dashed border-neutral-300 py-16 text-center text-neutral-500"><?php echo esc_html__('No bookings yet.', 'contabai'); ?></p>
        </template>

        <div x-show="!loading && bookings.length" class="grid gap-4 sm:grid-cols-2">
            <template x-for="b in bookings" x-bind:key="b.id">
                <?php include __DIR__ . '/../components/booking-card.php'; ?>
            </template>
        </div>

        <?php $pg_neutral = true; include __DIR__ . '/../components/pagination.php'; ?>
    </div>

    <!-- DETAIL -->
    <div x-show="view === 'detail'" x-cloak>
        <div x-show="loadingDetail" class="py-16 text-center text-sm text-neutral-500"><?php echo esc_html__('Loading…', 'contabai'); ?></div>

        <template x-if="!loadingDetail && detail">
            <div>
                <nav aria-label="breadcrumb" class="no-scrollbar mb-6 flex items-center gap-1 overflow-x-auto text-sm text-neutral-500">
                    <a href="<?php echo esc_url(home_url('/' . CONTABAI_ACCOUNT_PAGE_SLUG)); ?>" title="<?php esc_attr_e('Hub', 'contabai'); ?>" class="inline-flex flex-none items-center py-1 no-underline transition hover:text-neutral-900"><?php echo \Contabai\Heroicon::outline('home', 'w-4 h-4'); ?></a>
                    <?php echo \Contabai\Heroicon::outline('chevron-right', 'w-4 h-4 flex-none text-neutral-400'); ?>
                    <button type="button" x-on:click="back()" class="inline-flex flex-none items-center whitespace-nowrap py-1 no-underline transition hover:text-neutral-900"><?php echo esc_html__('My bookings', 'contabai'); ?></button>
                    <?php echo \Contabai\Heroicon::outline('chevron-right', 'w-4 h-4 flex-none text-neutral-400'); ?>
                    <span class="inline-flex flex-none items-center whitespace-nowrap py-1 font-medium text-neutral-600" x-text="detail.listing.title"></span>
                </nav>

                <!-- hero image + status -->
                <div class="relative mb-5 aspect-[16/9] overflow-hidden rounded-lg bg-neutral-100">
                    <img x-bind:src="detail.listing.medium || detail.listing.card || detail.listing.thumbnail" x-show="detail.listing.thumbnail" x-bind:alt="detail.listing.image_alt || detail.listing.title" x-cloak class="h-full w-full object-cover">
                    <?php
                    $badge_extra = 'absolute left-3 top-3 capitalize';
                    $badge_attrs = 'x-text="statusLabel(detail.status)" x-bind:class="statusClass(detail.status)"';
                    include __DIR__ . '/../components/badge.php';
                    ?>
                </div>

                <h2 x-text="detail.listing.title" class="text-2xl font-bold text-neutral-900"></h2>
                <div class="mt-1 flex items-center gap-1 text-sm text-neutral-500">
                    <?php echo \Contabai\Heroicon::outline('map-pin', 'w-4 h-4'); ?>
                    <span x-text="detail.listing.country_name || detail.listing.country"></span> · <span x-text="detail.listing.city_name || detail.listing.city"></span>
                </div>
                <p class="mt-1 text-sm text-neutral-400"><?php echo esc_html__('Booking', 'contabai'); ?> #<span x-text="detail.id"></span></p>

                <div class="mt-6 space-y-4">
                    <!-- Trip -->
                    <section class="grid gap-4 rounded-lg border border-neutral-200 bg-white p-5 sm:grid-cols-3">
                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-neutral-400"><?php echo esc_html__('Dates', 'contabai'); ?></div>
                            <div class="mt-1 text-sm text-neutral-700"><span x-text="detail.arrival"></span> → <span x-text="detail.departure"></span></div>
                        </div>
                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-neutral-400"><?php echo esc_html__('Party', 'contabai'); ?></div>
                            <div class="mt-1 text-sm text-neutral-700">
                                <span x-text="detail.adults"></span> <?php echo esc_html__('adults', 'contabai'); ?><template x-if="detail.children"><span> · <span x-text="detail.children"></span> <?php echo esc_html__('children', 'contabai'); ?></span></template><template x-if="detail.infants"><span> · <span x-text="detail.infants"></span> <?php echo esc_html__('infants', 'contabai'); ?></span></template><template x-if="detail.pets"><span> · <span x-text="detail.pets"></span> <?php echo esc_html__('pets', 'contabai'); ?></span></template>
                            </div>
                        </div>
                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-neutral-400"><?php echo esc_html__('Nights', 'contabai'); ?></div>
                            <div class="mt-1 text-sm text-neutral-700"><span x-text="detail.nights"></span> <?php echo esc_html__('nights', 'contabai'); ?></div>
                        </div>
                    </section>

                    <!-- Price (single breakdown; night + cost rows folded together) -->
                    <section class="rounded-lg border border-neutral-200 bg-white p-5">
                        <h3 class="mb-3 text-sm font-semibold text-neutral-900"><?php echo esc_html__('Price', 'contabai'); ?></h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between text-neutral-600"><span><span x-text="detail.nights"></span> <?php echo esc_html__('nights', 'contabai'); ?></span><span x-text="money(detail.accommodation_cents, detail.currency)"></span></div>
                            <template x-for="c in (detail.cost_breakdown || [])" x-bind:key="c.type_key">
                                <div class="flex justify-between text-neutral-600"><span x-text="costLabel(c)"></span><span x-text="money(c.computed_cents, detail.currency)"></span></div>
                            </template>
                            <template x-if="detail.deposit_cents">
                                <div class="flex justify-between text-neutral-600"><span><?php echo esc_html__('Security deposit', 'contabai'); ?></span><span x-text="money(detail.deposit_cents, detail.currency)"></span></div>
                            </template>
                            <div class="flex justify-between border-t border-neutral-200 pt-2 font-semibold text-neutral-900"><span><?php echo esc_html__('Total', 'contabai'); ?></span><span x-text="money(detail.due_at_booking_cents, detail.currency)"></span></div>
                        </div>
                    </section>

                    <!-- Payment details -->
                    <template x-if="detail.payee && (detail.payee.account_name || detail.payee.iban || detail.payee.bic)">
                        <section class="rounded-lg border border-neutral-200 bg-white p-5">
                            <h3 class="mb-3 text-sm font-semibold text-neutral-900"><?php echo esc_html__('Payment details', 'contabai'); ?></h3>
                            <div class="space-y-1 text-sm text-neutral-600">
                                <div x-show="detail.payee.account_name" x-text="detail.payee.account_name"></div>
                                <div x-show="detail.payee.iban">IBAN: <span x-text="detail.payee.iban"></span></div>
                                <div x-show="detail.payee.bic">BIC: <span x-text="detail.payee.bic"></span></div>
                            </div>
                        </section>
                    </template>

                    <!-- Leave a review — only for a completed stay not yet reviewed -->
                    <template x-if="detail.status === 'checked_out' && !detail.has_review && !reviewSubmitted">
                        <section class="rounded-lg border border-neutral-200 bg-white p-5">
                            <h3 class="mb-3 text-sm font-semibold text-neutral-900"><?php echo esc_html__('Leave a review', 'contabai'); ?></h3>
                            <div class="flex items-center gap-1" x-on:mouseleave="reviewHover = 0">
                                <template x-for="n in 5" x-bind:key="n">
                                    <button type="button" x-on:click="reviewRating = n" x-on:mouseenter="reviewHover = n"
                                            x-bind:aria-label="n + ' / 5'" class="p-1">
                                        <span x-bind:class="n <= (reviewHover || reviewRating) ? 'text-amber-400' : 'text-neutral-300'"><?php echo \Contabai\Heroicon::solid('star', 'w-8 h-8'); ?></span>
                                    </button>
                                </template>
                            </div>
                            <p x-show="reviewError" x-text="reviewError" x-cloak class="mt-2 text-sm text-red-600"></p>
                            <textarea x-model="reviewBody" rows="3" maxlength="2000"
                                      placeholder="<?php esc_attr_e('Share a few words about your stay (optional)…', 'contabai'); ?>"
                                      class="mt-3 w-full resize-none rounded-lg border border-neutral-300 px-3 py-2 text-sm text-neutral-800 placeholder:text-neutral-400 focus:border-neutral-400 focus:outline-none"></textarea>
                            <div class="mt-3">
                                <button type="button" x-on:click="submitReview()" x-bind:disabled="reviewSubmitting || !reviewRating"
                                        class="inline-flex items-center gap-2 rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-800 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50">
                                    <?php echo \Contabai\Heroicon::outline('paper-airplane', 'w-4 h-4'); ?>
                                    <span x-text="reviewSubmitting ? '<?php echo esc_js(__('Posting…', 'contabai')); ?>' : '<?php echo esc_js(__('Post review', 'contabai')); ?>'"></span>
                                </button>
                            </div>
                            <p class="mt-2 text-xs text-neutral-400"><?php echo esc_html__('Reviews are public and cannot be edited after posting.', 'contabai'); ?></p>
                        </section>
                    </template>

                    <!-- Posted confirmation / already reviewed -->
                    <template x-if="detail.status === 'checked_out' && (detail.has_review || reviewSubmitted)">
                        <section class="rounded-lg border border-neutral-200 bg-white p-5">
                            <div class="flex items-center gap-2 text-sm text-neutral-700">
                                <?php echo \Contabai\Heroicon::solid('check-circle', 'w-5 h-5 text-green-600'); ?>
                                <span><?php echo esc_html__('Thanks — your review has been posted.', 'contabai'); ?></span>
                            </div>
                        </section>
                    </template>
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                    <a x-bind:href="pdfUrl(detail.id)" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-md border border-neutral-300 px-4 py-2 text-sm font-medium text-neutral-700 no-underline transition hover:bg-neutral-50">
                        <?php echo \Contabai\Heroicon::outline('document-arrow-down', 'w-4 h-4'); ?>
                        <span><?php echo esc_html__('Booking', 'contabai'); ?></span>
                    </a>
                    <template x-if="detail.has_refund">
                        <a x-bind:href="refundPdfUrl(detail.id)" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-md border border-neutral-300 px-4 py-2 text-sm font-medium text-neutral-700 no-underline transition hover:bg-neutral-50">
                            <?php echo \Contabai\Heroicon::outline('document-arrow-down', 'w-4 h-4'); ?>
                            <span><?php echo esc_html__('Deposit', 'contabai'); ?></span>
                        </a>
                    </template>
                </div>
            </div>
        </template>
    </div>
</div>
<script>
document.addEventListener('alpine:init', function () {
    Alpine.data('contabaiBookings', function () {
        return {
            endpoints: <?php echo wp_json_encode([
                'list' => rest_url('contabai/v1/sanctum/guest/bookings'),
                'detailBase' => rest_url('contabai/v1/sanctum/guest/booking/'),
                'pdfBase' => rest_url('contabai/v1/sanctum/guest/booking/pdf/'),
                'refundPdfBase' => rest_url('contabai/v1/sanctum/guest/booking/deposit-refund-pdf/'),
                'reviewBase' => rest_url('contabai/v1/sanctum/guest/review/'),
            ]); ?>,
            view: 'list',
            loaded: false,
            loading: false,
            loadFailed: false,
            loadingDetail: false,
            bookings: [],
            currentPage: 1,
            lastPage: 1,
            total: 0,
            perPage: 4,
            detail: null,
            reviewRating: 0,
            reviewHover: 0,
            reviewBody: '',
            reviewSubmitting: false,
            reviewSubmitted: false,
            reviewError: '',
            reviewLabels: {
                thanks: '<?php echo esc_js(__('Thanks — your review has been posted.', 'contabai')); ?>',
                chooseRating: '<?php echo esc_js(__('Please choose a rating between 1 and 5.', 'contabai')); ?>',
                cannot: '<?php echo esc_js(__('This booking can no longer be reviewed.', 'contabai')); ?>',
                error: '<?php echo esc_js(__('Could not post your review.', 'contabai')); ?>',
                confirm: '<?php echo esc_js(__("Post this review? It's public and can't be edited or removed once posted.", 'contabai')); ?>',
            },
            labels: <?php echo wp_json_encode($labels ?? []); ?>,
            statuses: <?php echo wp_json_encode(($labels['booking_statuses'] ?? [])); ?>,
            init: function () {
                this.load();
            },
            load: function () {
                let self = this;
                self.loading = true;
                self.loadFailed = false;
                self.fetchList(1)
                    .then(function () { self.loaded = true; self.loading = false; })
                    .catch(function (e) { self.loading = false; self.loadFailed = true; contabaiToast(contabaiStatusText(e.status, '<?php echo esc_js(__('Could not load your bookings.', 'contabai')); ?>'), 'danger'); });
            },
            fetchList: function (page) {
                let self = this;
                return fetch(self.endpoints.list + '?page=' + page + '&per_page=' + self.perPage, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                    .then(function (r) { if (! r.ok) { let e = new Error('http'); e.status = r.status; throw e; } return r.json(); })
                    .then(function (res) {
                        let data = (res && res.data) || {};
                        self.bookings = data.bookings || [];
                        self.currentPage = data.current_page || 1;
                        self.lastPage = data.last_page || 1;
                        self.total = data.total || 0;
                    });
            },
            goTo: function (page) {
                let self = this;
                if (page < 1 || page > self.lastPage) return;
                self.loading = true;
                self.fetchList(page)
                    .then(function () { self.loading = false; })
                    .catch(function (e) { self.loading = false; contabaiToast(contabaiStatusText(e.status, '<?php echo esc_js(__('Could not load your bookings.', 'contabai')); ?>'), 'danger'); });
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
            openDetail: function (id) {
                let self = this;
                self.view = 'detail';
                self.detail = null;
                self.loadingDetail = true;
                self.reviewRating = 0; self.reviewHover = 0; self.reviewBody = ''; self.reviewError = ''; self.reviewSubmitted = false;
                fetch(self.endpoints.detailBase + id, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                    .then(function (r) { if (! r.ok) { let e = new Error('http'); e.status = r.status; throw e; } return r.json(); })
                    .then(function (res) { self.detail = (res && res.data && res.data.booking) || null; self.loadingDetail = false; })
                    .catch(function (e) { self.loadingDetail = false; contabaiToast(contabaiStatusText(e.status, '<?php echo esc_js(__('Could not load the booking.', 'contabai')); ?>'), 'danger'); self.back(); });
            },
            back: function () { this.view = 'list'; this.detail = null; },
            // POST the guest's one review of a completed stay. Form is gated on checked_out && !has_review,
            // so the 422/404 branches are defensive (Laravel enforces the same rules server-side).
            submitReview: function () {
                let self = this;
                if (! self.reviewRating) { self.reviewError = self.reviewLabels.chooseRating; return; }
                if (! window.confirm(self.reviewLabels.confirm)) { return; }
                self.reviewSubmitting = true;
                self.reviewError = '';
                fetch(self.endpoints.reviewBase + self.detail.id, {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ rating: self.reviewRating, body: self.reviewBody })
                })
                    .then(function (r) { return r.json().then(function (body) { return { status: r.status, body: body }; }, function () { return { status: r.status, body: {} }; }); })
                    .then(function (out) {
                        self.reviewSubmitting = false;
                        if (out.status === 201) {
                            self.detail.has_review = true;
                            self.reviewSubmitted = true;
                            contabaiToast(self.reviewLabels.thanks, 'success');
                            return;
                        }
                        if (out.status === 422 && out.body && out.body.errors && out.body.errors.rating) {
                            self.reviewError = self.reviewLabels.chooseRating;
                            return;
                        }
                        if (out.status === 422 || out.status === 404) {
                            // not checked-out / already reviewed / not this guest's booking — re-sync + hide the form
                            contabaiToast(self.reviewLabels.cannot, 'danger');
                            self.resyncDetail();
                            return;
                        }
                        contabaiToast(contabaiStatusText(out.status, self.reviewLabels.error), 'danger');
                    })
                    .catch(function () { self.reviewSubmitting = false; contabaiToast(self.reviewLabels.error, 'danger'); });
            },
            resyncDetail: function () {
                let self = this;
                fetch(self.endpoints.detailBase + self.detail.id, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (res) { self.detail = (res && res.data && res.data.booking) || self.detail; })
                    .catch(function () {});
            },
            pdfUrl: function (id) { return this.endpoints.pdfBase + id; },
            refundPdfUrl: function (id) { return this.endpoints.refundPdfBase + id; },
            statusLabel: function (s) { return this.statuses[s] || s; },
            // Cost line label: stored `label` (only set for type_key='other'), else the translated
            // additional_cost_types entry from the bundle, else the raw key. Mirrors Laravel's views.
            costLabel: function (c) { return c.label || (this.labels.additional_cost_types || {})[c.type_key] || c.type_key; },
            statusClass: function (s) {
                // Pines badge palette (bg-{c}-100 text-{c}-800), mapped from Laravel status variants.
                let map = {
                    pending: 'bg-yellow-100 text-yellow-800',
                    confirmed: 'bg-green-100 text-green-800',
                    checked_in: 'bg-cyan-100 text-cyan-800',
                    checked_out: 'bg-gray-100 text-gray-800',
                    declined: 'bg-red-100 text-red-800',
                    expired: 'bg-red-100 text-red-800',
                    cancelled: 'bg-red-100 text-red-800'
                };
                return map[s] || 'bg-gray-100 text-gray-800';
            },
            money: function (cents, currency) {
                if (cents === null || cents === undefined) return '';
                let code = currency || 'EUR';
                try {
                    return new Intl.NumberFormat(undefined, { style: 'currency', currency: code, currencyDisplay: 'code' }).format(cents / 100);
                } catch (e) {
                    return code + ' ' + (cents / 100).toFixed(2);
                }
            }
        };
    });
});
</script>
