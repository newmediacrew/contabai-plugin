<?php

namespace Contabai\Controllers;

use Contabai\Helper;

class LocationPagesController
{
    const FLAG_META = '_contabai_generated_by_tools';
    const KEY_META  = '_contabai_location_key';
    const TEMPLATE  = 'template-location.php';

    const COUNTRIES_OPTION  = 'contabai_location_countries';
    const DEFAULT_COUNTRIES = '';

    public function __construct()
    {

        add_action('admin_menu', [$this, 'register_menu'], 20);
        add_action('admin_post_contabai_location_sync', [$this, 'handle_sync']);
        add_action('admin_post_contabai_save_location_countries', [$this, 'handle_save_countries']);
    }

    private function allowed_countries(): array
    {
        $raw = (string) get_option(self::COUNTRIES_OPTION, self::DEFAULT_COUNTRIES);

        return array_values(array_filter(array_map('trim', explode(',', $raw)), 'strlen'));
    }

    private function country_allowed(string $code, string $name, array $tokens): bool
    {
        $codeLower = strtolower($code);
        $nameSlug  = sanitize_title($name);

        foreach ($tokens as $token) {
            if (strtolower($token) === $codeLower || sanitize_title($token) === $nameSlug) {
                return true;
            }
        }

        return false;
    }

    public function register_menu(): void
    {
        if (! (int) get_option('contabai_show_ai_tools', 0)) {
            return;
        }

        add_submenu_page('contabai-settings', 'Tools', 'Tools', 'manage_options', 'contabai-tools', [$this, 'render_page']);
    }

    private function fetch_locations(): array
    {
        [$body, $status] = Helper::api('GET', '/sanctum/listings/locations');

        if ($status !== 200) {
            return [];
        }

        return $body['data']['locations'] ?? [];
    }

    private function valid_nodes(array $locations): array
    {
        $nodes = [];
        $allowed = $this->allowed_countries();

        foreach ($locations as $countryCode => $country) {
            if (! is_array($country)) {
                continue;
            }

            $countryName = $country['name'] ?? $countryCode;

            if (! empty($allowed) && ! $this->country_allowed($countryCode, $countryName, $allowed)) {
                continue;
            }

            $countrySlug = sanitize_title($countryName);
            $nodes[$countryCode] = [
                'key'        => $countryCode,
                'title'      => $countryName,
                'slug'       => $countrySlug,
                'path'       => $countrySlug,
                'parent_key' => null,
                'country'    => $countryCode,
                'city'       => null,
            ];

            foreach (($country['cities'] ?? []) as $cityCode => $city) {
                $cityName = is_array($city) ? ($city['name'] ?? $cityCode) : $city;
                $citySlug = sanitize_title($cityName);
                $key = $countryCode . '/' . $cityCode;
                $nodes[$key] = [
                    'key'        => $key,
                    'title'      => $cityName,
                    'slug'       => $citySlug,
                    'path'       => $countrySlug . '/' . $citySlug,
                    'parent_key' => $countryCode,
                    'country'    => $countryCode,
                    'city'       => $cityCode,
                ];
            }
        }

        return $nodes;
    }

    private function owned(): array
    {
        $posts = get_posts([
            'post_type'      => 'page',
            'post_status'    => ['publish', 'draft', 'pending', 'private', 'future'],
            'posts_per_page' => -1,
            'meta_key'       => self::FLAG_META,
            'meta_value'     => '1',
        ]);

        $map = [];
        foreach ($posts as $post) {
            $key = get_post_meta($post->ID, self::KEY_META, true);
            if ($key !== '') {
                $map[$key] = $post;
            }
        }

        return $map;
    }

    public static function permalink_for_key(string $key): string
    {
        static $map = null;

        if ($map === null) {
            $map = [];
            $posts = get_posts([
                'post_type'      => 'page',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'meta_key'       => self::FLAG_META,
                'meta_value'     => '1',
            ]);
            foreach ($posts as $post) {
                $k = (string) get_post_meta($post->ID, self::KEY_META, true);
                if ($k !== '') {
                    $map[$k] = get_permalink($post->ID);
                }
            }
        }

        return $map[$key] ?? '';
    }

    public function plan(): array
    {
        $locations = $this->fetch_locations();
        if (empty($locations)) {
            return ['error' => __('Could not load locations from the API — check the connection before syncing.', 'contabai')];
        }

        $available = [];
        foreach ($locations as $code => $country) {
            if (is_array($country)) {
                $available[$code] = $country['name'] ?? $code;
            }
        }

        $nodes = $this->valid_nodes($locations);
        $owned = $this->owned();

        $create = [];
        $keep   = [];
        foreach ($nodes as $key => $node) {
            if (isset($owned[$key])) {
                $keep[] = $node;
            } else {
                $create[] = $node;
            }
        }

        $trash = [];
        foreach ($owned as $key => $post) {
            if (! isset($nodes[$key])) {
                $trash[] = $post;
            }
        }

        return ['error' => null, 'create' => $create, 'keep' => $keep, 'trash' => $trash, 'countries' => $available];
    }

