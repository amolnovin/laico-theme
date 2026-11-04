<?php

/**
 * Global helper functions for Laico Sage Theme.
 */

declare(strict_types=1);

use App\Services\ThemeOptions;
use App\Support\Bilingual;
use App\Support\Hero;
use App\Support\Jalali;

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('collect_or_each_laico')) {
    /**
     * Load core theme files (`app/{file}.php`) with or without Illuminate's collect().
     *
     * @param array<int, string> $files
     */
    function collect_or_each_laico(array $files): void
    {
        foreach ($files as $file) {
            $relative = "app/{$file}.php";
            if (! locate_template($relative, true, true)) {
                wp_die(
                    sprintf(
                        /* translators: %s: relative file path */
                        esc_html__('خطا در بارگذاری فایل قالب: %s', 'laico'),
                        '<code>' . esc_html($relative) . '</code>'
                    )
                );
            }
        }
    }
}

if (! function_exists('laico_option')) {
    /**
     * Retrieve a typed theme setting with automatic default fallback.
     */
    function laico_option(string $key, mixed $default = null): mixed
    {
        return ThemeOptions::get($key, $default);
    }
}

if (! function_exists('laico_bi')) {
    /**
     * Render bilingual HTML pair (<span class="lang-fa">...</span><span class="lang-en" hidden>...</span>).
     */
    function laico_bi(string $fa, string $en = '', bool $allowInlineHtml = false): string
    {
        return Bilingual::pair($fa, $en, $allowInlineHtml);
    }
}

if (! function_exists('laico_asset')) {
    /**
     * Return public URI to a static theme resource in resources/{images,fonts,video}/.
     */
    function laico_asset(string $relativePath): string
    {
        $clean = ltrim($relativePath, '/');
        return trailingslashit(LAICO_THEME_URI) . 'resources/' . $clean;
    }
}

if (! function_exists('laico_hero')) {
    /**
     * Resolve hero data (including Featured Image from the corresponding WordPress Page).
     *
     * @return array<string, mixed>
     */
    function laico_hero(string $context, ?int $postId = null): array
    {
        return Hero::resolve($context, $postId);
    }
}

if (! function_exists('laico_fa_digits')) {
    /**
     * Convert ASCII digits to Persian digits.
     */
    function laico_fa_digits(string|int|float $value): string
    {
        return Jalali::toPersianDigits((string) $value);
    }
}

if (! function_exists('laico_date_pair')) {
    /**
     * Return [fa_jalali_date, en_gregorian_date] for a WP_Post or timestamp.
     *
     * @return array{0: string, 1: string}
     */
    function laico_date_pair(int|\WP_Post|null $post = null): array
    {
        return Jalali::postDatePair($post);
    }
}

if (! function_exists('laico_page_url')) {
    /**
     * Resolve permalink of a page by option key, template, or slug, with safe fallback.
     */
    function laico_page_url(string $slug, string $fallback = '#'): string
    {
        if ($slug === 'home') {
            return home_url('/');
        }

        if ($slug === 'projects') {
            $pageId = Hero::findSectionPageId('projects');
            if ($pageId > 0) {
                $url = get_permalink($pageId);
                if ($url) {
                    return $url;
                }
            }
            $archive = get_post_type_archive_link('laico_project');
            return $archive ?: home_url('/projects/');
        }

        if ($slug === 'journal') {
            $postsPage = (int) get_option('page_for_posts');
            if ($postsPage > 0) {
                $url = get_permalink($postsPage);
                if ($url) {
                    return $url;
                }
            }
            $pageId = Hero::findSectionPageId('journal');
            if ($pageId > 0) {
                $url = get_permalink($pageId);
                if ($url) {
                    return $url;
                }
            }
            return home_url('/journal/');
        }

        $pageId = Hero::findSectionPageId($slug);
        if ($pageId > 0) {
            $url = get_permalink($pageId);
            if ($url) {
                return $url;
            }
        }

        $page = get_page_by_path($slug);
        if ($page instanceof \WP_Post && $page->post_status === 'publish') {
            $url = get_permalink($page);
            if ($url) {
                return $url;
            }
        }

        return $fallback !== '#' ? $fallback : home_url('/' . trim($slug, '/') . '/');
    }
}
