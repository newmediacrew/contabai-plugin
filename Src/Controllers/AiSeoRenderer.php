<?php

namespace Contabai\Controllers;

use Contabai\View;

class AiSeoRenderer
{

    public static function is_generated(int $pageId): bool
    {
        return (string) get_post_meta($pageId, '_contabai_seo_generated', true) !== '';
    }

    public static function content(int $pageId): string
    {
        [$h1, $intro, $rest] = self::article_parts($pageId);
        $location = get_the_title($pageId);

        $panels = [];
        if (trim($rest) !== '') {
            $panels[] = ['key' => 'more', 'title' => sprintf(__('More about %s', 'contabai'), $location),
                         'body' => '<div class="entry-content">' . wp_kses_post($rest) . '</div>'];
        }
        $table = self::best_areas_table($pageId);
        if ($table !== '') {
            $panels[] = ['key' => 'areas', 'title' => __('Best areas nearby', 'contabai'), 'body' => $table];
        }
        $faq = self::faq_list($pageId);
        if ($faq !== '') {
            $panels[] = ['key' => 'faq', 'title' => __('Frequently asked questions', 'contabai'), 'body' => $faq];
        }

        if ($h1 === '' && trim($intro) === '' && ! $panels) {
            return '';
        }

        ob_start();
        if ($h1 !== '' || trim($intro) !== '') {
            echo '<section class="mx-auto w-full max-w-7xl px-4 pt-8">';
            if ($h1 !== '') {
                echo '<h1 class="contabai-heading text-3xl text-[color:var(--heading-color,#111827)]">' . wp_kses_post($h1) . '</h1>';
            }
            if (trim($intro) !== '') {
                echo '<div class="entry-content mt-4">' . wp_kses_post($intro) . '</div>';
            }
            echo '</section>';
        }

        if ($panels) {
            echo '<section class="mx-auto w-full max-w-7xl px-4 py-6" x-data="{ open: \'\', toggle(k) { this.open = this.open === k ? \'\' : k } }">';
            echo '<div class="space-y-3">';
            foreach ($panels as $panel) {
                $acc_key   = $panel['key'];
                $acc_title = $panel['title'];
                $acc_body  = $panel['body'];
                include CONTABAI_PLUGIN_DIR . 'Src/Views/components/accordion-section.php';
            }
            echo '</div></section>';
        }

        return (string) ob_get_clean();
    }

