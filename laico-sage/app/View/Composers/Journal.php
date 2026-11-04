<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Support\Hero;
use App\Support\Jalali;
use App\Support\ViteAssets;
use Roots\Acorn\View\Composer;

/**
 * View Composer for Journal Archive (`home.blade.php`, `archive.blade.php`)
 * and Single Journal Article (`single.blade.php`) powered by native WordPress `post` entries.
 */
final class Journal extends Composer
{
    /** @var array<int, string> */
    protected static $views = [
        'home',
        'index',
        'archive',
        'single',
    ];

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        if (is_singular('post')) {
            $post = get_queried_object();
            if ($post instanceof \WP_Post) {
                return [
                    'article' => self::formatSingleArticle($post),
                    'relatedArticles' => self::queryRelatedPosts($post, 2),
                ];
            }
        }

        global $wp_query;
        $posts = [];
        if ($wp_query instanceof \WP_Query && ! empty($wp_query->posts)) {
            foreach ($wp_query->posts as $p) {
                if ($p instanceof \WP_Post && $p->post_type === 'post') {
                    $posts[] = self::formatArticleCard($p);
                }
            }
        }

        $paged = max(1, (int) get_query_var('paged', 1));
        $featuredPost = ($paged === 1 && ! empty($posts)) ? array_shift($posts) : null;

        return [
            'hero' => Hero::resolve('journal'),
            'featuredArticle' => $featuredPost,
            'journalArticles' => $posts,
        ];
    }

    /**
     * Query latest WordPress posts for Section `05 — مجله` on the Front Page.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function queryLatestForHome(int $limit = 3): array
    {
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        return array_map([self::class, 'formatArticleCard'], $posts);
    }

    /**
     * Format a native WordPress `post` into a Journal card array.
     *
     * @return array<string, mixed>
     */
    public static function formatArticleCard(\WP_Post $post): array
    {
        $id = (int) $post->ID;
        $titleFa = get_the_title($id);
        $titleEn = trim((string) get_post_meta($id, '_laico_title_en', true)) ?: $titleFa;

        $excerptFa = has_excerpt($id)
            ? get_the_excerpt($id)
            : wp_trim_words(wp_strip_all_tags($post->post_content), 22, '…');
        $excerptEn = trim((string) get_post_meta($id, '_laico_excerpt_en', true)) ?: $excerptFa;

        // Category label (FA + EN)
        $cats = get_the_category($id);
        $catFa = 'یادداشت';
        $catEn = 'Journal';
        if (! empty($cats[0]) && $cats[0] instanceof \WP_Term && $cats[0]->slug !== 'uncategorized') {
            $catFa = $cats[0]->name;
            $catEn = trim((string) get_term_meta($cats[0]->term_id, '_laico_name_en', true)) ?: ucfirst($cats[0]->slug);
        }

        // Jalali & Gregorian formatted dates
        $ts = get_post_timestamp($id) ?: time();
        $isoDate = gmdate('Y-m-d', $ts);
        $dateFa = Jalali::fromTimestamp($ts);
        $dateEn = gmdate('j M Y', $ts);

        // Reading time (meta override or auto-computed at ~180 words/minute)
        $readMin = (int) get_post_meta($id, '_laico_read_time', true);
        if ($readMin <= 0) {
            $plain = trim(wp_strip_all_tags($post->post_content));
            $wordCount = count(preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY) ?: []);
            $readMin = max(1, (int) ceil($wordCount / 180));
        }

        // Featured image (`post-thumbnail`)
        $thumbId = (int) get_post_thumbnail_id($id);
        $imgUrl = ViteAssets::assetUrl('images/detail.jpg');
        $srcset = '';
        $w = 1200;
        $h = 800;

        if ($thumbId > 0) {
            $src = wp_get_attachment_image_src($thumbId, 'large') ?: wp_get_attachment_image_src($thumbId, 'full');
            if (is_array($src) && ! empty($src[0])) {
                $imgUrl = (string) $src[0];
                $w = (int) ($src[1] ?: 1200);
                $h = (int) ($src[2] ?: 800);
                $srcset = (string) (wp_get_attachment_image_srcset($thumbId, 'large') ?: '');
            }
        }

        return [
            'id' => $id,
            'url' => (string) get_permalink($id),
            'title_fa' => $titleFa,
            'title_en' => $titleEn,
            'excerpt_fa' => $excerptFa,
            'excerpt_en' => $excerptEn,
            'cat_fa' => $catFa,
            'cat_en' => $catEn,
            'iso_date' => $isoDate,
            'date_fa' => $dateFa,
            'date_en' => $dateEn,
            'read_fa' => Jalali::toPersianDigits($readMin) . ' دقیقه مطالعه',
            'read_en' => $readMin . ' min read',
            'image' => [
                'url' => $imgUrl,
                'srcset' => $srcset,
                'width' => $w,
                'height' => $h,
            ],
        ];
    }

    /**
     * Format full single article data for `single.blade.php`.
     *
     * @return array<string, mixed>
     */
    public static function formatSingleArticle(\WP_Post $post): array
    {
        $card = self::formatArticleCard($post);
        $contentFa = apply_filters('the_content', $post->post_content);
        $rawEn = trim((string) get_post_meta($post->ID, '_laico_content_en', true));
        $contentEn = $rawEn !== '' ? wpautop($rawEn) : '';

        return array_merge($card, [
            'content_fa' => $contentFa,
            'content_en' => $contentEn,
            'has_en_body' => $contentEn !== '',
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function queryRelatedPosts(\WP_Post $current, int $limit = 2): array
    {
        $cats = wp_get_post_categories($current->ID);
        $args = [
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'post__not_in' => [$current->ID],
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        if (! empty($cats)) {
            $args['category__in'] = $cats;
        }

        $related = get_posts($args);
        if (count($related) < $limit) {
            $exclude = array_merge([$current->ID], array_map(static fn (\WP_Post $p): int => (int) $p->ID, $related));
            $more = get_posts([
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => $limit - count($related),
                'post__not_in' => $exclude,
            ]);
            $related = array_merge($related, $more);
        }

        return array_map([self::class, 'formatArticleCard'], array_slice($related, 0, $limit));
    }
}
