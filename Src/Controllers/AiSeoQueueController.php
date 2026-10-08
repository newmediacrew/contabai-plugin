<?php

namespace Contabai\Controllers;

class AiSeoQueueController
{
    const QUEUE_OPTION = 'contabai_ai_seo_queue';
    const STATUS_META  = '_contabai_seo_status';
    const CRON_HOOK    = 'contabai_ai_seo_tick';
    const CLAIM_OPTION = 'contabai_ai_seo_claim';
    const STOP_OPTION  = 'contabai_ai_seo_stop';
    const CLAIM_TTL    = 610;

    public function __construct()
    {
        add_action('admin_post_contabai_ai_seo_enqueue', [$this, 'handle_enqueue']);
        add_action('wp_ajax_contabai_ai_seo_status', [$this, 'ajax_status']);
        add_action('wp_ajax_contabai_ai_seo_run', [$this, 'ajax_run']);
        add_action('wp_ajax_contabai_ai_seo_stop', [$this, 'ajax_stop']);
        add_action(self::CRON_HOOK, [$this, 'tick']);
    }

    public function handle_enqueue(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions.', 'contabai'));
        }
        check_admin_referer('contabai_ai_seo_enqueue');

        $mode   = sanitize_key(wp_unslash($_POST['mode'] ?? ''));
        $force  = ! empty($_POST['force']);
        $pageId = (int) ($_POST['page_id'] ?? 0);

        $queued = ($mode === 'single' && $pageId > 0)
            ? self::push_front($pageId)
            : self::rebuild($force);

        if ($queued > 0 && ! wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_single_event(time() + 60, self::CRON_HOOK);
        }

