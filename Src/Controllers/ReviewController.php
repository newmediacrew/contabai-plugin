<?php

namespace Contabai\Controllers;

use Contabai\Helper;
use WP_REST_Request;
use WP_REST_Response;

class ReviewController
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route('contabai/v1', '/sanctum/listing/reviews/(?P<id>[0-9]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'index'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('contabai/v1', '/sanctum/guest/review/(?P<booking>[0-9]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'store'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        $query = [];
        $per_page = absint($request->get_param('per_page'));
        $page = absint($request->get_param('page'));
        if ($per_page) {
            $query['per_page'] = $per_page;
        }
        if ($page) {
            $query['page'] = $page;
        }
        $qs = $query ? '?' . http_build_query($query) : '';

        [$body, $status] = Helper::api('GET', '/sanctum/listing/reviews/' . (int) $request['id'] . $qs);

        return new WP_REST_Response($body, $status ?: 500);
    }

    public function store(WP_REST_Request $request): WP_REST_Response
    {
        $payload = [
            'rating' => absint($request->get_param('rating')),
            'body' => (string) $request->get_param('body'),
        ];
        [$body, $status] = Helper::api('POST', '/sanctum/guest/review/' . (int) $request['booking'], $payload, true);

        return new WP_REST_Response($body, $status ?: 500);
    }
}
