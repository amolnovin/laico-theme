<?php

/**
 * Laico Design — Sage Theme Filters (`app/filters.php`).
 */

declare(strict_types=1);

namespace App;

/**
 * Clean excerpt suffix.
 */
add_filter('excerpt_more', static fn (): string => '…');
add_filter('excerpt_length', static fn (): int => 26, 999);

/**
 * Explicitly expose custom Blade page templates in the WordPress Page Attributes selector.
 */
add_filter('theme_page_templates', static function (array $templates): array {
    $custom = [
        'template-services.blade.php' => 'لایکو — صفحه خدمات (Services)',
        'template-projects.blade.php' => 'لایکو — آرشیو پروژه‌ها (Projects)',
        'template-about.blade.php' => 'لایکو — درباره ما (About)',
        'template-contact.blade.php' => 'لایکو — تماس با ما (Contact)',
        'template-showroom.blade.php' => 'لایکو — شو روم (Showroom)',
        'template-collection.blade.php' => 'لایکو — صفحه مجموعه / زیرصفحه تخصصی',
        'template-legal.blade.php' => 'لایکو — برگه متنی / حقوقی (حریم خصوصی، کوکی‌ها)',
    ];

    return array_merge($templates, $custom);
});

/**
 * Add module type attribute when Vite dev server is active.
 */
add_filter('script_loader_tag', static function (string $tag, string $handle): string {
    if (in_array($handle, ['laico-app', 'laico-editor-scripts'], true) && is_file(LAICO_THEME_DIR . '/public/hot')) {
        return str_replace('<script ', '<script type="module" ', $tag);
    }
    return $tag;
}, 15, 2);
