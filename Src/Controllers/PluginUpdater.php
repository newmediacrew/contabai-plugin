<?php

namespace Contabai\Controllers;

use WP_Error;

class PluginUpdater
{
    private const REPOSITORY = 'newmediacrew/contabai-plugin';

    private const ASSET = 'contabai.zip';

    public function __construct()
    {
        add_filter('update_plugins_github.com', [$this, 'check'], 10, 2);
        add_filter('plugins_api', [$this, 'details'], 10, 3);
        add_filter('upgrader_pre_download', [$this, 'refuse_in_dev_mode'], 10, 2);
        add_action('add_option_contabai_dev_mode', [$this, 'dev_mode_changed'], 10, 0);
        add_action('update_option_contabai_dev_mode', [$this, 'dev_mode_changed'], 10, 0);
        add_action('admin_post_contabai_updates_check', [$this, 'handle_check']);
    }

    public function check($update, array $pluginData)
    {
        if (($pluginData['UpdateURI'] ?? '') !== 'https://github.com/' . self::REPOSITORY || self::dev_mode()) {
            return $update;
        }

        $release = self::release();
        if (! self::valid_version((string) ($release['version'] ?? '')) || ! self::valid_package((string) ($release['package'] ?? ''))) {
            return $update;
        }

        return [
            'slug'         => 'contabai',
            'version'      => $release['version'],
            'url'          => 'https://github.com/' . self::REPOSITORY,
            'package'      => $release['package'],
            'tested'       => self::tested($release['tested']),
            'requires_php' => $release['requires_php'],
            'icons'        => ['svg' => CONTABAI_PLUGIN_URL . 'assets/icon.svg'],
        ];
    }

    public function details($result, $action, $args)
    {
        if ($action !== 'plugin_information' || ($args->slug ?? '') !== 'contabai') {
            return $result;
        }

        $release = self::release();
        $plugin = get_plugin_data(CONTABAI_PLUGIN_DIR . 'contabai.php', false, false);

        return (object) [
            'name'          => 'Contabai',
            'slug'          => 'contabai',
            'version'       => $release['version'] !== '' ? $release['version'] : $plugin['Version'],
            'author'        => 'Contabai',
            'homepage'      => 'https://github.com/' . self::REPOSITORY,
            'requires'      => $plugin['RequiresWP'],
            'tested'        => self::tested($release['tested']),
            'requires_php'  => $release['requires_php'] !== '' ? $release['requires_php'] : $plugin['RequiresPHP'],
            'last_updated'  => $release['published'],
            'download_link' => $release['package'],
            'sections'      => [
                'description' => esc_html($plugin['Description']),
                'changelog'   => nl2br(esc_html($release['notes'])),
            ],
        ];
    }

    public function refuse_in_dev_mode($reply, $package)
    {
        if (false !== $reply || ! is_string($package) || ! self::valid_package($package) || ! self::dev_mode()) {
            return $reply;
        }

        return new WP_Error('contabai_update', __('Development mode is on, so this update is not installed.', 'contabai'));
    }

    public function dev_mode_changed(): void
    {
        delete_site_transient('update_plugins');
    }

    public function handle_check(): void
    {
        if (! current_user_can('update_plugins')) {
            wp_die(esc_html__('You are not allowed to do this.', 'contabai'), 403);
        }
        check_admin_referer('contabai_updates_check', 'contabai_updates_check_nonce');

        self::release(true);
        delete_site_transient('update_plugins');
        wp_update_plugins();

        wp_safe_redirect(add_query_arg(['page' => 'contabai-settings', 'tab' => 'updates'], admin_url('admin.php')));
        exit;
    }

    public static function render_status(): void
    {
        $state = (array) get_option('contabai_update_state', []);
        $installed = (string) get_plugin_data(CONTABAI_PLUGIN_DIR . 'contabai.php', false, false)['Version'];
        $latest = (string) ($state['version'] ?? '');
        $error = (string) ($state['error'] ?? '');
        $notes = (string) ($state['notes'] ?? '');
        $checked = (int) ($state['checked'] ?? 0);
        ?>
        <h2><?php esc_html_e('Status', 'contabai'); ?></h2>
        <?php if (self::dev_mode()) : ?>
            <div class="notice notice-info inline"><p><?php esc_html_e('Development mode is on (Advanced tab), so updates are switched off.', 'contabai'); ?></p></div>
        <?php endif; ?>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><?php esc_html_e('Installed version', 'contabai'); ?></th>
                    <td><?php echo esc_html($installed); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Latest release', 'contabai'); ?></th>
                    <td>
                        <?php echo esc_html($latest !== '' ? $latest : '—'); ?>
                        <?php if ($latest !== '' && version_compare($latest, $installed, '>')) : ?>
                            · <a href="<?php echo esc_url(admin_url('update-core.php')); ?>"><?php esc_html_e('Update available', 'contabai'); ?></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Last check', 'contabai'); ?></th>
                    <td><?php echo esc_html($checked ? wp_date(get_option('date_format') . ' ' . get_option('time_format'), $checked) : __('Never', 'contabai')); ?></td>
                </tr>
                <?php if ($error !== '') : ?>
                    <tr>
                        <th scope="row"><?php esc_html_e('Last error', 'contabai'); ?></th>
                        <td><?php echo esc_html($error); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if (! self::dev_mode()) : ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="contabai_updates_check">
                <?php wp_nonce_field('contabai_updates_check', 'contabai_updates_check_nonce'); ?>
                <?php submit_button(__('Check now', 'contabai'), 'secondary', 'contabai-updates-check', false); ?>
            </form>
        <?php endif; ?>
        <?php if ($notes !== '') : ?>
            <h2><?php esc_html_e('Release notes', 'contabai'); ?></h2>
            <div class="contabai-release-notes"><?php echo nl2br(esc_html($notes)); ?></div>
        <?php endif; ?>
        <?php
    }

