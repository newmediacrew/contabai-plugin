<?php
/*
Plugin Name: Contabai
Plugin URI: https://www.contabai.network
Description: Contabai — property listings platform frontend for WordPress.
Version: 1.0.2
Requires at least: 7.1
Requires PHP: 8.3
Tested up to: 7.1
Author: Contabai
Author URI: https://www.contabai.network
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: contabai
Domain Path: /languages
Update URI: https://github.com/newmediacrew/contabai-plugin
*/

use Contabai\Controllers\SessionController;
use Contabai\Controllers\RegisterController;
use Contabai\Controllers\PasswordResetController;
use Contabai\Controllers\EmailVerificationController;
use Contabai\Controllers\ListingController;
use Contabai\Controllers\TranslationController;
use Contabai\Controllers\ListingRewriteController;
use Contabai\Controllers\SeoController;
use Contabai\Controllers\ShortCodes;
use Contabai\Controllers\GuestProfileController;
use Contabai\Controllers\BookingController;
use Contabai\Controllers\ChatController;
use Contabai\Controllers\ReviewController;
use Contabai\Controllers\LocationPagesController;
use Contabai\Controllers\AiSeoController;
use Contabai\Controllers\AiSeoQueueController;
use Contabai\Controllers\AiSeoMetaBoxController;
use Contabai\Controllers\PluginUpdater;

defined('ABSPATH') || exit();

require_once __DIR__ . '/vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| CORS restriction for plugin REST API routes (/contabai/v1/)
|--------------------------------------------------------------------------
|
| WordPress core reflects any Origin header with Access-Control-Allow-Credentials: true
| on all REST API responses by default. This plugin uses its own cookie-based auth
| (contabai_sanctum_session_token) forwarded to Laravel as a Bearer token — WordPress
| nonces are not involved. Without this filter, any website could make authenticated
| cross-origin requests to /contabai/v1/ endpoints using the visitor's cookies.
|
| This filter only overrides CORS headers for /contabai/v1/ routes. All other WordPress
| REST API routes (/wp/v2/, other plugins) are left untouched.
|
*/
add_filter('rest_pre_serve_request', function ($value) {
    $rest_route = $_SERVER['REQUEST_URI'] ?? '';

    if (strpos($rest_route, '/contabai/v1/') === false) {
        return $value;
    }

    header_remove('Access-Control-Allow-Origin');

    $origin = get_http_origin();
    $allowed = home_url();

    if ($origin === $allowed) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
    }

    header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');

    return $value;
}, 15);

define('CONTABAI_API_BASE_URL', esc_url_raw(get_option('contabai_base_url', 'https://app.contabai.network')));
define('CONTABAI_VERIFY_SSL', (int) get_option('contabai_verify_ssl', 1));
define('CONTABAI_LISTINGS_PAGE_SLUG', esc_attr(get_option('contabai_listings_page_slug', 'contabai-listings')));
define('CONTABAI_LISTING_PAGE_SLUG', esc_attr(get_option('contabai_listing_page_slug', 'contabai-listing')));
define('CONTABAI_LOGIN_PAGE_SLUG', esc_attr(get_option('contabai_login_page_slug', 'contabai-login')));
define('CONTABAI_REGISTER_PAGE_SLUG', esc_attr(get_option('contabai_register_page_slug', 'contabai-register')));
define('CONTABAI_FORGOT_PASSWORD_PAGE_SLUG', esc_attr(get_option('contabai_forgot_password_page_slug', 'contabai-forgot-password')));
define('CONTABAI_VERIFY_EMAIL_PAGE_SLUG', esc_attr(get_option('contabai_verify_email_page_slug', 'contabai-verify-email')));
define('CONTABAI_ACCOUNT_PAGE_SLUG', esc_attr(get_option('contabai_account_page_slug', 'contabai-account')));
define('CONTABAI_PROFILE_PAGE_SLUG', esc_attr(get_option('contabai_profile_page_slug', 'contabai-profile')));
define('CONTABAI_BOOKINGS_PAGE_SLUG', esc_attr(get_option('contabai_bookings_page_slug', 'contabai-bookings')));
define('CONTABAI_CHAT_PAGE_SLUG', esc_attr(get_option('contabai_chat_page_slug', 'contabai-chat')));
define('CONTABAI_BOOK_PAGE_SLUG', esc_attr(get_option('contabai_book_page_slug', 'contabai-book')));
define('CONTABAI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CONTABAI_PLUGIN_DIR', plugin_dir_path(__FILE__));

