<?php

namespace Contabai\Controllers;

use Contabai\Helper;
use WP_REST_Request;
use WP_REST_Response;

class RegisterController
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route('contabai/v1', '/sanctum/guest/register', [
            'methods' => 'POST',
            'callback' => [$this, 'store'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function store(WP_REST_Request $request): WP_REST_Response
    {
        $payload = [
            'username' => sanitize_text_field($request->get_param('username') ?? ''),
            'email' => trim((string) ($request->get_param('email') ?? '')),
            'password' => $request->get_param('password') ?? '',
            'password_confirmation' => $request->get_param('password_confirmation') ?? '',
        ];

        $language = sanitize_text_field($request->get_param('language') ?? '');
        if ($language !== '') {
            $payload['language'] = $language;
        }

        [$body, $status] = Helper::api('POST', '/sanctum/guest/register', $payload);

        return new WP_REST_Response($body, $status ?: 500);
    }
}
