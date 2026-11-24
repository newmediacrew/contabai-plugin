<?php

namespace Contabai\Controllers;

class SeoController
{
    private ?array $listing = null;
    private ?int $locationId = null;

    public function __construct()
    {
        add_action('wp', [$this, 'init']);
    }

    public function init(): void
    {

        $listingId = (int) get_query_var('contabai_listing_id');
        if ($listingId >= 1) {
            [$data] = ListingController::get($listingId);
            $this->listing = $data['data']['listing'] ?? null;
            if ($this->listing !== null && ListingController::hostAllows($this->listing)) {
                $this->takeover();
                add_action('wp_head', [$this, 'render_meta_tags'], 1);
            }
            return;
        }

        if (is_page()) {
            $pageId = get_queried_object_id();
            if ((string) get_post_meta($pageId, '_contabai_seo_title', true) !== '') {
                $this->locationId = $pageId;
                $this->takeover();
                add_action('wp_head', [$this, 'render_location_meta'], 1);
            }
        }
    }

    private function takeover(): void
    {
        if (defined('WPSEO_VERSION')) {
            add_filter('wpseo_head', '__return_empty_string');
            add_filter('wpseo_frontend_presenters', '__return_empty_array');
        }

        remove_action('wp_head', 'rel_canonical');
        remove_action('wp_head', '_wp_render_title_tag', 1);

        add_filter('pre_get_document_title', [$this, 'set_title'], 20);
        add_filter('document_title_parts', [$this, 'set_title_parts'], 20);
    }

    public function set_title(): string
    {
            return esc_html($this->with_site_name($this->build_title()));
    }

    public function set_title_parts(array $parts): array
    {
        $parts['title'] = esc_html($this->build_title());

        return $parts;
    }

    public function render_meta_tags(): void
    {
        $title = esc_attr($this->with_site_name($this->build_title()));
        $description = esc_attr($this->build_meta_description($this->build_description()));
        $image = esc_url($this->listing['photos'][0]['hero'] ?? ($this->listing['photos'][0]['card'] ?? ''));
        $url = $this->build_url();

        $this->render_head_tags($title, $description, $image, $url);
        $this->render_structured_data();
    }

    public function render_location_meta(): void
    {
        $title = esc_attr($this->with_site_name((string) get_post_meta($this->locationId, '_contabai_seo_title', true)));
        $description = esc_attr(get_post_meta($this->locationId, '_contabai_seo_metadesc', true));
        $url = esc_url(get_permalink($this->locationId));

        $img = [];
        $image = '';
        $attachId = (int) get_post_thumbnail_id($this->locationId);
        if ($attachId) {
            $src = wp_get_attachment_image_src($attachId, 'contabai_og');
            if ($src) {
                $img = [
                    'width'  => (int) $src[1],
                    'height' => (int) $src[2],
                    'type'   => esc_attr(get_post_mime_type($attachId) ?: 'image/jpeg'),
                    'alt'    => esc_attr((string) get_post_meta($attachId, '_wp_attachment_image_alt', true)),
                ];
                $image = esc_url($src[0]);
            }
        }

        $this->render_head_tags($title, $description, $image, $url, $img);
    }

