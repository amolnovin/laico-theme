<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Hero;

/**
 * Built-in SEO, Open Graph, Twitter Card, JSON-LD Schema, Meta Box,
 * and Legacy `.html` Redirect Engine.
 *
 * Automatically yields meta tag output if Yoast SEO, Rank Math, or SEOPress is active.
 */
final class SeoService
{
    public function register(): void
    {
        add_action('template_redirect', [$this, 'handleLegacyHtmlRedirects'], 1);
        add_filter('document_title_parts', [$this, 'filterDocumentTitleParts'], 20);
        add_filter('document_title_separator', [$this, 'filterTitleSeparator'], 20);
        add_action('wp_head', [$this, 'outputMetaAndSchema'], 5);
        add_action('wp_head', [$this, 'outputCustomHeadScripts'], 99);
        add_action('wp_footer', [$this, 'outputCustomFooterScripts'], 99);
        add_action('add_meta_boxes', [$this, 'registerSeoMetaBox']);
        add_action('save_post', [$this, 'saveSeoMetaBox'], 10, 2);
    }

    public static function hasExternalSeoPlugin(): bool
    {
        return defined('WPSEO_VERSION')
            || defined('RANK_MATH_VERSION')
            || defined('SEOPRESS_VERSION')
            || class_exists('All_in_One_SEO_Pack');
    }

    /**
     * Redirect legacy static `.html` URLs (e.g. /services.html, /projects.html,
     * /journal.html, /about.html, /contact.html, /project-{slug}.html, /article-{slug}.html)
     * cleanly with HTTP 301 so existing links never 404.
     */
    public function handleLegacyHtmlRedirects(): void
    {
        if (! is_404() || ! (bool) ThemeOptions::get('redirect_legacy_html', true)) {
            return;
        }

        $requestUri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash((string) $_SERVER['REQUEST_URI'])) : '';
        $path = trim((string) wp_parse_url($requestUri, PHP_URL_PATH), '/');
        if ($path === '' || ! str_ends_with(strtolower($path), '.html')) {
            return;
        }

        $basename = basename($path, '.html');
        if ($basename === 'index') {
            wp_safe_redirect(home_url('/'), 301);
            exit;
        }

        if (in_array($basename, ['services', 'projects', 'journal', 'about', 'contact', 'kitchen', 'living', 'bath', 'cafe', 'studio', 'systems', 'process', 'materials', 'showroom', 'privacy', 'cookies'], true)) {
            wp_safe_redirect(laico_page_url($basename), 301);
            exit;
        }

        if (str_starts_with($basename, 'project-')) {
            $slug = substr($basename, strlen('project-'));
            $project = get_page_by_path($slug, OBJECT, 'laico_project');
            if ($project instanceof \WP_Post && $project->post_status === 'publish') {
                $permalink = get_permalink($project);
                if ($permalink) {
                    wp_safe_redirect($permalink, 301);
                    exit;
                }
            }
        }

