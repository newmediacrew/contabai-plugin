<?php
$amenityFlat = [];
foreach (($amenitiesCatalogue ?? []) as $group) {
    if (is_array($group)) {
        $amenityFlat += $group;
    }
}
$bedBathOptions = ['' => __('Any', 'contabai'), '1' => '1+', '2' => '2+', '3' => '3+', '4' => '4+'];
$ratingOptions = ['' => __('Any', 'contabai'), '1' => '1+', '2' => '2+', '3' => '3+', '4' => '4+', '5' => '5'];
$sortOptions = [
    '' => __('Newest', 'contabai'),
    'price_asc' => __('Price: low to high', 'contabai'),
    'price_desc' => __('Price: high to low', 'contabai'),
    'rating_asc' => __('Review score: low to high', 'contabai'),
    'rating_desc' => __('Review score: high to low', 'contabai'),
];
$quickAmenities = array_values(array_filter(['pool', 'wifi', 'pets_allowed'], function ($key) use ($amenityFlat) {
    return isset($amenityFlat[$key]);
}));
$initialTotal = (int) $initial['total'];
?>
<div x-data="contabaiListings()" x-ref="root" x-init="readFilters(); setupResponsive(); watchSearchBar(); initialListings.length ? hydrate() : load(1)"
     x-on:keydown.escape.window="selectedId = null; menu = ''; filtersOpen = false"
     x-effect="document.body.style.overflow = (filtersOpen || (mapOpen && ! isLg)) ? 'hidden' : ''">
<?php if (! empty($searchBar)): ?>
<div class="mx-auto w-full max-w-7xl px-4 pt-8" x-ref="searchBar"><?php echo $searchBar; ?></div>
<?php endif; ?>

