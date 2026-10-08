<?php

namespace Contabai\Controllers;

use Contabai\Helper;
use WP_REST_Request;
use WP_REST_Response;

class BookingController
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route('contabai/v1', '/sanctum/guest/bookings', [
            'methods' => 'GET',
            'callback' => [$this, 'index'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('contabai/v1', '/sanctum/guest/booking/(?P<id>[0-9]+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'show'],
                'permission_callback' => '__return_true',
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'store'],
                'permission_callback' => '__return_true',
            ],
        ]);

        register_rest_route('contabai/v1', '/sanctum/guest/booking/quote/(?P<listing>[0-9]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'quote'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('contabai/v1', '/sanctum/guest/booking/pdf/(?P<id>[0-9]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'pdf'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('contabai/v1', '/sanctum/guest/booking/deposit-refund-pdf/(?P<id>[0-9]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'depositRefundPdf'],
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

        [$body, $status] = Helper::api('GET', '/sanctum/guest/bookings' . $qs, [], true);

        return new WP_REST_Response($body, $status ?: 500);
    }

    public function show(WP_REST_Request $request): WP_REST_Response
    {
        [$body, $status] = Helper::api('GET', '/sanctum/guest/booking/' . (int) $request['id'], [], true);

        return new WP_REST_Response($body, $status ?: 500);
    }

    public function quote(WP_REST_Request $request): WP_REST_Response
    {
        $body = [
            'arrival' => sanitize_text_field((string) $request->get_param('arrival')),
            'departure' => sanitize_text_field((string) $request->get_param('departure')),
            'adults' => absint($request->get_param('adults')),
        ];

        foreach (['children', 'pets'] as $key) {
            $value = $request->get_param($key);
            if ($value !== null && $value !== '') {
                $body[$key] = absint($value);
            }
        }

        $optional = $request->get_param('selected_optional_ids');
        if (is_array($optional) && $optional !== []) {
            $body['selected_optional_ids'] = array_values(array_map('absint', $optional));
        }

        [$data, $status] = Helper::api('POST', '/sanctum/guest/booking/quote/' . (int) $request['listing'], $body, true);

        return new WP_REST_Response($data, $status ?: 500);
    }

    public function store(WP_REST_Request $request): WP_REST_Response
    {
        $body = [
            'arrival' => sanitize_text_field((string) $request->get_param('arrival')),
            'departure' => sanitize_text_field((string) $request->get_param('departure')),
            'adults' => absint($request->get_param('adults')),
            'agreed_house_rules' => rest_sanitize_boolean($request->get_param('agreed_house_rules')),
            'agreed_terms' => rest_sanitize_boolean($request->get_param('agreed_terms')),
        ];

        foreach (['children', 'infants', 'pets'] as $key) {
            $value = $request->get_param($key);
            if ($value !== null && $value !== '') {
                $body[$key] = absint($value);
            }
        }

        $intro = (string) $request->get_param('intro_message');
        if ($intro !== '') {
            $body['intro_message'] = $intro;
        }

        $optional = $request->get_param('selected_optional_ids');
        if (is_array($optional) && $optional !== []) {
            $body['selected_optional_ids'] = array_values(array_map('absint', $optional));
        }

        foreach (['first_name', 'last_name', 'address', 'postcode', 'city', 'country', 'phone'] as $key) {
            $body[$key] = sanitize_text_field((string) $request->get_param($key));
        }

        [$data, $status] = Helper::api('POST', '/sanctum/guest/booking/' . (int) $request['id'], $body, true);

        return new WP_REST_Response($data, $status ?: 500);
    }

    public function pdf(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request['id'];
        [$body, $status, $ctype] = Helper::api_raw('GET', '/sanctum/guest/booking/pdf/' . $id);

        if ($status !== 200) {
            return new WP_REST_Response(json_decode($body, true), $status ?: 500);
        }

        header_remove('Content-Type');
        header('Content-Type: ' . ($ctype ?: 'application/pdf'));
        header('Content-Disposition: inline; filename="booking-' . $id . '.pdf"');
        header('Content-Length: ' . strlen($body));
        echo $body;
        exit;
    }

    public function depositRefundPdf(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request['id'];
        [$body, $status, $ctype] = Helper::api_raw('GET', '/sanctum/guest/booking/deposit-refund-pdf/' . $id);

        if ($status !== 200) {
            return new WP_REST_Response(json_decode($body, true), $status ?: 500);
        }

        header_remove('Content-Type');
        header('Content-Type: ' . ($ctype ?: 'application/pdf'));
        header('Content-Disposition: inline; filename="deposit-refund-' . $id . '.pdf"');
        header('Content-Length: ' . strlen($body));
        echo $body;
        exit;
    }
}