    public function sync(): array
    {
        $locations = $this->fetch_locations();
        if (empty($locations)) {
            return ['error' => __('Could not load locations from the API — nothing was changed.', 'contabai')];
        }

        $nodes = $this->valid_nodes($locations);
        $owned = $this->owned();

        $created = 0;
        $kept    = 0;
        $trashed = 0;
        $keyToId = [];
        foreach ($owned as $key => $post) {
            $keyToId[$key] = $post->ID;
        }

        foreach ($nodes as $key => $node) {
            if (isset($owned[$key])) {
                $kept++;
                continue;
            }

            $parentId = 0;
            if ($node['parent_key'] !== null) {
                $parentId = $keyToId[$node['parent_key']] ?? 0;
            }

            $id = $this->create_page($node, $parentId);
            if ($id) {
                $created++;
                $keyToId[$key] = $id;
            }
        }

        foreach ($owned as $key => $post) {
            if (! isset($nodes[$key])) {
                wp_trash_post($post->ID);
                $trashed++;
            }
        }

        flush_rewrite_rules();

        return ['error' => null, 'created' => $created, 'kept' => $kept, 'trashed' => $trashed];
    }

    private function author_id(): int
    {
        $current = get_current_user_id();
        if ($current) {
            return $current;
        }

        $admins = get_users(['role' => 'administrator', 'number' => 1, 'orderby' => 'ID', 'order' => 'ASC', 'fields' => 'ID']);

        return $admins ? (int) $admins[0] : 0;
    }

    private function create_page(array $node, int $parentId): int
    {
        $content = '[contabai_listings country="' . esc_attr($node['country']) . '"';
        if (! empty($node['city'])) {
            $content .= ' city="' . esc_attr($node['city']) . '"';
        }
        $content .= ']';

        $id = wp_insert_post([
            'post_title'   => $node['title'],
            'post_name'    => $node['slug'],
            'post_author'  => $this->author_id(),
            'post_parent'  => $parentId,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => $content,
            'meta_input'   => [
                '_wp_page_template' => self::TEMPLATE,
                self::FLAG_META     => '1',
                self::KEY_META      => $node['key'],
            ],
        ]);

        return is_wp_error($id) ? 0 : (int) $id;
    }

    public function handle_sync(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions.', 'contabai'));
        }
        check_admin_referer('contabai_location_sync');

        $result = $this->sync();
        set_transient('contabai_location_sync_result', $result, 60);

