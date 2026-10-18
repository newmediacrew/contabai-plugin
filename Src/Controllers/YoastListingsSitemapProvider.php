<?php

namespace Contabai\Controllers;

class YoastListingsSitemapProvider implements \WPSEO_Sitemap_Provider
{
    public function handles_type($type)
    {
        return $type === 'listings';
    }

    public function get_index_links($max_entries)
    {
        $links = [];
        $last = (int) (ListingsSitemapProvider::page(1)['last_page'] ?? 0);
        for ($n = 1; $n <= $last; $n++) {
            $links[] = ['loc' => \WPSEO_Sitemaps_Router::get_base_url('listings-sitemap' . ($n === 1 ? '' : $n) . '.xml')];
        }

        return $links;
    }

    public function get_sitemap_links($type, $max_entries, $current_page)
    {
        $listings = ListingsSitemapProvider::page((int) $current_page)['listings'] ?? [];
        if ($listings === []) {
            throw new \OutOfBoundsException();
        }

        return array_map(fn ($listing) => ['loc' => ListingRewriteController::listing_url($listing)], $listings);
    }
}
