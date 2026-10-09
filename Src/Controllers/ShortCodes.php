<?php

namespace Contabai\Controllers;

use Contabai\Helper;
use Contabai\View;

class ShortCodes
{
    public function __construct()
    {
        add_shortcode('contabai_login_form', [$this, 'render_login_form']);
        add_shortcode('contabai_register_form', [$this, 'render_register_form']);
        add_shortcode('contabai_forgot_password_form', [$this, 'render_forgot_password_form']);
        add_shortcode('contabai_verify_email', [$this, 'render_verify_email']);
        add_shortcode('contabai_account', [$this, 'render_account_hub']);
        add_shortcode('contabai_profile', [$this, 'render_profile']);
        add_shortcode('contabai_bookings', [$this, 'render_bookings']);
        add_shortcode('contabai_chat', [$this, 'render_chat']);
        add_shortcode('contabai_book', [$this, 'render_book']);
        add_shortcode('contabai_search', [$this, 'render_search']);
        add_shortcode('contabai_listings', [$this, 'render_listings']);
        add_shortcode('contabai_random_listings', [$this, 'render_random_listings']);
        add_shortcode('contabai_listing', [$this, 'render_listing']);
    }

    public function render_login_form(): string
    {
        return View::render('auth.index');
    }

    public function render_register_form(): string
    {
        [$body] = Helper::api('GET', '/sanctum/translation');
        $translation = $body['data']['translations'] ?? [];
        $group = $translation[Helper::currentLang()] ?? $translation['en'] ?? [];

        return View::render('auth.register', [
            'languages' => $group['languages'] ?? [],
        ]);
    }

    public function render_forgot_password_form(): string
    {
        return View::render('auth.forgot-password');
    }

    public function render_verify_email(): string
    {
        return View::render('auth.verify-email');
    }

    public function render_account_hub(): string
    {
        return View::render('account.hub');
    }

    public function render_profile(): string
    {

        [$body] = Helper::api('GET', '/sanctum/translation');
        $translation = $body['data']['translations'] ?? [];
        $group = $translation[Helper::currentLang()] ?? $translation['en'] ?? [];

        return View::render('account.index', [
            'languages' => $group['languages'] ?? [],
            'countryOptions' => $this->country_options($group),
        ]);
    }

    public function render_bookings(): string
    {

        [$body] = Helper::api('GET', '/sanctum/translation');
        $translation = $body['data']['translations'] ?? [];
        $group = $translation[Helper::currentLang()] ?? $translation['en'] ?? [];

        return View::render('bookings.index', [
            'labels' => $group,
        ]);
    }

    public function render_chat(): string
    {

        return View::render('chat.index');
    }

    public function render_book(): string
    {
        $listingId = absint($_GET['listing'] ?? 0);
        $arrival = sanitize_text_field(wp_unslash($_GET['arrival'] ?? ''));
        $departure = sanitize_text_field(wp_unslash($_GET['departure'] ?? ''));

        $isDate = function (string $value): bool {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
                return false;
            }
            [$y, $m, $d] = array_map('intval', explode('-', $value));

            return checkdate($m, $d, $y);
        };

        $valid = $listingId > 0
            && $isDate($arrival)
            && $isDate($departure)
            && $departure > $arrival
            && $arrival >= current_time('Y-m-d');

        $listing = null;
        if ($valid) {
            [$data, $status] = ListingController::get($listingId);
            $listing = $data['data']['listing'] ?? null;
            if (! $listing && ($status === 429 || $status >= 500)) {
                return $this->unavailable();
            }
            $valid = $listing !== null && ListingController::hostAllows($listing);
        }

        if (! $valid) {
            return View::render('booking.index', [
                'listingId' => $listingId,
                'arrival' => $arrival,
                'departure' => $departure,
                'valid' => false,
                'listingsUrl' => home_url('/' . CONTABAI_LISTINGS_PAGE_SLUG),
            ]);
        }

        $currentLang = Helper::currentLang();

        [$prepareBody] = Helper::api('GET', '/sanctum/listing/prepare/' . $listingId);
        $termsMap = $prepareBody['data']['terms_and_conditions'] ?? null;
        $terms = is_array($termsMap)
            ? (string) ($termsMap[$currentLang] ?? $termsMap['en'] ?? '')
            : (string) ($termsMap ?? '');

        $rulesMap = $listing['house_rules'] ?? null;
        $houseRules = is_array($rulesMap)
            ? (string) ($rulesMap[$currentLang] ?? $rulesMap['en'] ?? '')
            : (string) ($rulesMap ?? '');