        if (str_starts_with($basename, 'article-')) {
            $slug = substr($basename, strlen('article-'));
            $post = get_page_by_path($slug, OBJECT, 'post');
            if ($post instanceof \WP_Post && $post->post_status === 'publish') {
                $permalink = get_permalink($post);
                if ($permalink) {
                    wp_safe_redirect($permalink, 301);
                    exit;
                }
            }
        }
    }

    public function filterTitleSeparator(string $sep): string
    {
        if ($this->hasExternalSeoPlugin()) {
            return $sep;
        }
        $custom = trim((string) ThemeOptions::get('seo_title_separator', '—'));
        return $custom !== '' ? $custom : '—';
    }

    /**
     * @param array<string, string> $parts
     * @return array<string, string>
     */
    public function filterDocumentTitleParts(array $parts): array
    {
        if ($this->hasExternalSeoPlugin() || ! (bool) ThemeOptions::get('enable_builtin_seo', true)) {
            return $parts;
        }

        $postId = $this->resolveCurrentMetaPostId();
        if ($postId > 0) {
            $customTitle = trim((string) get_post_meta($postId, '_laico_seo_title', true));
            if ($customTitle !== '') {
                $parts['title'] = $customTitle;
            }
        }

        return $parts;
    }

    public function outputMetaAndSchema(): void
    {
        $builtinSeo = (bool) ThemeOptions::get('enable_builtin_seo', true) && ! $this->hasExternalSeoPlugin();
        $enableSchema = (bool) ThemeOptions::get('enable_schema_jsonld', true) && ! $this->hasExternalSeoPlugin();

        if (! $builtinSeo && ! $enableSchema) {
            return;
        }

        $postId = $this->resolveCurrentMetaPostId();
        $title = wp_get_document_title();
        $description = $this->resolveDescription($postId);
        $canonical = $this->resolveCanonicalUrl($postId);
        $ogImage = $this->resolveOgImage($postId);
        $noindex = $postId > 0 && (bool) get_post_meta($postId, '_laico_seo_noindex', true);
        $ogType = is_singular(['post', 'laico_project']) ? 'article' : 'website';

        $noindexSearch = is_search() && (bool) ThemeOptions::get('seo_noindex_search', true);
        $noindexAuthor = is_author() && (bool) ThemeOptions::get('seo_noindex_author', true);

        if ($builtinSeo) {
            if ($noindex || is_404() || $noindexSearch || $noindexAuthor) {
                echo '<meta name="robots" content="noindex, follow">' . "\n";
            }

            if (is_front_page()) {
                $gVerify = trim((string) ThemeOptions::get('seo_google_verification', ''));
                $bVerify = trim((string) ThemeOptions::get('seo_bing_verification', ''));
                if ($gVerify !== '') {
                    printf('<meta name="google-site-verification" content="%s">' . "\n", esc_attr($gVerify));
                }
                if ($bVerify !== '') {
                    printf('<meta name="msvalidate.01" content="%s">' . "\n", esc_attr($bVerify));
                }
            }

            if ($description !== '') {
                printf('<meta name="description" content="%s">' . "\n", esc_attr($description));
            }

            if ($canonical !== '') {
                printf('<link rel="canonical" href="%s">' . "\n", esc_url($canonical));
            }

            printf('<meta property="og:title" content="%s">' . "\n", esc_attr($title));
            if ($description !== '') {
                printf('<meta property="og:description" content="%s">' . "\n", esc_attr($description));
            }
            printf('<meta property="og:type" content="%s">' . "\n", esc_attr($ogType));
            if ($canonical !== '') {
                printf('<meta property="og:url" content="%s">' . "\n", esc_url($canonical));
            }
            printf('<meta property="og:site_name" content="%s">' . "\n", esc_attr(get_bloginfo('name')));
            echo '<meta property="og:locale" content="fa_IR">' . "\n";
            if ($ogImage !== '') {
                printf('<meta property="og:image" content="%s">' . "\n", esc_url($ogImage));
            }

            echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
            printf('<meta name="twitter:title" content="%s">' . "\n", esc_attr($title));
            if ($description !== '') {
                printf('<meta name="twitter:description" content="%s">' . "\n", esc_attr($description));
            }
            if ($ogImage !== '') {
                printf('<meta name="twitter:image" content="%s">' . "\n", esc_url($ogImage));
            }
        }

        if ($enableSchema) {
            $graph = $this->buildSchemaGraph($postId, $title, $description, $canonical, $ogImage);
            if (! empty($graph)) {
                $payload = [
                    '@context' => 'https://schema.org',
                    '@graph' => $graph,
                ];
                echo '<script type="application/ld+json">' . wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
            }
        }
    }

    private function resolveCurrentMetaPostId(): int
    {
        if (is_front_page()) {
            return (int) get_option('page_on_front') ?: (is_singular() ? (int) get_queried_object_id() : 0);
        }
        if (is_home()) {
            return (int) get_option('page_for_posts');
        }
        if (is_post_type_archive('laico_project')) {
            return Hero::findSectionPageId('projects');
        }
        if (is_singular()) {
            return (int) get_queried_object_id();
        }
        return 0;
    }

    private function resolveDescription(int $postId): string
    {
        if ($postId > 0) {
            $metaDesc = trim((string) get_post_meta($postId, '_laico_seo_description', true));
            if ($metaDesc !== '') {
                return $metaDesc;
            }
            $excerpt = trim(wp_strip_all_tags((string) get_post_field('post_excerpt', $postId)));
            if ($excerpt !== '') {
                return mb_substr($excerpt, 0, 165);
            }
            $content = trim(wp_strip_all_tags(strip_shortcodes((string) get_post_field('post_content', $postId))));
            if ($content !== '') {
                return mb_substr(preg_replace('/\s+/u', ' ', $content) ?: $content, 0, 165);
            }
        }

        if (is_category() || is_tag() || is_tax()) {
            $termDesc = trim(wp_strip_all_tags(term_description()));
            if ($termDesc !== '') {
                return mb_substr($termDesc, 0, 165);
            }
        }

        return (string) ThemeOptions::get('seo_default_description', get_bloginfo('description'));
    }

    private function resolveCanonicalUrl(int $postId): string
    {
        if (is_front_page()) {
            return home_url('/');
        }
        if (is_post_type_archive('laico_project')) {
            return laico_page_url('projects');
        }
        if (is_home()) {
            return laico_page_url('journal');
        }
        if ($postId > 0) {
            return (string) (get_permalink($postId) ?: '');
        }
        if (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            if ($term instanceof \WP_Term) {
                $link = get_term_link($term);
                return is_string($link) ? $link : '';
            }
        }
        return '';
    }

    private function resolveOgImage(int $postId): string
    {
        if ($postId > 0) {
            $customOg = trim((string) get_post_meta($postId, '_laico_seo_og_image', true));
            if ($customOg !== '') {
                return $customOg;
            }
            if (has_post_thumbnail($postId)) {
                $src = wp_get_attachment_image_url((int) get_post_thumbnail_id($postId), 'full');
                if ($src) {
                    return $src;
                }
            }
        }

        $defaultOg = trim((string) ThemeOptions::get('seo_default_og_image', ''));
        if ($defaultOg !== '') {
            return $defaultOg;
        }

        return laico_asset('images/hero.jpg');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildSchemaGraph(int $postId, string $title, string $description, string $canonical, string $ogImage): array
    {
        $siteUrl = home_url('/');
        $siteName = get_bloginfo('name') ?: 'لایکو دیزاین';
        $logoUrl = (string) ThemeOptions::get('logo_url', '') ?: laico_asset('images/logo2.png');

        $sameAs = array_values(array_filter([
            (string) ThemeOptions::get('social_instagram', ''),
            (string) ThemeOptions::get('social_telegram', ''),
            (string) ThemeOptions::get('social_whatsapp', ''),
            (string) ThemeOptions::get('social_youtube', ''),
            (string) ThemeOptions::get('social_x', ''),
        ]));

        $graph = [
            [
                '@type' => ['InteriorDesigner', 'LocalBusiness'],
                '@id' => $siteUrl . '#organization',
                'name' => $siteName,
                'url' => $siteUrl,
                'logo' => $logoUrl,
                'image' => $ogImage,
                'telephone' => (string) ThemeOptions::get('phone_raw', '+989120898938'),
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => (string) ThemeOptions::get('address_full_fa', 'مازندران، آمل، دریای ۲۰'),
                    'addressLocality' => 'آمل',
                    'addressRegion' => 'مازندران',
                    'addressCountry' => 'IR',
                ],
                'sameAs' => $sameAs,
            ],
            [
                '@type' => 'WebSite',
                '@id' => $siteUrl . '#website',
                'url' => $siteUrl,
                'name' => $siteName,
                'inLanguage' => 'fa-IR',
                'publisher' => ['@id' => $siteUrl . '#organization'],
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => $siteUrl . '?s={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ];

        if (is_singular('post') && $postId > 0) {
            $graph[] = [
                '@type' => 'BlogPosting',
                'headline' => get_the_title($postId),
                'description' => $description,
                'image' => $ogImage,
                'datePublished' => get_the_date('c', $postId),
                'dateModified' => get_the_modified_date('c', $postId),
                'mainEntityOfPage' => $canonical,
                'author' => ['@id' => $siteUrl . '#organization'],
                'publisher' => ['@id' => $siteUrl . '#organization'],
            ];
            $graph[] = $this->breadcrumbSchema([
                ['name' => 'صفحه اصلی', 'url' => $siteUrl],
                ['name' => 'مجله', 'url' => laico_page_url('journal')],
                ['name' => get_the_title($postId), 'url' => $canonical],
            ]);
        } elseif (is_singular('laico_project') && $postId > 0) {
            $graph[] = [
                '@type' => 'CreativeWork',
                'name' => get_the_title($postId),
                'description' => $description,
                'image' => $ogImage,
                'url' => $canonical,
                'creator' => ['@id' => $siteUrl . '#organization'],
                'locationCreated' => (string) get_post_meta($postId, '_laico_location_fa', true),
            ];
            $graph[] = $this->breadcrumbSchema([
                ['name' => 'صفحه اصلی', 'url' => $siteUrl],
                ['name' => 'پروژه‌ها', 'url' => laico_page_url('projects')],
                ['name' => get_the_title($postId), 'url' => $canonical],
            ]);
        } elseif (! is_front_page() && $canonical !== '') {
            $graph[] = $this->breadcrumbSchema([
                ['name' => 'صفحه اصلی', 'url' => $siteUrl],
                ['name' => $title, 'url' => $canonical],
            ]);
        }

        return $graph;
    }

    /**
     * @param array<int, array{name: string, url: string}> $items
     * @return array<string, mixed>
     */
    private function breadcrumbSchema(array $items): array
    {
        $elements = [];
        foreach ($items as $idx => $item) {
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $idx + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ];
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }

    public function outputCustomHeadScripts(): void
    {
        $code = (string) ThemeOptions::get('seo_head_scripts', '');
        if ($code !== '') {
            echo "\n" . $code . "\n";
        }
    }

    public function outputCustomFooterScripts(): void
    {
        $code = (string) ThemeOptions::get('seo_footer_scripts', '');
        if ($code !== '') {
            echo "\n" . $code . "\n";
        }
    }

    public function registerSeoMetaBox(): void
    {
        if (! (bool) ThemeOptions::get('enable_builtin_seo', true)) {
            return;
        }

        add_meta_box(
            'laico_seo_metabox',
            'تنظیمات سئو و شبکه‌های اجتماعی (لایکو)',
            [$this, 'renderSeoMetaBox'],
            ['page', 'post', 'laico_project'],
            'normal',
            'low'
        );
    }

    public function renderSeoMetaBox(\WP_Post $post): void
    {
        wp_nonce_field('laico_save_seo_meta', '_laico_seo_nonce');
        $seoTitle = (string) get_post_meta($post->ID, '_laico_seo_title', true);
        $seoDesc = (string) get_post_meta($post->ID, '_laico_seo_description', true);
        $seoOg = (string) get_post_meta($post->ID, '_laico_seo_og_image', true);
        $noindex = (bool) get_post_meta($post->ID, '_laico_seo_noindex', true);
        ?>
        <div class="laico-meta-grid" dir="rtl">
          <div class="laico-meta-field">
            <label for="laico_seo_title"><strong>عنوان سئو (اختیاری)</strong></label>
            <input type="text" id="laico_seo_title" name="laico_seo_title" value="<?php echo esc_attr($seoTitle); ?>" class="widefat" placeholder="در صورت خالی بودن از عنوان صفحه استفاده می‌شود">
          </div>
          <div class="laico-meta-field">
            <label for="laico_seo_description"><strong>توضیحات متا (Meta Description)</strong></label>
            <textarea id="laico_seo_description" name="laico_seo_description" rows="3" class="widefat" placeholder="خلاصهٔ جذاب بین ۱۲۰ تا ۱۶۰ کاراکتر برای نتایج گوگل"><?php echo esc_textarea($seoDesc); ?></textarea>
          </div>
          <div class="laico-meta-field">
            <label for="laico_seo_og_image"><strong>تصویر اشتراک‌گذاری (og:image — اختیاری)</strong></label>
            <div class="laico-media-input-row">
              <input type="url" id="laico_seo_og_image" name="laico_seo_og_image" value="<?php echo esc_attr($seoOg); ?>" class="widefat" dir="ltr" placeholder="https://... (پیش‌فرض: تصویر شاخص صفحه)">
              <button type="button" class="button laico-pick-media" data-target="#laico_seo_og_image">انتخاب از رسانه</button>
            </div>
          </div>
          <div class="laico-meta-field">
            <label>
              <input type="checkbox" name="laico_seo_noindex" value="1" <?php checked($noindex); ?>>
              عدم نمایه‌سازی این صفحه در موتورهای جستجو (<code>noindex</code>)
            </label>
          </div>
        </div>
        <?php
    }

    public function saveSeoMetaBox(int $postId, \WP_Post $post): void
    {
        if (! isset($_POST['_laico_seo_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['_laico_seo_nonce'])), 'laico_save_seo_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (! current_user_can('edit_post', $postId)) {
            return;
        }

        $title = isset($_POST['laico_seo_title']) ? sanitize_text_field(wp_unslash((string) $_POST['laico_seo_title'])) : '';
        $desc = isset($_POST['laico_seo_description']) ? sanitize_textarea_field(wp_unslash((string) $_POST['laico_seo_description'])) : '';
        $og = isset($_POST['laico_seo_og_image']) ? esc_url_raw(wp_unslash((string) $_POST['laico_seo_og_image'])) : '';
        $noindex = ! empty($_POST['laico_seo_noindex']) ? '1' : '';

        update_post_meta($postId, '_laico_seo_title', $title);
        update_post_meta($postId, '_laico_seo_description', $desc);
        update_post_meta($postId, '_laico_seo_og_image', $og);
        if ($noindex === '1') {
            update_post_meta($postId, '_laico_seo_noindex', '1');
        } else {
            delete_post_meta($postId, '_laico_seo_noindex');
        }
    }
}
