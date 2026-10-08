<?php

namespace Contabai\Controllers;

use Contabai\Helper;
use WP_REST_Request;
use WP_REST_Response;

class GuestProfileController
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route('contabai/v1', '/sanctum/guest/profile', [
            'methods' => 'PATCH',
            'callback' => [$this, 'update'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('contabai/v1', '/sanctum/guest/profile/contact-details', [
            'methods' => 'PATCH',
            'callback' => [$this, 'update_contact_details'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('contabai/v1', '/sanctum/guest/profile/email', [
            'methods' => 'PATCH',
            'callback' => [$this, 'update_email'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('contabai/v1', '/sanctum/guest/profile/password', [
            'methods' => 'PATCH',
            'callback' => [$this, 'update_password'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('contabai/v1', '/sanctum/guest/profile/avatar', [
            'methods' => 'POST',
            'callback' => [$this, 'update_avatar'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('contabai/v1', '/sanctum/guest/profile/avatar', [
            'methods' => 'DELETE',
            'callback' => [$this, 'destroy_avatar'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function update(WP_REST_Request $request): WP_REST_Response
    {
        [$body, $status] = Helper::api('PATCH', '/sanctum/guest/profile', [
            'nickname' => sanitize_text_field($request->get_param('nickname') ?? ''),
            'language' => sanitize_text_field($request->get_param('language') ?? ''),
        ], true);

        return new WP_REST_Response($body, $status ?: 500);
    }

    public function update_contact_details(WP_REST_Request $request): WP_REST_Response
    {
        [$body, $status] = Helper::api('PATCH', '/sanctum/guest/profile/contact-details', [
            'first_name' => sanitize_text_field($request->get_param('first_name') ?? ''),
            'last_name' => sanitize_text_field($request->get_param('last_name') ?? ''),
            'address' => sanitize_text_field($request->get_param('address') ?? ''),
            'postcode' => sanitize_text_field($request->get_param('postcode') ?? ''),
            'city' => sanitize_text_field($request->get_param('city') ?? ''),
            'country' => sanitize_text_field($request->get_param('country') ?? ''),
            'phone' => sanitize_text_field($request->get_param('phone') ?? ''),
        ], true);

        return new WP_REST_Response($body, $status ?: 500);
    }

    public function update_email(WP_REST_Request $request): WP_REST_Response
    {
        [$body, $status] = Helper::api('PATCH', '/sanctum/guest/profile/email', [
            'email' => trim((string) ($request->get_param('email') ?? '')),
        ], true);

        return new WP_REST_Response($body, $status ?: 500);
    }

    public function update_password(WP_REST_Request $request): WP_REST_Response
    {

        [$body, $status] = Helper::api('PATCH', '/sanctum/guest/profile/password', [
            'password' => $request->get_param('password') ?? '',
            'password_confirmation' => $request->get_param('password_confirmation') ?? '',
        ], true);

        return new WP_REST_Response($body, $status ?: 500);
    }

    public function update_avatar(WP_REST_Request $request): WP_REST_Response
    {
        $files = $request->get_file_params();
        $file = $files['avatar'] ?? null;

        if (empty($file) || empty($file['tmp_name'])) {
            return new WP_REST_Response(['errors' => ['avatar' => [__('No file uploaded.', 'contabai')]]], 422);
        }

        [$body, $status] = Helper::api_upload_with_fields(
            '/sanctum/guest/profile/avatar',
            'avatar',
            $file['tmp_name'],
            $file['name'],
            $file['type'],
            ['_method' => 'PATCH']
        );

        return new WP_REST_Response($body, $status ?: 500);
    }

    public function destroy_avatar(WP_REST_Request $request): WP_REST_Response
    {
        [$body, $status] = Helper::api('DELETE', '/sanctum/guest/profile/avatar', [], true);

        return new WP_REST_Response($body, $status ?: 500);
    }
}