        [$body] = Helper::api('GET', '/sanctum/translation');
        $translation = $body['data']['translations'] ?? [];
        $labels = $translation[$currentLang] ?? $translation['en'] ?? [];

        return View::render('booking.index', [
            'listingId' => $listingId,
            'arrival' => $arrival,
            'departure' => $departure,
            'valid' => true,
            'listing' => $listing,
            'terms' => $terms,
            'houseRules' => $houseRules,
            'labels' => $labels,
            'countryOptions' => $this->country_options($labels),
            'listingsUrl' => home_url('/' . CONTABAI_LISTINGS_PAGE_SLUG),
            'bookingsUrl' => home_url('/' . CONTABAI_BOOKINGS_PAGE_SLUG),
            'listingUrl' => ListingRewriteController::listing_url($listing),
            'sessionEndpoint' => rest_url('contabai/v1/sanctum/guest/session'),
            'quoteEndpoint' => rest_url('contabai/v1/sanctum/guest/booking/quote/' . $listingId),
            'storeEndpoint' => rest_url('contabai/v1/sanctum/guest/booking/' . $listingId),
        ]);
    }

    public function render_search($atts = []): string
    {
        static $printed = false;

        $atts = shortcode_atts([
            'results' => home_url('/' . CONTABAI_LISTINGS_PAGE_SLUG),
            'compact' => '',
            'country' => '',
            'city' => '',
        ], $atts, 'contabai_search');

        $this->enqueue_calendar();

        $locations = [];
        if (! $printed) {
            [$body] = Helper::api('GET', '/sanctum/listings/locations');
            $locations = $body['data']['locations'] ?? [];
        }

        $out = View::render('search.index', [
            'locations' => $locations,
            'listingsEndpoint' => rest_url('contabai/v1/sanctum/listings'),
            'resultsUrl' => $atts['results'],
            'printScript' => ! $printed,
            'compact' => (string) $atts['compact'] !== '' && $atts['compact'] !== '0',
            'preset' => [
                'country' => sanitize_text_field((string) $atts['country']),
                'city' => sanitize_text_field((string) $atts['city']),
            ],
        ]);

        $printed = true;

        return $out;
    }

    private function country_options(array $group): array
    {
        $countries = $group['world_countries'] ?? [];
        uasort($countries, function ($a, $b) {
            return strcasecmp(remove_accents((string) $a), remove_accents((string) $b));
        });

        $options = [];
        foreach ($countries as $code => $name) {
            $options[] = ['value' => (string) $code, 'title' => (string) $name];
        }

        return $options;
    }

    private function enqueue_calendar(): void
    {
        $css = CONTABAI_PLUGIN_DIR . 'assets/css/vanilla-calendar.css';
        if (file_exists($css)) {
            wp_enqueue_style('contabai-vanilla-calendar', CONTABAI_PLUGIN_URL . 'assets/css/vanilla-calendar.css', [], '3.4.0');
        }

        wp_enqueue_script('contabai-vanilla-calendar-boot', CONTABAI_PLUGIN_URL . 'assets/js/vanilla-calendar-boot.js', [], '2', ['strategy' => 'defer']);
    }

    public function render_listings($atts = []): string
    {
        $atts = shortcode_atts(['per_page' => 36, 'country' => '', 'city' => ''], $atts, 'contabai_listings');
        $perPage = min(max(absint($atts['per_page']), 1), 50);

        $lockedFilters = [];
        $country = sanitize_text_field($atts['country']);
        $city = sanitize_text_field($atts['city']);
        if ($country !== '') {
            $lockedFilters['country'] = $country;
        }
        if ($city !== '') {
            $lockedFilters['city'] = $city;
        }

        $scope = ListingController::hostScope();
        $initialPage = max(1, absint($_GET['pg'] ?? 1));
        if ($scope['mode'] === 'empty') {
            $skeletonCount = 0;
        } else {
            $countQuery = $lockedFilters;
            if ($scope['mode'] === 'host') {
                $countQuery['host'] = $scope['host'];
            }
            $countQuery['per_page'] = 1;
            [$countBody] = Helper::api('GET', '/sanctum/listings?' . http_build_query($countQuery));
            $total = (int) ($countBody['data']['total'] ?? 0);
            $skeletonCount = max(0, min($total, $perPage));
        }

        [$body] = Helper::api('GET', '/sanctum/translation');
        $translation = $body['data']['translations'] ?? [];
        $currentLang = Helper::currentLang();
        $propertyTypes = $translation[$currentLang]['property_types'] ?? $translation['en']['property_types'] ?? [];
        $countries = $translation[$currentLang]['countries'] ?? $translation['en']['countries'] ?? [];
        $amenitiesCatalogue = $translation[$currentLang]['amenities_catalogue'] ?? $translation['en']['amenities_catalogue'] ?? [];
        $amenityCategories = $translation[$currentLang]['amenity_categories'] ?? $translation['en']['amenity_categories'] ?? [];

        return View::render('listing.index', [
            'endpoint' => rest_url('contabai/v1/sanctum/listings'),
            'initialPerPage' => $perPage,
            'skeletonCount' => $skeletonCount,
            'initialPage' => $initialPage,
            'propertyTypes' => $propertyTypes,
            'countries' => $countries,
            'amenitiesCatalogue' => $amenitiesCatalogue,
            'amenityCategories' => $amenityCategories,
            'popularAmenities' => ['wifi', 'air_conditioning', 'pool', 'pets_allowed', 'free_parking_on_premises', 'beach_access', 'waterfront', 'washer', 'hot_tub', 'bbq_grill'],
            'detailBase' => home_url('/' . CONTABAI_LISTING_PAGE_SLUG . '/'),
            'lockedFilters' => $lockedFilters,
            'searchBar' => $this->render_search(['compact' => '1', 'country' => $country, 'city' => $city]),
        ]);
    }

    public function render_random_listings($atts = []): string
    {
        static $printed = false;

        $atts = shortcode_atts([
            'count' => 8,
            'country' => '', 'city' => '', 'area' => '',
            'property_type' => '', 'amenities' => '',
            'guests' => '', 'bedrooms' => '', 'bathrooms' => '', 'price_min' => '', 'price_max' => '',
        ], $atts, 'contabai_random_listings');

        $query = ['count' => min(max(absint($atts['count']), 1), 50)];
        foreach (['country', 'city', 'area'] as $key) {
            $value = sanitize_text_field((string) $atts[$key]);
            if ($value !== '') {
                $query[$key] = $value;
            }
        }
        foreach (['guests', 'bedrooms', 'bathrooms', 'price_min', 'price_max'] as $key) {
            if ($atts[$key] !== '') {
                $query[$key] = absint($atts[$key]);
            }
        }
        foreach (['property_type', 'amenities'] as $key) {
            $list = array_values(array_filter(array_map('trim', explode(',', (string) $atts[$key])), 'strlen'));
            if ($list !== []) {
                $query[$key] = $list;
            }
        }

        $propertyTypes = [];
        $countries = [];
        if (! $printed) {
            [$body] = Helper::api('GET', '/sanctum/translation');
            $translation = $body['data']['translations'] ?? [];
            $currentLang = Helper::currentLang();
            $propertyTypes = $translation[$currentLang]['property_types'] ?? $translation['en']['property_types'] ?? [];
            $countries = $translation[$currentLang]['countries'] ?? $translation['en']['countries'] ?? [];
        }

        $out = View::render('listing.random', [
            'endpoint' => rest_url('contabai/v1/sanctum/listings/random') . '?' . http_build_query($query),
            'propertyTypes' => $propertyTypes,
            'countries' => $countries,
            'detailBase' => home_url('/' . CONTABAI_LISTING_PAGE_SLUG . '/'),
            'printScript' => ! $printed,
        ]);

        $printed = true;

        return $out;
    }

    public function render_listing(): string
    {
        $id = (int) get_query_var('contabai_listing_id');

        if ($id < 1) {
            return $this->not_found();
        }

        [$data, $status] = ListingController::get($id);
        $listing = $data['data']['listing'] ?? null;

        if (! $listing && ($status === 429 || $status >= 500)) {
            return $this->unavailable();
        }

        if (! $listing || ! ListingController::hostAllows($listing)) {
            return $this->not_found();
        }

        $this->enqueue_calendar();

        [$body] = Helper::api('GET', '/sanctum/translation');
        $translation = $body['data']['translations'] ?? [];
        $currentLang = Helper::currentLang();
        $labels = $translation[$currentLang] ?? $translation['en'] ?? [];

        return View::render('listing.show', [
            'listing' => $listing,
            'labels' => $labels,
            'listingsPageSlug' => CONTABAI_LISTINGS_PAGE_SLUG,
        ]);
    }

    private function not_found(): string
    {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);

        return '';
    }

    private function unavailable(): string
    {
        status_header(503);
        if (! headers_sent()) {
            header('Retry-After: 60');
        }

        return '';
    }
}