add_filter('script_loader_tag', function ($tag, $handle) {
    if ($handle === 'contabai-vanilla-calendar-boot') {
        return str_replace('<script ', '<script type="module" ', $tag);
    }

    return $tag;
}, 10, 2);

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('contabai-plugin-css', plugin_dir_url(__FILE__) . 'assets/css/plugin.css', [], '47');
    wp_enqueue_script('contabai-alpine-collapse', plugin_dir_url(__FILE__) . 'assets/js/alpine-collapse.min.js', [], '3', ['strategy' => 'defer']);
    wp_enqueue_script('contabai-alpinejs', plugin_dir_url(__FILE__) . 'assets/js/alpine.min.js', ['contabai-alpine-collapse'], '4', ['strategy' => 'defer']);
});

add_action('admin_enqueue_scripts', function () {
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    if (in_array($page, ['contabai-settings', 'contabai-shortcodes', 'contabai-tools'], true)) {
        wp_enqueue_style('contabai-admin', plugin_dir_url(__FILE__) . 'assets/css/admin.css', [], '2');
    }
});

add_action('wp_enqueue_scripts', function () {
    if (contabai_current_guest() === null) {
        return;
    }
    wp_enqueue_script('contabai-chat-sound', CONTABAI_PLUGIN_URL . 'assets/js/chat-sound.js', [], '1', ['strategy' => 'defer', 'in_footer' => true]);
    wp_enqueue_script('contabai-chat-bootstrap', CONTABAI_PLUGIN_URL . 'assets/js/chat-bootstrap.js', ['contabai-chat-sound'], '2', ['strategy' => 'defer', 'in_footer' => true]);
    wp_add_inline_script('contabai-chat-bootstrap', 'window.contabaiChatConfig = ' . wp_json_encode([
        'worker' => CONTABAI_PLUGIN_URL . 'assets/js/chat-poll-worker.js?v=2',
        'lastSeenUrl' => rest_url('contabai/v1/sanctum/guest/last-seen'),
        'pollBase' => rest_url('contabai/v1/sanctum/guest/chat/poll/'),
        'heartbeatMs' => 20000,
        'threadMs' => 5000,
        'newMessageFrom' => __('New message from %s', 'contabai'),
        'newMessage' => __('New message', 'contabai'),
    ]) . ';', 'before');
});

add_action('plugins_loaded', function () {
    $controllers = [
        SessionController::class,
        RegisterController::class,
        PasswordResetController::class,
        EmailVerificationController::class,
        ListingController::class,
        TranslationController::class,
        ListingRewriteController::class,
        SeoController::class,
        ShortCodes::class,
        GuestProfileController::class,
        BookingController::class,
        ChatController::class,
        ReviewController::class,
        LocationPagesController::class,
        AiSeoController::class,
        AiSeoQueueController::class,
        AiSeoMetaBoxController::class,
        PluginUpdater::class,
    ];

    foreach ($controllers as $controller) {
        if (class_exists($controller)) {
            new $controller();
        }
    }
});

add_action('admin_menu', function () {
    add_menu_page('Contabai Settings', 'Contabai plugin', 'manage_options', 'contabai-settings', 'contabai_render_settings_page', 'dashicons-admin-home', 59.1);
    add_submenu_page('contabai-settings', 'Shortcodes', 'Shortcodes', 'manage_options', 'contabai-shortcodes', 'contabai_render_shortcodes_page');
});