    private function render_head_tags(string $title, string $description, string $image, string $url, array $img = []): void
    {
        $siteName = esc_attr(get_bloginfo('name'));

        $imageTag = '';
        if ($image) {
            $imageTag  = "\n        <meta property=\"og:image\" content=\"{$image}\">";
            $imageTag .= "\n        <meta property=\"og:image:secure_url\" content=\"{$image}\">";
            if (! empty($img['width']))  { $imageTag .= "\n        <meta property=\"og:image:width\" content=\"{$img['width']}\">"; }
            if (! empty($img['height'])) { $imageTag .= "\n        <meta property=\"og:image:height\" content=\"{$img['height']}\">"; }
            if (! empty($img['type']))   { $imageTag .= "\n        <meta property=\"og:image:type\" content=\"{$img['type']}\">"; }
            if (! empty($img['alt']))    { $imageTag .= "\n        <meta property=\"og:image:alt\" content=\"{$img['alt']}\">"; }
            $imageTag .= "\n        <meta name=\"twitter:image\" content=\"{$image}\">";
            if (! empty($img['alt']))    { $imageTag .= "\n        <meta name=\"twitter:image:alt\" content=\"{$img['alt']}\">"; }
        }

        echo trim(<<<META
        <!-- Contabai SEO -->
        <title>{$title}</title>
        <meta name="description" content="{$description}">
        <link rel="canonical" href="{$url}">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{$title}">
        <meta property="og:description" content="{$description}">
        <meta property="og:url" content="{$url}">
        <meta property="og:site_name" content="{$siteName}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{$title}">
        <meta name="twitter:description" content="{$description}">{$imageTag}
        <!-- /Contabai SEO -->
        META
            ) . "\n";
    }

    private function build_title(): string
    {
        if ($this->locationId !== null) {
            return (string) get_post_meta($this->locationId, '_contabai_seo_title', true);
        }

        $title = $this->listing['title'] ?? '';
        $region = $this->listing['region_name'] ?? $this->listing['region'] ?? '';

        return $region ? $title . ' - ' . $region : $title;
    }

    private function with_site_name(string $title): string
    {
        $site = get_bloginfo('name');

        return str_ends_with($title, $site) ? $title : $title . ' - ' . $site;
    }

    private function build_url(): string
    {
        return esc_url(ListingRewriteController::listing_url($this->listing));
    }

    private function build_description(): string
    {
        $descMap = $this->listing['description'] ?? null;

        return is_array($descMap)
            ? (string) ($descMap[\Contabai\Helper::currentLang()] ?? $descMap['en'] ?? '')
            : (string) ($descMap ?? '');
    }

