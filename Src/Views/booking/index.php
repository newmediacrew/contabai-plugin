<?php if (! $valid): ?>

    <div class="mx-auto max-w-3xl px-4 py-8">
        <h1 class="mb-6 text-2xl font-bold text-neutral-900"><?php echo esc_html__('Book your stay', 'contabai'); ?></h1>
        <p class="rounded-xl border border-dashed border-neutral-300 py-16 text-center text-neutral-500"><?php echo esc_html__('We could not read your dates. Please choose your arrival and departure again on the property page.', 'contabai'); ?></p>
        <div class="mt-6 text-center text-sm">
            <a href="<?php echo esc_url($listingsUrl); ?>" class="text-neutral-500 no-underline transition hover:text-neutral-900"><?php echo esc_html__('Back to the properties', 'contabai'); ?></a>
        </div>
    </div>

<?php else: ?>

<?php

$costTypes = $labels['additional_cost_types'] ?? [];
$costUnits = $labels['cost_billing_units'] ?? [];
$costClasses = $labels['cost_classifications'] ?? [];

$listingCosts = $listing['listing_costs'] ?? [];
$mandatoryCosts = [];
$optionalCosts = [];
foreach ($listingCosts as $cost) {
    if (($cost['classification'] ?? '') === 'optional') {
        $optionalCosts[] = $cost;
    } else {
        $mandatoryCosts[] = $cost;
    }
}

$costLabel = function (array $cost) use ($costTypes): string {
    $key = (string) ($cost['type_key'] ?? '');

    return (string) ($cost['label'] ?? '') !== ''
        ? (string) $cost['label']
        : (string) ($costTypes[$key] ?? $key);
};

$unitLabel = function (array $cost) use ($costUnits): string {
    $unit = (string) ($cost['billing_unit'] ?? '');

    return (string) ($costUnits[$unit] ?? $unit);
};

$rateLabel = function (array $cost) use ($listing): string {
    $unit = (string) ($cost['billing_unit'] ?? '');
    $amount = (int) ($cost['amount_cents'] ?? 0);

    if (strpos($unit, 'percent_') === 0) {
        return rtrim(rtrim(number_format($amount / 100, 2, '.', ''), '0'), '.') . '%';
    }

    return (string) ($listing['currency'] ?? '') . ' ' . number_format($amount / 100, 2);
};

$maxGuests = (int) ($listing['max_guests'] ?? 0);
$nights = (int) round((strtotime($departure) - strtotime($arrival)) / 86400);