add_action('admin_init', function () {
    // Advanced tab — API connection (its own group so saving it never touches the Basic options).
    register_setting('contabai_advanced_group', 'contabai_base_url');
    register_setting('contabai_advanced_group', 'contabai_verify_ssl', ['sanitize_callback' => 'intval']);
    register_setting('contabai_advanced_group', 'contabai_dev_mode', ['sanitize_callback' => 'intval']);
    register_setting('contabai_advanced_group', 'contabai_show_ai_tools', ['sanitize_callback' => 'intval']);
    register_setting('contabai_advanced_group', 'contabai_catalog_mode', ['sanitize_callback' => 'intval']);

    // Basic tab — host binding + page slugs (its own group).
    register_setting('contabai_basic_group', 'contabai_host_id', ['sanitize_callback' => 'absint']);
    foreach ([
        'contabai_listings_page_slug', 'contabai_listing_page_slug',
        'contabai_login_page_slug', 'contabai_register_page_slug',
        'contabai_forgot_password_page_slug', 'contabai_verify_email_page_slug',
        'contabai_account_page_slug', 'contabai_profile_page_slug',
        'contabai_bookings_page_slug', 'contabai_chat_page_slug', 'contabai_book_page_slug',
    ] as $option) {
        register_setting('contabai_basic_group', $option);
    }

    add_settings_section(
        'contabai_advanced_section',
        'API connection',
        function () {
            echo '<p>Connection to the Laravel Sanctum backend the Contabai frontend proxies. Change these only if you know what you are doing.</p>';
        },
        'contabai-settings-advanced'
    );

    add_settings_field(
        'contabai_base_url',
        'Laravel API Base URL',
        function () {
            $value = esc_url_raw(get_option('contabai_base_url', 'https://app.contabai.network'));
            echo '<input type="url" name="contabai_base_url" value="' . esc_attr($value) . '" placeholder="https://app.contabai.network" />';
            echo '<p class="description">' . esc_html__('A FQDN without a trailing slash.', 'contabai') . '</p>';
        },
        'contabai-settings-advanced',
        'contabai_advanced_section'
    );

    add_settings_field(
        'contabai_verify_ssl',
        'Verify SSL Certificate',
        function () {
            $checked = checked(1, get_option('contabai_verify_ssl', 1), false);
            echo '<input type="hidden" name="contabai_verify_ssl" value="0" />';
            echo '<label><input type="checkbox" name="contabai_verify_ssl" value="1" ' . $checked . '> ' . esc_html__('Enable SSL verification for API requests.', 'contabai') . '</label>';
            echo '<p class="description">' . esc_html__('Leave OFF for a local .test site with a self-signed certificate. Turn ON in production.', 'contabai') . '</p>';
        },
        'contabai-settings-advanced',
        'contabai_advanced_section'
    );

    add_settings_field(
        'contabai_dev_mode',
        'Development mode',
        function () {
            $checked = checked(1, get_option('contabai_dev_mode', 0), false);
            echo '<input type="hidden" name="contabai_dev_mode" value="0" />';
            echo '<label><input type="checkbox" name="contabai_dev_mode" value="1" ' . $checked . '> ' . esc_html__('Enable', 'contabai') . '</label>';
            echo '<p class="description">' . esc_html__('Switches off plugin updates from GitHub. Turn ON for a local development site that is a git checkout. Leave OFF in production.', 'contabai') . '</p>';
        },
        'contabai-settings-advanced',
        'contabai_advanced_section'
    );

    add_settings_field(
        'contabai_catalog_mode',
        'Catalog mode',
        function () {
            $checked = checked(1, get_option('contabai_catalog_mode', 0), false);
            echo '<input type="hidden" name="contabai_catalog_mode" value="0" />';
            echo '<label><input type="checkbox" name="contabai_catalog_mode" value="1" ' . $checked . '> Show the whole Contabai catalogue.</label>';
            echo '<p class="description">Careful: this puts <em>everyone&rsquo;s</em> listings on your site — your competitors included. Unless advertising the neighbourhood is your idea of a good time, leave it off and just set your Host ID on the Basic tab. (Nothing here is secret — it&rsquo;s all public on the marketplace anyway; you&rsquo;d only be doing your rivals a favour.)</p>';
        },
        'contabai-settings-advanced',
        'contabai_advanced_section'
    );

    add_settings_field(
        'contabai_show_ai_tools',
        'AI &amp; SEO tools',
        function () {
            $checked = checked(1, get_option('contabai_show_ai_tools', 0), false);
            echo '<input type="hidden" name="contabai_show_ai_tools" value="0" />';
            echo '<label><input type="checkbox" name="contabai_show_ai_tools" value="1" ' . $checked . '> Show the Tools menu (location pages + AI SEO generation).</label>';
            echo '<p class="description"><strong class="contabai-admin-warning">⚠ These tools are still BETA — use at your own risk.</strong></p>';
        },
        'contabai-settings-advanced',
        'contabai_advanced_section'
    );

    add_settings_section(
        'contabai_host_section',
        'Host',
        function () {
            echo '<p>Enter your Host ID to show your own listings on this site. You will find your Host ID in the app, on your profile page.</p>';
        },
        'contabai-settings-basic'
    );

    add_settings_field(
        'contabai_host_id',
        'Host ID',
        function () {
            $value = (int) get_option('contabai_host_id', 0);
            echo '<input type="number" min="0" step="1" name="contabai_host_id" value="' . esc_attr($value ?: '') . '" placeholder="e.g. 42" />';
        },
        'contabai-settings-basic',
        'contabai_host_section'
    );

    add_settings_section(
        'contabai_basic_section',
        'Page slugs',
        function () {
            echo '<p>The URL slug for each Contabai page. Change one only if it clashes with a page you already have.</p>';
        },
        'contabai-settings-basic'
    );

    add_settings_field(
        'contabai_listings_page_slug',
        'Listings page slug',
        function () {
            $value = get_option('contabai_listings_page_slug', 'contabai-listings');
            echo '<input type="text" name="contabai_listings_page_slug" value="' . esc_attr($value) . '" />';
        },
        'contabai-settings-basic',
        'contabai_basic_section'
    );

    add_settings_field(
        'contabai_listing_page_slug',
        'Single listing page slug',
        function () {
            $value = get_option('contabai_listing_page_slug', 'contabai-listing');
            echo '<input type="text" name="contabai_listing_page_slug" value="' . esc_attr($value) . '" />';
            echo '<p class="description">' . esc_html__('Base of the single-listing URL: /{slug}/{country}/{city}/{title}-{id}. After changing this, visit Settings → Permalinks → Save to refresh the rewrite.', 'contabai') . '</p>';
        },
        'contabai-settings-basic',
        'contabai_basic_section'
    );

    foreach ([
        'contabai_login_page_slug' => ['Login page slug', 'contabai-login'],
        'contabai_register_page_slug' => ['Register page slug', 'contabai-register'],
        'contabai_forgot_password_page_slug' => ['Forgot-password page slug', 'contabai-forgot-password'],
        'contabai_verify_email_page_slug' => ['Verify-email page slug', 'contabai-verify-email'],
        'contabai_account_page_slug' => ['Account hub page slug', 'contabai-account'],
        'contabai_profile_page_slug' => ['Profile page slug', 'contabai-profile'],
        'contabai_bookings_page_slug' => ['Bookings page slug', 'contabai-bookings'],
        'contabai_chat_page_slug' => ['Chat page slug', 'contabai-chat'],
        'contabai_book_page_slug' => ['Booking wizard page slug', 'contabai-book'],
    ] as $option => $meta) {
        add_settings_field(
            $option,
            $meta[0],
            function () use ($option, $meta) {
                $value = get_option($option, $meta[1]);
                echo '<input type="text" name="' . esc_attr($option) . '" value="' . esc_attr($value) . '" />';
            },
            'contabai-settings-basic',
            'contabai_basic_section'
        );
    }

});

