<?php

namespace Contabai\Controllers;

class AiSeoMetaBoxController
{
    const NONCE = 'contabai_seo_metabox';

    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'add_box'], 10, 2);
        add_action('save_post_page', [$this, 'save'], 10, 1);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets(string $hook): void
    {
        if (! in_array($hook, ['post.php', 'post-new.php'], true)) {
            return;
        }
        global $post;
        if (! $post instanceof \WP_Post || $post->post_type !== 'page') {
            return;
        }
        if (get_page_template_slug($post->ID) !== LocationPagesController::TEMPLATE) {
            return;
        }
        wp_enqueue_style('contabai-admin', CONTABAI_PLUGIN_URL . 'assets/css/admin.css', [], '1');
    }

    public function add_box(string $postType, \WP_Post $post): void
    {
        if ($postType !== 'page' || get_page_template_slug($post->ID) !== LocationPagesController::TEMPLATE) {
            return;
        }

        add_meta_box('contabai_seo', __('Contabai SEO content', 'contabai'), [$this, 'render'], 'page', 'normal', 'high');
    }

    public function render(\WP_Post $post): void
    {
        wp_nonce_field(self::NONCE, self::NONCE);

        $title    = (string) get_post_meta($post->ID, '_contabai_seo_title', true);
        $metadesc = (string) get_post_meta($post->ID, '_contabai_seo_metadesc', true);
        $focus    = (string) get_post_meta($post->ID, '_contabai_seo_focus', true);
        $article  = (string) get_post_meta($post->ID, '_contabai_seo_article', true);
        $faq      = (array) get_post_meta($post->ID, '_contabai_seo_faq', true);
        $areas    = (array) get_post_meta($post->ID, '_contabai_seo_best_areas', true);
        $related  = (array) get_post_meta($post->ID, '_contabai_seo_related', true);
        $keywords = (array) get_post_meta($post->ID, '_contabai_seo_keywords', true);
        $entities = (array) get_post_meta($post->ID, '_contabai_seo_entities', true);
        ?>
        <h2 class="nav-tab-wrapper wp-clearfix">
            <a href="#" class="nav-tab nav-tab-active" data-tab="snippet"><?php esc_html_e('Snippet', 'contabai'); ?></a>
            <a href="#" class="nav-tab" data-tab="article"><?php esc_html_e('Article', 'contabai'); ?></a>
            <a href="#" class="nav-tab" data-tab="faq"><?php esc_html_e('FAQ', 'contabai'); ?></a>
            <a href="#" class="nav-tab" data-tab="areas"><?php esc_html_e('Best areas', 'contabai'); ?></a>
            <a href="#" class="nav-tab" data-tab="related"><?php esc_html_e('Related', 'contabai'); ?></a>
        </h2>

        <!-- Snippet -->
        <div class="contabai-seo-panel is-active" data-panel="snippet">
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="contabai_seo_title"><?php esc_html_e('SEO title', 'contabai'); ?></label></th>
                    <td><input type="text" id="contabai_seo_title" name="contabai_seo_title" class="large-text" value="<?php echo esc_attr($title); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="contabai_seo_metadesc"><?php esc_html_e('Meta description', 'contabai'); ?></label></th>
                    <td><textarea id="contabai_seo_metadesc" name="contabai_seo_metadesc" class="large-text" rows="3"><?php echo esc_textarea($metadesc); ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="contabai_seo_focus"><?php esc_html_e('Focus keyword', 'contabai'); ?></label></th>
                    <td><input type="text" id="contabai_seo_focus" name="contabai_seo_focus" class="regular-text" value="<?php echo esc_attr($focus); ?>"></td>
                </tr>
            </table>
        </div>

        <!-- Article -->
        <div class="contabai-seo-panel" data-panel="article">
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="contabai_seo_article"><?php esc_html_e('Article HTML', 'contabai'); ?></label></th>
                    <td>
                        <textarea id="contabai_seo_article" name="contabai_seo_article" class="large-text code" rows="18"><?php echo esc_textarea($article); ?></textarea>
                        <p class="description"><?php esc_html_e('Semantic HTML (h1, h2, p, ul). Rendered as-is.', 'contabai'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- FAQ -->
        <div class="contabai-seo-panel" data-panel="faq">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th class="contabai-col-question"><?php esc_html_e('Question', 'contabai'); ?></th>
                        <th><?php esc_html_e('Answer', 'contabai'); ?></th>
                        <th class="contabai-col-remove"></th>
                    </tr>
                </thead>
                <tbody id="contabai-faq-rows">
                    <?php foreach ($faq as $item): if (! is_array($item)) { continue; } ?>
                        <?php $this->faq_row($item['question'] ?? '', $item['answer'] ?? ''); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><button type="button" class="button" data-add="faq"><?php esc_html_e('+ Add question', 'contabai'); ?></button></p>
            <template id="contabai-faq-tmpl"><?php $this->faq_row('', ''); ?></template>
        </div>

        <!-- Best areas -->
        <div class="contabai-seo-panel" data-panel="areas">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Location', 'contabai'); ?></th>
                        <th><?php esc_html_e('Distance / relation', 'contabai'); ?></th>
                        <th><?php esc_html_e('Best for', 'contabai'); ?></th>
                        <th class="contabai-col-remove"></th>
                    </tr>
                </thead>
                <tbody id="contabai-areas-rows">
                    <?php foreach ($areas as $row): if (! is_array($row)) { continue; } ?>
                        <?php $this->area_row($row['location'] ?? '', $row['relation'] ?? '', $row['best_for'] ?? ''); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><button type="button" class="button" data-add="areas"><?php esc_html_e('+ Add area', 'contabai'); ?></button></p>
            <template id="contabai-areas-tmpl"><?php $this->area_row('', '', ''); ?></template>
        </div>

        <!-- Related -->
        <div class="contabai-seo-panel" data-panel="related">
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="contabai_seo_related"><?php esc_html_e('Related locations', 'contabai'); ?></label></th>
                    <td>
                        <textarea id="contabai_seo_related" name="contabai_seo_related" class="large-text" rows="5"><?php echo esc_textarea(implode("\n", array_map('strval', $related))); ?></textarea>
                        <p class="description"><?php esc_html_e('One per line.', 'contabai'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="contabai_seo_keywords"><?php esc_html_e('Secondary keywords', 'contabai'); ?></label></th>
                    <td>
                        <textarea id="contabai_seo_keywords" name="contabai_seo_keywords" class="large-text" rows="5"><?php echo esc_textarea(implode("\n", array_map('strval', $keywords))); ?></textarea>
                        <p class="description"><?php esc_html_e('One per line.', 'contabai'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="contabai_seo_entities"><?php esc_html_e('Entities', 'contabai'); ?></label></th>
                    <td>
                        <textarea id="contabai_seo_entities" name="contabai_seo_entities" class="large-text" rows="6"><?php echo esc_textarea(implode("\n", array_map('strval', $entities))); ?></textarea>
                        <p class="description"><?php esc_html_e('One per line.', 'contabai'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="notice notice-warning inline">
            <p><?php esc_html_e('Regenerating this page (Tools → Generate) overwrites everything here.', 'contabai'); ?></p>
        </div>

        <script>
        (function () {
            const box = document.getElementById('contabai_seo');
            if (! box) { return; }

            box.querySelectorAll('.nav-tab').forEach(function (tab) {
                tab.addEventListener('click', function (e) {
                    e.preventDefault();
                    box.querySelectorAll('.nav-tab').forEach(function (t) { t.classList.remove('nav-tab-active'); });
                    tab.classList.add('nav-tab-active');
                    box.querySelectorAll('.contabai-seo-panel').forEach(function (p) {
                        p.classList.toggle('is-active', p.getAttribute('data-panel') === tab.getAttribute('data-tab'));
                    });
                });
            });

            box.querySelectorAll('[data-add]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const key = btn.getAttribute('data-add');
                    const tmpl = document.getElementById('contabai-' + key + '-tmpl');
                    const rows = document.getElementById('contabai-' + key + '-rows');
                    rows.appendChild(tmpl.content.cloneNode(true));
                });
            });

            box.addEventListener('click', function (e) {
                if (e.target.classList.contains('contabai-remove')) {
                    e.preventDefault();
                    const row = e.target.closest('tr');
                    if (row) { row.remove(); }
                }
            });
        })();
        </script>
        <?php
    }

    private function faq_row(string $q, string $a): void
    {
        ?>
        <tr>
            <td><input type="text" name="contabai_faq_q[]" class="large-text" value="<?php echo esc_attr($q); ?>" placeholder="<?php esc_attr_e('Question', 'contabai'); ?>"></td>
            <td><textarea name="contabai_faq_a[]" class="large-text" rows="2" placeholder="<?php esc_attr_e('Answer', 'contabai'); ?>"><?php echo esc_textarea($a); ?></textarea></td>
            <td><button type="button" class="button button-small contabai-remove" aria-label="<?php esc_attr_e('Remove', 'contabai'); ?>"><?php echo esc_html_x('×', 'remove row', 'contabai'); ?></button></td>
        </tr>
        <?php
    }

    private function area_row(string $location, string $relation, string $bestFor): void
    {
        ?>
        <tr>
            <td><input type="text" name="contabai_area_location[]" class="large-text" value="<?php echo esc_attr($location); ?>" placeholder="<?php esc_attr_e('Location', 'contabai'); ?>"></td>
            <td><input type="text" name="contabai_area_relation[]" class="large-text" value="<?php echo esc_attr($relation); ?>" placeholder="<?php esc_attr_e('Distance / relation', 'contabai'); ?>"></td>
            <td><input type="text" name="contabai_area_bestfor[]" class="large-text" value="<?php echo esc_attr($bestFor); ?>" placeholder="<?php esc_attr_e('Best for', 'contabai'); ?>"></td>
            <td><button type="button" class="button button-small contabai-remove" aria-label="<?php esc_attr_e('Remove', 'contabai'); ?>"><?php echo esc_html_x('×', 'remove row', 'contabai'); ?></button></td>
        </tr>
        <?php
    }

    public function save(int $postId): void
    {
        if (! isset($_POST[self::NONCE]) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE])), self::NONCE)) {
            return;
        }
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($postId)) {
            return;
        }
        if (! current_user_can('edit_post', $postId)) {
            return;
        }

        update_post_meta($postId, '_contabai_seo_title', sanitize_text_field(wp_unslash($_POST['contabai_seo_title'] ?? '')));
        update_post_meta($postId, '_contabai_seo_metadesc', sanitize_text_field(wp_unslash($_POST['contabai_seo_metadesc'] ?? '')));
        update_post_meta($postId, '_contabai_seo_focus', sanitize_text_field(wp_unslash($_POST['contabai_seo_focus'] ?? '')));
        update_post_meta($postId, '_contabai_seo_article', wp_slash(wp_kses_post(wp_unslash($_POST['contabai_seo_article'] ?? ''))));

        update_post_meta($postId, '_contabai_seo_faq', $this->collect_faq());
        update_post_meta($postId, '_contabai_seo_best_areas', $this->collect_areas());

        update_post_meta($postId, '_contabai_seo_related', $this->lines('contabai_seo_related'));
        update_post_meta($postId, '_contabai_seo_keywords', $this->lines('contabai_seo_keywords'));
        update_post_meta($postId, '_contabai_seo_entities', $this->lines('contabai_seo_entities'));
    }

    private function collect_faq(): array
    {
        $qs = array_map('strval', (array) ($_POST['contabai_faq_q'] ?? []));
        $as = array_map('strval', (array) ($_POST['contabai_faq_a'] ?? []));
        $out = [];
        foreach ($qs as $i => $q) {
            $question = sanitize_text_field(wp_unslash($q));
            $answer   = wp_kses_post(wp_unslash($as[$i] ?? ''));
            if ($question === '' && trim($answer) === '') {
                continue;
            }
            $out[] = ['question' => $question, 'answer' => $answer];
        }

        return $out;
    }

    private function collect_areas(): array
    {
        $loc = array_map('strval', (array) ($_POST['contabai_area_location'] ?? []));
        $rel = array_map('strval', (array) ($_POST['contabai_area_relation'] ?? []));
        $bf  = array_map('strval', (array) ($_POST['contabai_area_bestfor'] ?? []));
        $out = [];
        foreach ($loc as $i => $l) {
            $location = sanitize_text_field(wp_unslash($l));
            $relation = sanitize_text_field(wp_unslash($rel[$i] ?? ''));
            $bestFor  = sanitize_text_field(wp_unslash($bf[$i] ?? ''));
            if ($location === '' && $relation === '' && $bestFor === '') {
                continue;
            }
            $out[] = ['location' => $location, 'relation' => $relation, 'best_for' => $bestFor];
        }

        return $out;
    }

    private function lines(string $field): array
    {
        $raw = (string) wp_unslash($_POST[$field] ?? '');
        $lines = array_map('sanitize_text_field', preg_split('/\r\n|\r|\n/', $raw) ?: []);

        return array_values(array_filter($lines, 'strlen'));
    }
}