    private function build_meta_description(string $html, int $max = 155): string
    {
        $text  = (string) preg_replace('#</(p|div|h[1-6]|li|blockquote)>|<br\s*/?>#i', "\n", $html);
        $text  = html_entity_decode(wp_strip_all_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $lines = array_values(array_filter(array_map('trim', explode("\n", $text)), 'strlen'));

        if (count($lines) > 1 && ! preg_match('/[.!?…]$/u', $lines[0])) {
            array_shift($lines);
        }
        foreach ($lines as $i => $line) {
            if (! preg_match('/[.!?…:]$/u', $line)) {
                $lines[$i] = $line . '.';
            }
        }
        $desc = (string) preg_replace('/\s+/u', ' ', implode(' ', $lines));

        if (mb_strlen($desc) > $max) {
            $desc = preg_replace('/\s+\S*$/u', '', mb_substr($desc, 0, $max)) . '…';
        }

        return $desc;
    }

    private function render_structured_data(): void
    {
        $listing = $this->listing;
        $url = ListingRewriteController::listing_url($listing);

        $description = html_entity_decode(wp_strip_all_tags(str_replace('<', ' <', $this->build_description())), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $description = trim((string) preg_replace('/\s+/u', ' ', $description));

        $images = [];
        foreach ((array) ($listing['photos'] ?? []) as $photo) {
            $images[] = (string) ($photo['hero'] ?? $photo['card'] ?? '');
        }

        $amenities = [];
        foreach ((array) ($listing['amenities'] ?? []) as $key) {
            $amenities[] = ['@type' => 'LocationFeatureSpecification', 'name' => ucfirst(str_replace('_', ' ', (string) $key)), 'value' => true];
        }

        $price = '';
        if (! empty($listing['from_price_cents'])) {
            $price = __('from', 'contabai') . ' ' . trim(($listing['currency'] ?? '') . ' ' . number_format(((int) $listing['from_price_cents']) / 100, 2, '.', '')) . ' / ' . __('night', 'contabai');
        }

        $hour = function ($value) {
            return $value === null ? '' : sprintf('%02d:00', (int) $value);
        };

        $rental = [
            '@type' => 'VacationRental',
            '@id' => $url,
            'url' => $url,
            'identifier' => (string) ($listing['id'] ?? ''),
            'name' => $listing['title'] ?? '',
            'description' => $description,
            'image' => $images,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $listing['address'] ?? '',
                'postalCode' => $listing['postcode'] ?? '',
                'addressRegion' => $listing['region_name'] ?? '',
                'addressCountry' => strtoupper(explode('-', (string) ($listing['country'] ?? ''))[0]),
            ],
            'containsPlace' => [
                '@type' => 'Accommodation',
                'occupancy' => isset($listing['max_guests']) ? ['@type' => 'QuantitativeValue', 'value' => (int) $listing['max_guests']] : null,
                'numberOfBedrooms' => $listing['bedrooms'] ?? null,
                'numberOfBathroomsTotal' => $listing['bathrooms'] ?? null,
                'floorSize' => ! empty($listing['living_area_sqm']) ? ['@type' => 'QuantitativeValue', 'value' => $listing['living_area_sqm'], 'unitCode' => 'MTK'] : null,
                'amenityFeature' => $amenities,
            ],
            'checkinTime' => $hour($listing['checkin_from_hour'] ?? null),
            'checkoutTime' => $hour($listing['checkout_by_hour'] ?? null),
            'priceRange' => $price,
        ];

        if (isset($listing['latitude'], $listing['longitude'])) {
            $rental['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) $listing['latitude'], 'longitude' => (float) $listing['longitude']];
        }

        $reviews = $listing['reviews'] ?? [];
        if ((int) ($reviews['count'] ?? 0) > 0 && isset($reviews['average'])) {
            $rental['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (float) $reviews['average'],
                'ratingCount' => (int) $reviews['count'],
                'bestRating' => 5,
                'worstRating' => 1,
            ];
            $rental['review'] = [];
            foreach ((array) ($reviews['recent'] ?? []) as $review) {
                $rental['review'][] = [
                    '@type' => 'Review',
                    'author' => ['@type' => 'Person', 'name' => (string) ($review['reviewer']['nickname'] ?? '')],
                    'datePublished' => substr((string) ($review['created_at'] ?? ''), 0, 10),
                    'reviewRating' => ['@type' => 'Rating', 'ratingValue' => (int) ($review['rating'] ?? 0), 'bestRating' => 5],
                    'reviewBody' => (string) ($review['body'] ?? ''),
                ];
            }
        }

        $ld = ['@context' => 'https://schema.org', '@graph' => [self::without_empty($rental), self::without_empty($this->build_breadcrumb($url))]];

        echo '<script type="application/ld+json">' . wp_json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '</script>' . "\n";
    }

    private function build_breadcrumb(string $url): array
    {
        $listing = $this->listing;
        $crumbs = [[__('Home', 'contabai'), home_url('/')]];

        if (! empty($listing['country'])) {
            $countryUrl = LocationPagesController::permalink_for_key($listing['country']);
            if ($countryUrl !== '') {
                $crumbs[] = [$listing['country_name'] ?? $listing['country'], $countryUrl];
            }
            if (! empty($listing['region'])) {
                $regionUrl = LocationPagesController::permalink_for_key($listing['country'] . '/' . $listing['region']);
                if ($regionUrl !== '') {
                    $crumbs[] = [$listing['region_name'] ?? $listing['region'], $regionUrl];
                }
            }
        }
        $crumbs[] = [$listing['title'] ?? '', $url];

        $items = [];
        foreach ($crumbs as $position => [$name, $item]) {
            $items[] = ['@type' => 'ListItem', 'position' => $position + 1, 'name' => (string) $name, 'item' => $item];
        }

        return ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }

    private static function without_empty(array $data): array
    {
        $isList = array_is_list($data);
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = self::without_empty($value);
                $data[$key] = $value;
            }
            if ($value === null || $value === '' || $value === [] || (is_array($value) && array_keys($value) === ['@type'])) {
                unset($data[$key]);
            }
        }

        return $isList ? array_values($data) : $data;
    }
}