function contabai_render_settings_page(): void
{
    if (! current_user_can('manage_options')) return;

    $tabs = ['basic' => 'Basic', 'updates' => __('Updates', 'contabai'), 'advanced' => 'Advanced'];
    $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'basic';
    if (! isset($tabs[$tab])) {
        $tab = 'basic';
    }
    ?>
    <div class="wrap contabai-admin">
        <h1><?php esc_html_e('Contabai Settings', 'contabai'); ?></h1>
        <nav class="nav-tab-wrapper">
            <?php foreach ($tabs as $slug => $label): ?>
                <a href="<?php echo esc_url(add_query_arg(['page' => 'contabai-settings', 'tab' => $slug], admin_url('admin.php'))); ?>"
                   class="nav-tab <?php echo $tab === $slug ? 'nav-tab-active' : ''; ?>"><?php echo esc_html($label); ?></a>
            <?php endforeach; ?>
        </nav>
        <?php if ($tab === 'updates') : ?>
            <?php PluginUpdater::render_status(); ?>
        <?php else : ?>
        <form method="post" action="options.php">
            <?php
            if ($tab === 'advanced') {
                settings_fields('contabai_advanced_group');
                do_settings_sections('contabai-settings-advanced');
            } else {
                settings_fields('contabai_basic_group');
                do_settings_sections('contabai-settings-basic');
            }
            submit_button();
            ?>
        </form>
        <?php endif; ?>
    </div>
    <?php
}