    private static function release(bool $force = false): array
    {
        $state = get_option('contabai_update_state', []);
        if (! $force && is_array($state) && isset($state['version']) && get_transient('contabai_update_fresh')) {
            return $state;
        }

        $state = self::fetch();
        update_option('contabai_update_state', $state, false);
        set_transient('contabai_update_fresh', 1, $state['error'] === '' ? 6 * HOUR_IN_SECONDS : HOUR_IN_SECONDS);

        return $state;
    }

    private static function fetch(): array
    {
        $state = [
            'checked'      => time(),
            'error'        => '',
            'version'      => '',
            'tag'          => '',
            'notes'        => '',
            'published'    => '',
            'package'      => '',
            'tested'       => '',
            'requires_php' => '',
        ];

        $response = wp_remote_get('https://api.github.com/repos/' . self::REPOSITORY . '/releases/latest', [
            'timeout' => 10,
            'headers' => [
                'Accept'               => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
            ],
        ]);
        if (is_wp_error($response)) {
            $state['error'] = sprintf(__('GitHub is not reachable: %s', 'contabai'), $response->get_error_message());
            return $state;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            $messages = [
                403 => __('GitHub refused the request (rate limit). Try again later.', 'contabai'),
                404 => __('No release published yet.', 'contabai'),
                429 => __('GitHub refused the request (rate limit). Try again later.', 'contabai'),
            ];
            $state['error'] = $messages[$code] ?? sprintf(__('GitHub answered with HTTP %d.', 'contabai'), $code);
            return $state;
        }

        $release = json_decode((string) wp_remote_retrieve_body($response), true);
        if (! is_array($release) || ! is_string($release['tag_name'] ?? null)) {
            $state['error'] = __('GitHub sent an unexpected answer.', 'contabai');
            return $state;
        }

        $tag = sanitize_text_field($release['tag_name']);
        $version = ltrim($tag, 'vV');
        if (! self::valid_version($version)) {
            $state['error'] = sprintf(__('Release tag %s is not a valid version.', 'contabai'), $tag);
            return $state;
        }

        $package = '';
        foreach ((array) ($release['assets'] ?? []) as $asset) {
            if (is_array($asset) && ($asset['name'] ?? '') === self::ASSET) {
                $package = esc_url_raw((string) ($asset['browser_download_url'] ?? ''));
                break;
            }
        }
        if (! self::valid_package($package)) {
            $state['error'] = sprintf(__('Release %1$s has no %2$s.', 'contabai'), $tag, self::ASSET);
            return $state;
        }

        $notes = sanitize_textarea_field((string) ($release['body'] ?? ''));
        preg_match('/^Tested up to:\s*(\d+(?:\.\d+){0,2})\s*$/m', $notes, $tested);
        preg_match('/^Requires PHP:\s*(\d+(?:\.\d+){0,2})\s*$/m', $notes, $requiresPhp);

        $state['tag'] = $tag;
        $state['version'] = $version;
        $state['notes'] = $notes;
        $state['published'] = sanitize_text_field((string) ($release['published_at'] ?? ''));
        $state['package'] = $package;
        $state['tested'] = $tested[1] ?? '';
        $state['requires_php'] = $requiresPhp[1] ?? '';

        return $state;
    }

    private static function valid_version(string $version): bool
    {
        return preg_match('/^\d+(\.\d+){0,3}$/', $version) === 1;
    }

    private static function tested(string $tested): string
    {
        $wp = get_bloginfo('version');
        if (preg_match('/^\d+\.\d+$/', $tested) === 1 && str_starts_with($wp . '.', $tested . '.')) {
            return $wp;
        }

        return $tested;
    }

    private static function dev_mode(): bool
    {
        return (int) get_option('contabai_dev_mode', 0) === 1;
    }

    private static function valid_package(string $package): bool
    {
        return preg_match('~^https://github\.com/' . preg_quote(self::REPOSITORY, '~') . '/releases/download/v\d+(\.\d+){0,3}/' . preg_quote(self::ASSET, '~') . '$~', $package) === 1;
    }
}
