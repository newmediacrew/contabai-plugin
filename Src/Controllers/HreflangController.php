<?php

namespace Contabai\Controllers;

class HreflangController
{
    public function __construct()
    {
        add_action('wp_head', [$this, 'render'], 1);
    }

    public function render(): void
    {
        if (! is_multisite() || ! get_option('blog_public')) {
            return;
        }

        $translated = null;
        $listingId = (int) get_query_var('contabai_listing_id');
        if ($listingId >= 1) {
            [$data] = ListingController::get($listingId);
            $listing = $data['data']['listing'] ?? null;
            if ($listing === null) {
                return;
            }
            $url = ListingRewriteController::listing_url($listing);
            $translated = array_keys(array_filter((array) ($listing['description'] ?? []), function ($html) {
                return trim(wp_strip_all_tags((string) $html)) !== '';
            }));
        } elseif (is_front_page()) {
            $url = home_url('/');
        } elseif (is_page()) {
            $url = (string) get_permalink();
            $pageUri = get_page_uri(get_queried_object_id());
        } else {
            return;
        }

        $path = substr($url, strlen(home_url('/')));
        $alternates = [];
        foreach (get_sites(['public' => 1, 'archived' => 0, 'deleted' => 0, 'spam' => 0, 'number' => 0, 'fields' => 'ids']) as $siteId) {
            $lang = self::language($siteId);
            if (isset($alternates[$lang]) || ($translated !== null && ! in_array($lang, $translated, true))) {
                continue;
            }
            if (isset($pageUri)) {
                switch_to_blog($siteId);
                $page = get_page_by_path($pageUri);
                restore_current_blog();
                if (! $page || $page->post_status !== 'publish') {
                    continue;
                }
            }
            $alternates[$lang] = get_home_url($siteId, '/' . $path);
        }

        if (count($alternates) < 2 || ! isset($alternates[self::language(get_current_blog_id())])) {
            return;
        }

        $mainLang = self::language(get_main_site_id());
        if (isset($alternates[$mainLang])) {
            $alternates['x-default'] = $alternates[$mainLang];
        }

        foreach ($alternates as $lang => $href) {
            echo '<link rel="alternate" hreflang="' . esc_attr($lang) . '" href="' . esc_url($href) . '">' . "\n";
        }
    }

    private static function language(int $siteId): string
    {
        $locale = get_blog_option($siteId, 'WPLANG');
        if ($locale === false) {
            $locale = get_site_option('WPLANG');
        }

        return $locale ? strtolower(substr((string) $locale, 0, 2)) : 'en';
    }
}
