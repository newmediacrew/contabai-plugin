<?php

namespace Contabai\Controllers;

class SitemapController
{
    public function __construct()
    {
        add_action('init', [$this, 'register']);
        add_filter('wp_sitemaps_add_provider', [$this, 'drop_users'], 10, 2);
        add_filter('wp_sitemaps_posts_query_args', [$this, 'exclude_app_pages'], 10, 2);
    }

    public static function render_tab(): void
    {
        $url = home_url('/wp-sitemap.xml');
        ?>
        <h2><?php esc_html_e('Sitemap', 'contabai'); ?></h2>
        <p class="contabai-admin-intro"><?php esc_html_e('Lists your pages, posts and every listing for search engines. Submit this URL in Google Search Console.', 'contabai'); ?></p>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Sitemap URL', 'contabai'); ?></th>
                <td><a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener"><code><?php echo esc_html($url); ?></code></a></td>
            </tr>
        </table>
        <?php if (! get_option('blog_public')) : ?>
            <div class="notice notice-warning inline"><p>
                <?php printf(
                    esc_html__('Search engines are discouraged on this site, so the sitemap is switched off and every page is hidden from search. Change this under %s.', 'contabai'),
                    '<a href="' . esc_url(admin_url('options-reading.php')) . '">' . esc_html__('Settings → Reading', 'contabai') . '</a>'
                ); ?>
            </p></div>
        <?php endif;
    }

    public function register(): void
    {
        wp_register_sitemap_provider('listings', new ListingsSitemapProvider());
    }

    public function drop_users($provider, string $name)
    {
        return $name === 'users' ? false : $provider;
    }

    public function exclude_app_pages(array $args, string $postType): array
    {
        if ($postType !== 'page') {
            return $args;
        }

        $slugs = [
            CONTABAI_LISTING_PAGE_SLUG, CONTABAI_LOGIN_PAGE_SLUG, CONTABAI_REGISTER_PAGE_SLUG,
            CONTABAI_FORGOT_PASSWORD_PAGE_SLUG, CONTABAI_VERIFY_EMAIL_PAGE_SLUG, CONTABAI_ACCOUNT_PAGE_SLUG,
            CONTABAI_PROFILE_PAGE_SLUG, CONTABAI_BOOKINGS_PAGE_SLUG, CONTABAI_CHAT_PAGE_SLUG, CONTABAI_BOOK_PAGE_SLUG,
        ];
        foreach ($slugs as $slug) {
            $page = get_page_by_path($slug);
            if ($page) {
                $args['post__not_in'][] = $page->ID;
            }
        }

        return $args;
    }
}