        wp_safe_redirect(add_query_arg(
            ['page' => 'contabai-tools', 'tab' => 'generate', 'queued' => $queued],
            admin_url('admin.php')
        ));
        exit;
    }

    private static function rebuild(bool $force): int
    {
        delete_option(self::STOP_OPTION);

        $busy   = (int) (self::claim()['page'] ?? 0);
        $target = [];

        foreach (self::location_pages() as $post) {
            if (! $force && get_post_meta($post->ID, '_contabai_seo_generated', true) !== '') {
                continue;
            }
            if ($post->ID === $busy) {
                continue;
            }
            $target[] = $post->ID;
        }

        foreach (self::queue() as $old) {
            if (! in_array($old, $target, true)
                && (string) get_post_meta($old, self::STATUS_META, true) === 'queued') {
                delete_post_meta($old, self::STATUS_META);
            }
        }

        foreach ($target as $id) {
            update_post_meta($id, self::STATUS_META, 'queued');
        }

        update_option(self::QUEUE_OPTION, $target, false);

        return count($target);
    }

    private static function push_front(int $pageId): int
    {
        delete_option(self::STOP_OPTION);

        if ((int) (self::claim()['page'] ?? 0) === $pageId) {
            return count(self::queue());
        }

        $queue = array_values(array_diff(self::queue(), [$pageId]));
        array_unshift($queue, $pageId);
        update_option(self::QUEUE_OPTION, $queue, false);
        update_post_meta($pageId, self::STATUS_META, 'queued');

        return count($queue);
    }

    private static function claim(): array
    {
        $claim = get_option(self::CLAIM_OPTION, []);
        if (! is_array($claim) || empty($claim['page'])) {
            return [];
        }

        if (time() - (int) ($claim['at'] ?? 0) > self::CLAIM_TTL) {
            self::release((int) $claim['page'], 'queued', true);

            return [];
        }

        return $claim;
    }

    private static function take_claim(int $pageId): bool
    {
        return add_option(self::CLAIM_OPTION, ['page' => $pageId, 'at' => time()], '', false);
    }

    private static function release(int $pageId, string $status, bool $requeue = false): void
    {
        if ($pageId > 0) {
            if ($status === '') {
                delete_post_meta($pageId, self::STATUS_META);
            } else {
                update_post_meta($pageId, self::STATUS_META, $status);
            }

            if ($requeue) {
                $queue = self::queue();
                if (! in_array($pageId, $queue, true)) {
                    array_unshift($queue, $pageId);
                    update_option(self::QUEUE_OPTION, $queue, false);
                }
            }
        }

        delete_option(self::CLAIM_OPTION);
    }

    public function ajax_run(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error('', 403);
        }
        check_ajax_referer('contabai_ai_seo_run');

        if (self::claim()) {
            wp_send_json_success(['state' => 'busy', 'remaining' => count(self::queue())]);
        }
        if (! self::queue()) {
            wp_send_json_success(['state' => 'idle', 'remaining' => 0]);
        }

        delete_option(self::STOP_OPTION);

        @header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        echo wp_json_encode([
            'success' => true,
            'data'    => ['state' => 'started', 'remaining' => count(self::queue())],
        ]);

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        ignore_user_abort(true);
        self::drain();
        exit;
    }

    public function ajax_stop(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error('', 403);
        }
        check_ajax_referer('contabai_ai_seo_run');

        update_option(self::STOP_OPTION, 1, false);

        wp_send_json_success(['state' => 'stopping']);
    }

    private static function drain(): void
    {
        while (true) {
            if (get_option(self::STOP_OPTION)) {
                delete_option(self::STOP_OPTION);

                return;
            }

            $queue = self::queue();
            if (empty($queue)) {
                return;
            }

            $pageId = (int) $queue[0];
            if (! self::take_claim($pageId)) {
                return;
            }

            array_shift($queue);
            update_option(self::QUEUE_OPTION, array_values($queue), false);

            $page = $pageId > 0 ? get_post($pageId) : null;
            if (! $page instanceof \WP_Post) {
                self::release($pageId, '');
                continue;
            }

            update_post_meta($pageId, self::STATUS_META, 'generating');

            if (function_exists('set_time_limit')) {
                @set_time_limit(self::CLAIM_TTL);
            }

            $ok = AiSeoGenerator::generate_and_store($page);

            self::release($pageId, $ok ? 'done' : 'failed');
        }
    }

    public function tick(): void
    {
        if (self::claim()) {
            wp_schedule_single_event(time() + 60, self::CRON_HOOK);

            return;
        }

        if (! self::queue()) {
            return;
        }

        wp_schedule_single_event(time() + 60, self::CRON_HOOK);

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        ignore_user_abort(true);
        self::drain();
    }

    public function ajax_status(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error('', 403);
        }
        check_ajax_referer('contabai_ai_seo_status');

        $pages = [];
        foreach (self::location_pages() as $post) {
            $pages[$post->ID] = ['label' => self::status_label($post->ID)];
        }

        wp_send_json_success([
            'remaining' => count(self::queue()),
            'current'   => (int) (self::claim()['page'] ?? 0),
            'pages'     => $pages,
        ]);
    }

    private static function queue(): array
    {
        $queue = get_option(self::QUEUE_OPTION, []);

        return is_array($queue) ? array_values(array_map('intval', $queue)) : [];
    }

    private static function location_pages(): array
    {
        $posts = get_posts([
            'post_type'      => 'page',
            'post_status'    => ['publish', 'draft', 'pending', 'private', 'future'],
            'posts_per_page' => -1,
            'meta_key'       => LocationPagesController::FLAG_META,
            'meta_value'     => '1',
        ]);

        usort($posts, function ($a, $b) {
            $ka = $a->post_parent ? get_the_title($a->post_parent) . '/' . $a->post_title : $a->post_title;
            $kb = $b->post_parent ? get_the_title($b->post_parent) . '/' . $b->post_title : $b->post_title;
            return strcasecmp($ka, $kb);
        });

        return $posts;
    }

    private static function pending_count(): int
    {
        $pending = 0;
        foreach (self::location_pages() as $post) {
            if (get_post_meta($post->ID, '_contabai_seo_generated', true) === '') {
                $pending++;
            }
        }

        return $pending;
    }

    private static function display_status(int $id): string
    {
        $status = (string) get_post_meta($id, self::STATUS_META, true);
        if ($status !== '') {
            return $status;
        }

        return get_post_meta($id, '_contabai_seo_generated', true) !== '' ? 'done' : 'none';
    }

    private static function status_label(int $id): string
    {
        switch (self::display_status($id)) {
            case 'queued':     return __('Queued', 'contabai');
            case 'generating': return __('Generating…', 'contabai');
            case 'failed':
                $why = (string) get_post_meta($id, '_contabai_seo_error', true);
                return $why !== ''
                    ? sprintf(__('Failed · %s', 'contabai'), $why)
                    : __('Failed', 'contabai');
            case 'done':
                $ts = (string) get_post_meta($id, '_contabai_seo_generated', true);
                return $ts !== '' ? sprintf(__('Generated · %s', 'contabai'), $ts) : __('Generated', 'contabai');
            default:           return __('Not generated', 'contabai');
        }
    }

    public static function render_tab(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $pages      = self::location_pages();
        $nonce      = wp_create_nonce('contabai_ai_seo_status');
        $runNonce   = wp_create_nonce('contabai_ai_seo_run');
        $pending    = self::pending_count();
        ?>
        <?php if (isset($_GET['queued'])): $n = (int) $_GET['queued']; ?>
            <div class="notice notice-success is-dismissible"><p>
                <?php echo esc_html($n > 0
                    ? sprintf(_n('%d page queued for generation.', '%d pages queued for generation.', $n, 'contabai'), $n)
                    : __('Nothing to generate — those pages already have content (tick “Regenerate” to force).', 'contabai')); ?>
            </p></div>
        <?php endif; ?>

        <h2><?php esc_html_e('Generate', 'contabai'); ?></h2>
        <p class="contabai-admin-intro"><?php esc_html_e('Generate the AI SEO article + metadata for each location page. Each page calls OpenAI and takes a few minutes. Generation keeps running on the server, so you can close this page and come back later — the status column updates automatically while it is open.', 'contabai'); ?></p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('Start generating all location pages? Each page calls OpenAI and can take several minutes.', 'contabai')); ?>');">
            <input type="hidden" name="action" value="contabai_ai_seo_enqueue">
            <input type="hidden" name="mode" value="all">
            <?php wp_nonce_field('contabai_ai_seo_enqueue'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Regenerate', 'contabai'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="force" value="1">
                            <?php esc_html_e('Regenerate pages that already have content', 'contabai'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Unchecked: only pages that have no content yet are generated. Checked: every page is regenerated.', 'contabai'); ?>
                            <br>
                            <?php echo esc_html($pending > 0
                                ? sprintf(_n('%d page still needs generating.', '%d pages still need generating.', $pending, 'contabai'), $pending)
                                : __('All pages generated.', 'contabai')); ?>
                        </p>
                    </td>
                </tr>
            </table>
            <p class="contabai-admin-block">
                <?php submit_button(__('Generate all', 'contabai'), 'primary', 'submit', false); ?>
                <button type="button" class="button" id="contabai-stop"><?php esc_html_e('Stop', 'contabai'); ?></button>
                <span id="contabai-queue-status" class="contabai-inline-action"></span>
            </p>
        </form>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Location', 'contabai'); ?></th>
                    <th><?php esc_html_e('URL', 'contabai'); ?></th>
                    <th class="contabai-col-w230"><?php esc_html_e('Status', 'contabai'); ?></th>
                    <th class="contabai-col-w130"><?php esc_html_e('Action', 'contabai'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $post): $done = get_post_meta($post->ID, '_contabai_seo_generated', true) !== ''; ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url((string) get_edit_post_link($post->ID)); ?>"><?php echo esc_html(get_the_title($post)); ?></a>
                            <span class="description"> · <?php echo $post->post_parent ? esc_html__('City', 'contabai') : esc_html__('Country', 'contabai'); ?></span>
                        </td>
                        <td><code><?php echo esc_html(wp_make_link_relative(get_permalink($post->ID))); ?></code></td>
                        <td class="contabai-status" data-page="<?php echo (int) $post->ID; ?>"><?php echo esc_html(self::status_label($post->ID)); ?></td>
                        <td>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('Generate this page now?', 'contabai')); ?>');">
                                <input type="hidden" name="action" value="contabai_ai_seo_enqueue">
                                <input type="hidden" name="mode" value="single">
                                <input type="hidden" name="page_id" value="<?php echo (int) $post->ID; ?>">
                                <?php wp_nonce_field('contabai_ai_seo_enqueue'); ?>
                                <?php submit_button($done ? __('Regenerate', 'contabai') : __('Generate', 'contabai'), 'primary small', 'submit', false); ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <script>
        (function () {
            const ajaxurl   = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
            const nonce     = <?php echo wp_json_encode($nonce); ?>;
            const runNonce  = <?php echo wp_json_encode($runNonce); ?>;
            const box       = document.getElementById('contabai-queue-status');
            const stopBtn   = document.getElementById('contabai-stop');
            const inQueue   = <?php echo wp_json_encode(__('In queue:', 'contabai')); ?>;
            const stopping  = <?php echo wp_json_encode(__('Stopping…', 'contabai')); ?>;
            let stopped = false;

            function run() {
                fetch(ajaxurl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: new URLSearchParams({ action: 'contabai_ai_seo_run', _ajax_nonce: runNonce }),
                }).catch(function () {});
            }

            if (stopBtn) {
                stopBtn.addEventListener('click', function () {
                    stopped = true;
                    box.textContent = stopping;
                    fetch(ajaxurl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: new URLSearchParams({ action: 'contabai_ai_seo_stop', _ajax_nonce: runNonce }),
                    }).catch(function () {});
                });
            }

            function poll() {
                fetch(ajaxurl + '?action=contabai_ai_seo_status&_ajax_nonce=' + encodeURIComponent(nonce), { credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (!res || !res.success) { return; }
                        document.querySelectorAll('.contabai-status').forEach(function (cell) {
                            const page = res.data.pages[cell.getAttribute('data-page')];
                            if (page) { cell.textContent = page.label; }
                        });
                        if (box && !stopped) {
                            box.textContent = res.data.remaining > 0 ? (inQueue + ' ' + res.data.remaining) : '';
                        }
                        if (!stopped && !res.data.current && res.data.remaining > 0) { run(); }
                        if (res.data.remaining === 0 && !res.data.current) { stopped = false; }
                    })
                    .catch(function () {});
            }
            poll();
            setInterval(poll, 5000);
        })();
        </script>
        <?php
    }
}
