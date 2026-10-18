<?php

namespace Contabai\Controllers;

use Contabai\Helper;

class ListingsSitemapProvider extends \WP_Sitemaps_Provider
{
    public function __construct()
    {
        $this->name        = 'listings';
        $this->object_type = 'listing';
    }

    public function get_url_list($page_num, $object_subtype = ''): array
    {
        return array_map(
            fn ($listing) => ['loc' => ListingRewriteController::listing_url($listing)],
            self::page((int) $page_num)['listings'] ?? []
        );
    }

    public function get_max_num_pages($object_subtype = ''): int
    {
        return (int) (self::page(1)['last_page'] ?? 0);
    }

    public static function page(int $page): array
    {
        $scope = ListingController::hostScope();
        if ($scope['mode'] === 'empty') {
            return [];
        }

        $query = ['per_page' => 50, 'page' => $page];
        if ($scope['mode'] === 'host') {
            $query['host'] = $scope['host'];
        }
        [$body, $status] = Helper::api('GET', '/sanctum/listings?' . http_build_query($query));

        return $status === 200 ? ($body['data'] ?? []) : [];
    }
}
