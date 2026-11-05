<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Hero;
use App\Support\ViteAssets;
use App\View\Composers\App as AppComposer;

/**
 * WordPress Performance, Core Web Vitals & Bloat Removal Service.
 * Controlled via Tab 3 (بهینه‌سازی و سرعت) in Laico Theme Settings.
 */
final class OptimizationService
{
    public function register(): void
    {
        add_action('init', [$this, 'applyCoreCleanups']);
        add_action('wp_default_scripts', [$this, 'maybeDequeueJqueryMigrate']);
        add_action('wp_enqueue_scripts', [$this, 'optimizeFrontendAssets'], 100);
        add_action('wp_head', [$this, 'outputPreloadHints'], 1);
        add_action('wp_head', [$this, 'outputEarlyHeadFlags'], 2);
        add_filter('script_loader_tag', [$this, 'maybeDeferThemeScript'], 20, 2);
        add_filter('wp_lazy_loading_enabled', [$this, 'filterLazyLoading']);
        add_action('send_headers', [$this, 'sendSecurityHeaders']);

        if ((bool) ThemeOptions::get('opt_disable_xmlrpc', true)) {
            add_filter('xmlrpc_enabled', '__return_false');
            add_filter('wp_headers', static function (array $headers): array {
                unset($headers['X-Pingback']);
                return $headers;
            });
        }

        if ((bool) ThemeOptions::get('opt_disable_self_pingbacks', true)) {
            add_action('pre_ping', static function (array &$links): void {
                $home = home_url();
                foreach ($links as $i => $link) {
                    if (str_starts_with((string) $link, $home)) {
                        unset($links[$i]);
                    }
                }
            });
        }
    }

    public function applyCoreCleanups(): void
    {
        if ((bool) ThemeOptions::get('opt_remove_wp_version', true)) {
            remove_action('wp_head', 'wp_generator');
            remove_action('wp_head', 'rsd_link');
            remove_action('wp_head', 'wlwmanifest_link');
            remove_action('wp_head', 'wp_shortlink_wp_head', 10);
            remove_action('wp_head', 'Adjacent_Posts_Rel_Link_WP_Head', 10);
            add_filter('the_generator', '__return_empty_string');
        }

        if ((bool) ThemeOptions::get('opt_disable_emojis', true)) {
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
            remove_action('wp_print_styles', 'print_emoji_styles');
            remove_action('admin_print_styles', 'print_emoji_styles');
            remove_filter('the_content_feed', 'wp_staticize_emoji');
            remove_filter('comment_text_rss', 'wp_staticize_emoji');
            remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
            add_filter('emoji_svg_url', '__return_false');
        }

        if ((bool) ThemeOptions::get('opt_disable_embeds', true) && ! is_admin()) {
            remove_action('wp_head', 'wp_oembed_add_discovery_links');
            remove_action('wp_head', 'wp_oembed_add_host_js');
        }
    }

    public function maybeDequeueJqueryMigrate(\WP_Scripts $scripts): void
    {
        if (is_admin() || ! (bool) ThemeOptions::get('opt_dequeue_jquery_migrate', true)) {
            return;
        }
        if (isset($scripts->registered['jquery']) && is_object($scripts->registered['jquery'])) {
            $scripts->registered['jquery']->deps = array_diff(
                $scripts->registered['jquery']->deps,
                ['jquery-migrate']
            );
        }
    }

    public function optimizeFrontendAssets(): void
    {
        if (is_admin()) {
            return;
        }

        if ((bool) ThemeOptions::get('opt_remove_dashicons_guest', true) && ! is_user_logged_in()) {
            wp_deregister_style('dashicons');
        }

        if ((bool) ThemeOptions::get('opt_disable_embeds', true)) {
            wp_deregister_script('wp-embed');
        }
    }

    /**
     * Preload critical Vazirmatn WOFF2 fonts & LCP Page Hero Featured Image.
     */
    public function outputPreloadHints(): void
    {
        if ((bool) ThemeOptions::get('opt_preload_fonts', true)) {
            $regular = ViteAssets::assetUrl('fonts/Vazirmatn-Regular.woff2');
            $medium = ViteAssets::assetUrl('fonts/Vazirmatn-Medium.woff2');
            printf('<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url($regular));
            printf('<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url($medium));
        }

        if ((bool) ThemeOptions::get('opt_preload_hero', true)) {
            $section = AppComposer::detectCurrentSection();
            if (in_array($section, ['home', 'services', 'projects', 'journal', 'about', 'contact'], true) && ! is_singular(['post', 'laico_project'])) {
                $hero = Hero::resolve($section);
                if (! empty($hero['url'])) {
                    printf(
                        '<link rel="preload" as="image" href="%s"%s fetchpriority="high">' . "\n",
                        esc_url((string) $hero['url']),
                        ! empty($hero['srcset']) ? ' imagesrcset="' . esc_attr((string) $hero['srcset']) . '" imagesizes="100vw"' : ''
                    );
                }
            }
        }
    }

    /**
     * Output zero-FOUC inline script for saved language & motion flag.
     */
    public function outputEarlyHeadFlags(): void
    {
        if (! (bool) ThemeOptions::get('opt_zero_fouc_lang', true)) {
            return;
        }

        $langSwitcher = ThemeOptions::isEnglishEnabled();
        $defaultLang = $langSwitcher ? (string) ThemeOptions::get('default_language', 'fa') : 'fa';

        echo '<script>(function(){try{if(!window.matchMedia("(prefers-reduced-motion: reduce)").matches)document.documentElement.classList.add("mo-on");}catch(e){}})();</script>' . "\n";

        if ($langSwitcher) {
            printf(
                '<script>(function(){try{var l=localStorage.getItem("laico-lang")||"%s";if(l==="en"){document.documentElement.lang="en";document.documentElement.dir="ltr";}else{document.documentElement.lang="fa";document.documentElement.dir="rtl";}}catch(e){}})();</script>' . "\n",
                esc_js($defaultLang)
            );
        } else {
            echo '<script>(function(){try{document.documentElement.lang="fa";document.documentElement.dir="rtl";localStorage.removeItem("laico-lang");}catch(e){}})();</script>' . "\n";
        }
    }

    public function maybeDeferThemeScript(string $tag, string $handle): string
    {
        if ($handle !== 'laico-app' || ! (bool) ThemeOptions::get('opt_defer_scripts', true)) {
            return $tag;
        }
        if (str_contains($tag, ' defer') || str_contains($tag, 'type="module"')) {
            return $tag;
        }
        return str_replace(' src=', ' defer src=', $tag);
    }

    public function filterLazyLoading(bool $default): bool
    {
        return (bool) ThemeOptions::get('opt_lazy_images', true) ? $default : false;
    }

    public function sendSecurityHeaders(): void
    {
        if (is_admin() || headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}