function contabai_render_shortcodes_page(): void
{
    if (! current_user_can('manage_options')) return;
    ?>
    <div class="wrap contabai-admin">
        <h1><?php esc_html_e('Available Shortcodes', 'contabai'); ?></h1>
        <p><?php esc_html_e('Shortcodes provided by the Contabai plugin.', 'contabai'); ?></p>
        <table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th scope="col" class="contabai-col-shortcode"><?php esc_html_e('Shortcode', 'contabai'); ?></th>
                <th scope="col" class="contabai-col-desc"><?php esc_html_e('Description', 'contabai'); ?></th>
            </tr>
            </thead>
            <tbody>
            <tr><td colspan="2"><strong><?php esc_html_e('Catalogue', 'contabai'); ?></strong></td></tr>
            <tr>
                <td><code>[contabai_listings]</code></td>
                <td>
                    <?php echo sprintf(esc_html__('Displays listings in a paginated grid. Use on the page with slug %s.', 'contabai'), '<b>' . esc_html(CONTABAI_LISTINGS_PAGE_SLUG) . '</b>'); ?>
                    <br><br>
                    <code>per_page</code> — <?php esc_html_e('(optional) Listings per page, 1–50. Default: 36', 'contabai'); ?><br>
                    <code>country</code>, <code>city</code> — <?php esc_html_e('(optional) Lock the grid to a location (codes, e.g. cw / willemstad). Used by the auto-generated location pages.', 'contabai'); ?><br>
                    <code>[contabai_listings country="cw" city="willemstad"]</code>
                    <br><br>
                    <?php esc_html_e('Every grid puts a compact search bar above itself, filled in from the URL so a visitor can change the destination, dates or party without going back to the home page. On a location-locked grid the bar starts on that location.', 'contabai'); ?>
                </td>
            </tr>
            <tr>
                <td><code>[contabai_random_listings]</code></td>
                <td>
                    <?php esc_html_e('Shows a grid of random active listings (fresh on each load). Place on any page; you can use it multiple times. (Not auto-created — add it yourself.)', 'contabai'); ?>
                    <br><br>
                    <code>count</code> — <?php esc_html_e('(optional) How many listings to show (1–50). Default: 8', 'contabai'); ?><br><br>
                    <?php esc_html_e('Optional filters (all fixed for this placement): country, city, area, guests, bedrooms, bathrooms, price_min, price_max (prices in cents, e.g. 20000 = 200.00), and comma-separated property_type and amenities.', 'contabai'); ?><br>
                    <code>[contabai_random_listings count="6" country="cw" property_type="apartment,villa" price_max="20000"]</code>
                </td>
            </tr>
            <tr>
                <td><code>[contabai_listing]</code></td>
                <td><?php echo sprintf(esc_html__('Renders a single listing detail page. Use on the page with slug %1$s. The listing is looked up by the trailing integer id in the URL /%2$s/{country}/{city}/{title}-{id}.', 'contabai'), '<b>' . esc_html(CONTABAI_LISTING_PAGE_SLUG) . '</b>', esc_html(CONTABAI_LISTING_PAGE_SLUG)); ?></td>
            </tr>
            <tr>
                <td><code>[contabai_search]</code></td>
                <td><?php esc_html_e('Search bar (Where / When / Who) that navigates to the listings results grid with the chosen filters in the URL. Rendered automatically in the front-page hero; place it on any other page too, and more than once per page. Best on a template without .entry-content — theme prose styles otherwise add spacing inside the panels.', 'contabai'); ?>
                    <br><br>
                    <code>results</code> — <?php echo sprintf(esc_html__('(optional) Where the search submits to. Default: the %s page.', 'contabai'), '<b>' . esc_html(CONTABAI_LISTINGS_PAGE_SLUG) . '</b>'); ?><br>
                    <code>compact</code> — <?php esc_html_e('(optional) A tighter, narrower bar for above a results grid. Set it to 1. The grid shortcode already does this for you.', 'contabai'); ?><br>
                    <code>country</code>, <code>city</code> — <?php esc_html_e('(optional) Location keys to preselect in Where when the URL has none (e.g. country="cw" city="willemstad"). The grid shortcode passes its own location on location pages.', 'contabai'); ?><br>
                    <code>[contabai_search results="https://example.com/holiday-homes"]</code>
                    <br><br>
                    <?php esc_html_e('The bar reads the URL on load, so wherever it sits it opens showing the destination, dates and party the visitor already chose.', 'contabai'); ?></td>
            </tr>

            <tr><td colspan="2"><strong><?php esc_html_e('Auth (auto-created pages)', 'contabai'); ?></strong></td></tr>
            <tr>
                <td><code>[contabai_login_form]</code></td>
                <td><?php echo sprintf(esc_html__('Guest login form. Auto-created on the %s page.', 'contabai'), '<b>' . esc_html(CONTABAI_LOGIN_PAGE_SLUG) . '</b>'); ?></td>
            </tr>
            <tr>
                <td><code>[contabai_register_form]</code></td>
                <td><?php echo sprintf(esc_html__('Guest registration form (with language select). Auto-created on the %s page.', 'contabai'), '<b>' . esc_html(CONTABAI_REGISTER_PAGE_SLUG) . '</b>'); ?></td>
            </tr>
            <tr>
                <td><code>[contabai_forgot_password_form]</code></td>
                <td><?php echo sprintf(esc_html__('Forgot-password request form. Auto-created on the %s page. (The reset-password step itself stays on the Laravel host.)', 'contabai'), '<b>' . esc_html(CONTABAI_FORGOT_PASSWORD_PAGE_SLUG) . '</b>'); ?></td>
            </tr>
            <tr>
                <td><code>[contabai_verify_email]</code></td>
                <td><?php echo sprintf(esc_html__('Post-registration "check your email" screen with resend-verification. Auto-created on the %s page.', 'contabai'), '<b>' . esc_html(CONTABAI_VERIFY_EMAIL_PAGE_SLUG) . '</b>'); ?></td>
            </tr>

            <tr><td colspan="2"><strong><?php esc_html_e('Account (logged-in, auto-created pages)', 'contabai'); ?></strong></td></tr>
            <tr>
                <td><code>[contabai_account]</code></td>
                <td><?php echo sprintf(esc_html__('Logged-in account hub linking to profile and bookings. Auto-created on the %s page.', 'contabai'), '<b>' . esc_html(CONTABAI_ACCOUNT_PAGE_SLUG) . '</b>'); ?></td>
            </tr>
            <tr>
                <td><code>[contabai_profile]</code></td>
                <td><?php echo sprintf(esc_html__('Guest account self-service (profile, contact details, avatar, email, password). Auto-created on the %s page.', 'contabai'), '<b>' . esc_html(CONTABAI_PROFILE_PAGE_SLUG) . '</b>'); ?></td>
            </tr>
            <tr>
                <td><code>[contabai_bookings]</code></td>
                <td><?php echo sprintf(esc_html__('Guest booking history — list, detail and PDF. Auto-created on the %s page.', 'contabai'), '<b>' . esc_html(CONTABAI_BOOKINGS_PAGE_SLUG) . '</b>'); ?></td>
            </tr>
            <tr>
                <td><code>[contabai_chat]</code></td>
                <td><?php echo sprintf(esc_html__('Guest ↔ host chat (inbox + thread). Auto-created on the %s page.', 'contabai'), '<b>' . esc_html(CONTABAI_CHAT_PAGE_SLUG) . '</b>'); ?></td>
            </tr>
            <tr>
                <td><code>[contabai_book]</code></td>
                <td><?php echo sprintf(esc_html__('Booking wizard (party, your details, final check). Auto-created on the %s page. Reached from the Book now button on a property, which passes the property and the chosen dates in the URL.', 'contabai'), '<b>' . esc_html(CONTABAI_BOOK_PAGE_SLUG) . '</b>'); ?></td>
            </tr>
            </tbody>
        </table>
    </div>
    <?php
}

