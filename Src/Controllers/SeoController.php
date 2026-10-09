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
        return esc_html($this->build_title() . ' - ' . get_bloginfo('name'));
    }

    public function set_title_parts(array $parts): array
    {
        $parts['title'] = esc_html($this->build_title());

        return $parts;
    }

    public function render_meta_tags(): void
    {
        $title = esc_attr($this->build_title() . ' - ' . get_bloginfo('name'));
        $descMap  = $this->listing['description'] ?? null;
        $descText = is_array($descMap)
            ? (string) ($descMap[\Contabai\Helper::currentLang()] ?? $descMap['en'] ?? '')
            : (string) ($descMap ?? '');
        $description = esc_attr(wp_trim_words(wp_strip_all_tags($descText), 30));
        $image = esc_url($this->listing['photos'][0]['hero'] ?? ($this->listing['photos'][0]['card'] ?? ''));
        $url = $this->build_url();

        $this->emit($title, $description, $image, $url);
    }

    public function render_location_meta(): void
    {
        $title = esc_attr(get_post_meta($this->locationId, '_contabai_seo_title', true) . ' - ' . get_bloginfo('name'));
        $description = esc_attr(get_post_meta($this->locationId, '_contabai_seo_metadesc', true));
        $url = esc_url(get_permalink($this->locationId));

        $img = [];
        $attachId = (int) get_post_meta($this->locationId, '_contabai_seo_image', true);
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
        if (empty($image)) {
            $image = esc_url((string) get_the_post_thumbnail_url($this->locationId, 'large'));
        }

        $this->emit($title, $description, $image, $url, $img);
    }

    private function emit(string $title, string $description, string $image, string $url, array $img = []): void
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
        $city = $this->listing['city_name'] ?? $this->listing['city'] ?? '';

        return $city ? $title . ' - ' . $city : $title;
    }

    private function build_url(): string
    {
        return esc_url(ListingRewriteController::listing_url($this->listing));
    }
}