<div class="contabai-toolbar" x-ref="toolbar">
    <div class="relative mx-auto flex w-full items-center gap-2 px-4 py-3" x-ref="toolbarInner" x-bind:class="mapOpen && isLg ? '' : 'max-w-7xl'">
        <?php if (! empty($searchBar)): ?>
        <button type="button" x-show="searchHidden" x-cloak x-on:click="openSearch()"
                class="contabai-toolbar-search inline-flex h-11 min-w-0 shrink items-center gap-2 rounded-full border border-neutral-300 bg-white pl-1.5 pr-4 text-left text-sm font-medium text-neutral-900">
            <span class="contabai-accent-bg flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-white"><?php echo \Contabai\Heroicon::outline('magnifying-glass', 'w-4 h-4'); ?></span>
            <span class="truncate" x-text="searchSummary()"></span>
        </button>
        <?php endif; ?>

        <button type="button" x-on:click="openFilters('')"
                class="inline-flex h-11 shrink-0 items-center gap-2 rounded-full border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-900 transition hover:border-neutral-900">
            <?php echo \Contabai\Heroicon::outline('adjustments-horizontal', 'w-4 h-4'); ?><?php echo esc_html__('Filters', 'contabai'); ?>
            <span x-show="railCount()" x-cloak class="contabai-accent-bg inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-xs font-semibold text-white" x-text="railCount()"></span>
        </button>
        <span class="h-7 w-px shrink-0 bg-neutral-300"></span>

        <div class="contabai-chip-row flex min-w-0 flex-1 items-center gap-2">
            <?php if (! empty($propertyTypes)): ?>
            <button type="button" data-menu-trigger x-on:click="chipMenu('type', $event)" class="contabai-chip" x-bind:class="rail.property_type.length ? 'is-on' : ''">
                <?php echo esc_html__('Property type', 'contabai'); ?>
                <span x-show="rail.property_type.length" x-cloak x-text="'(' + rail.property_type.length + ')'"></span>
                <?php echo \Contabai\Heroicon::outline('chevron-down', 'w-4 h-4'); ?>
            </button>
            <?php endif; ?>
            <button type="button" data-menu-trigger x-on:click="chipMenu('bedrooms', $event)" class="contabai-chip" x-bind:class="rail.bedrooms ? 'is-on' : ''">
                <?php echo esc_html__('Bedrooms', 'contabai'); ?>
                <span x-show="rail.bedrooms" x-cloak x-text="rail.bedrooms + '+'"></span>
                <?php echo \Contabai\Heroicon::outline('chevron-down', 'w-4 h-4'); ?>
            </button>
            <button type="button" data-menu-trigger x-on:click="chipMenu('rating', $event)" class="contabai-chip" x-bind:class="rail.min_rating ? 'is-on' : ''">
                <?php echo esc_html__('Review score', 'contabai'); ?>
                <span x-show="rail.min_rating" x-cloak x-text="rail.min_rating === '5' ? '5' : rail.min_rating + '+'"></span>
                <?php echo \Contabai\Heroicon::outline('chevron-down', 'w-4 h-4'); ?>
            </button>
            <?php foreach ($quickAmenities as $key): ?>
            <button type="button" x-on:click="toggleArr('amenities', '<?php echo esc_js($key); ?>')" class="contabai-chip"
                    x-bind:class="inArr('amenities', '<?php echo esc_js($key); ?>') ? 'is-on' : ''"
                    x-bind:aria-pressed="inArr('amenities', '<?php echo esc_js($key); ?>')"><?php echo esc_html($amenityFlat[$key]); ?></button>
            <?php endforeach; ?>
        </div>

        <div class="contabai-lg-only relative shrink-0">
            <button type="button" data-menu-trigger x-on:click="toggleMenu('sort')" class="contabai-chip">
                <span class="font-semibold text-neutral-900" x-text="sortLabel()"><?php echo esc_html($sortOptions['']); ?></span>
                <?php echo \Contabai\Heroicon::outline('chevron-down', 'w-4 h-4'); ?>
            </button>
            <div x-show="menu === 'sort'" x-cloak x-on:click.outside="closeMenu($event)" class="contabai-menu right-0 w-64 p-2">
                <?php foreach ($sortOptions as $val => $label): ?>
                <button type="button" x-on:click="setSort('<?php echo esc_js($val); ?>'); menu = ''"
                        class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2.5 text-left text-sm text-neutral-800 transition hover:bg-neutral-100">
                    <?php echo esc_html($label); ?>
                    <span x-show="(rail.sort || '') === '<?php echo esc_js($val); ?>'"><?php echo \Contabai\Heroicon::outline('check', 'w-4 h-4'); ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (! empty($mapEnabled)): ?>
        <button type="button" x-show="hasPins()" x-on:click="mapOpen ? closeMap() : openMap()"
                class="contabai-lg-only contabai-accent-bg h-11 shrink-0 items-center gap-2 rounded-full px-5 text-sm font-semibold text-white transition hover:opacity-90">
            <span x-show="! mapOpen"><?php echo \Contabai\Heroicon::outline('map', 'w-5 h-5'); ?></span>
            <span x-show="mapOpen" x-cloak><?php echo \Contabai\Heroicon::outline('x-mark', 'w-5 h-5'); ?></span>
            <span x-text="mapOpen ? '<?php echo esc_js(__('Hide map', 'contabai')); ?>' : '<?php echo esc_js(__('Show map', 'contabai')); ?>'"><?php echo esc_html__('Show map', 'contabai'); ?></span>
        </button>
        <?php endif; ?>

        <?php if (! empty($propertyTypes)): ?>
        <div x-show="menu === 'type'" x-cloak x-on:click.outside="closeMenu($event)" class="contabai-menu w-72" x-bind:style="{ left: menuLeft + 'px' }">
            <div class="max-h-80 space-y-1 overflow-y-auto pr-1">
                <?php foreach ($propertyTypes as $key => $label): ?>
                    <label class="flex items-center gap-3 rounded-lg px-2 py-2 text-sm text-neutral-800 hover:bg-neutral-50">
                        <input type="checkbox" x-bind:checked="inArr('property_type', '<?php echo esc_js($key); ?>')" x-on:change="toggleArr('property_type', '<?php echo esc_js($key); ?>')"
                               class="h-4 w-4 rounded border-neutral-300 text-[color:var(--theme-color,#ff5400)] focus:ring-neutral-400">
                        <span class="min-w-0 truncate"><?php echo esc_html($label); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <div x-show="menu === 'bedrooms'" x-cloak x-on:click.outside="closeMenu($event)" class="contabai-menu w-80" x-bind:style="{ left: menuLeft + 'px' }">
            <div class="flex gap-2">
                <?php foreach ($bedBathOptions as $val => $label): ?>
                <button type="button" x-on:click="pickVal('bedrooms', '<?php echo esc_js($val); ?>')" class="contabai-seg" x-bind:class="rail.bedrooms === '<?php echo esc_js($val); ?>' ? 'is-on' : ''"><?php echo esc_html($label); ?></button>
                <?php endforeach; ?>
            </div>
        </div>
        <div x-show="menu === 'rating'" x-cloak x-on:click.outside="closeMenu($event)" class="contabai-menu w-[22rem]" x-bind:style="{ left: menuLeft + 'px' }">
            <div class="flex gap-2">
                <?php foreach ($ratingOptions as $val => $label): ?>
                <button type="button" x-on:click="pickVal('min_rating', '<?php echo esc_js($val); ?>')" class="contabai-seg" x-bind:class="rail.min_rating === '<?php echo esc_js($val); ?>' ? 'is-on' : ''"><?php echo esc_html($label); ?></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="contabai-results-body mx-auto w-full px-4 pb-12 pt-6" x-bind:class="mapOpen ? 'is-split' : 'max-w-7xl'">
    <div class="min-w-0 scroll-mt-40" x-ref="results">
        <div class="mb-5 flex items-center justify-between gap-3">
            <h2 class="contabai-heading contabai-seo-place m-0 text-2xl text-neutral-900 sm:text-[28px]" x-text="(loading && ! listings.length && ! initialListings.length) ? '' : resultLabel()"><?php
                if ($initial['listings']) {
                    echo esc_html($initialTotal . ' ' . ($initialTotal === 1 ? __('place', 'contabai') : __('places', 'contabai')));
                }
            ?></h2>
            <div class="contabai-lt-lg relative shrink-0">
                <button type="button" data-menu-trigger x-on:click="toggleMenu('sort')" class="inline-flex h-11 items-center gap-1.5 text-sm text-neutral-600">
                    <span class="font-semibold text-neutral-900" x-text="sortLabel()"><?php echo esc_html($sortOptions['']); ?></span>
                    <?php echo \Contabai\Heroicon::outline('chevron-down', 'w-4 h-4'); ?>
                </button>
                <div x-show="menu === 'sort'" x-cloak x-on:click.outside="closeMenu($event)" class="contabai-menu right-0 w-64 p-2">
                    <?php foreach ($sortOptions as $val => $label): ?>
                    <button type="button" x-on:click="setSort('<?php echo esc_js($val); ?>'); menu = ''"
                            class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2.5 text-left text-sm text-neutral-800 transition hover:bg-neutral-100">
                        <?php echo esc_html($label); ?>
                        <span x-show="(rail.sort || '') === '<?php echo esc_js($val); ?>'"><?php echo \Contabai\Heroicon::outline('check', 'w-4 h-4'); ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <template x-if="error">
            <p class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-700" x-text="error"></p>
        </template>

        <div class="contabai-results-grid" x-show="<?php echo $initial['listings'] ? 'initialListings.length > 0' : 'loading && listings.length === 0'; ?>">
            <?php if ($initial['listings']): ?>
                <?php foreach ($initial['listings'] as $item) { include __DIR__ . '/../components/listing-card-static.php'; } ?>
            <?php else: ?>
            <?php for ($skeleton = 0; $skeleton < (int) $skeletonCount; $skeleton++) : ?>
                <div class="animate-pulse">
                    <div class="aspect-[4/3] rounded-2xl bg-neutral-100"></div>
                    <div class="h-34 pt-2.5">
                        <div class="h-4 w-3/4 rounded bg-neutral-100"></div>
                        <div class="mt-2.5 h-3 w-1/2 rounded bg-neutral-100"></div>
                        <div class="mt-3 h-3 w-1/3 rounded bg-neutral-100"></div>
                        <div class="mt-3 h-3 w-2/3 rounded bg-neutral-100"></div>
                        <div class="mt-3 h-4 w-1/3 rounded bg-neutral-100"></div>
                    </div>
                </div>
            <?php endfor; ?>
            <?php endif; ?>
        </div>

        <div class="contabai-results-grid" x-show="listings.length > 0" x-cloak>
            <template x-for="listing in listings" x-bind:key="listing.id">
                <div>
                    <?php $cardMap = ! empty($mapEnabled); include __DIR__ . '/../components/listing-card.php'; ?>
                </div>
            </template>
        </div>

        <template x-if="listings.length === 0 && !loading && !error && !initialListings.length">
            <p class="py-16 text-center text-neutral-500"><?php echo esc_html__('No listings found.', 'contabai'); ?></p>
        </template>

        <div x-show="loading && listings.length > 0" x-cloak class="py-16 text-center text-sm text-neutral-500"><?php echo esc_html__('Loading...', 'contabai'); ?></div>

        <?php include __DIR__ . '/../components/pagination.php'; ?>
    </div>

    <?php if (! empty($mapEnabled)): ?>
    <section class="contabai-map-pane" x-show="mapOpen" x-cloak x-ref="mapBand" aria-label="<?php echo esc_attr__('Map of the search results', 'contabai'); ?>">
        <div class="contabai-map" x-ref="map"></div>

        <div class="contabai-lt-lg absolute inset-x-3 top-3 z-10 flex items-center gap-2">
            <button type="button" x-on:click="closeMap()" aria-label="<?php echo esc_attr__('Back to the list', 'contabai'); ?>"
                    class="contabai-map-float flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white text-neutral-900"><?php echo \Contabai\Heroicon::outline('arrow-left', 'w-5 h-5'); ?></button>
            <button type="button" x-on:click="openSearch()" class="contabai-map-float flex h-12 min-w-0 flex-1 items-center rounded-full bg-white px-5 text-left text-sm font-semibold text-neutral-900">
                <span class="truncate" x-text="searchSummary()"></span>
            </button>
            <button type="button" x-on:click="openFilters('')" aria-label="<?php echo esc_attr__('Filters', 'contabai'); ?>"
                    class="contabai-map-float relative flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white text-neutral-900">
                <?php echo \Contabai\Heroicon::outline('adjustments-horizontal', 'w-5 h-5'); ?>
                <span x-show="railCount()" x-cloak class="contabai-accent-bg absolute -right-0.5 -top-0.5 inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-xs font-semibold text-white" x-text="railCount()"></span>
            </button>
        </div>

        <div class="contabai-map-zoom pointer-events-auto absolute right-4 top-4 z-10 overflow-hidden rounded-xl bg-white">
            <button type="button" x-on:click="zoomBy(1)" aria-label="<?php echo esc_attr__('Zoom in', 'contabai'); ?>"
                    class="flex h-11 w-11 items-center justify-center border-b border-neutral-200 text-neutral-800 transition hover:bg-neutral-100"><?php echo \Contabai\Heroicon::outline('plus', 'w-5 h-5'); ?></button>
            <button type="button" x-on:click="zoomBy(-1)" aria-label="<?php echo esc_attr__('Zoom out', 'contabai'); ?>"
                    class="flex h-11 w-11 items-center justify-center text-neutral-800 transition hover:bg-neutral-100"><?php echo \Contabai\Heroicon::outline('minus', 'w-5 h-5'); ?></button>
        </div>

        <div class="contabai-map-credits flex items-center gap-1.5 rounded-md bg-white/90 px-1.5 py-1 text-xs text-neutral-700" x-ref="credits" x-bind:class="! isLg && selectedListing() ? 'is-raised' : ''">
            <span x-show="creditsOpen" class="[&_a]:underline"><?php echo wp_kses((string) ($mapAttribution ?? ''), ['a' => ['href' => []]]); ?></span>
            <button type="button" x-on:click="creditsOpen = ! creditsOpen" x-bind:aria-expanded="creditsOpen" aria-label="<?php echo esc_attr__('Map credits', 'contabai'); ?>" title="<?php echo esc_attr__('Map credits', 'contabai'); ?>"
                    class="flex h-5 w-5 items-center justify-center text-neutral-700 hover:text-neutral-900"><?php echo \Contabai\Heroicon::outline('eye', 'w-4 h-4'); ?></button>
        </div>

        <template x-if="isLg && popupVisible && selectedListing()">
            <div class="contabai-map-popup" x-bind:style="'left: ' + popupX + 'px; top: ' + popupY + 'px'">
                <div class="relative w-[272px] overflow-hidden rounded-2xl bg-white">
                    <img x-show="slides(selectedListing()).length" x-bind:src="(slides(selectedListing())[0] || {}).card" x-bind:alt="(slides(selectedListing())[0] || {}).alt" class="block h-40 w-[272px] object-cover">
                    <button type="button" x-on:click="selectedId = null" aria-label="<?php echo esc_attr__('Close', 'contabai'); ?>"
                            class="absolute right-2.5 top-2.5 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 text-neutral-700 transition hover:bg-white"><?php echo \Contabai\Heroicon::outline('x-mark', 'w-4 h-4'); ?></button>
                    <a x-bind:href="detailUrl(selectedListing())" class="block px-3.5 pb-3.5 pt-3 no-underline">
                        <p class="truncate text-[15px] font-semibold text-neutral-900" x-text="selectedListing().title"></p>
                        <p class="truncate text-[13px] text-neutral-600" x-text="locationLabel(selectedListing())"></p>
                        <p class="mt-1.5 flex items-center gap-1 text-[13px] text-neutral-600">
                            <span x-show="reviewCount(selectedListing()) > 0" class="text-amber-400"><?php echo \Contabai\Heroicon::solid('star', 'w-4 h-4'); ?></span>
                            <span x-show="reviewCount(selectedListing()) > 0" class="font-medium text-neutral-900" x-text="ratingLabel(selectedListing())"></span>
                            <span class="truncate" x-text="reviewsLabel(selectedListing())"></span>
                        </p>
                        <p class="mt-2 flex items-baseline gap-1 text-sm text-neutral-600">
                            <span><?php echo esc_html__('from', 'contabai'); ?></span>
                            <span class="font-bold text-neutral-900" x-text="priceLabel(selectedListing())"></span>
                            <span><?php echo esc_html__('/ night', 'contabai'); ?></span>
                        </p>
                    </a>
                </div>
            </div>
        </template>

        <button type="button" x-on:click="closeMap()" class="contabai-lt-lg contabai-map-float contabai-accent-bg absolute left-1/2 z-10 flex h-12 -translate-x-1/2 items-center gap-2 rounded-full px-5 text-sm font-semibold text-white"
                x-bind:class="selectedListing() ? 'bottom-48' : 'bottom-[4.5rem]'">
            <?php echo esc_html__('List', 'contabai'); ?><?php echo \Contabai\Heroicon::outline('list-bullet', 'w-5 h-5'); ?>
        </button>

        <template x-if="! isLg && selectedListing()">
            <a x-bind:href="detailUrl(selectedListing())" class="contabai-map-preview absolute inset-x-3 bottom-4 z-10 flex gap-3 rounded-2xl bg-white p-2.5 no-underline">
                <img x-show="slides(selectedListing()).length" x-bind:src="(slides(selectedListing())[0] || {}).card" x-bind:alt="(slides(selectedListing())[0] || {}).alt" class="h-24 w-24 flex-none rounded-xl object-cover">
                <span class="flex min-w-0 flex-col justify-center">
                    <span class="truncate text-[15px] font-semibold text-neutral-900" x-text="selectedListing().title"></span>
                    <span class="truncate text-[13px] text-neutral-600" x-text="locationLabel(selectedListing())"></span>
                    <span class="mt-1 flex items-center gap-1 text-[13px] text-neutral-600">
                        <span x-show="reviewCount(selectedListing()) > 0" class="text-amber-400"><?php echo \Contabai\Heroicon::solid('star', 'w-4 h-4'); ?></span>
                        <span x-show="reviewCount(selectedListing()) > 0" class="font-medium text-neutral-900" x-text="ratingLabel(selectedListing())"></span>
                        <span class="truncate" x-text="reviewsLabel(selectedListing())"></span>
                    </span>
                    <span class="mt-1 flex items-baseline gap-1 text-sm text-neutral-600">
                        <span><?php echo esc_html__('from', 'contabai'); ?></span>
                        <span class="font-bold text-neutral-900" x-text="priceLabel(selectedListing())"></span>
                        <span><?php echo esc_html__('/ night', 'contabai'); ?></span>
                    </span>
                </span>
            </a>
        </template>
    </section>
    <?php endif; ?>