add_action('admin_init', function () {
    $pages = [
        [
            'slug' => get_option('contabai_listings_page_slug', 'contabai-listings'),
            'title' => 'Listings',
            'shortcode' => '[contabai_listings]',

            'template' => 'template-listing.php',
        ],
        [
            'slug' => get_option('contabai_listing_page_slug', 'contabai-listing'),
            'title' => 'Listing',
            'shortcode' => '[contabai_listing]',
            'template' => 'template-listing.php',
        ],
        [
            'slug' => get_option('contabai_login_page_slug', 'contabai-login'),
            'title' => 'Login',
            'shortcode' => '[contabai_login_form]',
            'template' => 'template-contabai-session-and-hub.php',
        ],
        [
            'slug' => get_option('contabai_register_page_slug', 'contabai-register'),
            'title' => 'Register',
            'shortcode' => '[contabai_register_form]',
            'template' => 'template-contabai-session-and-hub.php',
        ],
        [
            'slug' => get_option('contabai_forgot_password_page_slug', 'contabai-forgot-password'),
            'title' => 'Forgot Password',
            'shortcode' => '[contabai_forgot_password_form]',
            'template' => 'template-contabai-session-and-hub.php',
        ],
        [
            'slug' => get_option('contabai_verify_email_page_slug', 'contabai-verify-email'),
            'title' => 'Verify Email',
            'shortcode' => '[contabai_verify_email]',
            'template' => 'template-contabai-session-and-hub.php',
        ],

        [
            'slug' => get_option('contabai_account_page_slug', 'contabai-account'),
            'title' => 'Account',
            'shortcode' => '[contabai_account]',
            'template' => 'template-contabai-session-and-hub.php',
        ],
        [
            'slug' => get_option('contabai_profile_page_slug', 'contabai-profile'),
            'title' => 'Profile',
            'shortcode' => '[contabai_profile]',
            'template' => 'template-contabai-session-and-hub.php',
        ],
        [
            'slug' => get_option('contabai_bookings_page_slug', 'contabai-bookings'),
            'title' => 'Bookings',
            'shortcode' => '[contabai_bookings]',
            'template' => 'template-contabai-session-and-hub.php',
        ],
        [
            'slug' => get_option('contabai_chat_page_slug', 'contabai-chat'),
            'title' => 'Chat',
            'shortcode' => '[contabai_chat]',
            'template' => 'template-contabai-session-and-hub.php',
        ],
        [
            'slug' => get_option('contabai_book_page_slug', 'contabai-book'),
            'title' => 'Book',
            'shortcode' => '[contabai_book]',
            'template' => 'template-contabai-session-and-hub.php',
        ],
    ];

    $created = false;
    foreach ($pages as $page) {
        $existing = get_page_by_path($page['slug']);

        if ($existing) {
            if (! empty($page['template'])) {
                update_post_meta($existing->ID, '_wp_page_template', $page['template']);
            }
            continue;
        }

        $args = [
            'post_title' => $page['title'],
            'post_name' => $page['slug'],
            'post_content' => $page['shortcode'],
            'post_status' => 'publish',
            'post_type' => 'page',
        ];
        if (! empty($page['template'])) {
            $args['meta_input'] = ['_wp_page_template' => $page['template']];
        }

        wp_insert_post($args);
        $created = true;
    }

    if ($created) {
        delete_option('rewrite_rules');
    }
});

