<?php
$amenityFlat = [];
foreach (($amenitiesCatalogue ?? []) as $group) {
    if (is_array($group)) {
        $amenityFlat += $group;
    }
}
$bedBathOptions = ['1', '2', '3', '4'];
?>
<?php if (! empty($searchBar)): ?>
<div class="mx-auto w-full max-w-7xl px-4 pt-8"><?php echo $searchBar; ?></div>
<?php endif; ?>

<div x-data="contabaiListings()" x-init="readFilters(); setupResponsive(); load(1)" class="mx-auto w-full max-w-7xl px-4 py-8 lg:flex lg:gap-8">

    <aside class="mb-6 lg:mb-0 lg:w-64 lg:shrink-0">
        <button type="button" x-on:click="railOpen = true"
                class="mb-4 inline-flex w-full items-center justify-between rounded-lg border border-neutral-300 px-4 py-2.5 text-sm font-medium text-neutral-800 transition hover:bg-neutral-50 lg:!hidden">
            <span class="inline-flex items-center gap-2"><?php echo \Contabai\Heroicon::outline('adjustments-horizontal', 'w-4 h-4'); ?><?php echo esc_html__('Filters', 'contabai'); ?></span>
            <span x-show="railActive()" x-cloak class="contabai-accent-bg inline-flex h-2 w-2 rounded-full"></span>
        </button>

        <div x-show="railOpen" x-cloak x-transition.opacity x-on:click="railOpen = false" class="contabai-filter-backdrop lg:hidden"></div>

        <div class="contabai-filter-panel" x-bind:class="railOpen ? 'is-open' : ''"
             x-on:keydown.escape.window="railOpen = false"
             x-effect="document.body.style.overflow = (railOpen && ! isLg) ? 'hidden' : ''">
            <div class="contabai-filter-drawer-head mb-4 items-center justify-between border-b border-neutral-200 pb-3 lg:!hidden">
                <span class="text-base font-semibold text-neutral-900"><?php echo esc_html__('Filters', 'contabai'); ?></span>
                <button type="button" x-on:click="railOpen = false" aria-label="<?php echo esc_attr__('Close', 'contabai'); ?>"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-neutral-300 text-neutral-600 transition hover:bg-neutral-50"><?php echo \Contabai\Heroicon::outline('x-mark', 'w-5 h-5'); ?></button>
            </div>
            <div class="space-y-3">

            <?php if (! empty($propertyTypes)): ?>
            <section x-data="{ o: true }" class="rounded-2xl border border-neutral-200 bg-white shadow-sm">
                <button type="button" x-on:click="o = ! o" class="flex w-full items-center justify-between px-4 py-3 text-left">
                    <span class="contabai-seo-place text-sm font-semibold text-neutral-900"><?php echo esc_html__('Property type', 'contabai'); ?></span>
                    <span class="text-neutral-400 transition" x-bind:class="o ? 'rotate-180' : ''"><?php echo \Contabai\Heroicon::outline('chevron-down', 'w-4 h-4'); ?></span>
                </button>
                <div x-show="o" x-collapse>
                    <div class="space-y-1.5 border-t border-neutral-200 px-4 py-4">
                        <?php foreach ($propertyTypes as $key => $label): ?>
                            <label class="flex items-center gap-2 text-sm text-neutral-700">
                                <input type="checkbox" x-bind:checked="inArr('property_type', '<?php echo esc_js($key); ?>')" x-on:change="toggleArr('property_type', '<?php echo esc_js($key); ?>')"
                                       class="h-4 w-4 rounded border-neutral-300 text-[color:var(--theme-color,#ff5400)] focus:ring-neutral-400">
                                <span class="min-w-0 truncate"><?php echo esc_html($label); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <section x-data="{ o: false }" class="rounded-2xl border border-neutral-200 bg-white shadow-sm">
                <button type="button" x-on:click="o = ! o" class="flex w-full items-center justify-between px-4 py-3 text-left">
                    <span class="contabai-seo-place text-sm font-semibold text-neutral-900"><?php echo esc_html__('Bedrooms', 'contabai'); ?></span>
                    <span class="text-neutral-400 transition" x-bind:class="o ? 'rotate-180' : ''"><?php echo \Contabai\Heroicon::outline('chevron-down', 'w-4 h-4'); ?></span>
                </button>
                <div x-show="o" x-collapse>
                    <div class="space-y-1.5 border-t border-neutral-200 px-4 py-4">
                        <?php foreach ($bedBathOptions as $n): ?>
                            <label class="flex items-center gap-2 text-sm text-neutral-700">
                                <input type="checkbox" x-bind:checked="rail.bedrooms === '<?php echo esc_js($n); ?>'" x-on:change="setVal('bedrooms', '<?php echo esc_js($n); ?>')"
                                       class="h-4 w-4 rounded border-neutral-300 text-[color:var(--theme-color,#ff5400)] focus:ring-neutral-400">
                                <span><?php echo esc_html($n . '+'); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section x-data="{ o: false }" class="rounded-2xl border border-neutral-200 bg-white shadow-sm">
                <button type="button" x-on:click="o = ! o" class="flex w-full items-center justify-between px-4 py-3 text-left">
                    <span class="contabai-seo-place text-sm font-semibold text-neutral-900"><?php echo esc_html__('Bathrooms', 'contabai'); ?></span>
                    <span class="text-neutral-400 transition" x-bind:class="o ? 'rotate-180' : ''"><?php echo \Contabai\Heroicon::outline('chevron-down', 'w-4 h-4'); ?></span>
                </button>
                <div x-show="o" x-collapse>
                    <div class="space-y-1.5 border-t border-neutral-200 px-4 py-4">
                        <?php foreach ($bedBathOptions as $n): ?>
                            <label class="flex items-center gap-2 text-sm text-neutral-700">
                                <input type="checkbox" x-bind:checked="rail.bathrooms === '<?php echo esc_js($n); ?>'" x-on:change="setVal('bathrooms', '<?php echo esc_js($n); ?>')"
                                       class="h-4 w-4 rounded border-neutral-300 text-[color:var(--theme-color,#ff5400)] focus:ring-neutral-400">
                                <span><?php echo esc_html($n . '+'); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section x-data="{ o: false }" class="rounded-2xl border border-neutral-200 bg-white shadow-sm">
                <button type="button" x-on:click="o = ! o" class="flex w-full items-center justify-between px-4 py-3 text-left">
                    <span class="contabai-seo-place text-sm font-semibold text-neutral-900"><?php echo esc_html__('Sort by', 'contabai'); ?></span>
                    <span class="text-neutral-400 transition" x-bind:class="o ? 'rotate-180' : ''"><?php echo \Contabai\Heroicon::outline('chevron-down', 'w-4 h-4'); ?></span>
                </button>
                <div x-show="o" x-collapse>
                    <div class="space-y-1.5 border-t border-neutral-200 px-4 py-4">
                        <?php
                        $sortOptions = [
                            '' => __('Newest', 'contabai'),
                            'price_asc' => __('Price: low to high', 'contabai'),
                            'price_desc' => __('Price: high to low', 'contabai'),
                        ];
                        foreach ($sortOptions as $val => $label): ?>
                            <label class="flex items-center gap-2 text-sm text-neutral-700">
                                <input type="checkbox" x-bind:checked="(rail.sort || '') === '<?php echo esc_js($val); ?>'" x-on:change="setSort('<?php echo esc_js($val); ?>')"
                                       class="h-4 w-4 rounded border-neutral-300 text-[color:var(--theme-color,#ff5400)] focus:ring-neutral-400">
                                <span><?php echo esc_html($label); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <?php if (! empty($amenityFlat)): ?>
            <section x-data="{ o: false, openCat: '' }" class="rounded-2xl border border-neutral-200 bg-white shadow-sm">
                <button type="button" x-on:click="o = ! o" class="flex w-full items-center justify-between px-4 py-3 text-left">
                    <span class="contabai-seo-place text-sm font-semibold text-neutral-900"><?php echo esc_html__('Amenities', 'contabai'); ?></span>
                    <span class="text-neutral-400 transition" x-bind:class="o ? 'rotate-180' : ''"><?php echo \Contabai\Heroicon::outline('chevron-down', 'w-4 h-4'); ?></span>
                </button>
                <div x-show="o" x-collapse>
                    <div class="border-t border-neutral-200 px-4 py-4">
                        <div class="space-y-1.5">
                            <?php foreach (($popularAmenities ?? []) as $key): ?>
                                <?php if (! isset($amenityFlat[$key])) { continue; } ?>
                                <label class="flex items-center gap-2 text-sm text-neutral-700">
                                    <input type="checkbox" x-bind:checked="inArr('amenities', '<?php echo esc_js($key); ?>')" x-on:change="toggleArr('amenities', '<?php echo esc_js($key); ?>')"
                                           class="h-4 w-4 rounded border-neutral-300 text-[color:var(--theme-color,#ff5400)] focus:ring-neutral-400">
                                    <span class="min-w-0 truncate"><?php echo esc_html($amenityFlat[$key]); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-4 border-t border-neutral-200">
                            <?php foreach (($amenitiesCatalogue ?? []) as $groupKey => $items): ?>
                                <?php if (! is_array($items) || $items === []) { continue; } ?>
                                <?php $catKeys = wp_json_encode(array_keys($items)); ?>
                                <?php $catId = esc_js($groupKey); ?>
                                <div class="border-b border-neutral-200">
                                    <button type="button" x-on:click="openCat = (openCat === '<?php echo $catId; ?>' ? '' : '<?php echo $catId; ?>')" class="flex w-full items-center justify-between gap-2 py-2.5 text-left">
                                        <span class="min-w-0 truncate text-sm font-medium text-neutral-700"><?php echo esc_html($amenityCategories[$groupKey] ?? ucfirst(str_replace('_', ' ', (string) $groupKey))); ?></span>
                                        <span class="flex shrink-0 items-center gap-2">
                                            <span class="contabai-accent-bg rounded-full px-1.5 text-xs font-semibold text-white"
                                                  x-show='catCount(<?php echo $catKeys; ?>)' x-cloak x-text='catCount(<?php echo $catKeys; ?>)'></span>
                                            <span class="text-neutral-400 transition" x-bind:class="openCat === '<?php echo $catId; ?>' ? 'rotate-180' : ''"><?php echo \Contabai\Heroicon::outline('chevron-down', 'w-4 h-4'); ?></span>
                                        </span>
                                    </button>
                                    <div x-show="openCat === '<?php echo $catId; ?>'" x-collapse>
                                        <div class="space-y-1.5 pb-3">
                                            <?php foreach ($items as $key => $label): ?>
                                                <label class="flex items-center gap-2 text-sm text-neutral-700">
                                                    <input type="checkbox" x-bind:checked="inArr('amenities', '<?php echo esc_js($key); ?>')" x-on:change="toggleArr('amenities', '<?php echo esc_js($key); ?>')"
                                                           class="h-4 w-4 rounded border-neutral-300 text-[color:var(--theme-color,#ff5400)] focus:ring-neutral-400">
                                                    <span class="min-w-0 truncate"><?php echo esc_html($label); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <button type="button" x-show="railActive()" x-cloak x-on:click="clearRail()"
                    class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-sm font-semibold text-neutral-800 transition hover:bg-neutral-50"><?php echo esc_html__('Clear all filters', 'contabai'); ?></button>
            </div>
        </div>
    </aside>

    <div class="min-w-0 flex-1 scroll-mt-28" x-ref="results">
        <template x-if="error">
            <p class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-700" x-text="error"></p>
        </template>

        <div class="contabai-listings-grid" x-show="loading && listings.length === 0">
            <?php for ($skeleton = 0; $skeleton < (int) $skeletonCount; $skeleton++) : ?>
                <div class="animate-pulse">
                    <div class="aspect-[4/3] rounded-2xl bg-neutral-100"></div>
                    <div class="h-28 pt-2.5">
                        <div class="h-4 w-3/4 rounded bg-neutral-100"></div>
                        <div class="mt-2.5 h-3 w-1/2 rounded bg-neutral-100"></div>
                        <div class="mt-3 h-3 w-2/3 rounded bg-neutral-100"></div>
                        <div class="mt-3 h-4 w-1/3 rounded bg-neutral-100"></div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>

        <div class="contabai-listings-grid" x-show="listings.length > 0" x-cloak>
            <template x-for="listing in listings" x-bind:key="listing.id">
                <div>
                    <?php include __DIR__ . '/../components/listing-card.php'; ?>
                </div>
            </template>
        </div>

        <template x-if="listings.length === 0 && !loading && !error">
            <p class="py-16 text-center text-neutral-500"><?php echo esc_html__('No listings found.', 'contabai'); ?></p>
        </template>

        <div x-show="loading && listings.length > 0" x-cloak class="py-16 text-center text-sm text-neutral-500"><?php echo esc_html__('Loading...', 'contabai'); ?></div>

        <?php include __DIR__ . '/../components/pagination.php'; ?>
    </div>