</div>

<?php if (! empty($mapEnabled)): ?>
<button type="button" x-show="! mapOpen && hasPins()" x-cloak x-on:click="openMap()"
        class="contabai-lt-lg contabai-map-fab contabai-accent-bg flex h-12 items-center gap-2 rounded-full px-5 text-sm font-semibold text-white">
    <?php echo esc_html__('Map', 'contabai'); ?><?php echo \Contabai\Heroicon::outline('map', 'w-5 h-5'); ?>
</button>
<?php endif; ?>

<div x-show="filtersOpen" x-cloak x-on:click="filtersOpen = false" class="contabai-filters-backdrop"></div>
<section role="dialog" aria-modal="true" aria-label="<?php echo esc_attr__('Filters', 'contabai'); ?>" x-show="filtersOpen" x-cloak class="contabai-filters-dialog">
    <div class="relative flex h-16 shrink-0 items-center border-b border-neutral-200 px-3">
        <button type="button" x-on:click="filtersOpen = false" aria-label="<?php echo esc_attr__('Close', 'contabai'); ?>"
                class="flex h-11 w-11 items-center justify-center rounded-full text-neutral-900 transition hover:bg-neutral-100"><?php echo \Contabai\Heroicon::outline('x-mark', 'w-5 h-5'); ?></button>
        <h2 class="pointer-events-none absolute inset-x-0 m-0 text-center text-base font-bold text-neutral-900"><?php echo esc_html__('Filters', 'contabai'); ?></h2>
    </div>

    <div class="min-h-0 flex-1 overflow-y-auto px-5" x-ref="filtersBody">
        <?php if (! empty($propertyTypes)): ?>
        <fieldset class="m-0 scroll-mt-2 border-0 border-b border-solid border-neutral-200 px-0 py-6" x-ref="filters_type">
            <legend class="contabai-heading contabai-seo-place float-left mb-4 w-full p-0 text-xl text-neutral-900"><?php echo esc_html__('Property type', 'contabai'); ?></legend>
            <div class="clear-left flex flex-wrap gap-2">
                <?php foreach ($propertyTypes as $key => $label): ?>
                <button type="button" x-on:click="toggleArr('property_type', '<?php echo esc_js($key); ?>')" class="contabai-chip"
                        x-bind:class="inArr('property_type', '<?php echo esc_js($key); ?>') ? 'is-on' : ''"
                        x-bind:aria-pressed="inArr('property_type', '<?php echo esc_js($key); ?>')"><?php echo esc_html($label); ?></button>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <?php endif; ?>

        <?php foreach (['bedrooms' => __('Bedrooms', 'contabai'), 'bathrooms' => __('Bathrooms', 'contabai'), 'min_rating' => __('Review score', 'contabai')] as $field => $legend): ?>
        <fieldset class="m-0 scroll-mt-2 border-0 border-b border-solid border-neutral-200 px-0 py-6" x-ref="filters_<?php echo $field === 'min_rating' ? 'rating' : esc_attr($field); ?>">
            <legend class="contabai-heading contabai-seo-place float-left mb-4 w-full p-0 text-xl text-neutral-900"><?php echo esc_html($legend); ?></legend>
            <div class="clear-left flex gap-2">
                <?php foreach (($field === 'min_rating' ? $ratingOptions : $bedBathOptions) as $val => $label): ?>
                <button type="button" x-on:click="pickVal('<?php echo esc_js($field); ?>', '<?php echo esc_js($val); ?>')" class="contabai-seg"
                        x-bind:class="rail.<?php echo esc_attr($field); ?> === '<?php echo esc_js($val); ?>' ? 'is-on' : ''"><?php echo esc_html($label); ?></button>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <?php endforeach; ?>

        <?php if (! empty($amenityFlat)): ?>
        <fieldset class="m-0 scroll-mt-2 border-0 px-0 py-6" x-ref="filters_amenities" x-data="{ openCat: '' }">
            <legend class="contabai-heading contabai-seo-place float-left mb-4 w-full p-0 text-xl text-neutral-900"><?php echo esc_html__('Amenities', 'contabai'); ?></legend>
            <div class="clear-left flex flex-wrap gap-2">
                <?php foreach (($popularAmenities ?? []) as $key): ?>
                    <?php if (! isset($amenityFlat[$key])) { continue; } ?>
                    <button type="button" x-on:click="toggleArr('amenities', '<?php echo esc_js($key); ?>')" class="contabai-chip"
                            x-bind:class="inArr('amenities', '<?php echo esc_js($key); ?>') ? 'is-on' : ''"
                            x-bind:aria-pressed="inArr('amenities', '<?php echo esc_js($key); ?>')"><?php echo esc_html($amenityFlat[$key]); ?></button>
                <?php endforeach; ?>
            </div>

            <div class="mt-5 border-t border-neutral-200">
                <?php foreach (($amenitiesCatalogue ?? []) as $groupKey => $items): ?>
                    <?php if (! is_array($items) || $items === []) { continue; } ?>
                    <?php $catKeys = wp_json_encode(array_keys($items)); ?>
                    <?php $catId = esc_js($groupKey); ?>
                    <div class="border-b border-neutral-200">
                        <button type="button" x-on:click="openCat = (openCat === '<?php echo $catId; ?>' ? '' : '<?php echo $catId; ?>')" class="flex w-full items-center justify-between gap-2 py-3 text-left">
                            <span class="min-w-0 truncate text-sm font-medium text-neutral-800"><?php echo esc_html($amenityCategories[$groupKey] ?? ucfirst(str_replace('_', ' ', (string) $groupKey))); ?></span>
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
        </fieldset>
        <?php endif; ?>
    </div>

    <div class="flex h-20 shrink-0 items-center justify-between gap-3 border-t border-neutral-200 px-5">
        <button type="button" x-on:click="clearRail()" class="h-11 px-1 text-base font-semibold text-neutral-900 underline"><?php echo esc_html__('Clear all', 'contabai'); ?></button>
        <button type="button" x-on:click="filtersOpen = false" class="contabai-accent-bg h-12 rounded-xl px-6 text-base font-semibold text-white transition hover:opacity-90" x-text="showLabel()"></button>
    </div>
