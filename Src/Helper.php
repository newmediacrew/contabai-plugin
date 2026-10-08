<?php

namespace Contabai;

class Helper
{
    public static function api(string $method, string $endpoint, array $body = [], bool $authenticated = false, string $token = ''): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Frontend-Origin' => home_url(),
        ];

        if (self::visitorIp() !== '') {
            $headers['X-Forwarded-For'] = self::visitorIp();
        }

        if ($authenticated) {
            $headers['Authorization'] = 'Bearer ' . ($token ?: sanitize_text_field($_COOKIE['contabai_sanctum_session_token'] ?? ''));
        }

        $args = [
            'method' => $method,
            'headers' => $headers,
            'sslverify' => CONTABAI_VERIFY_SSL,
            'timeout' => 30,
        ];

        if (self::visitorAgent() !== '') {
            $args['user-agent'] = self::visitorAgent();
        }

        if (! empty($body)) {
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request(CONTABAI_API_BASE_URL . $endpoint, $args);

        if (is_wp_error($response)) {
            return [null, 500];
        }

        return [
            json_decode(wp_remote_retrieve_body($response), true),
            wp_remote_retrieve_response_code($response),
        ];
    }

    public static function currentLang(): string
    {
        return substr(get_locale(), 0, 2);
    }

    private static function visitorIp(): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }

    private static function visitorAgent(): string
    {
        return sanitize_text_field(wp_unslash((string) ($_SERVER['HTTP_USER_AGENT'] ?? '')));
    }

    public static function api_raw(string $method, string $endpoint, bool $authenticated = true): array
    {
        $headers = [
            'X-Frontend-Origin' => home_url(),
        ];

        if (self::visitorIp() !== '') {
            $headers['X-Forwarded-For'] = self::visitorIp();
        }

        if ($authenticated) {
            $headers['Authorization'] = 'Bearer ' . sanitize_text_field($_COOKIE['contabai_sanctum_session_token'] ?? '');
        }

        $args = [
            'method' => $method,
            'headers' => $headers,
            'sslverify' => CONTABAI_VERIFY_SSL,
            'timeout' => 30,
        ];

        if (self::visitorAgent() !== '') {
            $args['user-agent'] = self::visitorAgent();
        }

        $response = wp_remote_request(CONTABAI_API_BASE_URL . $endpoint, $args);

        if (is_wp_error($response)) {
            return ['', 500, ''];
        }

        return [
            wp_remote_retrieve_body($response),
            wp_remote_retrieve_response_code($response),
            wp_remote_retrieve_header($response, 'content-type'),
        ];
    }

    public static function api_upload(string $endpoint, string $fieldName, string $filePath, string $fileName, string $mimeType): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, CONTABAI_API_BASE_URL . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, CONTABAI_VERIFY_SSL);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, CONTABAI_VERIFY_SSL ? 2 : 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_filter([
            'Authorization: Bearer ' . sanitize_text_field($_COOKIE['contabai_sanctum_session_token'] ?? ''),
            'Accept: application/json',
            'X-Frontend-Origin: ' . home_url(),
            self::visitorIp() !== '' ? 'X-Forwarded-For: ' . self::visitorIp() : '',
        ]));
        if (self::visitorAgent() !== '') {
            curl_setopt($ch, CURLOPT_USERAGENT, self::visitorAgent());
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            $fieldName => new \CURLFile($filePath, $mimeType, $fileName),
        ]);

        $body = json_decode(curl_exec($ch), true);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$body, $status];
    }

    public static function api_upload_with_fields(string $endpoint, string $fieldName, string $filePath, string $fileName, string $mimeType, array $fields = []): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, CONTABAI_API_BASE_URL . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, CONTABAI_VERIFY_SSL);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, CONTABAI_VERIFY_SSL ? 2 : 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_filter([
            'Authorization: Bearer ' . sanitize_text_field($_COOKIE['contabai_sanctum_session_token'] ?? ''),
            'Accept: application/json',
            'X-Frontend-Origin: ' . home_url(),
            self::visitorIp() !== '' ? 'X-Forwarded-For: ' . self::visitorIp() : '',
        ]));
        if (self::visitorAgent() !== '') {
            curl_setopt($ch, CURLOPT_USERAGENT, self::visitorAgent());
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, array_merge($fields, [
            $fieldName => new \CURLFile($filePath, $mimeType, $fileName),
        ]));

        $body = json_decode(curl_exec($ch), true);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$body, $status];
    }

    public static function openai_responses(string $apiKey, array $input, array $schema, string $model, string $reasoningEffort = 'high', bool $useWebSearch = true, int $timeout = 300): array
    {
        $body = [
            'model'     => $model,
            'input'     => $input,
            'reasoning' => ['effort' => $reasoningEffort],
            'text'      => [
                'format' => [
                    'type'   => 'json_schema',
                    'name'   => 'seo_page',
                    'schema' => $schema,
                    'strict' => true,
                ],
            ],
        ];

        if ($useWebSearch) {
            $body['tools'] = [['type' => 'web_search']];
        }

        $response = wp_remote_post('https://api.openai.com/v1/responses', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ],
            'sslverify' => true,
            'timeout'   => $timeout,
            'body'      => wp_json_encode($body),
        ]);

        if (is_wp_error($response)) {
            return [null, 0];
        }

        return [
            json_decode(wp_remote_retrieve_body($response), true),
            wp_remote_retrieve_response_code($response),
        ];
    }

    public static function pexels_search(string $apiKey, string $query, int $perPage = 15, int $timeout = 20): array
    {
        $url = 'https://api.pexels.com/v1/search?' . http_build_query([
            'query'       => $query,
            'orientation' => 'landscape',
            'per_page'    => $perPage,
        ]);

        $response = wp_remote_get($url, [
            'headers'   => ['Authorization' => $apiKey],
            'sslverify' => true,
            'timeout'   => $timeout,
        ]);

        if (is_wp_error($response)) {
            return [null, 0];
        }

        return [
            json_decode(wp_remote_retrieve_body($response), true),
            wp_remote_retrieve_response_code($response),
        ];
    }
}
