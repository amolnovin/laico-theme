<?php

/**
 * Laico Design — Sage Theme Setup (`app/setup.php`).
 */

declare(strict_types=1);

namespace App;

use App\Services\ThemeOptions;
use App\Support\ViteAssets;

/**
 * Register theme assets on the frontend.
 */
add_action('wp_enqueue_scripts', static function (): void {
    // Remix Icon stylesheet used by social & UI icons
    wp_enqueue_style(
        'laico-remixicon',
        'https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css',
        [],
        '4.2.0'
    );

    $cssUrl = ViteAssets::entryCss('resources/css/app.css');
    if ($cssUrl !== null) {
        wp_enqueue_style('laico-styles', $cssUrl, ['laico-remixicon'], null);
    }

    $jsUrl = ViteAssets::entryJs('resources/js/app.js');
    if ($jsUrl !== null) {
        wp_enqueue_script('laico-app', $jsUrl, [], null, true);
        wp_localize_script('laico-app', 'laicoConfig', [
            'restUrl' => esc_url_raw(rest_url('laico/v1/submissions')),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('laico_public_form'),
            'defaultLang' => (string) ThemeOptions::get('default_language', 'fa'),
            'enableLangSwitcher' => (bool) ThemeOptions::get('enable_lang_switcher', true),
            'prefetch' => (bool) ThemeOptions::get('opt_instant_page_prefetch', true),
        ]);
    }
}, 20);

/**
 * Register Block Editor styles & scripts.
 */
add_action('enqueue_block_editor_assets', static function (): void {
    $editorCss = ViteAssets::entryCss('resources/css/editor.css');
    if ($editorCss !== null) {
        wp_enqueue_style('laico-editor-styles', $editorCss, [], null);
    }

    $editorJs = ViteAssets::entryJs('resources/js/editor.js');
    if ($editorJs !== null) {
        wp_enqueue_script('laico-editor-scripts', $editorJs, ['wp-blocks', 'wp-dom-ready'], null, true);
    }
});

/**
 * Register initial theme supports, image sizes, and navigation menus.
 */
add_action('after_setup_theme', static function (): void {
    load_theme_textdomain('laico-sage', get_template_directory() . '/resources/lang');

    remove_theme_support('block-templates');

    register_nav_menus([
        'primary_navigation' => 'منوی اصلی هدر (Primary Navigation)',
        'footer_navigation' => 'منوی ستون اول فوتر (Footer Navigation)',
    ]);

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');
    add_theme_support('customize-selective-refresh-widgets');
    add_theme_support('html5', [
        'caption',
        'comment-form',
        'comment-list',
        'gallery',
        'search-form',
        'script',
        'style',
    ]);

    // High-resolution Hero & Portfolio image sizes
    add_image_size('laico-hero', 1920, 1080, false);
    add_image_size('laico-card', 1200, 900, true);
}, 20);
