<?php

namespace Contabai\Controllers;

use Contabai\Helper;

class AiSeoGenerator
{

    public static function generate_and_store(\WP_Post $page): bool
    {
        $obj = self::generate_for_page($page);
        if ($obj === null) {
            return false;
        }

        delete_post_meta($page->ID, '_contabai_seo_error');

        self::store_page($page, $obj);
        self::attach_image($page);

        return true;
    }

    private static function attach_image(\WP_Post $page): void
    {
        $key = AiSeoController::get_pexels_key();
        if ($key === '') {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $location    = get_the_title($page);
        $parentTitle = $page->post_parent ? get_the_title($page->post_parent) : '';
        $query = $parentTitle !== ''
            ? trim($parentTitle . ' ' . $location)
            : $location;

        [$body, $status] = Helper::pexels_search($key, $query, 15);
        $photos = ($status === 200) ? ($body['photos'] ?? []) : [];

        if (empty($photos) && $parentTitle !== '') {
            [$body, $status] = Helper::pexels_search($key, $parentTitle, 15);
            $photos = ($status === 200) ? ($body['photos'] ?? []) : [];
        }
        if (empty($photos)) {
            return;
        }

        $photo = $photos[array_rand($photos)];
        $src   = $photo['src']['large2x'] ?? $photo['src']['original'] ?? $photo['src']['large'] ?? '';
        if ($src === '') {
            return;
        }

        $old = (int) get_post_meta($page->ID, '_contabai_seo_image', true);
        if ($old) {
            wp_delete_attachment($old, true);
            delete_post_meta($page->ID, '_contabai_seo_image');
        }

        $tmp = download_url($src);
        if (is_wp_error($tmp)) {
            return;
        }

        $slug = sanitize_title((string) get_post_meta($page->ID, '_contabai_seo_focus', true) ?: $location) ?: 'location';
        $fileArray = ['name' => $slug . '.jpg', 'tmp_name' => $tmp];

        $attachId = media_handle_sideload($fileArray, $page->ID);
        if (is_wp_error($attachId)) {
            @unlink($tmp);
            return;
        }

        $alt = SeoController::strip_brand((string) get_post_meta($page->ID, '_contabai_seo_title', true)) ?: $location;
        update_post_meta($attachId, '_wp_attachment_image_alt', $alt);
        wp_update_post(['ID' => $attachId, 'post_title' => $location]);

        set_post_thumbnail($page->ID, $attachId);
        update_post_meta($page->ID, '_contabai_seo_image', $attachId);
    }

    public static function generate_for_page(\WP_Post $page): ?array
    {
        $prompt   = strtr(AiSeoController::get_prompt(), self::page_context($page));
        $language = self::language();

        $input = [
            ['role' => 'system', 'content' => $prompt],
            ['role' => 'system', 'content' => "Write ALL output — the article, title, meta_description, focus_keyword, secondary_keywords, faq, best_areas, every field — in {$language}. Do not use any other language."],
            ['role' => 'user',   'content' => 'Write the SEO landing page now.'],
        ];

        [$body, $status] = Helper::openai_responses(
            AiSeoController::get_openai_key(),
            $input,
            AiSeoController::response_schema(),
            AiSeoController::get_model(),
            AiSeoController::get_reasoning_effort(),
            true,
            600
        );

        if ($status !== 200) {
            $message = is_array($body) ? trim((string) ($body['error']['message'] ?? '')) : '';
            self::record_error($page, $status > 0
                ? trim(sprintf('HTTP %d %s', $status, $message))
                : __('No response from OpenAI (network error or timeout).', 'contabai'));

            return null;
        }

        $obj = json_decode(self::extract_output_text($body), true);
        if (! is_array($obj)) {
            self::record_error($page, __('OpenAI returned no usable JSON.', 'contabai'));

            return null;
        }

        return $obj;
    }

    private static function record_error(\WP_Post $page, string $message): void
    {
        $message = sanitize_text_field($message);
        if (mb_strlen($message) > 180) {
            $message = mb_substr($message, 0, 180) . '…';
        }

        update_post_meta($page->ID, '_contabai_seo_error', $message);
    }

    public static function store_page(\WP_Post $page, array $obj): void
    {

        $key = (string) get_post_meta($page->ID, LocationPagesController::KEY_META, true);
        [$country, $city] = array_pad(explode('/', $key), 2, '');
        $grid = '[contabai_listings country="' . esc_attr($country) . '"' . ($city !== '' ? ' city="' . esc_attr($city) . '"' : '') . ']';
        $article = wp_kses_post(self::strip_citations($obj['article_html'] ?? ''));

        wp_update_post([
            'ID'           => $page->ID,
            'post_content' => $grid,
        ]);

        update_post_meta($page->ID, '_contabai_seo_article', wp_slash($article));
        update_post_meta($page->ID, '_contabai_seo_title', sanitize_text_field($obj['title'] ?? ''));
        update_post_meta($page->ID, '_contabai_seo_metadesc', sanitize_text_field(self::strip_citations($obj['meta_description'] ?? '')));
        update_post_meta($page->ID, '_contabai_seo_focus', sanitize_text_field($obj['focus_keyword'] ?? ''));
        update_post_meta($page->ID, '_contabai_seo_keywords', array_map('sanitize_text_field', $obj['secondary_keywords'] ?? []));
        update_post_meta($page->ID, '_contabai_seo_entities', array_map('sanitize_text_field', $obj['entities'] ?? []));
        update_post_meta($page->ID, '_contabai_seo_schema_types', array_map('sanitize_text_field', $obj['schema_types'] ?? []));
        update_post_meta($page->ID, '_contabai_seo_internal_links', self::clean_links($obj['internal_links'] ?? []));
        update_post_meta($page->ID, '_contabai_seo_related', array_map('sanitize_text_field', $obj['related_locations'] ?? []));
        update_post_meta($page->ID, '_contabai_seo_best_areas', self::clean_best_areas($obj['best_areas'] ?? []));
        update_post_meta($page->ID, '_contabai_seo_faq', self::clean_faq($obj['faq'] ?? []));
        update_post_meta($page->ID, '_contabai_seo_generated', current_time('mysql'));
    }

    private static function page_context(\WP_Post $page): array
    {
        $key = get_post_meta($page->ID, LocationPagesController::KEY_META, true);
        [$countryCode, $cityCode] = array_pad(explode('/', (string) $key), 2, '');
        $isCity = $cityCode !== '';

        [$locBody] = Helper::api('GET', '/sanctum/listings/locations');
        $tree        = $locBody['data']['locations'] ?? [];
        $country     = $tree[$countryCode] ?? [];
        $countryName = $country['name'] ?? $countryCode;
        $cityName    = $isCity ? ($country['cities'][$cityCode]['name'] ?? $cityCode) : '';

        $nearby = [];
        foreach (($country['cities'] ?? []) as $code => $c) {
            if ($code !== $cityCode) {
                $nearby[] = is_array($c) ? ($c['name'] ?? $code) : $c;
            }
        }

        return [
            '{{site_title}}'       => get_bloginfo('name'),
            '{{site_tagline}}'     => get_bloginfo('description'),
            '{{target_location}}'  => $isCity ? $cityName : $countryName,
            '{{target_type}}'      => $isCity ? 'city' : 'country',
            '{{country}}'          => $countryName,
            '{{city}}'             => $cityName,
            '{{area}}'             => '',
            '{{nearby_locations}}' => implode(', ', $nearby),
        ];
    }

    private static function extract_output_text(array $body): string
    {
        if (! empty($body['output_text'])) {
            return (string) $body['output_text'];
        }

        foreach (($body['output'] ?? []) as $item) {
            if (($item['type'] ?? '') === 'message') {
                foreach (($item['content'] ?? []) as $content) {
                    if (isset($content['text'])) {
                        return (string) $content['text'];
                    }
                }
            }
        }

        return '';
    }

    private static function language(): string
    {
        $locale = get_locale();

        if (function_exists('locale_get_display_language')) {
            $name = locale_get_display_language($locale, 'en');
            if (! empty($name)) {
                return $name;
            }
        }

        return $locale;
    }

    private static function strip_citations(string $text): string
    {
        $text = preg_replace('/\s*\(\[[^\]]*\]\(https?:\/\/[^\)]*\)\)/', '', $text);
        $text = preg_replace('/[?&]utm_source=openai\b/', '', $text);

        return (string) $text;
    }

    private static function clean_faq(array $faq): array
    {
        $out = [];
        foreach ($faq as $item) {
            if (is_array($item)) {
                $out[] = [
                    'question' => sanitize_text_field(self::strip_citations($item['question'] ?? '')),
                    'answer'   => wp_kses_post(self::strip_citations($item['answer'] ?? '')),
                ];
            }
        }

        return $out;
    }

    private static function clean_best_areas(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $out[] = [
                    'location' => sanitize_text_field(self::strip_citations($row['location'] ?? '')),
                    'relation' => sanitize_text_field(self::strip_citations($row['relation'] ?? '')),
                    'best_for' => sanitize_text_field(self::strip_citations($row['best_for'] ?? '')),
                ];
            }
        }

        return $out;
    }

    private static function clean_links(array $links): array
    {
        $out = [];
        foreach ($links as $link) {
            if (is_array($link)) {
                $out[] = [
                    'anchor'          => sanitize_text_field($link['anchor'] ?? ''),
                    'target_type'     => sanitize_text_field($link['target_type'] ?? ''),
                    'target_location' => sanitize_text_field($link['target_location'] ?? ''),
                    'reason'          => sanitize_text_field($link['reason'] ?? ''),
                ];
            }
        }

        return $out;
    }
}
