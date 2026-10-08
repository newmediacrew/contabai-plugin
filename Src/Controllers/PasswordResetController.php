<?php

namespace Contabai\Controllers;

use Contabai\Helper;
use WP_REST_Request;
use WP_REST_Response;

class PasswordResetController
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route('contabai/v1', '/sanctum/guest/forgot-password', [
            'methods' => 'POST',
            'callback' => [$this, 'store'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('contabai/v1', '/sanctum/guest/reset-password', [
            'methods' => 'POST',
            'callback' => [$this, 'update'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function store(WP_REST_Request $request): WP_REST_Response
    {
        [$body, $status] = Helper::api('POST', '/sanctum/guest/forgot-password', [
            'username' => sanitize_text_field($request->get_param('username') ?? ''),
        ]);

        return new WP_REST_Response($body, $status ?: 500);
    }

    public function update(WP_REST_Request $request): WP_REST_Response
    {
        [$body, $status] = Helper::api('POST', '/sanctum/guest/reset-password', [
            'token' => sanitize_text_field($request->get_param('token') ?? ''),
            'username' => sanitize_text_field($request->get_param('username') ?? ''),
            'password' => $request->get_param('password') ?? '',
            'password_confirmation' => $request->get_param('password_confirmation') ?? '',
        ]);

        return new WP_REST_Response($body, $status ?: 500);
    }
}
