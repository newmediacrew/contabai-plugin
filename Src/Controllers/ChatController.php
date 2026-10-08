<?php

namespace Contabai\Controllers;

use Contabai\Helper;
use WP_REST_Request;
use WP_REST_Response;

class ChatController
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route('contabai/v1', '/sanctum/guest/chats', [
            'methods' => 'GET',
            'callback' => [$this, 'index'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('contabai/v1', '/sanctum/guest/chat/(?P<id>[0-9]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'show'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('contabai/v1', '/sanctum/guest/message/(?P<host>[0-9]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'send'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('contabai/v1', '/sanctum/guest/chat/reply/(?P<id>[0-9]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'reply'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('contabai/v1', '/sanctum/guest/chat/poll/(?P<id>[0-9]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'poll'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('contabai/v1', '/sanctum/guest/chat/read/(?P<id>[0-9]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'read'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('contabai/v1', '/sanctum/guest/last-seen', [
            'methods' => 'POST',
            'callback' => [$this, 'last_seen'],
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

        [$body, $status] = Helper::api('GET', '/sanctum/guest/chats' . $qs, [], true);

        return new WP_REST_Response($body, $status ?: 500);
    }

    public function show(WP_REST_Request $request): WP_REST_Response
    {
        $page = absint($request->get_param('page'));
        $qs = $page ? '?page=' . $page : '';
        [$body, $status] = Helper::api('GET', '/sanctum/guest/chat/' . (int) $request['id'] . $qs, [], true);

        return new WP_REST_Response($body, $status ?: 500);
    }

    public function send(WP_REST_Request $request): WP_REST_Response
    {
        $payload = ['body' => (string) $request->get_param('body')];
        [$body, $status] = Helper::api('POST', '/sanctum/guest/message/' . (int) $request['host'], $payload, true);

        return new WP_REST_Response($body, $status ?: 500);
    }

    public function reply(WP_REST_Request $request): WP_REST_Response
    {
        $payload = ['body' => (string) $request->get_param('body')];
        [$body, $status] = Helper::api('POST', '/sanctum/guest/chat/reply/' . (int) $request['id'], $payload, true);

        return new WP_REST_Response($body, $status ?: 500);
    }

    public function poll(WP_REST_Request $request): WP_REST_Response
    {
        $after = absint($request->get_param('after'));
        $qs = $after ? '?after=' . $after : '';
        [$body, $status] = Helper::api('GET', '/sanctum/guest/chat/poll/' . (int) $request['id'] . $qs, [], true);

        return new WP_REST_Response($body, $status ?: 500);
    }

    public function read(WP_REST_Request $request): WP_REST_Response
    {
        [$body, $status] = Helper::api('POST', '/sanctum/guest/chat/read/' . (int) $request['id'], [], true);

        return new WP_REST_Response($body, $status ?: 500);
    }

    public function last_seen(WP_REST_Request $request): WP_REST_Response
    {
        [$body, $status] = Helper::api('POST', '/sanctum/guest/last-seen', [], true);

        return new WP_REST_Response($body, $status ?: 500);
    }
}
