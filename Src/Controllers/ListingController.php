<?php

namespace Contabai\Controllers;

use Contabai\Helper;
use WP_REST_Request;
use WP_REST_Response;

class ListingController
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route('contabai/v1', '/sanctum/listings', [
            'methods' => 'GET',
            'callback' => [$this, 'index'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('contabai/v1', '/sanctum/listings/locations', [
            'methods' => 'GET',
            'callback' => [$this, 'locations'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('contabai/v1', '/sanctum/listings/random', [
            'methods' => 'GET',
            'callback' => [$this, 'random'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('contabai/v1', '/sanctum/listing/(?P<id>[0-9]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'show'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('contabai/v1', '/sanctum/listing/quote/(?P<id>[0-9]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'quote'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('contabai/v1', '/sanctum/listing/prepare/(?P<id>[0-9]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'prepare'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function prepare(WP_REST_Request $request): WP_REST_Response
    {
        [$body, $status] = Helper::api('GET', '/sanctum/listing/prepare/' . (int) $request['id']);

        return new WP_REST_Response($body, $status ?: 500);
    }

    public function quote(WP_REST_Request $request): WP_REST_Response
    {
        $body = [
            'arrival' => sanitize_text_field((string) $request->get_param('arrival')),
            'departure' => sanitize_text_field((string) $request->get_param('departure')),
        ];

        [$data, $status] = Helper::api('POST', '/sanctum/listing/quote/' . (int) $request['id'], $body);

        return new WP_REST_Response($data, $status ?: 500);
    }

    public static function get(int $id): array
    {
        static $cache = [];

        if (! isset($cache[$id])) {
            [$body, $status] = Helper::api('GET', '/sanctum/listing/' . $id);
            $cache[$id] = [$body ?? [], $status];
        }

        return $cache[$id];
    }

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        $query = [];

        foreach (['country', 'city', 'area', 'from_date', 'till_date'] as $key) {
            $value = sanitize_text_field((string) $request->get_param($key));
            if ($value !== '') {
                $query[$key] = $value;
            }
        }

        foreach (['guests', 'bedrooms', 'bathrooms', 'price_min', 'price_max', 'per_page', 'page'] as $key) {
            $value = $request->get_param($key);
            if ($value !== null && $value !== '') {
                $query[$key] = absint($value);
            }
        }

        $sort = sanitize_text_field((string) $request->get_param('sort'));
        if (in_array($sort, ['price_asc', 'price_desc', 'newest'], true)) {
            $query['sort'] = $sort;
        }

        foreach (['property_type', 'amenities'] as $key) {
            $value = $request->get_param($key);
            if (is_array($value) && $value !== []) {
                $query[$key] = array_values(array_filter(array_map('sanitize_text_field', $value), 'strlen'));
            }
        }

        $scope = self::hostScope();
        if ($scope['mode'] === 'empty') {
            return new WP_REST_Response(['data' => [
                'listings' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => (int) ($query['per_page'] ?? 36),
                'total' => 0,
            ]], 200);
        }
        if ($scope['mode'] === 'host') {
            $query['host'] = $scope['host'];
        }

        [$body, $status] = Helper::api('GET', '/sanctum/listings?' . http_build_query($query));

        return new WP_REST_Response($body, $status ?: 500);
    }

    public static function hostScope(): array
    {
        if ((int) get_option('contabai_catalog_mode', 0) === 1) {
            return ['mode' => 'catalog'];
        }

        $hostId = (int) get_option('contabai_host_id', 0);
        if ($hostId > 0) {
            return ['mode' => 'host', 'host' => $hostId];
        }

        return ['mode' => 'empty'];
    }

    public static function hostAllows(?array $listing): bool
    {
        $scope = self::hostScope();
        if ($scope['mode'] === 'catalog') {
            return true;
        }
        if ($scope['mode'] === 'empty') {
            return false;
        }

        return (int) ($listing['host']['host_id'] ?? 0) === $scope['host'];
    }

    public function locations(): WP_REST_Response
    {
        [$body, $status] = Helper::api('GET', '/sanctum/listings/locations');

        return new WP_REST_Response($body, $status ?: 500);
    }

    public function random(WP_REST_Request $request): WP_REST_Response
    {

        $query = ['count' => min(max(absint($request->get_param('count') ?: 12), 1), 50)];

        foreach (['country', 'city', 'area'] as $key) {
            $value = sanitize_text_field((string) $request->get_param($key));
            if ($value !== '') {
                $query[$key] = $value;
            }
        }

        foreach (['guests', 'bedrooms', 'bathrooms', 'price_min', 'price_max'] as $key) {
            $value = $request->get_param($key);
            if ($value !== null && $value !== '') {
                $query[$key] = absint($value);
            }
        }

        foreach (['property_type', 'amenities'] as $key) {
            $value = $request->get_param($key);
            if (is_array($value) && $value !== []) {
                $query[$key] = array_values(array_filter(array_map('sanitize_text_field', $value), 'strlen'));
            }
        }

        $scope = self::hostScope();
        if ($scope['mode'] === 'empty') {
            return new WP_REST_Response(['data' => ['listings' => []]], 200);
        }
        if ($scope['mode'] === 'host') {
            $query['host'] = $scope['host'];
        }

        [$body, $status] = Helper::api('GET', '/sanctum/listings/random?' . http_build_query($query));

        return new WP_REST_Response($body, $status ?: 500);
    }

    public function show(WP_REST_Request $request): WP_REST_Response
    {
        [$body, $status] = self::get((int) $request['id']);

        return new WP_REST_Response($body, $status ?: 500);
    }
}