    private static function article_parts(int $pageId): array
    {
        $html = (string) get_post_meta($pageId, '_contabai_seo_article', true);
        if (trim($html) === '') {
            $post = get_post($pageId);
            $content = (string) ($post->post_content ?? '');
            if (preg_match('/<div class="entry-content[^"]*">(.*)<\/div>\s*$/is', $content, $m)) {
                $html = $m[1];
            }
        }
        if (trim($html) === '') {
            return ['', '', ''];
        }

        $h1 = '';
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $m)) {
            $h1   = trim($m[1]);
            $html = preg_replace('/<h1[^>]*>.*?<\/h1>/is', '', $html, 1);
        }

        $intro = $html;
        $rest  = '';
        if (preg_match('/<h2[\s>]/i', $html, $m, PREG_OFFSET_CAPTURE)) {
            $pos   = $m[0][1];
            $intro = substr($html, 0, $pos);
            $rest  = substr($html, $pos);
        }

        return [$h1, $intro, $rest];
    }

    private static function best_areas_table(int $pageId): string
    {
        $rows = get_post_meta($pageId, '_contabai_seo_best_areas', true);
        if (empty($rows) || ! is_array($rows)) {
            return '';
        }

        $table_headers = [__('Location', 'contabai'), __('Distance / relation', 'contabai'), __('Best for', 'contabai')];
        $table_rows = [];
        foreach ($rows as $r) {
            $table_rows[] = [$r['location'] ?? '', $r['relation'] ?? '', $r['best_for'] ?? ''];
        }

        ob_start();
        include CONTABAI_PLUGIN_DIR . 'Src/Views/components/table.php';

        return (string) ob_get_clean();
    }

    private static function faq_list(int $pageId): string
    {
        $faq = get_post_meta($pageId, '_contabai_seo_faq', true);
        if (empty($faq) || ! is_array($faq)) {
            return '';
        }

        ob_start();
        echo '<div class="divide-y divide-neutral-200">';
        foreach ($faq as $item) {
            echo '<div class="py-4">';
            echo '<h4 class="contabai-heading text-[color:var(--heading-color,#111827)]">' . esc_html($item['question'] ?? '') . '</h4>';
            echo '<div class="mt-2 leading-relaxed text-neutral-700">' . wp_kses_post($item['answer'] ?? '') . '</div>';
            echo '</div>';
        }
        echo '</div>';

        return (string) ob_get_clean();
    }

    public static function related(int $pageId): string
    {
        $links = (array) get_post_meta($pageId, '_contabai_seo_internal_links', true);
        $related = (array) get_post_meta($pageId, '_contabai_seo_related', true);

        $items = [];
        foreach ($links as $l) {
            $url = self::url_for_location($l['target_location'] ?? '');
            if ($url && $url !== get_permalink($pageId)) {
                $items[$url] = $l['anchor'] ?: ($l['target_location'] ?? '');
            }
        }
        foreach ($related as $name) {
            $url = self::url_for_location((string) $name);
            if ($url && $url !== get_permalink($pageId) && ! isset($items[$url])) {
                $items[$url] = $name;
            }
        }
        if (! $items) {
            return '';
        }

        ob_start();
        echo '<section class="mx-auto w-full max-w-7xl px-4 py-6">';
        echo '<h2 class="contabai-heading mb-4 text-2xl text-[color:var(--heading-color,#111827)]">' . esc_html__('Related destinations', 'contabai') . '</h2>';
        echo '<ul class="flex flex-wrap gap-2">';
        foreach ($items as $url => $anchor) {
            echo '<li><a href="' . esc_url($url) . '" class="inline-block rounded-md border border-neutral-200 bg-white px-3 py-1.5 text-sm text-neutral-700 no-underline transition hover:bg-neutral-50">' . esc_html(self::ucfirst_mb((string) $anchor)) . '</a></li>';
        }
        echo '</ul></section>';

        return (string) ob_get_clean();
    }

    public static function grid(int $pageId): string
    {
        $key = (string) get_post_meta($pageId, LocationPagesController::KEY_META, true);
        if ($key === '') {
            return '';
        }
        [$country, $city] = array_pad(explode('/', $key), 2, '');

        $shortcode = '[contabai_listings country="' . esc_attr($country) . '"';
        if ($city !== '') {
            $shortcode .= ' city="' . esc_attr($city) . '"';
        }
        $shortcode .= ']';

        return do_shortcode($shortcode);
    }

    public static function jsonld(int $pageId): string
    {
        $graph = [self::breadcrumb_ld($pageId)];

        $attachId = (int) get_post_meta($pageId, '_contabai_seo_image', true);
        if ($attachId) {
            $src = wp_get_attachment_image_src($attachId, 'contabai_og');
            if ($src) {
                $graph[] = [
                    '@type'   => 'ImageObject',
                    'url'     => $src[0],
                    'width'   => (int) $src[1],
                    'height'  => (int) $src[2],
                    'caption' => wp_strip_all_tags(get_the_title($pageId)),
                ];
            }
        }

        $faq = get_post_meta($pageId, '_contabai_seo_faq', true);
        if (! empty($faq) && is_array($faq)) {
            $mainEntity = [];
            foreach ($faq as $item) {
                $mainEntity[] = [
                    '@type'          => 'Question',
                    'name'           => wp_strip_all_tags($item['question'] ?? ''),
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => wp_strip_all_tags($item['answer'] ?? '')],
                ];
            }
            $graph[] = ['@type' => 'FAQPage', 'mainEntity' => $mainEntity];
        }

        $ld = ['@context' => 'https://schema.org', '@graph' => $graph];

        return '<script type="application/ld+json">' . wp_json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }

    private static function breadcrumb_ld(int $pageId): array
    {
        $items = [];
        $chain = array_reverse(get_post_ancestors($pageId));
        $chain[] = $pageId;
        $pos = 1;
        $items[] = ['@type' => 'ListItem', 'position' => $pos++, 'name' => __('Home', 'contabai'), 'item' => home_url('/')];
        foreach ($chain as $id) {
            $items[] = ['@type' => 'ListItem', 'position' => $pos++, 'name' => get_the_title($id), 'item' => get_permalink($id)];
        }

        return ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }

    private static function ucfirst_mb(string $s): string
    {
        if ($s === '') {
            return $s;
        }

        return mb_strtoupper(mb_substr($s, 0, 1)) . mb_substr($s, 1);
    }

    public static function url_for_location(string $name): string
    {
        static $map = null;
        if ($map === null) {
            $map = [];
            $pages = get_posts([
                'post_type'      => 'page',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'meta_key'       => LocationPagesController::FLAG_META,
                'meta_value'     => '1',
            ]);
            foreach ($pages as $p) {
                $map[sanitize_title(get_the_title($p))] = get_permalink($p->ID);
            }
        }

        return $map[sanitize_title($name)] ?? '';
    }
}
