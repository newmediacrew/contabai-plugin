<?php

$photos = $listing['photos'] ?? [];
$propertyTypes = $labels['property_types'] ?? [];
$typeLabel = $propertyTypes[$listing['property_type']] ?? ucfirst((string) ($listing['property_type'] ?? ''));

$amenityLabels = [];
foreach (($labels['amenities_catalogue'] ?? []) as $group) {
    if (is_array($group)) {
        $amenityLabels += $group;
    }
}
$host = $listing['host'] ?? [];
$hostName = (string) (($host['company_name'] ?? '') ?: ($host['nickname'] ?? ''));

$bioMap  = $host['bio'] ?? null;
$hostBio = is_array($bioMap)
    ? (string) ($bioMap[\Contabai\Helper::currentLang()] ?? $bioMap['en'] ?? '')
    : (string) ($bioMap ?? '');

$hostAvatar = is_array($host['avatar'] ?? null)
    ? ($host['avatar']['256x256'] ?? $host['avatar']['original'] ?? '')
    : ($host['avatar'] ?? '');

$reviews       = $listing['reviews'] ?? ['average' => null, 'count' => 0, 'recent' => []];
$reviewAverage = $reviews['average'] ?? null;
$reviewCount   = (int) ($reviews['count'] ?? 0);
$reviewRecent  = $reviews['recent'] ?? [];

$currency = $listing['currency'] ?? '';
$priceLabel = function () use ($listing, $currency) {
    $amount = number_format(((int) ($listing['from_price_cents'] ?? 0)) / 100, 2);
    return ($currency ? $currency . ' ' : '') . $amount;
};

$formatHour = function ($hour) {
    return sprintf('%02d:00', (int) $hour);
};

$facts = array_filter([
    ['users', $listing['max_guests'] ?? null, __('guests', 'contabai')],
    ['home', $listing['bedrooms'] ?? null, __('bedrooms', 'contabai')],
    ['home-modern', $listing['bathrooms'] ?? null, __('bathrooms', 'contabai')],
    ['building-office-2', $listing['floors'] ?? null, __('floors', 'contabai')],
    ['squares-2x2', ! empty($listing['living_area_sqm']) ? $listing['living_area_sqm'] . ' m²' : null, __('living area', 'contabai')],
], function ($fact) {
    return $fact[1] !== null && $fact[1] !== '';
});