        wp_safe_redirect(add_query_arg('page', 'contabai-tools', admin_url('admin.php')));
        exit;
    }

    public function handle_save_countries(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions.', 'contabai'));
        }
        check_admin_referer('contabai_save_location_countries');

        $codes = array_map(function ($code) {
            return sanitize_text_field(wp_unslash((string) $code));
        }, (array) ($_POST['location_countries'] ?? []));
        $codes = array_values(array_filter($codes, 'strlen'));

        update_option(self::COUNTRIES_OPTION, implode(',', $codes));

        wp_safe_redirect(add_query_arg(['page' => 'contabai-tools', 'tab' => 'location-pages', 'countries_saved' => '1'], admin_url('admin.php')));
        exit;
    }

    public function render_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $tabs = [
            'location-pages' => __('Location pages', 'contabai'),
            'ai-seo'         => __('AI SEO Settings', 'contabai'),
            'generate'       => __('Generate', 'contabai'),
        ];
        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'location-pages';
        if (! isset($tabs[$tab])) {
            $tab = 'location-pages';
        }
        ?>
        <div class="wrap contabai-admin">
            <h1><?php esc_html_e('Tools', 'contabai'); ?></h1>
            <nav class="nav-tab-wrapper">
                <?php foreach ($tabs as $slug => $label): ?>
                    <a href="<?php echo esc_url(add_query_arg(['page' => 'contabai-tools', 'tab' => $slug], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $tab === $slug ? 'nav-tab-active' : ''; ?>"><?php echo esc_html($label); ?></a>
                <?php endforeach; ?>
            </nav>

            <?php
            if ($tab === 'ai-seo') {
                AiSeoController::render_tab();
            } elseif ($tab === 'generate') {
                AiSeoQueueController::render_tab();
            } else {
                $this->render_location_tab();
            }
            ?>
        </div>
        <?php
    }

    private function render_location_tab(): void
    {
        $summary = get_transient('contabai_location_sync_result');
        if ($summary !== false) {
            delete_transient('contabai_location_sync_result');
        }

        $plan = $this->plan();
        ?>
            <?php if (is_array($summary) && ! empty($summary['error'])): ?>
                <div class="notice notice-error"><p><?php echo esc_html($summary['error']); ?></p></div>
            <?php elseif (is_array($summary)): ?>
                <div class="notice notice-success is-dismissible"><p>
                    <?php echo esc_html(sprintf(__('Location pages synced — created %1$d, kept %2$d, trashed %3$d.', 'contabai'), $summary['created'], $summary['kept'], $summary['trashed'])); ?>
                </p></div>
            <?php endif; ?>

            <?php if (isset($_GET['countries_saved'])): ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Country list saved.', 'contabai'); ?></p></div>
            <?php endif; ?>

            <h2><?php esc_html_e('Location pages', 'contabai'); ?></h2>
            <p class="contabai-admin-intro"><?php esc_html_e('Generates a nested page per country and city from the Laravel locations endpoint, each showing that location\'s listings. Only pages created by this tool are ever touched — your other pages are never modified. This view is a preview; nothing changes until you click the button.', 'contabai'); ?></p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="contabai_save_location_countries">
                <?php wp_nonce_field('contabai_save_location_countries'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Countries', 'contabai'); ?></th>
                        <td>
                            <?php
                            $selected = $this->allowed_countries();
                            $available = $plan['countries'] ?? [];
                            ?>
                            <?php if (empty($available)): ?>
                                <p class="description"><?php esc_html_e('No countries available from the API yet.', 'contabai'); ?></p>
                            <?php else: ?>
                                <fieldset>
                                    <?php foreach ($available as $code => $name):
                                        $isOn = empty($selected) || $this->country_allowed((string) $code, (string) $name, $selected); ?>
                                        <label class="contabai-country-option">
                                            <input type="checkbox" name="location_countries[]" value="<?php echo esc_attr($code); ?>" <?php checked($isOn); ?>>
                                            <?php echo esc_html($name); ?> <code><?php echo esc_html($code); ?></code>
                                        </label>
                                    <?php endforeach; ?>
                                </fieldset>
                                <p class="description"><?php esc_html_e('Tick the countries to generate location pages for. All are on by default.', 'contabai'); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <p class="contabai-admin-block"><?php submit_button(__('Save countries', 'contabai'), 'primary', 'submit', false); ?></p>
            </form>

            <?php if (! empty($plan['error'])): ?>
                <div class="notice notice-error"><p><?php echo esc_html($plan['error']); ?></p></div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <tbody>
                        <tr><td><strong><?php esc_html_e('To create', 'contabai'); ?></strong></td><td><?php echo (int) count($plan['create']); ?></td></tr>
                        <tr><td><strong><?php esc_html_e('Already exist (kept)', 'contabai'); ?></strong></td><td><?php echo (int) count($plan['keep']); ?></td></tr>
                        <tr><td><strong><?php esc_html_e('To trash (removed upstream)', 'contabai'); ?></strong></td><td><?php echo (int) count($plan['trash']); ?></td></tr>
                    </tbody>
                </table>

                <?php if (! empty($plan['create'])): ?>
                    <h3><?php esc_html_e('Will create', 'contabai'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Location', 'contabai'); ?></th>
                                <th><?php esc_html_e('Type', 'contabai'); ?></th>
                                <th><?php esc_html_e('URL', 'contabai'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($plan['create'] as $node): ?>
                                <tr>
                                    <td><?php echo esc_html($node['title']); ?></td>
                                    <td><?php echo $node['parent_key'] === null ? esc_html__('Country', 'contabai') : esc_html__('City', 'contabai'); ?></td>
                                    <td><code><?php echo esc_html(wp_make_link_relative(home_url('/' . $node['path'] . '/'))); ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <?php if (! empty($plan['trash'])): ?>
                    <h3><?php esc_html_e('Will move to Trash', 'contabai'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Location', 'contabai'); ?></th>
                                <th><?php esc_html_e('Type', 'contabai'); ?></th>
                                <th><?php esc_html_e('URL', 'contabai'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($plan['trash'] as $post): ?>
                                <tr>
                                    <td><a href="<?php echo esc_url((string) get_edit_post_link($post->ID)); ?>"><?php echo esc_html(get_the_title($post)); ?></a></td>
                                    <td><?php echo $post->post_parent ? esc_html__('City', 'contabai') : esc_html__('Country', 'contabai'); ?></td>
                                    <td><code><?php echo esc_html(wp_make_link_relative(get_permalink($post->ID))); ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="contabai-admin-top">
                    <input type="hidden" name="action" value="contabai_location_sync">
                    <?php wp_nonce_field('contabai_location_sync'); ?>
                    <?php submit_button(__('Generate / sync location pages', 'contabai')); ?>
                </form>
            <?php endif; ?>
        <?php
    }
}
