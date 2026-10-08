<?php

namespace Contabai\Controllers;

class AiSeoController
{
    const OPENAI_KEY_OPTION = 'contabai_openai_api_key';
    const PEXELS_KEY_OPTION = 'contabai_pexels_api_key';
    const PROMPT_OPTION     = 'contabai_ai_seo_prompt';
    const MODEL_OPTION      = 'contabai_openai_model';
    const DEFAULT_MODEL     = 'gpt-5.6-sol';
    const REASONING_OPTION  = 'contabai_openai_reasoning_effort';
    const DEFAULT_REASONING = 'high';

    public function __construct()
    {
        add_action('admin_post_contabai_save_ai_seo', [$this, 'handle_save']);

        add_action('after_setup_theme', static function () {
            add_image_size('contabai_og', 1200, 630, true);
        });
    }

    public static function get_openai_key(): string
    {
        return (string) get_option(self::OPENAI_KEY_OPTION, '');
    }

    public static function get_pexels_key(): string
    {
        return (string) get_option(self::PEXELS_KEY_OPTION, '');
    }

    public static function get_model(): string
    {
        $model = (string) get_option(self::MODEL_OPTION, '');

        return $model !== '' ? $model : self::DEFAULT_MODEL;
    }

    public static function get_reasoning_effort(): string
    {
        $effort = (string) get_option(self::REASONING_OPTION, '');

        return $effort !== '' ? $effort : self::DEFAULT_REASONING;
    }

    public static function response_schema(): array
    {
        $strObj = function (array $props) {
            return [
                'type'                 => 'object',
                'additionalProperties' => false,
                'required'             => array_keys($props),
                'properties'           => $props,
            ];
        };
        $strArr = function ($items) {
            return ['type' => 'array', 'items' => $items];
        };
        $str = ['type' => 'string'];

        return $strObj([
            'article_html'       => $str,
            'title'              => $str,
            'meta_description'   => $str,
            'focus_keyword'      => $str,
            'secondary_keywords' => $strArr($str),
            'entities'           => $strArr($str),
            'schema_types'       => $strArr($str),
            'internal_links'     => $strArr($strObj([
                'anchor'          => $str,
                'target_type'     => $str,
                'target_location' => $str,
                'reason'          => $str,
            ])),
            'related_locations'  => $strArr($str),
            'best_areas'         => $strArr($strObj([
                'location' => $str,
                'relation' => $str,
                'best_for' => $str,
            ])),
            'faq'                => $strArr($strObj([
                'question' => $str,
                'answer'   => $str,
            ])),
        ]);
    }

    public static function get_prompt(): string
    {
        $stored = (string) get_option(self::PROMPT_OPTION, '');

        return $stored !== '' ? $stored : self::default_prompt();
    }

