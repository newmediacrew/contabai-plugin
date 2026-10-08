<?php

namespace Contabai\Controllers;

class ListingRewriteController
{

    public static function listing_url(array $listing): string
    {
        $id = (int) ($listing['id'] ?? 0);
        $country = str_replace('_', '-', sanitize_title((string) ($listing['country_name'] ?? $listing['country'] ?? '')));
        $city = str_replace('_', '-', sanitize_title((string) ($listing['city_name'] ?? $listing['city'] ?? '')));
        $titleSlug = sanitize_title((string) ($listing['title'] ?? ''));

        return home_url('/' . CONTABAI_LISTING_PAGE_SLUG . '/' . $country . '/' . $city . '/' . $titleSlug . '-' . $id);
    }

    public function __construct()
    {
        add_filter('query_vars', [$this, 'add_query_var']);
        add_action('init', [$this, 'add_rewrite_rule']);
        add_action('template_redirect', [$this, 'handle_template_redirect']);
        add_filter('redirect_canonical', [$this, 'disable_canonical_redirect'], 10, 2);
        add_action('update_option_contabai_listing_page_slug', [$this, 'invalidate_rewrite_cache']);
    }

    public function add_query_var(array $vars): array
    {
        $vars[] = 'contabai_listing_id';

        return $vars;
    }

    public function add_rewrite_rule(): void
    {
        $listing_page = get_page_by_path(CONTABAI_LISTING_PAGE_SLUG);
        if ($listing_page) {

            add_rewrite_rule(
                '^' . CONTABAI_LISTING_PAGE_SLUG . '/(.+)-([0-9]+)/?$',
                'index.php?page_id=' . $listing_page->ID . '&contabai_listing_id=$matches[2]',
                'top'
            );
        }
    }

    public function handle_template_redirect(): void
    {
        if (! is_page(CONTABAI_LISTING_PAGE_SLUG)) {
            return;
        }

        $id = (int) get_query_var('contabai_listing_id');

        if ($id < 1) {
            wp_safe_redirect(home_url('/' . CONTABAI_LISTINGS_PAGE_SLUG), 302);
            exit;
        }

        [$data] = ListingController::get($id);
        $listing = $data['data']['listing'] ?? null;
        if ($listing && ListingController::hostAllows($listing)) {
            $canonical = self::listing_url($listing);
            $canonicalPath = untrailingslashit((string) parse_url($canonical, PHP_URL_PATH));
            $currentPath = untrailingslashit((string) strtok($_SERVER['REQUEST_URI'] ?? '', '?'));
            if ($currentPath !== $canonicalPath) {
                wp_safe_redirect($canonical, 301);
                exit;
            }
        }
    }

    public function disable_canonical_redirect($redirect_url, $requested_url): mixed
    {
        if (is_page(CONTABAI_LISTING_PAGE_SLUG) && get_query_var('contabai_listing_id')) {
            return false;
        }

        return $redirect_url;
    }

    public function invalidate_rewrite_cache(): void
    {
        delete_option('rewrite_rules');
    }
}
