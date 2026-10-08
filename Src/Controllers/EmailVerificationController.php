<?php

namespace Contabai\Controllers;

use Contabai\Helper;
use WP_REST_Request;
use WP_REST_Response;

class EmailVerificationController
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route('contabai/v1', '/sanctum/guest/email/verification-notification', [
            'methods' => 'POST',
            'callback' => [$this, 'resend'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function resend(WP_REST_Request $request): WP_REST_Response
    {
        [$body, $status] = Helper::api('POST', '/sanctum/guest/email/verification-notification', [
            'username' => sanitize_text_field($request->get_param('username') ?? ''),
        ]);

        return new WP_REST_Response($body, $status ?: 500);
    }
}