    public static function default_prompt(): string
    {

        return trim(<<<'PROMPT'
You are an expert travel writer and SEO / AI-search specialist creating destination landing
pages for the holiday rental marketplace {{site_title}} ({{site_tagline}}).

Write like a knowledgeable local editor. The page must be easy for readers AND AI search engines
(Google AI Overviews, Bing Copilot, ChatGPT/Perplexity/Gemini) to extract and cite.

PAGE CONTEXT — read first. This is the page you are writing.

You are writing ONE page about: {{target_location}}  (a {{target_type}} — "country" or "city")

Hierarchy (never confuse; never drop the middle):
- Country: {{country}}
- City:    {{city}}      (empty if this page is a country)
- Area:    {{area}}      (currently always empty — no area pages)
Correct: Palm Beach (area) → Noord (city) → Aruba (country); Willemstad (city) → Curaçao (country).
Nearby locations in {{country}}: {{nearby_locations}}

- country page → overview of the country; link down to its cities; don't treat one city/area as the subject.
- city page → guide to the city; compare/link to nearby cities and up to the country; don't invent an area.

WRITING BEHAVIOUR (concrete, not vague "optimize for E-E-A-T")
- Open every section with a direct, factual answer to its implied question, then supporting detail.
  Never open with storytelling or mood-setting.
- Precise, factual language. Prefer specific named entities over generic description. Explain WHY a
  place is known, not just that it's nice.
- Write sentences an AI can quote verbatim, e.g. "{{target_location}} is located in {{country}} and is known for …".
- Prefer explanatory paragraphs over lists. Every paragraph — and every list item — must add new,
  specific information a reader likely didn't know; never just repeat adjectives.
- Natural language only. Never optimise for keyword density; don't repeat "{{target_location}}" or
  "holiday rentals" mechanically. Vary terms naturally (holiday rentals, vacation homes, villas,
  apartments, self-catering stays, places to stay) — don't force every synonym.
- Ban clichés: no "hidden gem", "paradise", "breathtaking", "nestled", "vibrant atmosphere",
  "world-class", "something for everyone", "ultimate", "perfect destination".

ACCURACY & UNIQUENESS
- Every fact must be true and specific to {{target_location}}. Use web search to verify; if you cannot
  confirm something, omit it and write less. Never invent places, entities, distances or claims.
- Make it unmistakably about THIS location: if its name were removed, a reader should still recognise
  it from its landmarks, geography and character. No generic island text that fits anywhere.

ENTITIES (the main lever for AI search)
Weave in the real, relevant named entities where they genuinely apply — districts/neighbourhoods,
beaches, bays, nature reserves/parks, landmarks, museums, UNESCO sites, historic architecture,
cuisine/dishes, events, airports/ports, nearby destinations. Only entities that truly relate to
{{target_location}}. Never invent one to fill a category.
- Relate entities, don't just name them. Not "Mambo Beach is a beach" but "Mambo Beach sits just east
  of Willemstad's historic centre and is known for its beach clubs." AI search builds knowledge graphs
  from these relationships — location, direction, distance, what it's known for.
- Use one official name per entity throughout (e.g. always "Queen Emma Bridge", never also "Emma Bridge"
  or "the swinging bridge"). Consistent naming matters for AI indexing.

RENTAL CONTENT — EVERGREEN
Don't state listing numbers, that "only one X is available", or lock in current inventory (it changes
constantly). Describe the kinds of stays travellers look for and who they suit. A live listing grid on
the page shows current availability — refer readers to it instead of stating counts.

ARTICLE (article_html) — clean semantic HTML (h1, h2, p, ul), no <html>/<body> wrapper, starting with
the H1. Cover, in this order, matching depth to what the location actually offers (don't pad a thin topic):
  H1 — includes {{target_location}} and its parent.
  Intro — answer "holiday rentals in {{target_location}}"; name it; why travellers choose it.
  ## Why stay in {{target_location}}?     atmosphere, who it suits, advantages
  ## About {{target_location}}            geography, relation to parent/nearby, what's distinctive (entities)
  ## Things to do in and around {{target_location}}   only categories that genuinely matter here — don't force a beaches, nightlife or museums section that doesn't fit the place
  ## Holiday rentals in {{target_location}}   kinds of stays + who they suit — general/evergreen, NO counts
  ## Who should stay in {{target_location}}?   only the traveller types that actually fit (couples, families, groups, remote workers, luxury, budget)
Where genuinely useful, work in practical intent: getting there, best time to visit, climate, safety, accessibility.

Do NOT put in article_html: citations, URLs, links, footnotes, "according to …"; the FAQ; the best-areas
table. Those are separate fields below.

INTERNAL LINKS — don't write URLs (you don't know the routes). Return link INTENT: anchor text,
target_type (country | parent_city | nearby_city | listings), target_location, and a short reason.

OUTPUT — return ONE JSON object, nothing else:

{
  "article_html": "",        // the article above; starts with the H1; excludes FAQ + best_areas
  "title": "",               // SEO <title>, ideally <= 60 chars
  "meta_description": "",     // <= 155 chars, specific, includes {{target_location}}
  "focus_keyword": "",
  "secondary_keywords": [],   // natural variants, not stuffing
  "entities": [],            // only entities that actually appear in article_html; never inferred; don't include {{target_location}} itself unless it appears naturally
  "schema_types": [],         // subset of: BreadcrumbList, FAQPage, Article, Place, TouristDestination, LodgingBusiness
  "internal_links": [ { "anchor": "", "target_type": "country|parent_city|nearby_city|listings", "target_location": "", "reason": "" } ],
  "related_locations": [],
  "best_areas": [ { "location": "", "relation": "", "best_for": "" } ],   // the "best areas near" comparison AS DATA
  "faq": [ { "question": "", "answer": "" } ]   // exactly 10; direct answers; must include:
                             //   "Where is {{target_location}}?", "Is {{target_location}} good for a holiday?",
                             //   "What is there to do in {{target_location}}?", "What accommodation is available in {{target_location}}?"
}

SELF-CHECK before returning: specific not generic; entity-rich with relationships; consistent entity
names; every section opens with a direct answer; no clichés; no keyword stuffing; no invented facts;
unique to {{target_location}}; high information gain and easy for an LLM to extract.
PROMPT);
    }

    public function handle_save(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions.', 'contabai'));
        }
        check_admin_referer('contabai_save_ai_seo');

        update_option(self::OPENAI_KEY_OPTION, sanitize_text_field(wp_unslash($_POST['openai_key'] ?? '')));
        update_option(self::MODEL_OPTION, sanitize_text_field(wp_unslash($_POST['openai_model'] ?? '')));
        update_option(self::REASONING_OPTION, sanitize_text_field(wp_unslash($_POST['openai_reasoning_effort'] ?? '')));
        update_option(self::PEXELS_KEY_OPTION, sanitize_text_field(wp_unslash($_POST['pexels_key'] ?? '')));
        update_option(self::PROMPT_OPTION, wp_unslash($_POST['ai_seo_prompt'] ?? ''));

        wp_safe_redirect(add_query_arg(['page' => 'contabai-tools', 'tab' => 'ai-seo', 'saved' => '1'], admin_url('admin.php')));
        exit;
    }

    public static function render_tab(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $prompt = isset($_GET['restore']) ? self::default_prompt() : self::get_prompt();
        ?>
        <?php if (isset($_GET['saved'])): ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('AI SEO settings saved.', 'contabai'); ?></p></div>
        <?php endif; ?>

        <h2><?php esc_html_e('AI SEO Settings', 'contabai'); ?></h2>
        <p class="contabai-admin-intro"><?php esc_html_e('Connect OpenAI and Pexels, and set the prompt used to generate SEO text for each location page.', 'contabai'); ?></p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="contabai_save_ai_seo">
            <?php wp_nonce_field('contabai_save_ai_seo'); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="contabai_openai_key"><?php esc_html_e('OpenAI API key', 'contabai'); ?></label></th>
                    <td><input name="openai_key" id="contabai_openai_key" type="text" class="regular-text" autocomplete="off" value="<?php echo esc_attr(self::get_openai_key()); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="contabai_openai_model"><?php esc_html_e('OpenAI model', 'contabai'); ?></label></th>
                    <td>
                        <input name="openai_model" id="contabai_openai_model" type="text" class="regular-text" value="<?php echo esc_attr(self::get_model()); ?>">
                        <p class="description">
                            <?php esc_html_e('The OpenAI model to use (e.g. gpt-5.6-sol). Update this if OpenAI releases a newer or better model.', 'contabai'); ?>
                            <a href="https://developers.openai.com/api/docs/models" target="_blank" rel="noopener"><?php esc_html_e('See all OpenAI models', 'contabai'); ?></a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="contabai_openai_reasoning_effort"><?php esc_html_e('Reasoning effort', 'contabai'); ?></label></th>
                    <td>
                        <input name="openai_reasoning_effort" id="contabai_openai_reasoning_effort" type="text" class="regular-text" value="<?php echo esc_attr(self::get_reasoning_effort()); ?>">
                        <p class="description">
                            <?php esc_html_e('Examples: minimal, low, medium, high, xhigh.', 'contabai'); ?>
                            <a href="https://developers.openai.com/api/docs/guides/reasoning" target="_blank" rel="noopener"><?php esc_html_e('Reasoning guide', 'contabai'); ?></a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="contabai_pexels_key"><?php esc_html_e('Pexels API key', 'contabai'); ?></label></th>
                    <td><input name="pexels_key" id="contabai_pexels_key" type="text" class="regular-text" autocomplete="off" value="<?php echo esc_attr(self::get_pexels_key()); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"></th>
                    <td>
                        <a href="<?php echo esc_url(add_query_arg(['page' => 'contabai-tools', 'tab' => 'ai-seo', 'restore' => '1'], admin_url('admin.php'))); ?>" class="button button-primary"><?php esc_html_e('Restore default prompt', 'contabai'); ?></a>
                        <p class="description"><?php esc_html_e('Loads the default prompt into the editor — click Save to keep it.', 'contabai'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="contabai_ai_seo_prompt"><?php esc_html_e('SEO prompt', 'contabai'); ?></label></th>
                    <td>
                        <p class="description contabai-admin-tight"><?php esc_html_e('Sent to OpenAI per page. These placeholders are filled in automatically: {{site_title}}, {{site_tagline}}, {{target_location}}, {{target_type}}, {{country}}, {{city}}, {{area}}, {{nearby_locations}}.', 'contabai'); ?></p>
                        <textarea name="ai_seo_prompt" id="contabai_ai_seo_prompt" rows="26" class="large-text code"><?php echo esc_textarea($prompt); ?></textarea>
                    </td>
                </tr>
            </table>

            <?php submit_button(__('Save AI SEO settings', 'contabai')); ?>
        </form>
        <?php
    }
}