?>
<div x-data="contabaiBooking()" class="mx-auto max-w-3xl px-4 py-8">

    <h1 class="mb-6 text-2xl font-bold text-neutral-900"><?php echo esc_html__('Book your stay', 'contabai'); ?></h1>

    <ol class="mb-6 flex items-center gap-2 text-sm">
        <?php
        $stepNames = [
            1 => __('Party', 'contabai'),
            2 => __('Your details', 'contabai'),
            3 => __('Final check', 'contabai'),
        ];
        foreach ($stepNames as $n => $name): ?>
            <li class="flex items-center gap-2 <?php echo $n < 3 ? 'flex-1' : 'shrink-0'; ?>">
                <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                      x-bind:class="step >= <?php echo (int) $n; ?> ? 'contabai-accent-bg text-white' : 'bg-neutral-200 text-neutral-500'"><?php echo (int) $n; ?></span>
                <span class="hidden truncate sm:inline" x-bind:class="step === <?php echo (int) $n; ?> ? 'font-medium text-neutral-900' : 'text-neutral-500'"><?php echo esc_html($name); ?></span>
                <?php if ($n < 3): ?><span class="h-px flex-1 bg-neutral-200"></span><?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>

    <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-lg">

        <div class="mb-5 border-b border-neutral-200 pb-4">
            <div class="font-medium text-neutral-900"><?php echo esc_html((string) ($listing['title'] ?? '')); ?></div>
            <div class="mt-1 text-sm text-neutral-500">
                <?php echo esc_html($arrival . ' → ' . $departure); ?>
                <?php echo esc_html(' · ' . sprintf(_n('%d night', '%d nights', $nights, 'contabai'), $nights)); ?>
            </div>
            <div class="mt-1 text-sm text-neutral-500" x-show="step > 1" x-cloak x-text="partyLabel()"></div>
        </div>

        <div x-show="step === 1">

            <div class="grid gap-4 sm:grid-cols-2">
                <?php
                $partyFields = [
                    'adults' => [__('Adults', 'contabai'), 'partyOptions(1, maxGuests)'],
                    'children' => [__('Children', 'contabai'), 'partyOptions(0, Math.max(0, maxGuests - 1))'],
                    'infants' => [__('Infants', 'contabai'), 'partyOptions(0, 10)'],
                    'pets' => [__('Pets', 'contabai'), 'partyOptions(0, 10)'],
                ];
                foreach ($partyFields as $key => $meta): ?>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-neutral-500"><?php echo esc_html($meta[0]); ?></label>
                        <?php
                        $sel_value = $key;
                        $sel_items = $meta[1];
                        $sel_placeholder = $meta[0];
                        $sel_onchange = '';
                        include __DIR__ . '/../components/select.php';
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <p class="mt-2 text-sm" x-bind:class="overCapacity() ? 'text-red-600' : 'text-neutral-500'">
                <?php echo esc_html(sprintf(__('This property sleeps at most %d guests. Infants and pets do not count.', 'contabai'), $maxGuests)); ?>
            </p>

            <template x-for="msg in fieldErrorsFor(['adults','children','infants','pets','intro_message','selected_optional_ids'])" x-bind:key="msg">
                <p class="mt-2 text-sm text-red-600" x-text="msg"></p>
            </template>

            <?php if ($mandatoryCosts): ?>
                <div class="mt-6">
                    <div class="mb-2 text-sm font-medium text-neutral-900"><?php echo esc_html__('Always included', 'contabai'); ?></div>
                    <div class="divide-y divide-neutral-200 border-y border-neutral-200 text-sm">
                        <?php foreach ($mandatoryCosts as $cost): ?>
                            <div class="flex flex-col gap-1 py-2.5 sm:flex-row sm:items-center sm:justify-between sm:gap-3"<?php echo ['pet' => ' x-show="pets > 0"', 'extra_person' => ' x-show="(adults + children) > basePriceGuests"'][$cost['type_key'] ?? ''] ?? ''; ?>>
                                <span class="flex min-w-0 items-center gap-2">
                                    <span class="min-w-0 text-neutral-700 sm:truncate"><?php echo esc_html($costLabel($cost)); ?></span>
                                    <?php
                                    $badge_body = esc_html($costClasses['mandatory'] ?? __('Mandatory', 'contabai'));
                                    $badge_variant = 'bg-yellow-100 text-yellow-800';
                                    include __DIR__ . '/../components/badge.php';
                                    ?>
                                </span>
                                <span class="shrink-0 whitespace-nowrap text-neutral-800"><?php echo esc_html($rateLabel($cost)); ?> <span class="text-neutral-400"><?php echo esc_html('· ' . $unitLabel($cost)); ?></span></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($optionalCosts): ?>
                <div class="mt-6">
                    <div class="mb-2 text-sm font-medium text-neutral-900"><?php echo esc_html__('Optional extras', 'contabai'); ?></div>
                    <div class="divide-y divide-neutral-200 border-y border-neutral-200 text-sm">
                        <?php foreach ($optionalCosts as $cost): ?>
                            <label class="flex cursor-pointer flex-col gap-1 py-2.5 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
                                <span class="flex min-w-0 items-center gap-2">
                                    <input type="checkbox" value="<?php echo (int) ($cost['id'] ?? 0); ?>" x-model.number="selectedOptionalIds"
                                           class="h-4 w-4 shrink-0 rounded border-neutral-300 text-neutral-900 focus:ring-neutral-400">
                                    <span class="min-w-0 text-neutral-700 sm:truncate"><?php echo esc_html($costLabel($cost)); ?></span>
                                </span>
                                <span class="whitespace-nowrap text-neutral-800 sm:shrink-0"><?php echo esc_html($rateLabel($cost)); ?> <span class="text-neutral-400"><?php echo esc_html('· ' . $unitLabel($cost)); ?></span></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mt-6">
                <label class="mb-1 block text-xs font-medium text-neutral-500"><?php echo esc_html__('Message to the host (optional)', 'contabai'); ?></label>
                <textarea x-model="introMessage" rows="3" maxlength="500"
                          class="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-400 focus:ring-offset-2"></textarea>
                <div class="mt-1 text-right text-xs text-neutral-400"><span x-text="introMessage.length"></span>/500</div>
            </div>

        </div>

        <div x-show="step === 2" x-cloak>
            <form x-on:submit.prevent="toStep(3)" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-neutral-500"><?php echo esc_html__('First name', 'contabai'); ?></label>
                        <input type="text" x-model="first_name" class="h-10 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-400 focus:ring-offset-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-neutral-500"><?php echo esc_html__('Last name', 'contabai'); ?></label>
                        <input type="text" x-model="last_name" class="h-10 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-400 focus:ring-offset-2">
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-neutral-500"><?php echo esc_html__('Address', 'contabai'); ?></label>
                    <input type="text" x-model="address" class="h-10 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-400 focus:ring-offset-2">
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-neutral-500"><?php echo esc_html__('Postcode', 'contabai'); ?></label>
                        <input type="text" x-model="postcode" class="h-10 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-400 focus:ring-offset-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-neutral-500"><?php echo esc_html__('City', 'contabai'); ?></label>
                        <input type="text" x-model="city" class="h-10 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-400 focus:ring-offset-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-neutral-500"><?php echo esc_html__('Country', 'contabai'); ?></label>
                        <?php
                        $sel_value = 'country';
                        $sel_items = 'countryOptions';
                        $sel_placeholder = __('Select country', 'contabai');
                        $sel_onchange = '';
                        include __DIR__ . '/../components/select.php';
                        ?>
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-neutral-500"><?php echo esc_html__('Phone', 'contabai'); ?></label>
                    <input type="text" x-model="phone" class="h-10 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-400 focus:ring-offset-2">
                </div>
                <p x-show="detailsMissing()" x-cloak class="text-sm text-red-600"><?php echo esc_html__('Please complete every field before you continue.', 'contabai'); ?></p>
                <template x-for="msg in fieldErrorsFor(['first_name','last_name','address','postcode','city','country','phone'])" x-bind:key="msg">
                    <p class="text-sm text-red-600" x-text="msg"></p>
                </template>
            </form>
        </div>

        <div x-show="step === 3" x-cloak>

            <div x-show="loadingQuote" class="py-10 text-center text-sm text-neutral-500"><?php echo esc_html__('Calculating your total…', 'contabai'); ?></div>

            <div x-show="quoteError" x-cloak class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" x-text="quoteError"></div>

            <template x-if="quote && ! loadingQuote">
                <div>
                    <h3 class="mb-3 text-sm font-semibold text-neutral-900"><?php echo esc_html__('Price', 'contabai'); ?></h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between gap-4 text-neutral-600">
                            <span><?php echo esc_html__('Accommodation', 'contabai'); ?></span>
                            <span class="whitespace-nowrap" x-text="money(quote.accommodation_cents)"></span>
                        </div>
                        <template x-for="(row, index) in quote.cost_breakdown" x-bind:key="index">
                            <div class="flex justify-between gap-4 text-neutral-600">
                                <span class="min-w-0" x-text="costLabel(row)"></span>
                                <span class="shrink-0 whitespace-nowrap" x-text="money(row.computed_cents)"></span>
                            </div>
                        </template>
                        <template x-if="quote.deposit_cents">
                            <div class="flex justify-between gap-4 text-neutral-600">
                                <span><?php echo esc_html__('Security deposit (refundable)', 'contabai'); ?></span>
                                <span class="shrink-0 whitespace-nowrap" x-text="money(quote.deposit_cents)"></span>
                            </div>
                        </template>
                        <div class="flex justify-between gap-4 border-t border-neutral-200 pt-2 font-semibold text-neutral-900">
                            <span><?php echo esc_html__('Total', 'contabai'); ?></span>
                            <span class="whitespace-nowrap text-base" x-text="money(quote.due_at_booking_cents)"></span>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-neutral-500"><?php echo esc_html__('You pay nothing now. The host reviews your request first, and you receive payment details once it is accepted.', 'contabai'); ?></p>

                    <?php if ($houseRules !== ''): ?>
                        <div class="mt-6">
                            <?php
                            $acc_key = 'house_rules';
                            $acc_title = __('House rules', 'contabai');
                            $acc_body = '<div class="prose prose-sm max-w-none text-neutral-700">' . wp_kses_post($houseRules) . '</div>';
                            include __DIR__ . '/../components/accordion-section.php';
                            ?>
                        </div>
                    <?php endif; ?>

                    <label class="mt-3 flex cursor-pointer items-start gap-2 text-sm text-neutral-700">
                        <input type="checkbox" x-model="agreedHouseRules" class="mt-0.5 h-4 w-4 shrink-0 rounded border-neutral-300 text-neutral-900 focus:ring-neutral-400">
                        <span><?php echo esc_html__('I have read and accept the house rules.', 'contabai'); ?></span>
                    </label>

                    <?php if ($terms !== ''): ?>
                        <div class="mt-6">
                            <?php
                            $acc_key = 'terms';
                            $acc_title = __('Terms and conditions', 'contabai');
                            $acc_body = '<div class="prose prose-sm max-w-none text-neutral-700">' . wp_kses_post($terms) . '</div>';
                            include __DIR__ . '/../components/accordion-section.php';
                            ?>
                        </div>
                    <?php endif; ?>

                    <label class="mt-3 flex cursor-pointer items-start gap-2 text-sm text-neutral-700">
                        <input type="checkbox" x-model="agreedTerms" class="mt-0.5 h-4 w-4 shrink-0 rounded border-neutral-300 text-neutral-900 focus:ring-neutral-400">
                        <span><?php echo esc_html__('I have read and accept the terms and conditions.', 'contabai'); ?></span>
                    </label>

                    <template x-for="msg in fieldErrorsFor(['agreed_house_rules','agreed_terms','arrival','departure'])" x-bind:key="msg">
                        <p class="mt-3 text-sm text-red-600" x-text="msg"></p>
                    </template>

                    <div x-show="submitError" x-cloak class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <p x-text="submitError"></p>
                        <a href="<?php echo esc_url($listingUrl); ?>" class="mt-2 inline-block font-medium text-red-700 underline"><?php echo esc_html__('Choose different dates', 'contabai'); ?></a>
                    </div>
                </div>
            </template>

        </div>

        <div x-show="step === 4" x-cloak>
            <div class="py-4 text-center">
                <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-green-700"><?php echo \Contabai\Heroicon::outline('check', 'w-5 h-5'); ?></span>
                <h2 class="mt-4 text-lg font-semibold text-neutral-900"><?php echo esc_html__('Your booking request has been sent', 'contabai'); ?></h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-neutral-600"><?php echo esc_html__('The host reviews your request and you will hear back by email. You pay nothing until it is accepted.', 'contabai'); ?></p>
                <p class="mt-3 text-sm text-neutral-500"><?php echo esc_html__('Booking reference', 'contabai'); ?> <span class="font-medium text-neutral-900" x-text="'#' + bookingId"></span></p>
                <a href="<?php echo esc_url($bookingsUrl); ?>" class="mt-6 inline-flex items-center gap-2 rounded-md border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-800 no-underline transition hover:bg-neutral-50">
                    <?php echo \Contabai\Heroicon::outline('ticket', 'w-4 h-4'); ?>
                    <?php echo esc_html__('My bookings', 'contabai'); ?>
                </a>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-between gap-3 border-t border-neutral-200 pt-4" x-show="step < 4">
            <button type="button" x-show="step > 1" x-cloak x-on:click="toStep(step - 1)" x-bind:disabled="submitting"
                    class="inline-flex items-center gap-2 rounded-md border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-800 transition hover:bg-neutral-50">
                <?php echo \Contabai\Heroicon::outline('arrow-left', 'w-4 h-4'); ?>
                <?php echo esc_html__('Back', 'contabai'); ?>
            </button>
            <span x-show="step === 1"></span>

            <button type="button" x-show="step < 3" x-on:click="toStep(step + 1)" x-bind:disabled="! canAdvance()"
                    class="contabai-accent-bg inline-flex items-center gap-2 rounded-md px-5 py-2 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-50">
                <?php echo esc_html__('Continue', 'contabai'); ?>
                <?php echo \Contabai\Heroicon::outline('arrow-right', 'w-4 h-4'); ?>
            </button>

            <button type="button" x-show="step === 3" x-cloak x-on:click="submitBooking()" x-bind:disabled="! canConfirm()"
                    class="contabai-accent-bg inline-flex items-center gap-2 rounded-md px-5 py-2 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-50">
                <?php echo \Contabai\Heroicon::outline('check', 'w-4 h-4'); ?>
                <span x-show="! submitting"><?php echo esc_html__('Confirm', 'contabai'); ?></span>
                <span x-show="submitting" x-cloak><?php echo esc_html__('Sending…', 'contabai'); ?></span>
            </button>
        </div>

    </div>

    <div class="mt-6 text-center text-sm">
        <a href="<?php echo esc_url($listingsUrl); ?>" class="text-neutral-500 no-underline transition hover:text-neutral-900"><?php echo esc_html__('Back to the properties', 'contabai'); ?></a>
    </div>

