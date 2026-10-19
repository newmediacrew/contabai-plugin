<div x-data="contabaiRandomListings('<?php echo esc_js($endpoint); ?>', <?php echo esc_attr(wp_json_encode($initial)); ?>)" x-init="initial.length ? hydrate() : load()" class="mx-auto w-full max-w-7xl px-4 py-8">
    <template x-if="error">
        <p class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-700" x-text="error"></p>
    </template>

    <?php if ($initial): ?>
        <div class="contabai-listings-grid" x-show="! listings.length">
            <?php foreach ($initial as $item) { include __DIR__ . '/../components/listing-card-static.php'; } ?>
        </div>
    <?php endif; ?>

    <div class="contabai-listings-grid">
        <template x-for="listing in listings" x-bind:key="listing.id">
            <div>
                <?php include __DIR__ . '/../components/listing-card.php'; ?>
            </div>
        </template>
    </div>

    <div x-show="loading" x-cloak class="py-16 text-center text-sm text-neutral-500"><?php echo esc_html__('Loading...', 'contabai'); ?></div>
</div>

<?php if (! empty($printScript)): ?>
<?php include __DIR__ . '/../components/listing-card-helpers.php'; ?>
<script>
document.addEventListener('alpine:init', function () {
    // Registered once per page; each [contabai_random_listings] instance passes its own
    // endpoint (?count=N) as the argument, so multiple strips fetch independently.
    Alpine.data('contabaiRandomListings', function (endpoint, initial) {
        return {
            // Card rendering (photos carousel, labels, url, price) — shared with the listings grid.
            ...window.contabaiCardHelpers({
                propertyTypes: <?php echo wp_json_encode($propertyTypes); ?>,
                countries: <?php echo wp_json_encode($countries); ?>,
                detailBase: '<?php echo esc_url($detailBase); ?>'
            }),

            listings: [],
            initial: initial || [],
            loading: false,
            error: '',
            endpoint: endpoint,

            hydrate: function () {
                let self = this;
                let show = function () {
                    if (! self.initial.length) { return; }
                    self.listings = self.initial;
                    self.initial = [];
                };
                if (document.readyState === 'complete') { show(); } else { window.addEventListener('load', show, { once: true }); setTimeout(show, 5000); }
            },

            load: function () {
                let self = this;
                self.loading = true;
                self.error = '';
                fetch(self.endpoint, { credentials: 'same-origin' })
                    .then(function (r) { return r.json().then(function (body) { return { ok: r.ok, status: r.status, body: body }; }); })
                    .then(function (res) {
                        if (!res.ok) {
                            self.error = res.status === 422
                                ? '<?php echo esc_js(__('Some filters could not be applied. Please adjust them and try again.', 'contabai')); ?>'
                                : contabaiStatusText(res.status);
                            self.listings = [];
                            self.loading = false;
                            return;
                        }
                        self.listings = ((res.body && res.body.data) || {}).listings || [];
                        self.loading = false;
                    })
                    .catch(function () { self.loading = false; });
            }
        };
    });
});
</script>
<?php endif; ?>
