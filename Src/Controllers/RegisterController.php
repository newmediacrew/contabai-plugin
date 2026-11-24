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
        if (contabai_turnstile_enabled() && ! $this->turnstilePassed((string) ($request->get_param('cf-turnstile-response') ?? ''))) {
            return new WP_REST_Response(['errors' => ['cf-turnstile-response' => [__('We could not confirm that you are not a robot. Please try again.', 'contabai')]]], 422);
        }

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

    private function turnstilePassed(string $token): bool
    {
        if ($token === '' || strlen($token) > 2048) {
            return false;
        }

        $response = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'timeout' => 10,
            'body' => array_filter([
                'secret' => trim((string) get_option('contabai_turnstile_secret_key', '')),
                'response' => $token,
                'remoteip' => Helper::visitorIp(),
            ]),
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        return ($body['success'] ?? false) === true;
    }
}