</div>

<?php include __DIR__ . '/../components/listing-card-helpers.php'; ?>
<script>
document.addEventListener('alpine:init', function () {
    Alpine.data('contabaiListings', function () {
        return {
            ...window.contabaiCardHelpers({
                propertyTypes: <?php echo wp_json_encode($propertyTypes); ?>,
                countries: <?php echo wp_json_encode($countries); ?>,
                detailBase: '<?php echo esc_url($detailBase); ?>'
            }),

            listings: [],
            currentPage: 1,
            lastPage: 1,
            total: 0,
            perPage: <?php echo (int) $initialPerPage; ?>,
            loading: false,
            error: '',
            filters: {},
            baseFilters: {},
            rail: { property_type: [], amenities: [], bedrooms: '', bathrooms: '', sort: '' },
            railOpen: false,
            isLg: window.matchMedia('(min-width: 1024px)').matches,
            lockedFilters: <?php echo wp_json_encode((object) ($lockedFilters ?? [])); ?>,
            endpoint: '<?php echo esc_url($endpoint); ?>',

            setupResponsive: function () {
                let self = this;
                let mediaQuery = window.matchMedia('(min-width: 1024px)');
                let handler = function (e) { self.isLg = e.matches; };
                if (mediaQuery.addEventListener) { mediaQuery.addEventListener('change', handler); } else { mediaQuery.addListener(handler); }
            },

            // Location/date/guest filters come from the search bar's URL and stay fixed here;
            // the rail owns type/amenities/beds/baths/sort. Locked filters always win.
            readFilters: function () {
                let queryParams = new URLSearchParams(window.location.search);
                let get = function (key) { let value = queryParams.get(key); return (value !== null && value !== '') ? value : ''; };
                let base = {};
                ['country', 'city', 'area', 'from_date', 'till_date', 'guests', 'price_min', 'price_max'].forEach(function (key) {
                    let value = get(key); if (value) { base[key] = value; }
                });
                this.baseFilters = Object.assign(base, this.lockedFilters);
                this.rail.property_type = queryParams.getAll('property_type[]').filter(Boolean);
                this.rail.amenities = queryParams.getAll('amenities[]').filter(Boolean);
                this.rail.bedrooms = get('bedrooms');
                this.rail.bathrooms = get('bathrooms');
                this.rail.sort = get('sort');
                this.buildFilters();
            },

            buildFilters: function () {
                let filters = Object.assign({}, this.baseFilters);
                if (this.rail.bedrooms) { filters.bedrooms = this.rail.bedrooms; }
                if (this.rail.bathrooms) { filters.bathrooms = this.rail.bathrooms; }
                if (this.rail.sort) { filters.sort = this.rail.sort; }
                this.filters = filters;
            },

            syncUrl: function () {
                let self = this;
                let params = new URLSearchParams();
                Object.keys(self.baseFilters).forEach(function (k) {
                    if (! (k in self.lockedFilters)) { params.set(k, self.baseFilters[k]); }
                });
                if (self.rail.bedrooms) { params.set('bedrooms', self.rail.bedrooms); }
                if (self.rail.bathrooms) { params.set('bathrooms', self.rail.bathrooms); }
                if (self.rail.sort) { params.set('sort', self.rail.sort); }
                self.rail.property_type.forEach(function (v) { params.append('property_type[]', v); });
                self.rail.amenities.forEach(function (v) { params.append('amenities[]', v); });
                let queryString = params.toString();
                window.history.replaceState(null, '', window.location.pathname + (queryString ? '?' + queryString : ''));
            },

            applyRail: function () {
                this.buildFilters();
                this.syncUrl();
                this.load(1);
            },

            toggleArr: function (key, val) {
                let arr = this.rail[key];
                let index = arr.indexOf(val);
                if (index === -1) { arr.push(val); } else { arr.splice(index, 1); }
                this.applyRail();
            },

            inArr: function (key, val) { return this.rail[key].indexOf(val) !== -1; },

            catCount: function (keys) {
                let self = this;
                return keys.filter(function (k) { return self.rail.amenities.indexOf(k) !== -1; }).length;
            },

            setVal: function (key, val) {
                this.rail[key] = (this.rail[key] === val ? '' : val);
                this.applyRail();
            },

            setSort: function (val) { this.rail.sort = val; this.applyRail(); },

            clearRail: function () {
                this.rail.property_type = [];
                this.rail.amenities = [];
                this.rail.bedrooms = '';
                this.rail.bathrooms = '';
                this.rail.sort = '';
                this.applyRail();
            },

            railActive: function () {
                let rail = this.rail;
                return !! (rail.property_type.length || rail.amenities.length || rail.bedrooms || rail.bathrooms || rail.sort);
            },

            resultLabel: function () {
                let total = this.total;
                return total + ' ' + (total === 1 ? '<?php echo esc_js(__('place', 'contabai')); ?>' : '<?php echo esc_js(__('places', 'contabai')); ?>');
            },

            load: function (page) {
                let self = this;
                self.loading = true;
                self.error = '';
                let params = new URLSearchParams();
                Object.keys(self.filters).forEach(function (k) { params.set(k, self.filters[k]); });
                self.rail.property_type.forEach(function (v) { params.append('property_type[]', v); });
                self.rail.amenities.forEach(function (v) { params.append('amenities[]', v); });
                params.set('page', page);
                params.set('per_page', self.perPage);
                fetch(self.endpoint + '?' + params.toString(), { credentials: 'same-origin' })
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
                        let data = (res.body && res.body.data) || {};
                        self.listings = data.listings || [];
                        self.currentPage = data.current_page || page;
                        self.lastPage = data.last_page || 1;
                        self.total = data.total || 0;
                        self.loading = false;
                    })
                    .catch(function () { self.loading = false; });
            },

            goTo: function (page) {
                if (page < 1 || page > this.lastPage || this.loading) return;
                this.load(page);
                this.$refs.results.scrollIntoView({ behavior: 'smooth', block: 'start' });
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
            }
        };
    });
});
</script>