$crumbs = [
    ['label' => __('Home', 'contabai'), 'url' => home_url()],
];
if (! empty($listing['country'])) {
    $countryUrl = \Contabai\Controllers\LocationPagesController::permalink_for_key($listing['country']);
    $crumbs[] = ['label' => $listing['country_name'] ?? $listing['country'], 'url' => $countryUrl ?: null];
    if (! empty($listing['city'])) {
        $cityUrl = \Contabai\Controllers\LocationPagesController::permalink_for_key($listing['country'] . '/' . $listing['city']);
        $crumbs[] = ['label' => $listing['city_name'] ?? $listing['city'], 'url' => $cityUrl ?: null];
        if (! empty($listing['area'])) {
            $crumbs[] = ['label' => $listing['area_name'] ?? $listing['area'], 'url' => null];
        }
    }
}
$crumbs[] = ['label' => $listing['title'] ?? '', 'url' => null];
?>
<div class="mx-auto max-w-7xl px-4 py-8">

    <?php echo \Contabai\View::render('components.breadcrumb', ['crumbs' => $crumbs]); ?>

    <?php include __DIR__ . '/../components/image-gallery.php'; ?>

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900"><?php echo esc_html($listing['title'] ?? ''); ?></h1>
            <div class="mt-1 flex items-start gap-1 text-sm text-neutral-500">
                <?php echo \Contabai\Heroicon::outline('map-pin', 'w-4 h-4 shrink-0 mt-0.5'); ?>
                <span><?php echo esc_html(trim(implode(', ', array_filter([
                    $listing['address'] ?? '',
                    $listing['postcode'] ?? '',
                    $listing['city_name'] ?? $listing['city'] ?? '',
                    $listing['country_name'] ?? $listing['country'] ?? '',
                ])))); ?></span>
            </div>
        </div>
        <div class="text-right">
            <span class="text-sm text-neutral-500"><?php echo esc_html__('from', 'contabai'); ?></span>
            <span class="text-2xl font-bold text-neutral-900"><?php echo esc_html($priceLabel()); ?></span>
            <span class="text-sm text-neutral-500">/ <?php echo esc_html__('night', 'contabai'); ?></span>
        </div>
    </div>

    <div class="mt-2 flex flex-wrap gap-4">
        <?php foreach ($facts as $fact): ?>
            <div class="flex items-center gap-2 text-sm text-neutral-700">
                <?php echo \Contabai\Heroicon::outline($fact[0], 'w-4 h-4 text-neutral-400'); ?>
                <span><span class="font-semibold"><?php echo esc_html((string) $fact[1]); ?></span> <?php echo esc_html($fact[2]); ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($reviewCount > 0): ?>
        <a href="#reviews" x-data x-on:click.prevent="$dispatch('open-reviews')" class="mt-2 inline-flex items-center gap-2 text-sm text-neutral-700 no-underline hover:underline">
            <?php $sr_rating = $reviewAverage; $sr_size = 'w-4 h-4'; include __DIR__ . '/../components/star-rating.php'; ?>
            <span><span class="font-semibold"><?php echo esc_html(number_format((float) $reviewAverage, 1)); ?></span>
                · <?php echo esc_html(sprintf(_n('%d review', '%d reviews', $reviewCount, 'contabai'), $reviewCount)); ?></span>
        </a>
    <?php endif; ?>

    <?php if (! empty($host)): ?>
        <section class="mt-8 rounded-lg border border-neutral-200 bg-white p-5">
            <div class="flex items-center gap-5">
                <?php if (! empty($hostAvatar)): ?>
                    <img src="<?php echo esc_url($hostAvatar); ?>" alt="<?php echo esc_attr($hostName); ?>" class="h-20 w-20 rounded-full object-cover ring-2 ring-neutral-100">
                <?php endif; ?>
                <div>
                    <div class="text-xs uppercase tracking-wide text-neutral-400"><?php echo esc_html__('Host', 'contabai'); ?></div>
                    <div class="font-semibold text-neutral-900"><?php echo esc_html($hostName); ?></div>
                    <?php if (! empty($host['host_since'])): ?>
                        <div class="text-sm text-neutral-500"><?php echo esc_html(sprintf(__('Host since %s', 'contabai'), $host['host_since'])); ?></div>
                    <?php endif; ?>
                    <?php if (! empty($host['website'])): ?>
                        <a href="<?php echo esc_url($host['website']); ?>" target="_blank" rel="noopener"
                           class="inline-block text-sm font-medium text-neutral-600 no-underline hover:underline">
                            <?php echo esc_html(preg_replace('#^https?://#', '', (string) $host['website'])); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($hostBio !== ''): ?>
                <div class="mt-4 border-t border-neutral-200 pt-4" x-data="{ open: true }">
                    <button type="button" x-on:click="open = ! open" class="flex w-full items-center justify-between text-left">
                        <span class="text-sm font-semibold text-neutral-900"><?php echo esc_html__('About host', 'contabai'); ?></span>
                        <span class="text-neutral-400 transition" x-bind:class="open ? 'rotate-180' : ''"><?php echo \Contabai\Heroicon::outline('chevron-down', 'w-4 h-4'); ?></span>
                    </button>
                    <div x-show="open" x-collapse x-cloak>
                        <div class="prose prose-sm mt-3 max-w-none text-neutral-700"><?php echo wp_kses_post($hostBio); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <?php

            $hostId      = (int) ($host['host_id'] ?? 0);
            $msgLoggedIn = ! empty($_COOKIE['contabai_sanctum_session_token']);
            $msgLoginUrl = home_url('/' . CONTABAI_LOGIN_PAGE_SLUG);
            $msgChatUrl  = home_url('/' . CONTABAI_CHAT_PAGE_SLUG);
            $msgEndpoint = rest_url('contabai/v1/sanctum/guest/message/');
            ?>
            <?php if ($hostId): ?>
                <div class="mt-4 border-t border-neutral-200 pt-4">
                    <div class="mb-2 text-sm font-semibold text-neutral-900"><?php echo esc_html__('Chat with the host', 'contabai'); ?></div>
                    <?php if (! $msgLoggedIn): ?>
                        <a href="<?php echo esc_url($msgLoginUrl); ?>" class="inline-flex items-center gap-2 rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-800 no-underline transition hover:bg-neutral-50">
                            <?php echo \Contabai\Heroicon::outline('arrow-right-on-rectangle', 'w-4 h-4'); ?>
                            <?php echo esc_html__('Log in to chat', 'contabai'); ?>
                        </a>
                    <?php else: ?>
                        <div x-data="contabaiHostMessage()">
                            <div x-show="! sent">
                                <textarea x-model="body" rows="3" maxlength="2000"
                                          placeholder="<?php esc_attr_e('Write a message to the host…', 'contabai'); ?>"
                                          class="w-full resize-none rounded-lg border border-neutral-300 px-3 py-2 text-sm text-neutral-800 placeholder:text-neutral-400 focus:border-neutral-400 focus:outline-none"></textarea>
                                <div class="mt-2 flex items-center gap-3">
                                    <button type="button" x-on:click="send()" x-bind:disabled="sending || ! body.trim()"
                                            class="inline-flex items-center gap-2 rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-800 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50">
                                        <?php echo \Contabai\Heroicon::outline('paper-airplane', 'w-4 h-4'); ?>
                                        <span x-text="sending ? labels.sending : labels.send"></span>
                                    </button>
                                </div>
                                <p class="mt-2 text-xs text-neutral-400"><?php echo esc_html__('Mention the property so the host knows which listing you mean.', 'contabai'); ?></p>
                            </div>
                            <div x-show="sent" x-cloak class="rounded-lg border border-neutral-200 bg-neutral-50 p-3 text-sm text-neutral-700">
                                <?php echo esc_html__('Message sent.', 'contabai'); ?>
                                <a href="<?php echo esc_url($msgChatUrl); ?>" class="font-medium text-neutral-900 underline"><?php echo esc_html__('Go to your chats', 'contabai'); ?></a>
                            </div>
                        </div>
                        <script>
                        document.addEventListener('alpine:init', function () {
                            Alpine.data('contabaiHostMessage', function () {
                                return {
                                    hostId: <?php echo $hostId; ?>,
                                    endpoint: '<?php echo esc_js($msgEndpoint); ?>',
                                    labels: {
                                        send: '<?php echo esc_js(__('Send message', 'contabai')); ?>',
                                        sending: '<?php echo esc_js(__('Sending…', 'contabai')); ?>',
                                        sent: '<?php echo esc_js(__('Message sent.', 'contabai')); ?>',
                                        error: '<?php echo esc_js(__('Could not send your message.', 'contabai')); ?>',
                                        hostUnavailable: '<?php echo esc_js(__('This host is not available for chat right now.', 'contabai')); ?>'
                                    },
                                    body: '',
                                    sending: false,
                                    sent: false,
                                    send: function () {
                                        const self = this;
                                        const text = (self.body || '').trim();
                                        if (! text || self.sending) return;
                                        self.sending = true;
                                        fetch(self.endpoint + self.hostId, {
                                            method: 'POST',
                                            credentials: 'same-origin',
                                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                                            body: JSON.stringify({ body: text })
                                        })
                                            .then(function (r) { return r.json().then(function (res) { return { ok: r.ok, status: r.status, res: res }; }); })
                                            .then(function (out) {
                                                self.sending = false;
                                                if (! out.ok) {
                                                    if (out.status === 422) {
                                                        contabaiToast((out.res && out.res.errors) ? contabaiFieldText.body : self.labels.hostUnavailable, 'danger');
                                                    } else {
                                                        contabaiToast(contabaiStatusText(out.status, self.labels.error), 'danger');
                                                    }
                                                    return;
                                                }
                                                self.sent = true;
                                                contabaiToast(self.labels.sent, 'success');
                                            })
                                            .catch(function () { self.sending = false; contabaiToast(self.labels.error, 'danger'); });
                                    }
                                };
                            });
                        });
                        </script>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php

    $descMap = $listing['description'] ?? null;
    $description = is_array($descMap)
        ? (string) ($descMap[\Contabai\Helper::currentLang()] ?? $descMap['en'] ?? '')
        : (string) ($descMap ?? '');
    ?>
    <?php

    $rulesMap = $listing['house_rules'] ?? null;
    $houseRules = is_array($rulesMap)
        ? (string) ($rulesMap[\Contabai\Helper::currentLang()] ?? $rulesMap['en'] ?? '')
        : (string) ($rulesMap ?? '');

    $cancelMap = $listing['cancellation_policy'] ?? null;
    $cancellationPolicy = is_array($cancelMap)
        ? (string) ($cancelMap[\Contabai\Helper::currentLang()] ?? $cancelMap['en'] ?? '')
        : (string) ($cancelMap ?? '');
    ?>
        <!-- Content accordion — Description, Amenities, House rules, Cancellation, Video, Reviews: ONE panel open at a time; Description open by default. Always rendered (Reviews is always a member). -->
        <div class="mt-3 space-y-3" x-data="{ open: 'description', toggle(k) { this.open = this.open === k ? '' : k } }"
             x-on:open-reviews.window="open = 'reviews'; $nextTick(() => document.getElementById('reviews').scrollIntoView({ behavior: 'smooth', block: 'start' }))">
            <?php if ($description !== ''): ?>
                <section class="rounded-lg border border-neutral-200 bg-white" x-bind:class="open === 'description' ? '' : 'overflow-hidden'">
                    <button type="button" x-on:click="toggle('description')" class="flex w-full items-center justify-between px-5 py-4 text-left">
                        <h2 class="text-base font-semibold text-neutral-900"><?php echo esc_html__('Description', 'contabai'); ?></h2>
                        <span class="text-neutral-400 transition" x-bind:class="open === 'description' ? 'rotate-180' : ''"><?php echo \Contabai\Heroicon::outline('chevron-down', 'w-4 h-4'); ?></span>
                    </button>
                    <div x-show="open === 'description'" x-collapse>
                        <div class="border-t border-neutral-200 px-5 py-5">
                            <div class="prose prose-sm max-w-none text-neutral-700"><?php echo wp_kses_post($description); ?></div>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
            <?php if (! empty($listing['amenities'])): ?>
                <section class="rounded-lg border border-neutral-200 bg-white" x-bind:class="open === 'amenities' ? '' : 'overflow-hidden'">
                    <button type="button" x-on:click="toggle('amenities')" class="flex w-full items-center justify-between px-5 py-4 text-left">
                        <h2 class="text-base font-semibold text-neutral-900"><?php echo esc_html__('Amenities', 'contabai'); ?></h2>
                        <span class="text-neutral-400 transition" x-bind:class="open === 'amenities' ? 'rotate-180' : ''"><?php echo \Contabai\Heroicon::outline('chevron-down', 'w-4 h-4'); ?></span>
                    </button>
                    <div x-show="open === 'amenities'" x-collapse x-cloak>
                        <div class="border-t border-neutral-200 px-5 py-5">
                            <div class="flex flex-wrap gap-x-8 gap-y-2">
                                <?php foreach ($listing['amenities'] as $amenityKey): ?>
                                    <div class="flex items-center gap-2 text-sm text-neutral-700">
                                        <?php echo \Contabai\Heroicon::outline('check-circle', 'w-4 h-4 shrink-0 text-green-600'); ?>
                                        <span><?php echo esc_html($amenityLabels[$amenityKey] ?? ucfirst(str_replace('_', ' ', (string) $amenityKey))); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
            <?php
            if ($houseRules !== '') {
                $acc_key   = 'house_rules';
                $acc_title = __('House rules', 'contabai');
                $acc_body  = '<div class="prose prose-sm max-w-none text-neutral-700">' . wp_kses_post($houseRules) . '</div>';
                include __DIR__ . '/../components/accordion-section.php';
            }
            if ($cancellationPolicy !== '') {
                $acc_key   = 'cancellation_policy';
                $acc_title = __('Cancellation policy', 'contabai');
                $acc_body  = '<div class="prose prose-sm max-w-none text-neutral-700">' . wp_kses_post($cancellationPolicy) . '</div>';
                include __DIR__ . '/../components/accordion-section.php';
            }
            if (! empty($listing['video_url'])) {
                $acc_key   = 'video';
                $acc_title = __('Video', 'contabai');
                ob_start(); ?>
                <div class="aspect-video overflow-hidden rounded-lg bg-black">
                    <iframe src="<?php echo esc_url($listing['video_url']); ?>" class="h-full w-full" frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
                <?php
                $acc_body = ob_get_clean();
                include __DIR__ . '/../components/accordion-section.php';
            }
            ?>
            <?php

            $reviewsListingId = (int) ($listing['id'] ?? 0);
            $reviewsPerPage   = 5;
            $reviewsEndpoint  = rest_url('contabai/v1/sanctum/listing/reviews/' . $reviewsListingId);
            ?>
            <section id="reviews" class="rounded-lg border border-neutral-200 bg-white" x-bind:class="open === 'reviews' ? '' : 'overflow-hidden'">
                <button type="button" x-on:click="toggle('reviews')" class="flex w-full items-center justify-between px-5 py-4 text-left">
                    <h2 class="text-base font-semibold text-neutral-900"><?php echo esc_html__('Reviews', 'contabai'); ?></h2>
                    <span class="text-neutral-400 transition" x-bind:class="open === 'reviews' ? 'rotate-180' : ''"><?php echo \Contabai\Heroicon::outline('chevron-down', 'w-4 h-4'); ?></span>
                </button>
                <div x-show="open === 'reviews'" x-collapse x-cloak>
                    <div class="border-t border-neutral-200 px-5 py-5">
                        <?php if ($reviewCount === 0): ?>
                            <p class="text-sm text-neutral-500"><?php echo esc_html__('No reviews yet.', 'contabai'); ?></p>
                        <?php else: ?>
                            <?php ?>
                            <div x-data="contabaiReviews()" x-effect="open === 'reviews' && loadOnce()">
                                <div x-show="loading" class="py-6 text-center text-sm text-neutral-400"><?php echo esc_html__('Loading…', 'contabai'); ?></div>
                                <ul x-show="! loading" class="flex flex-col gap-5 !m-0 !list-none !p-0">
                                    <template x-for="r in reviews" x-bind:key="r.id">
                                        <li>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="font-semibold text-neutral-900" x-text="r.reviewer.nickname"></span>
                                                <span class="inline-flex items-center gap-0.5" role="img">
                                                    <template x-for="s in 5" x-bind:key="s">
                                                        <span x-bind:class="s <= r.rating ? 'text-amber-400' : 'text-neutral-300'"><?php echo \Contabai\Heroicon::solid('star', 'w-4 h-4'); ?></span>
                                                    </template>
                                                </span>
                                            </div>
                                            <div class="text-xs text-neutral-400" x-text="fmtDate(r.created_at)"></div>
                                            <p class="mt-1 text-sm text-neutral-700" x-show="r.body" x-text="r.body"></p>
                                        </li>
                                    </template>
                                </ul>
                                <?php $pg_neutral = true; include __DIR__ . '/../components/pagination.php'; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>

    <?php
    $availability     = $listing['availability'] ?? [];
    $unavailableDates = $availability['unavailable_dates'] ?? [];
    $leadDays         = (int) ($availability['min_days_before_checkin'] ?? 0);
    $windowFrom       = $availability['window']['from'] ?? null;
    if ($leadDays > 0 && $windowFrom) {
        for ($day = 0; $day < $leadDays; $day++) {
            $unavailableDates[] = gmdate('Y-m-d', strtotime($windowFrom . ' +' . $day . ' days'));
        }
    }
    $occupiedNights   = $availability['unavailable_dates'] ?? [];
    $disabledSet      = array_flip($unavailableDates);
    $checkoutOnly     = [];
    foreach ($occupiedNights as $night) {
        $prev = gmdate('Y-m-d', strtotime($night . ' -1 day'));
        if ($windowFrom && $prev >= $windowFrom && ! isset($disabledSet[$prev])) {
            $checkoutOnly[] = $night;
        }
    }
    $unavailableDates = array_values(array_diff($unavailableDates, $checkoutOnly));
    $windowTo         = $availability['window']['to'] ?? null;
    $minNights        = (int) ($listing['min_nights'] ?? 1);
    $maxNights        = (int) ($listing['max_nights'] ?? 0);
    $rangeErrorMsg    = ($minNights && $maxNights)
        ? sprintf(__('Stay must be between %1$d and %2$d nights.', 'contabai'), $minNights, $maxNights)
        : '';
    $listingId     = (int) ($listing['id'] ?? 0);
    $isLoggedIn    = ! empty($_COOKIE['contabai_sanctum_session_token']);
    $quoteEndpoint = rest_url('contabai/v1/sanctum/listing/quote/' . $listingId);
    $loginUrl      = home_url('/' . CONTABAI_LOGIN_PAGE_SLUG);
    $bookUrl       = home_url('/' . CONTABAI_BOOK_PAGE_SLUG . '/');
    $listingCosts  = $listing['listing_costs'] ?? [];
    ?>
    <section class="mt-8">
        <h2 class="mb-3 text-lg font-semibold text-neutral-900"><?php echo esc_html__('Availability', 'contabai'); ?></h2>
        <div x-data="contabaiAvailability()">
            <div x-ref="cal"></div>
            <?php if ($checkoutOnly): ?>
                <p class="mt-2 flex items-center gap-2 text-xs text-neutral-500"><span class="contabai-checkout-only-swatch inline-block h-4 w-4 flex-none rounded"></span><?php echo esc_html__('Check-out only', 'contabai'); ?></p>
            <?php endif; ?>

            <!-- Sticky booking bar — hidden until a valid range, then slides up (fixed, so DOM position here is fine) -->
            <div x-show="nights" x-cloak
                 x-effect="document.body.style.paddingBottom = nights ? '104px' : ''"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
                 class="fixed inset-x-0 bottom-0 z-40 border-t border-neutral-200 bg-white">
                <div class="mx-auto max-w-7xl px-4">
                    <?php if (! empty($listingCosts)): ?>
                        <button type="button" x-on:click="showDetails = ! showDetails" class="flex w-full items-center justify-center gap-1 border-b border-neutral-200 py-3 text-sm font-medium text-neutral-700">
                            <?php echo esc_html__('Additional costs', 'contabai'); ?>
                            <span class="text-neutral-400 transition" x-bind:class="showDetails ? 'rotate-180' : ''"><?php echo \Contabai\Heroicon::outline('chevron-down', 'w-4 h-4'); ?></span>
                        </button>
                        <div x-show="showDetails" x-collapse x-cloak class="border-b border-neutral-200">
                            <div class="mx-auto flex max-h-[38vh] max-w-md flex-col gap-2 overflow-y-auto py-4 text-sm sm:gap-3">
                                <?php foreach ($listingCosts as $c):
                                    $cLabel      = $c['label'] ?: ($labels['additional_cost_types'][$c['type_key']] ?? $c['type_key']);
                                    $isPercent   = strpos((string) $c['billing_unit'], 'percent_') === 0;
                                    $rate        = $isPercent
                                        ? rtrim(rtrim(number_format(((int) $c['amount_cents']) / 100, 2), '0'), '.') . '%'
                                        : $currency . ' ' . number_format(((int) $c['amount_cents']) / 100, 2);
                                    $unit        = $labels['cost_billing_units'][$c['billing_unit']] ?? $c['billing_unit'];
                                    $isPet       = ($c['type_key'] ?? '') === 'pet';
                                    $isExtra     = ($c['type_key'] ?? '') === 'extra_person' && isset($listing['base_price_guests']);
                                    $isMandatory = ! $isPet && ! $isExtra && ($c['classification'] ?? '') === 'mandatory';
                                    $classLabel  = $isPet ? __('Only with pets', 'contabai')
                                        : ($isExtra ? sprintf(_n('Above %d guest', 'Above %d guests', (int) $listing['base_price_guests'], 'contabai'), (int) $listing['base_price_guests'])
                                        : ($labels['cost_classifications'][$c['classification']] ?? $c['classification']));
                                ?>
                                    <div class="flex flex-col gap-0.5 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
                                        <span class="flex min-w-0 items-center gap-2">
                                            <span class="min-w-0 text-neutral-700 sm:truncate"><?php echo esc_html($cLabel); ?></span>
                                            <?php
                                            $badge_body    = esc_html($classLabel);
                                            $badge_variant = $isMandatory ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800';
                                            include __DIR__ . '/../components/badge.php';
                                            ?>
                                        </span>
                                        <span class="whitespace-nowrap text-neutral-800 sm:shrink-0"><?php echo esc_html($rate); ?> <span class="text-neutral-400"><?php echo esc_html('· ' . $unit); ?></span></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="flex items-center justify-between gap-4 py-3">
                        <div>
                            <div class="text-sm font-medium text-neutral-800" x-text="rangeLabel()"></div>
                            <div class="text-xl font-bold text-neutral-900" x-text="quote ? money(quote.accommodation_cents) : '…'"></div>
                            <div class="text-xs text-neutral-400" x-show="quote && quote.deposit_cents">+ <span x-text="money(quote?.deposit_cents)"></span> <?php echo esc_html__('deposit (refundable)', 'contabai'); ?></div>
                            <?php if (! empty($listingCosts)): ?>
                                <div class="mt-0.5 text-xs text-neutral-400"><?php echo esc_html__('Additional costs calculated at booking', 'contabai'); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-col items-end gap-1.5">
                            <button type="button" x-on:click="startBooking()" class="contabai-accent-bg inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold text-white">
                                <span x-show="isLoggedIn" class="inline-flex items-center gap-2"><?php echo \Contabai\Heroicon::outline('calendar-days', 'w-4 h-4'); ?><?php echo esc_html__('Book now', 'contabai'); ?></span>
                                <span x-show="! isLoggedIn" class="inline-flex items-center gap-2"><?php echo \Contabai\Heroicon::outline('arrow-right-on-rectangle', 'w-4 h-4'); ?><?php echo esc_html__('Log in to book', 'contabai'); ?></span>
                            </button>
                            <template x-if="! isLoggedIn">
                                <span class="flex items-start gap-1 text-xs text-neutral-500"><?php echo \Contabai\Heroicon::outline('lock-closed', 'w-4 h-4 shrink-0'); ?> <?php echo esc_html__('Booking requires a verified account', 'contabai'); ?></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
    document.addEventListener('alpine:init', function () {
        Alpine.data('contabaiReviews', function () {
            return {
                loaded: false,
                loading: false,
                reviews: [],
                currentPage: 1,
                lastPage: 1,
                total: 0,
                perPage: <?php echo (int) $reviewsPerPage; ?>,
                endpoint: '<?php echo esc_js($reviewsEndpoint); ?>',
                locale: '<?php echo esc_js(\Contabai\Helper::currentLang()); ?>',
                labels: { loadError: '<?php echo esc_js(__('Could not load the reviews.', 'contabai')); ?>' },
                loadOnce: function () {
                    if (! this.loaded) { this.loaded = true; this.load(1); }
                },
                load: function (page) {
                    let self = this;
                    self.loading = true;
                    let pageNumber = page || self.currentPage || 1;
                    return fetch(self.endpoint + '?page=' + pageNumber + '&per_page=' + self.perPage, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                        .then(function (r) { if (! r.ok) { let e = new Error('http'); e.status = r.status; throw e; } return r.json(); })
                        .then(function (res) {
                            let data = (res && res.data) || {};
                            self.reviews = data.reviews || [];
                            self.currentPage = data.current_page || 1;
                            self.lastPage = data.last_page || 1;
                            self.total = data.total || 0;
                            self.loading = false;
                        })
                        .catch(function (e) { self.loading = false; if (window.contabaiToast) { contabaiToast(contabaiStatusText(e.status, self.labels.loadError), 'danger'); } });
                },
                goTo: function (page) {
                    if (page < 1 || page > this.lastPage) { return; }
                    let self = this;
                    this.load(page).then(function () { self.$el.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
                },
                pages: function () {
                    let last = this.lastPage, cur = this.currentPage, delta = 1, range = [];
                    for (let pageNumber = 1; pageNumber <= last; pageNumber++) {
                        if (pageNumber === 1 || pageNumber === last || (pageNumber >= cur - delta && pageNumber <= cur + delta)) { range.push(pageNumber); }
                    }
                    let out = [], prev = 0;
                    range.forEach(function (pageNumber) {
                        if (pageNumber - prev > 1) { out.push({ ellipsis: true, key: 'e' + pageNumber }); }
                        out.push({ n: pageNumber, ellipsis: false, key: 'p' + pageNumber });
                        prev = pageNumber;
                    });
                    return out;
                },
                fmtDate: function (iso) {
                    if (! iso) { return ''; }
                    try { return new Date(iso).toLocaleDateString(this.locale, { year: 'numeric', month: 'long', day: 'numeric' }); } catch (e) { return ''; }
                },
            };
        });
        Alpine.data('contabaiAvailability', function () {
            return {
                calendar: null,
                cols: 0,
                disabledDates: <?php echo wp_json_encode($unavailableDates); ?>,   // occupied nights (blocks ∪ bookings, prep-buffer baked in) + arrival lead-time days — from the availability payload
                occupied: <?php echo wp_json_encode((object) array_fill_keys($occupiedNights, true)); ?>,
                checkoutOnly: <?php echo wp_json_encode((object) array_fill_keys($checkoutOnly, true)); ?>,
                checkoutOnlyLabel: '<?php echo esc_js(__('Check-out only', 'contabai')); ?>',
                checkoutOnlyErr: '<?php echo esc_js(__('You can check out on this day, but not arrive. Pick an earlier arrival date.', 'contabai')); ?>',
                occupiedErr: '<?php echo esc_js(__('Some nights in these dates are already booked. Please pick other dates.', 'contabai')); ?>',
                windowFrom: <?php echo wp_json_encode($windowFrom); ?>,             // Laravel's today (UTC) — first bookable day
                windowMax: <?php echo wp_json_encode($windowTo); ?>,               // booking horizon (today + booking_window_months), or null
                minNights: <?php echo $minNights; ?>,
                maxNights: <?php echo $maxNights; ?>,                               // 0 = no max
                nightsLabel: '<?php echo esc_js(__('nights', 'contabai')); ?>',
                nightLabel: '<?php echo esc_js(__('night', 'contabai')); ?>',
                rangeErr: '<?php echo esc_js($rangeErrorMsg); ?>',
                quoteErr: '<?php echo esc_js(__('We could not get a price for these dates. Please try other dates.', 'contabai')); ?>',
                locale: '<?php echo esc_js(\Contabai\Helper::currentLang()); ?>',   // guest language → localized month/weekday names
                // selected range (validate-on-complete)
                arrival: '',
                departure: '',
                nights: 0,
                isLoggedIn: <?php echo $isLoggedIn ? 'true' : 'false'; ?>,
                quoteEndpoint: '<?php echo esc_js($quoteEndpoint); ?>',
                loginUrl: '<?php echo esc_js($loginUrl); ?>',
                bookUrl: '<?php echo esc_js($bookUrl); ?>',
                listingId: <?php echo $listingId; ?>,
                currency: '<?php echo esc_js($currency); ?>',
                quote: null,
                loadingQuote: false,
                showDetails: false,   // top-of-bar "See details" cost-breakdown expander
                // 1 month on mobile, 2 on md (≥768), 3 on lg (≥1024)
                monthsForWidth: function () {
                    let width = window.innerWidth;
                    if (width >= 1024) return 3;
                    if (width >= 768) return 2;
                    return 1;
                },
                // type 'default' only allows 1 month; 'multiple' is required for 2–3.
                // selectedTheme pinned 'light' so the lib never follows the OS to dark (plugin has no dark mode).
                options: function (months) {
                    let component = this;
                    let calendarOptions = {
                        type: months === 1 ? 'default' : 'multiple',
                        displayMonthsCount: months,
                        monthsToSwitch: 1,
                        displayDatesOutside: false,   // don't render adjacent-month dates — else a date shows in two grids and both highlight on select
                        disableDatesPast: true,
                        disableDates: this.disabledDates,        // grey out occupied nights (non-selectable)
                        disableDatesGaps: true,                  // block a range spanning an occupied night
                        selectionDatesMode: 'multiple-ranged',   // pick arrival → departure
                        locale: this.locale,                     // localized months/weekdays
                        selectedTheme: 'light',
                        onClickDate: function (cal) { component.handleSelect(cal); },
                        onCreateDateEls: function (cal, dateEl) {
                            if (! component.checkoutOnly[dateEl.getAttribute('data-vc-date')]) return;
                            dateEl.setAttribute('data-contabai-checkout-only', '');
                            let btn = dateEl.querySelector('[data-vc-date-btn]');
                            if (btn) { btn.setAttribute('title', component.checkoutOnlyLabel); }
                        }
                    };
                    // v3.4+ requires the months extension for multi-month display; register it always (extensions are constructor-only, so a later 1→2 month resize can't add it).
                    if (window.VanillaCalendarMonths) { calendarOptions.extensions = [window.VanillaCalendarMonths]; }
                    if (this.windowFrom) calendarOptions.dateToday = this.windowFrom;   // "today" = Laravel's UTC today, not the visitor's clock
                    if (this.windowMax) calendarOptions.displayDateMax = this.windowMax;   // clamp navigation to the booking horizon
                    return calendarOptions;
                },
                // Validate-on-complete: VCP has no native min/max stay-length rule, so enforce it here.
                // occupied-span is already prevented by disableDatesGaps; here we only guard nights range.
                handleSelect: function (cal) {
                    let dates = (cal.context && cal.context.selectedDates) || [];   // sorted YYYY-MM-DD[], first=arrival last=departure
                    if (dates.length < 2) {   // arrival picked (or cleared) — awaiting departure
                        if (dates[0] && this.occupied[dates[0]]) {
                            this.rejectRange(cal, this.checkoutOnlyErr);
                            return;
                        }
                        this.arrival = dates[0] || ''; this.departure = ''; this.nights = 0;
                        this.quote = null;
                        return;
                    }
                    let firstDate = dates[0], lastDate = dates[dates.length - 1];
                    let nights = Math.round((new Date(lastDate) - new Date(firstDate)) / 86400000);
                    if ((this.minNights && nights < this.minNights) || (this.maxNights && nights > this.maxNights)) {
                        this.rejectRange(cal, this.rangeErr);
                        return;
                    }
                    if (this.hasOccupiedNight(firstDate, lastDate)) {
                        this.rejectRange(cal, this.occupiedErr);
                        return;
                    }
                    this.arrival = firstDate; this.departure = lastDate; this.nights = nights;
                    this.fetchQuote();   // public quote — real price for everyone
                },
                rejectRange: function (cal, message) {
                    this.arrival = ''; this.departure = ''; this.nights = 0;
                    this.quote = null;
                    cal.set({ selectedDates: [] });
                    if (window.contabaiToast && message) contabaiToast(message, 'warning');
                },
                hasOccupiedNight: function (firstDate, lastDate) {
                    let parts = firstDate.split('-').map(Number);
                    let cursor = new Date(Date.UTC(parts[0], parts[1] - 1, parts[2]));
                    let night = firstDate;
                    while (night < lastDate) {
                        if (this.occupied[night]) return true;
                        cursor.setUTCDate(cursor.getUTCDate() + 1);
                        night = cursor.toISOString().slice(0, 10);
                    }
                    return false;
                },
                fmt: function (d) {
                    try { return new Date(d + 'T00:00:00').toLocaleDateString(this.locale, { weekday: 'short', day: 'numeric', month: 'short' }); }
                    catch (e) { return d; }
                },
                rangeLabel: function () {
                    return this.nights ? (this.fmt(this.arrival) + ' → ' + this.fmt(this.departure) + ' · ' + this.nights + ' ' + (this.nights === 1 ? this.nightLabel : this.nightsLabel)) : '';
                },
                money: function (cents) {
                    if (cents === null || cents === undefined) return '';
                    let code = this.currency || 'EUR';
                    try { return new Intl.NumberFormat(this.locale, { style: 'currency', currency: code, currencyDisplay: 'code' }).format(cents / 100); }
                    catch (e) { return code + ' ' + (cents / 100).toFixed(2); }
                },
                fetchQuote: function () {   // public base-only quote — accommodation + deposit, no party
                    let self = this;
                    let arrival = self.arrival, departure = self.departure;
                    self.loadingQuote = true; self.quote = null;
                    fetch(self.quoteEndpoint, {
                        method: 'POST', credentials: 'same-origin',
                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify({ arrival: arrival, departure: departure })
                    })
                        .then(function (r) { return r.json().then(function (res) { return { ok: r.ok, status: r.status, res: res }; }); })
                        .then(function (out) {
                            if (self.arrival !== arrival || self.departure !== departure) return;
                            self.loadingQuote = false;
                            if (out.ok) { self.quote = (out.res && out.res.data && out.res.data.quote) || null; return; }
                            self.quoteFailed(out.status);
                        })
                        .catch(function () {
                            if (self.arrival !== arrival || self.departure !== departure) return;
                            self.loadingQuote = false;
                            self.quoteFailed(0);
                        });
                },
                quoteFailed: function (status) {
                    this.arrival = ''; this.departure = ''; this.nights = 0;
                    this.quote = null;
                    if (this.calendar) { this.calendar.set({ selectedDates: [] }); }
                    if (window.contabaiToast) { contabaiToast(contabaiStatusText(status, this.quoteErr), 'danger'); }
                },
                startBooking: function () {
                    if (! this.isLoggedIn) { window.location.href = this.loginUrl; return; }
                    if (! this.nights) { return; }
                    window.location.href = this.bookUrl
                        + '?listing=' + encodeURIComponent(this.listingId)
                        + '&arrival=' + encodeURIComponent(this.arrival)
                        + '&departure=' + encodeURIComponent(this.departure);
                },
                init: function () {
                    let self = this;
                    if (! window.VanillaCalendar) { setTimeout(function () { self.init(); }, 50); return; }
                    self.cols = self.monthsForWidth();
                    self.calendar = new window.VanillaCalendar(self.$refs.cal, self.options(self.cols));
                    self.calendar.init();
                    window.addEventListener('resize', function () {
                        let months = self.monthsForWidth();
                        if (months !== self.cols) { self.cols = months; self.calendar.set(self.options(months)); }
                    });
                }
            };
        });
    });
    </script>

    <div class="mt-8 grid gap-4 text-sm text-neutral-600 sm:grid-cols-2 lg:grid-cols-3">
        <?php if (! empty($listing['min_nights']) || ! empty($listing['max_nights'])): ?>
            <div class="rounded-lg border border-neutral-200 bg-white p-4">
                <div class="font-medium text-neutral-900"><?php echo esc_html__('Length of stay', 'contabai'); ?></div>
                <div class="mt-1"><?php echo esc_html(sprintf(__('%1$s–%2$s nights', 'contabai'), (int) ($listing['min_nights'] ?? 0), (int) ($listing['max_nights'] ?? 0))); ?></div>
            </div>
        <?php endif; ?>
        <div class="rounded-lg border border-neutral-200 bg-white p-4">
            <div class="font-medium text-neutral-900"><?php echo esc_html__('Check-in / Check-out', 'contabai'); ?></div>
            <div class="mt-1"><?php echo esc_html($formatHour($listing['checkin_from_hour'] ?? 0) . ' — ' . $formatHour($listing['checkout_by_hour'] ?? 0)); ?></div>
        </div>
        <?php if (! empty($listing['utility_rates'])): ?>
            <div class="rounded-lg border border-neutral-200 bg-white p-4">
                <div class="font-medium text-neutral-900"><?php echo esc_html__('Metered utilities', 'contabai'); ?></div>
                <?php foreach ($listing['utility_rates'] as $u): ?>
                    <div class="mt-1"><?php echo esc_html(($labels['utilities'][$u['utility']] ?? ucfirst((string) $u['utility'])) . ' · ' . $currency . ' ' . number_format(((int) $u['rate_cents']) / 100, 2) . ' / ' . $u['unit']); ?></div>
                <?php endforeach; ?>
                <div class="mt-1 text-xs text-neutral-400"><?php echo esc_html__('Read at check-out and settled from your deposit.', 'contabai'); ?></div>
            </div>
        <?php endif; ?>
    </div>

</div>