</div>

<script>
document.addEventListener('alpine:init', function () {
    Alpine.data('contabaiBooking', function () {
        return {
            step: 1,
            maxGuests: <?php echo (int) $maxGuests; ?>,
            basePriceGuests: <?php echo (int) ($listing['base_price_guests'] ?? 0); ?>,
            currency: '<?php echo esc_js((string) ($listing['currency'] ?? 'EUR')); ?>',
            locale: '<?php echo esc_js(\Contabai\Helper::currentLang()); ?>',
            arrival: '<?php echo esc_js($arrival); ?>',
            departure: '<?php echo esc_js($departure); ?>',
            sessionEndpoint: '<?php echo esc_js($sessionEndpoint); ?>',
            quoteEndpoint: '<?php echo esc_js($quoteEndpoint); ?>',
            costTypes: <?php echo wp_json_encode($costTypes); ?>,
            countryOptions: <?php echo wp_json_encode($countryOptions ?? []); ?>,
            errorText: <?php echo wp_json_encode([
                'stay' => __('This stay can no longer be booked as chosen — the dates may be taken, too close to today, too far ahead, or outside the allowed number of nights.', 'contabai'),
                'party' => __('Your party is larger than this property allows.', 'contabai'),
                'unavailable' => __('This property is no longer available.', 'contabai'),
            ]); ?>,
            fieldText: <?php echo wp_json_encode(array_merge(
                array_fill_keys(['first_name', 'last_name', 'address', 'postcode', 'city', 'phone'], __('Please complete every field before you continue.', 'contabai')),
                array_fill_keys(['adults', 'children', 'infants', 'pets'], __('Please check the number of guests.', 'contabai')),
                array_fill_keys(['agreed_house_rules', 'agreed_terms'], __('Please accept the house rules and the terms and conditions.', 'contabai')),
                array_fill_keys(['arrival', 'departure'], __('This stay can no longer be booked as chosen — the dates may be taken, too close to today, too far ahead, or outside the allowed number of nights.', 'contabai')),
                [
                    'selected_optional_ids' => __('Please check your optional extras.', 'contabai'),
                    'intro_message' => __('Your message to the host can be at most 500 characters.', 'contabai'),
                ]
            )); ?>,

            adults: 1,
            children: 0,
            infants: 0,
            pets: 0,
            selectedOptionalIds: [],
            introMessage: '',

            first_name: '',
            last_name: '',
            address: '',
            postcode: '',
            city: '',
            country: '',
            phone: '',

            agreedHouseRules: false,
            agreedTerms: false,

            open: 'house_rules',
            toggle: function (key) {
                this.open = this.open === key ? '' : key;
            },

            quote: null,
            loadingQuote: false,
            quoteError: '',

            submitting: false,
            submitError: '',
            fieldErrors: {},
            bookingId: null,
            storeEndpoint: '<?php echo esc_js($storeEndpoint); ?>',
            loginUrl: '<?php echo esc_js(home_url('/' . CONTABAI_LOGIN_PAGE_SLUG)); ?>',

            init: function () {
                let self = this;
                fetch(self.sessionEndpoint, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        let guest = (res && res.data) || {};
                        ['first_name', 'last_name', 'address', 'postcode', 'city', 'country', 'phone'].forEach(function (k) {
                            if (guest[k] && (k !== 'country' || self.isCountry(guest[k]))) { self[k] = guest[k]; }
                        });
                    })
                    .catch(function () {});
            },

            isCountry: function (code) {
                return this.countryOptions.some(function (option) { return option.value === code; });
            },

            partyOptions: function (min, max) {
                let out = [];
                for (let count = min; count <= max; count++) { out.push({ value: count, title: String(count) }); }
                return out;
            },

            overCapacity: function () {
                return this.maxGuests > 0 && (this.adults + this.children) > this.maxGuests;
            },

            detailsMissing: function () {
                let self = this;
                return ['first_name', 'last_name', 'address', 'postcode', 'city', 'country', 'phone'].some(function (k) {
                    return String(self[k] || '').trim() === '';
                });
            },

            canAdvance: function () {
                if (this.step === 1) { return this.adults >= 1 && ! this.overCapacity(); }
                if (this.step === 2) { return ! this.detailsMissing(); }
                return false;
            },

            canConfirm: function () {
                return this.agreedHouseRules && this.agreedTerms && !! this.quote;
            },

            toStep: function (n) {
                if (n > this.step && ! this.canAdvance()) { return; }
                this.step = n;
                if (n === 3) { this.fetchQuote(); }
            },

            fetchQuote: function () {
                let self = this;
                self.loadingQuote = true;
                self.quoteError = '';
                self.quote = null;
                fetch(self.quoteEndpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        arrival: self.arrival,
                        departure: self.departure,
                        adults: self.adults,
                        children: self.children,
                        pets: self.pets,
                        selected_optional_ids: self.selectedOptionalIds
                    })
                })
                    .then(function (r) {
                        return r.json().then(function (res) { return { ok: r.ok, status: r.status, res: res }; });
                    })
                    .then(function (out) {
                        self.loadingQuote = false;
                        if (out.ok && out.res && out.res.data && out.res.data.quote) {
                            self.quote = out.res.data.quote;
                            return;
                        }
                        if (out.status === 401) { window.location.reload(); return; }
                        if (out.status === 422) { self.quoteError = (out.res && out.res.errors) ? self.errorText.stay : self.errorText.party; return; }
                        if (out.status === 404) { self.quoteError = self.errorText.unavailable; return; }
                        self.quoteError = contabaiStatusText(out.status, '<?php echo esc_js(__('We could not calculate your total. Please try again.', 'contabai')); ?>');
                    })
                    .catch(function () {
                        self.loadingQuote = false;
                        self.quoteError = '<?php echo esc_js(__('We could not calculate your total. Please try again.', 'contabai')); ?>';
                    });
            },

            fieldErrorsFor: function (fields) {
                let self = this;
                let out = [];
                fields.forEach(function (f) {
                    let msgs = self.fieldErrors[f];
                    if (msgs) { msgs.forEach(function (m) { if (out.indexOf(m) === -1) { out.push(m); } }); }
                });
                return out;
            },

            localizeErrors: function (errors) {
                let self = this;
                let out = {};
                Object.keys(errors).forEach(function (key) {
                    let base = key.split('.')[0];
                    out[base] = [self.fieldText[base] || contabaiFieldText[base] || contabaiStatusText(0)];
                });
                return out;
            },

            stepForField: function (field) {
                if (['first_name', 'last_name', 'address', 'postcode', 'city', 'country', 'phone'].indexOf(field) !== -1) { return 2; }
                if (['adults', 'children', 'infants', 'pets', 'intro_message'].indexOf(field) !== -1) { return 1; }
                if (field.indexOf('selected_optional_ids') === 0) { return 1; }
                return 3;
            },

            submitBooking: function () {
                if (this.submitting || ! this.canConfirm()) { return; }

                let self = this;
                self.submitting = true;
                self.submitError = '';
                self.fieldErrors = {};

                fetch(self.storeEndpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        arrival: self.arrival,
                        departure: self.departure,
                        adults: self.adults,
                        children: self.children,
                        infants: self.infants,
                        pets: self.pets,
                        intro_message: self.introMessage,
                        agreed_house_rules: self.agreedHouseRules,
                        agreed_terms: self.agreedTerms,
                        selected_optional_ids: self.selectedOptionalIds,
                        first_name: self.first_name,
                        last_name: self.last_name,
                        address: self.address,
                        postcode: self.postcode,
                        city: self.city,
                        country: self.country,
                        phone: self.phone
                    })
                })
                    .then(function (r) {
                        return r.json().then(function (res) { return { ok: r.ok, status: r.status, res: res }; });
                    })
                    .then(function (out) {
                        self.submitting = false;

                        if (out.status === 201 && out.res && out.res.data && out.res.data.booking) {
                            self.bookingId = out.res.data.booking.id;
                            self.step = 4;
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                            return;
                        }

                        if (out.status === 401) { window.location.href = self.loginUrl; return; }

                        if (out.status === 422 && out.res && out.res.errors) {
                            self.fieldErrors = self.localizeErrors(out.res.errors);
                            let first = Object.keys(out.res.errors)[0];
                            let target = self.stepForField(first);
                            if (target !== 3) { self.step = target; }
                            self.submitError = '';
                            return;
                        }

                        if (out.status === 422) { self.submitError = self.errorText.stay; return; }
                        if (out.status === 404) { self.submitError = self.errorText.unavailable; return; }
                        self.submitError = contabaiStatusText(out.status, '<?php echo esc_js(__('We could not complete your booking. Please try again.', 'contabai')); ?>');
                    })
                    .catch(function () {
                        self.submitting = false;
                        self.submitError = '<?php echo esc_js(__('We could not complete your booking. Please try again.', 'contabai')); ?>';
                    });
            },

            costLabel: function (row) {
                return row.label || this.costTypes[row.type_key] || row.type_key;
            },

            partyLabel: function () {
                let parts = [];
                parts.push(this.adults + ' ' + (this.adults === 1 ? '<?php echo esc_js(__('adult', 'contabai')); ?>' : '<?php echo esc_js(__('adults', 'contabai')); ?>'));
                if (this.children) { parts.push(this.children + ' ' + (this.children === 1 ? '<?php echo esc_js(__('child', 'contabai')); ?>' : '<?php echo esc_js(__('children', 'contabai')); ?>')); }
                if (this.infants) { parts.push(this.infants + ' ' + (this.infants === 1 ? '<?php echo esc_js(__('infant', 'contabai')); ?>' : '<?php echo esc_js(__('infants', 'contabai')); ?>')); }
                if (this.pets) { parts.push(this.pets + ' ' + (this.pets === 1 ? '<?php echo esc_js(__('pet', 'contabai')); ?>' : '<?php echo esc_js(__('pets', 'contabai')); ?>')); }
                return parts.join(' · ');
            },

            money: function (cents) {
                if (cents === null || cents === undefined) { return ''; }
                let code = this.currency || 'EUR';
                try { return new Intl.NumberFormat(this.locale, { style: 'currency', currency: code, currencyDisplay: 'code' }).format(cents / 100); }
                catch (e) { return code + ' ' + (cents / 100).toFixed(2); }
            }
        };
    });
});
</script>

<?php endif; ?>
