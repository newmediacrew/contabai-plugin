<?php

namespace Contabai\Controllers;

use Contabai\Helper;
use WP_REST_Request;
use WP_REST_Response;

class SessionController
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route('contabai/v1', '/sanctum/guest/session', [
            'methods' => 'GET',
            'callback' => [$this, 'show'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('contabai/v1', '/sanctum/guest/session', [
            'methods' => 'POST',
            'callback' => [$this, 'store'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('contabai/v1', '/sanctum/guest/session', [
            'methods' => 'DELETE',
            'callback' => [$this, 'destroy'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function show(WP_REST_Request $request): WP_REST_Response
    {
        [$body, $status] = Helper::api('GET', '/sanctum/guest/session', [], true);

        return new WP_REST_Response($body, $status ?: 500);
    }

    public function store(WP_REST_Request $request): WP_REST_Response
    {
        $contentType = $request->get_content_type();
        if ($contentType === null || strtolower($contentType['subtype'] ?? '') !== 'json') {
            return new WP_REST_Response(['message' => __('Unsupported content type.', 'contabai')], 415);
        }

        $username = sanitize_text_field($request->get_param('username') ?? '');
        $password = $request->get_param('password') ?? '';

        [$body, $status] = Helper::api('POST', '/sanctum/guest/session', [
            'username' => $username,
            'password' => $password,
        ]);

        if ($status === 200 && ! empty($body['data']['token'])) {
            setcookie('contabai_sanctum_session_token', $body['data']['token'], [
                'path' => '/',
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            unset($body['data']['token']);
        }

        return new WP_REST_Response($body, $status);
    }

    public function destroy(WP_REST_Request $request): WP_REST_Response
    {
        [$body, $status] = Helper::api('DELETE', '/sanctum/guest/session', [], true);

        if ($status === 200) {
            setcookie('contabai_sanctum_session_token', '', [
                'path' => '/',
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        return new WP_REST_Response($body, $status);
    }

    public static function verify_token(string $token): array
    {
        [$body, $status] = Helper::api('GET', '/sanctum/guest/session', [], true, $token);

        return [$body, $status];
    }
}