</section>
</div>

<?php include __DIR__ . '/../components/listing-card-helpers.php'; ?>
<script>
document.addEventListener('alpine:init', function () {
    Alpine.data('contabaiListings', function () {
        // Leaflet objects stay out of Alpine's reactive proxy, which breaks them.
        let map = null;
        let pinLayer = null;
        let pins = {};
        let lastPoints = [];
        let leafletLoading = null;

        return {
            ...window.contabaiCardHelpers({
                propertyTypes: <?php echo wp_json_encode($propertyTypes); ?>,
                countries: <?php echo wp_json_encode($countries); ?>,
                detailBase: '<?php echo esc_url($detailBase); ?>'
            }),

            listings: [],
            initialListings: <?php echo wp_json_encode($initial['listings'], JSON_HEX_TAG); ?>,
            currentPage: 1,
            lastPage: <?php echo (int) $initial['last_page']; ?>,
            total: <?php echo $initialTotal; ?>,
            perPage: <?php echo (int) $initialPerPage; ?>,
            loading: false,
            error: '',
            filters: {},
            baseFilters: {},
            rail: { property_type: [], amenities: [], bedrooms: '', bathrooms: '', min_rating: '', sort: '' },
            sortOptions: <?php echo wp_json_encode((object) $sortOptions); ?>,
            menu: '',
            menuLeft: 0,
            filtersOpen: false,
            searchHidden: false,
            isLg: window.matchMedia('(min-width: 1024px)').matches,
            lockedFilters: <?php echo wp_json_encode((object) ($lockedFilters ?? [])); ?>,
            endpoint: '<?php echo esc_url($endpoint); ?>',
            mapEnabled: <?php echo ! empty($mapEnabled) ? 'true' : 'false'; ?>,
            mapOpen: false,
            isSm: window.matchMedia('(min-width: 640px)').matches,
            hoveredId: null,
            selectedId: null,
            creditsOpen: true,
            popupVisible: false,
            popupX: 0,
            popupY: 0,

            setupResponsive: function () {
                let self = this;
                let mediaQuery = window.matchMedia('(min-width: 1024px)');
                let handler = function (e) { self.isLg = e.matches; self.menu = ''; };
                if (mediaQuery.addEventListener) { mediaQuery.addEventListener('change', handler); } else { mediaQuery.addListener(handler); }
                let smQuery = window.matchMedia('(min-width: 640px)');
                let smHandler = function (e) { self.isSm = e.matches; };
                if (smQuery.addEventListener) { smQuery.addEventListener('change', smHandler); } else { smQuery.addListener(smHandler); }

                // The filter bar and the map stick below the theme's header, whose height changes as it shrinks on scroll.
                let header = document.querySelector('.contabai-header');
                // The filter bar takes the page's own colour, so it looks like no bar at all but still hides the cards scrolling under it.
                let pageBg = getComputedStyle(document.body).backgroundColor;
                if (pageBg && pageBg !== 'rgba(0, 0, 0, 0)' && pageBg !== 'transparent') { self.$refs.root.style.setProperty('--contabai-page-bg', pageBg); }
                let queued = false;
                let measure = function () {
                    queued = false;
                    let top = header && getComputedStyle(header).position === 'sticky' ? header.getBoundingClientRect().height : 0;
                    self.$refs.root.style.setProperty('--contabai-sticky-top', top + 'px');
                    self.$refs.root.style.setProperty('--contabai-toolbar-h', self.$refs.toolbar.offsetHeight + 'px');
                };
                let queue = function () { if (! queued) { queued = true; requestAnimationFrame(measure); } };
                measure();
                window.addEventListener('resize', queue);
                window.addEventListener('scroll', queue, { passive: true });
                if (header) { header.addEventListener('transitionend', queue); }
            },

            watchSearchBar: function () {
                let self = this;
                let bar = self.$refs.searchBar;
                if (! bar) { return; }
                new IntersectionObserver(function (entries) {
                    self.searchHidden = ! entries[0].isIntersecting && entries[0].boundingClientRect.top < 0;
                }).observe(bar);
            },

            openSearch: function () {
                if (! this.isLg) { this.closeMap(); }
                if (this.$refs.searchBar) { this.$refs.searchBar.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
                // After this click has finished bubbling, so the search bar's click-outside doesn't close it again.
                setTimeout(function () { window.dispatchEvent(new CustomEvent('contabai-search-open')); }, 0);
            },

            placeName: function () {
                let countryData = this.countries[this.baseFilters.country];
                let region = countryData && countryData.regions && countryData.regions[this.baseFilters.region];
                let area = region && region.areas && region.areas[this.baseFilters.area];
                return area || (region && region.name) || (countryData && countryData.name) || '';
            },

            fmtDate: function (iso) {
                try { return new Date(iso + 'T00:00:00').toLocaleDateString('<?php echo esc_js(\Contabai\Helper::currentLang()); ?>', { day: 'numeric', month: 'short' }); }
                catch (e) { return iso; }
            },

            searchSummary: function () {
                let from = this.baseFilters.from_date;
                let till = this.baseFilters.till_date;
                let guests = parseInt(this.baseFilters.guests, 10);
                return [
                    this.placeName() || '<?php echo esc_js(__('Any place', 'contabai')); ?>',
                    from ? (till ? this.fmtDate(from) + ' → ' + this.fmtDate(till) : this.fmtDate(from)) : '<?php echo esc_js(__('Any dates', 'contabai')); ?>',
                    guests > 0 ? this.guestsLabel(guests) : '<?php echo esc_js(__('Add guests', 'contabai')); ?>'
                ].join(' · ');
            },

            toggleMenu: function (key) {
                this.menu = this.menu === key ? '' : key;
            },

            chipMenu: function (key, event) {
                if (! this.isLg) { this.openFilters(key); return; }
                if (this.menu === key) { this.menu = ''; return; }
                let chip = event.currentTarget.getBoundingClientRect();
                let bar = this.$refs.toolbarInner.getBoundingClientRect();
                this.menuLeft = Math.max(16, Math.min(chip.left - bar.left, bar.width - 368));
                this.menu = key;
            },

            closeMenu: function (event) {
                if (event.target.closest('[data-menu-trigger]')) { return; }
                this.menu = '';
            },

            openFilters: function (section) {
                let self = this;
                self.menu = '';
                self.filtersOpen = true;
                self.$nextTick(function () {
                    let target = section ? self.$refs['filters_' + section] : null;
                    if (target) { target.scrollIntoView({ block: 'start' }); } else { self.$refs.filtersBody.scrollTop = 0; }
                });
            },

            sortLabel: function () {
                return this.sortOptions[this.rail.sort || ''] || this.sortOptions[''];
            },

            railCount: function () {
                let rail = this.rail;
                return rail.property_type.length + rail.amenities.length + (rail.bedrooms ? 1 : 0) + (rail.bathrooms ? 1 : 0) + (rail.min_rating ? 1 : 0);
            },

            showLabel: function () {
                let label = this.total === 1
                    ? '<?php echo esc_js(_n('Show %d place', 'Show %d places', 1, 'contabai')); ?>'
                    : '<?php echo esc_js(_n('Show %d place', 'Show %d places', 2, 'contabai')); ?>';
                return label.replace('%d', this.total);
            },

            loadLeaflet: function () {
                if (window.L) { return Promise.resolve(); }
                if (! leafletLoading) {
                    leafletLoading = new Promise(function (resolve, reject) {
                        let css = document.createElement('link');
                        css.rel = 'stylesheet';
                        css.href = <?php echo wp_json_encode($leafletCss ?? ''); ?>;
                        document.head.appendChild(css);
                        let js = document.createElement('script');
                        js.src = <?php echo wp_json_encode($leafletJs ?? ''); ?>;
                        js.onload = resolve;
                        js.onerror = function () { leafletLoading = null; reject(); };
                        document.head.appendChild(js);
                    });
                }
                return leafletLoading;
            },

            openMap: function () {
                let self = this;
                self.mapOpen = true;
                self.loadLeaflet().then(function () {
                    self.$nextTick(function () {
                        if (! map) { self.initMap(); return; }
                        map.invalidateSize();
                        self.fitPins(lastPoints);
                    });
                }).catch(function () { self.mapOpen = false; });
            },

            closeMap: function () {
                this.mapOpen = false;
                this.selectedId = null;
            },

            initMap: function () {
                let self = this;
                if (! window.L || ! self.$refs.map) { return; }
                map = L.map(self.$refs.map, { zoomControl: false, attributionControl: false, scrollWheelZoom: false, zoomSnap: 0.5 });
                L.tileLayer(<?php echo wp_json_encode($mapTilesUrl ?? ''); ?>, <?php echo wp_json_encode($mapTileOptions ?? ['maxZoom' => 19]); ?>).addTo(map);
                pinLayer = L.layerGroup().addTo(map);
                // OSMF attribution guidelines: the credit may fold after five seconds or on the first map interaction.
                // Only a visitor's pointer counts here; the map also moves by itself when the pins are re-fitted.
                let band = self.$refs.mapBand;
                let creditsTimer = null;
                let foldCredits = function (event) {
                    if (event && self.$refs.credits.contains(event.target)) { return; }
                    clearTimeout(creditsTimer);
                    band.removeEventListener('pointerdown', foldCredits);
                    self.creditsOpen = false;
                };
                band.addEventListener('pointerdown', foldCredits);
                creditsTimer = setTimeout(foldCredits, 5000);
                map.on('move zoomend resize', function () { self.placePopup(); });
                map.on('zoomstart', function () { self.popupVisible = false; });
                map.on('click', function () { self.selectedId = null; });
                self.$watch('listings', function () { self.drawPins(); });
                self.$watch('hoveredId', function () { self.paintPins(); });
                self.$watch('selectedId', function () { self.paintPins(); self.revealSelected(); });
                self.$watch('isSm', function () { self.paintPins(); });
                self.$watch('isLg', function () {
                    self.popupVisible = false;
                    self.$nextTick(function () { map.invalidateSize(); self.fitPins(lastPoints); self.placePopup(); });
                });
                self.drawPins();
            },

            pinSource: function () {
                return this.listings.length ? this.listings : this.initialListings;
            },

            hasPin: function (listing) {
                return typeof listing.latitude === 'number' && typeof listing.longitude === 'number';
            },

            hasPins: function () {
                return this.pinSource().some(this.hasPin);
            },

            pinLabel: function (listing) {
                let title = listing.title || '';
                let price = this.priceLabel(listing);
                return '<?php echo esc_js(__('%1$s, from %2$s a night', 'contabai')); ?>'
                    .replace('%1$s', function () { return title; })
                    .replace('%2$s', function () { return price; });
            },

            drawPins: function () {
                let self = this;
                if (! map) { return; }
                pinLayer.clearLayers();
                pins = {};
                let points = [];
                self.pinSource().forEach(function (listing) {
                    if (! self.hasPin(listing)) { return; }
                    let button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'contabai-pin';
                    button.setAttribute('aria-label', self.pinLabel(listing));
                    button.textContent = (listing.currency || 'EUR') + ' ' + Math.round((listing.from_price_cents || 0) / 100);
                    let latLng = [listing.latitude, listing.longitude];
                    let marker = L.marker(latLng, { icon: L.divIcon({ className: 'contabai-pin-icon', html: button, iconSize: null }), keyboard: false });
                    marker.on('click', function () { self.selectedId = listing.id; });
                    marker.addTo(pinLayer);
                    pins[listing.id] = { marker: marker, el: button };
                    points.push(latLng);
                });
                if (self.selectedId !== null && ! pins[self.selectedId]) { self.selectedId = null; }
                lastPoints = points;
                self.paintPins();
                self.fitPins(points);
            },

            paintPins: function () {
                let self = this;
                Object.keys(pins).forEach(function (id) {
                    let pin = pins[id];
                    let selected = String(self.selectedId) === id;
                    let hovered = ! selected && String(self.hoveredId) === id;
                    pin.el.classList.toggle('is-selected', selected);
                    pin.el.classList.toggle('is-hovered', hovered);
                    pin.el.classList.toggle('is-dot', ! self.isSm && ! selected);
                    pin.marker.setZIndexOffset(selected ? 1000 : (hovered ? 500 : 0));
                });
            },

            fitPins: function (points) {
                if (! map || ! points.length) { return; }
                // Pins hang above their point and are up to ~110px wide, so the edges need more room than the points alone.
                map.fitBounds(points, this.isLg
                    ? { paddingTopLeft: [60, 50], paddingBottomRight: [60, 20], maxZoom: 15 }
                    : { paddingTopLeft: [50, 170], paddingBottomRight: [50, 200], maxZoom: 15 });
            },

            revealSelected: function () {
                let pin = map && this.selectedId !== null ? pins[this.selectedId] : null;
                if (pin && this.isLg) {
                    map.panInside(pin.marker.getLatLng(), { paddingTopLeft: [150, 350], paddingBottomRight: [150, 40] });
                } else if (pin) {
                    map.panInside(pin.marker.getLatLng(), { paddingTopLeft: [40, 150], paddingBottomRight: [40, 230] });
                }
                this.placePopup();
            },

            placePopup: function () {
                let pin = map && this.selectedId !== null ? pins[this.selectedId] : null;
                if (! pin || ! this.isLg) { this.popupVisible = false; return; }
                let point = map.latLngToContainerPoint(pin.marker.getLatLng());
                this.popupX = Math.round(point.x);
                this.popupY = Math.round(point.y);
                this.popupVisible = true;
            },

            selectedListing: function () {
                let id = this.selectedId;
                if (id === null) { return null; }
                return this.pinSource().find(function (listing) { return listing.id === id; }) || null;
            },

            zoomBy: function (step) {
                if (! map) { return; }
                if (step > 0) { map.zoomIn(); } else { map.zoomOut(); }
            },

            // Location/date/guest filters come from the search bar's URL and stay fixed here;
            // the filter bar owns type/amenities/beds/baths/rating/sort. Locked filters always win.
            readFilters: function () {
                let queryParams = new URLSearchParams(window.location.search);
                let get = function (key) { let value = queryParams.get(key); return (value !== null && value !== '') ? value : ''; };
                let base = {};
                ['country', 'region', 'area', 'from_date', 'till_date', 'guests', 'price_min', 'price_max'].forEach(function (key) {
                    let value = get(key); if (value) { base[key] = value; }
                });
                this.baseFilters = Object.assign(base, this.lockedFilters);
                this.rail.property_type = queryParams.getAll('property_type[]').filter(Boolean);
                this.rail.amenities = queryParams.getAll('amenities[]').filter(Boolean);
                this.rail.bedrooms = get('bedrooms');
                this.rail.bathrooms = get('bathrooms');
                let rating = get('min_rating');
                this.rail.min_rating = ['1', '2', '3', '4', '5'].indexOf(rating) !== -1 ? rating : '';
                this.rail.sort = get('sort');
                this.buildFilters();
            },

            buildFilters: function () {
                let filters = Object.assign({}, this.baseFilters);
                if (this.rail.bedrooms) { filters.bedrooms = this.rail.bedrooms; }
                if (this.rail.bathrooms) { filters.bathrooms = this.rail.bathrooms; }
                if (this.rail.min_rating) { filters.min_rating = this.rail.min_rating; }
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
                if (self.rail.min_rating) { params.set('min_rating', self.rail.min_rating); }
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

            pickVal: function (key, val) {
                if (this.rail[key] === val) { return; }
                this.rail[key] = val;
                this.applyRail();
            },

            setSort: function (val) { this.rail.sort = val; this.applyRail(); },

            clearRail: function () {
                this.rail.property_type = [];
                this.rail.amenities = [];
                this.rail.bedrooms = '';
                this.rail.bathrooms = '';
                this.rail.min_rating = '';
                this.rail.sort = '';
                this.applyRail();
            },

            resultLabel: function () {
                let total = this.total;
                return total + ' ' + (total === 1 ? '<?php echo esc_js(__('place', 'contabai')); ?>' : '<?php echo esc_js(__('places', 'contabai')); ?>');
            },

            // Server-rendered first page: show it once the page (header photo included) has loaded,
            // so the card photos don't compete with the LCP image for bandwidth.
            hydrate: function () {
                let self = this;
                let show = function () {
                    if (! self.initialListings.length) { return; }
                    self.listings = self.initialListings;
                    self.initialListings = [];
                };
                if (document.readyState === 'complete') { show(); } else { window.addEventListener('load', show, { once: true }); setTimeout(show, 5000); }
            },

            load: function (page) {
                let self = this;
                self.initialListings = [];
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
