<?php

namespace Contabai\Controllers;

use Contabai\Helper;
use WP_REST_Response;

class TranslationController
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route('contabai/v1', '/sanctum/translation', [
            'methods' => 'GET',
            'callback' => [$this, 'show'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function show(): WP_REST_Response
    {
        [$body, $status] = Helper::api('GET', '/sanctum/translation');

        return new WP_REST_Response($body, $status ?: 500);
    }
}
