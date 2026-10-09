<?php

namespace Contabai\Controllers;

use Contabai\View;

class AiSeoRenderer
{

    public static function is_generated(int $pageId): bool
    {
        return (string) get_post_meta($pageId, '_contabai_seo_generated', true) !== '';
    }

    public static function content(int $pageId, string $bandImage = ''): string
    {
        [$h1, $intro, $rest] = self::article_parts($pageId);
        $location = get_the_title($pageId);
        $faq   = self::faq_list($pageId);
        $areas = self::best_areas_cards($pageId);
        $hasArticle = trim($rest) !== '';

        if ($h1 === '' && trim($intro) === '' && ! $hasArticle && $faq === '' && $areas === '') {
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

        if ($hasArticle || $faq !== '' || $areas !== '') {
            $eyebrow = 'contabai-seo-band-eyebrow mb-3 text-xs font-semibold uppercase tracking-[0.28em]';
            echo '<section class="contabai-seo-band mt-12">';
            if ($bandImage !== '') {
                echo '<img src="' . esc_url($bandImage) . '" alt="" loading="lazy" decoding="async" class="contabai-seo-band-img">';
            }
            echo '<div class="mx-auto grid w-full max-w-7xl gap-12 px-4 pb-16 pt-32 lg:grid-cols-[minmax(0,1fr)_24rem]">';
            if ($hasArticle) {
                echo '<div class="min-w-0">';
                echo '<p class="' . $eyebrow . '">' . esc_html__('Good to know', 'contabai') . '</p>';
                echo '<p class="contabai-heading contabai-seo-place text-4xl text-white">' . esc_html(sprintf(__('More about %s', 'contabai'), $location)) . '</p>';
                echo '<div class="entry-content mt-6">' . wp_kses_post($rest) . '</div>';
                echo '</div>';
            }
            if ($faq !== '') {
                echo '<div class="lg:sticky lg:top-28 lg:self-start">';
                echo '<p class="' . $eyebrow . '">' . esc_html__('Frequently asked questions', 'contabai') . '</p>';
                echo $faq;
                echo '</div>';
            }
            echo '</div>';
            if ($areas !== '') {
                echo '<div class="mx-auto w-full max-w-7xl px-4 pb-16">';
                echo '<p class="' . $eyebrow . '">' . esc_html__('Best areas nearby', 'contabai') . '</p>';
                echo $areas;
                echo '</div>';
            }
            echo '<div class="pb-16"></div>';
            echo '</section>';
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

    private static function best_areas_cards(int $pageId): string
    {
        $rows = get_post_meta($pageId, '_contabai_seo_best_areas', true);
        if (empty($rows) || ! is_array($rows)) {
            return '';
        }

        ob_start();
        echo '<ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">';
        foreach ($rows as $r) {
            echo '<li class="contabai-seo-glass rounded-2xl p-5">';
            echo '<p class="contabai-heading contabai-seo-place text-xl text-white">' . esc_html($r['location'] ?? '') . '</p>';
            echo '<p class="mt-1 text-sm text-[#ece3d9]">' . esc_html($r['relation'] ?? '') . '</p>';
            echo '<p class="mt-3 text-sm text-white"><span class="font-semibold">' . esc_html__('Best for', 'contabai') . ':</span> ' . esc_html($r['best_for'] ?? '') . '</p>';
            echo '</li>';
        }
        echo '</ul>';

        return (string) ob_get_clean();
    }

    private static function faq_list(int $pageId): string
    {
        $faq = get_post_meta($pageId, '_contabai_seo_faq', true);
        if (empty($faq) || ! is_array($faq)) {
            return '';
        }

        ob_start();
        echo '<div class="space-y-3">';
        foreach ($faq as $item) {
            echo '<details class="contabai-seo-glass group rounded-2xl px-5 py-4">';
            echo '<summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-white [&::-webkit-details-marker]:hidden">' . esc_html($item['question'] ?? '') . '<span class="shrink-0 transition group-open:rotate-180">' . \Contabai\Heroicon::outline('chevron-down', 'w-4 h-4') . '</span></summary>';
            echo '<div class="mt-3 text-sm leading-relaxed text-[#ece3d9]">' . wp_kses_post($item['answer'] ?? '') . '</div>';
            echo '</details>';
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
                $items[$url] = [(string) ($l['target_location'] ?? ''), (string) ($l['anchor'] ?: ($l['target_location'] ?? ''))];
            }
        }
        foreach ($related as $name) {
            $url = self::url_for_location((string) $name);
            if ($url && $url !== get_permalink($pageId) && ! isset($items[$url])) {
                $items[$url] = [(string) $name, (string) $name];
            }
        }
        if (! $items) {
            return '';
        }

        ob_start();
        echo '<section class="mx-auto w-full max-w-7xl px-4 pb-16 pt-4">';
        echo '<h2 class="contabai-heading mb-6 text-3xl text-[color:var(--heading-color,#111827)]">' . esc_html__('Related destinations', 'contabai') . '</h2>';
        echo '<ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">';
        foreach ($items as $url => [$name, $anchor]) {
            $targetId = url_to_postid($url);
            $image = (string) get_the_post_thumbnail_url($targetId, 'medium_large');
            $imageAlt = SeoController::strip_brand((string) get_post_meta((int) get_post_thumbnail_id($targetId), '_wp_attachment_image_alt', true)) ?: $name;
            echo '<li><a href="' . esc_url($url) . '" class="group flex h-full flex-col overflow-hidden rounded-2xl border border-neutral-200 bg-white no-underline shadow-sm transition hover:shadow-lg">';
            if ($image !== '') {
                echo '<span class="block aspect-[16/10] overflow-hidden bg-neutral-100"><img src="' . esc_url($image) . '" alt="' . esc_attr($imageAlt) . '" loading="lazy" decoding="async" class="h-full w-full object-cover transition duration-500 group-hover:scale-105"></span>';
            }
            echo '<span class="flex flex-1 items-center justify-between gap-4 p-5">';
            echo '<span class="min-w-0"><span class="contabai-heading contabai-seo-place block text-xl text-[color:var(--heading-color,#111827)]">' . esc_html($name) . '</span>';
            if (mb_strtolower($anchor) !== mb_strtolower($name)) {
                echo '<span class="mt-1 block text-sm text-neutral-500">' . esc_html(self::ucfirst_mb($anchor)) . '</span>';
            }
            echo '</span><span class="shrink-0 text-neutral-400 transition group-hover:text-[color:var(--theme-color)]">' . \Contabai\Heroicon::outline('arrow-right', 'w-5 h-5') . '</span></span></a></li>';
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