function contabai_session_check(): array
{
    static $result = null;

    if ($result !== null) {
        return $result;
    }
    $result = [null, 0];

    if (! empty($_COOKIE['contabai_sanctum_session_token'])) {
        [$body, $status] = SessionController::verify_token($_COOKIE['contabai_sanctum_session_token']);
        $result = [$status === 200 ? ($body['data'] ?? null) : null, $status];
    }

    return $result;
}

function contabai_current_guest(): ?array
{
    return contabai_session_check()[0];
}

add_action('template_redirect', function () {
    [$guest, $sessionStatus] = contabai_session_check();
    $loggedIn = $guest !== null;

    $accountPages = [CONTABAI_ACCOUNT_PAGE_SLUG, CONTABAI_PROFILE_PAGE_SLUG, CONTABAI_BOOKINGS_PAGE_SLUG, CONTABAI_CHAT_PAGE_SLUG, CONTABAI_BOOK_PAGE_SLUG];
    $authPages    = [CONTABAI_LOGIN_PAGE_SLUG, CONTABAI_REGISTER_PAGE_SLUG, CONTABAI_FORGOT_PASSWORD_PAGE_SLUG, CONTABAI_VERIFY_EMAIL_PAGE_SLUG];

    if ($sessionStatus === 401) {
        setcookie('contabai_sanctum_session_token', '', [
            'expires' => time() - 3600, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax',
        ]);
    }

    if (! $loggedIn && is_page($accountPages)) {
        wp_safe_redirect(home_url('/' . CONTABAI_LOGIN_PAGE_SLUG));
        exit;
    }
    if ($loggedIn && is_page($authPages)) {
        wp_safe_redirect(home_url('/' . CONTABAI_ACCOUNT_PAGE_SLUG));
        exit;
    }
});

add_action('wp_footer', function () {
    echo \Contabai\View::render('components.toast');
});

add_filter('locale', function ($locale) {
    $guest = contabai_current_guest();
    if (empty($guest['language'])) {
        return $locale;
    }
    $langMap = ['nl' => 'nl_NL', 'en' => 'en_US', 'es' => 'es_ES', 'de' => 'de_DE', 'fr' => 'fr_FR', 'pt' => 'pt_PT'];
    return $langMap[$guest['language']] ?? $locale;
});

add_action('init', function () {
    load_plugin_textdomain('contabai', false, dirname(plugin_basename(__FILE__)) . '/languages');
}, 0);
