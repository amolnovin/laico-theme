<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\PostTypes\ProjectPostType;
use App\Services\ThemeOptions;
use App\Support\Hero;
use Roots\Acorn\View\Composer;

/**
 * View Composer for `front-page.blade.php` (Home Page).
 */
final class FrontPage extends Composer
{
    /** @var array<int, string> */
    protected static $views = ['front-page'];

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'hero' => Hero::resolve('home'),
            'featuredProjects' => self::queryFeaturedMosaic(),
            'studioStats' => self::resolveStats(),
        ];
    }

    /**
     * Query the 4 featured portfolio projects for Section `02 — پروژه‌های منتخب`.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function queryFeaturedMosaic(): array
    {
        $posts = get_posts([
            'post_type' => ProjectPostType::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 4,
            'meta_key' => '_laico_featured_home',
            'meta_value' => '1',
            'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
        ]);

        if (count($posts) < 4) {
            $exclude = array_map(static fn (\WP_Post $p): int => (int) $p->ID, $posts);
            $extra = get_posts([
                'post_type' => ProjectPostType::POST_TYPE,
                'post_status' => 'publish',
                'posts_per_page' => 4 - count($posts),
                'post__not_in' => $exclude,
                'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
            ]);
            $posts = array_merge($posts, $extra);
        }

        // Sort explicitly by `_laico_mosaic_slot` (1..4) when set
        usort($posts, static function (\WP_Post $a, \WP_Post $b): int {
            $sa = (int) get_post_meta($a->ID, '_laico_mosaic_slot', true) ?: 99;
            $sb = (int) get_post_meta($b->ID, '_laico_mosaic_slot', true) ?: 99;
            return $sa <=> $sb;
        });

        return array_map([Projects::class, 'formatProjectCard'], array_slice($posts, 0, 4));
    }

    /**
     * @return array<int, array{num: string, fa: string, en: string}>
     */
    public static function resolveStats(): array
    {
        $items = [];
        for ($i = 1; $i <= 3; $i++) {
            $items[] = [
                'num' => (string) ThemeOptions::get("stat_{$i}_num", ''),
                'fa' => (string) ThemeOptions::get("stat_{$i}_fa", ''),
                'en' => (string) ThemeOptions::get("stat_{$i}_en", ''),
            ];
        }
        return $items;
    }
}
