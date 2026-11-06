<?php

declare(strict_types=1);

namespace App\PostTypes;

use App\Services\ThemeOptions;
use App\Support\Hero;
use App\Support\ViteAssets;

/**
 * Registers Hero, About Page Content/Media Controls, and Bilingual Meta Boxes
 * on WordPress Pages (`page`), plus Journal Meta Boxes on Posts (`post`).
 */
final class PageAndPostMeta
{
    public const DEFAULT_ABOUT_LEAD_FA = 'معماری برای ما، گفتگو میان نور، ماده و زندگی است. ما در<br class="about-br"><strong>لایکودیزاین</strong> معتقدیم هر کنج از یک فضا، پتانسیل تبدیل شدن به<br class="about-br">اثری ماندگار را دارد. از نخستین خطوط روی کاغذ تا آخرین جزئیات<br class="about-br">اجرا، تلاش می‌کنیم مرزهای میان زیبایی‌شناسی مینیمال و<br class="about-br">کارآمدی مدرن را کمرنگ کنیم.';
    public const DEFAULT_ABOUT_LEAD_EN = 'For us, architecture is a conversation between light, material and life. At <strong>Laico Design</strong> we believe every corner of a space can become a lasting work. From the first lines on paper to the last detail of the build, we soften the line between minimal beauty and modern usefulness.';

    /**
     * Default 4 collage images for `.about-collage-stage`.
     *
     * @return array<int, array{class: string, label: string, asset: string, alt: string, width: int, height: int}>
     */
    public static function aboutCollageDefaults(): array
    {
        return [
            1 => [
                'class' => 'c-sink',
                'label' => 'تصویر ۱ کلاژ — چپ بالا (c-sink)',
                'asset' => 'images/about-k-sink.webp',
                'alt' => 'سینک و کابینت سرمه‌ای',
                'width' => 476,
                'height' => 336,
            ],
            2 => [
                'class' => 'c-run',
                'label' => 'تصویر ۲ کلاژ — راست بالا عریض (c-run)',
                'asset' => 'images/about-k-run.webp',
                'alt' => 'کابینت سرمه‌ای با صفحه سنگ',
                'width' => 748,
                'height' => 394,
            ],
            3 => [
                'class' => 'c-base',
                'label' => 'تصویر ۳ کلاژ — پایین وسط/چپ (c-base)',
                'asset' => 'images/about-k-base.webp',
                'alt' => 'جزئیات کشوها و دستگیره‌ها',
                'width' => 654,
                'height' => 548,
            ],
            4 => [
                'class' => 'c-glass',
                'label' => 'تصویر ۴ کلاژ — راست پایین (c-glass)',
                'asset' => 'images/about-k-glass.webp',
                'alt' => 'کابینت شیشه‌ای دیواری',
                'width' => 510,
                'height' => 514,
            ],
        ];
    }

    public function register(): void
    {
        add_action('add_meta_boxes', [$this, 'registerMetaBoxes']);
        add_filter('postbox_classes_page_laico_about_page_meta', [$this, 'filterAboutMetaBoxClasses']);
        add_filter('postbox_classes_page_laico_services_page_meta', [$this, 'filterServicesMetaBoxClasses']);
        add_action('save_post_page', [$this, 'savePageMeta'], 10, 2);
        add_action('save_post_post', [$this, 'savePostMeta'], 10, 2);
    }

    public static function isAboutPage(\WP_Post $post): bool
    {
        if ($post->post_name === 'about') {
            return true;
        }
        $tpl = (string) get_page_template_slug($post->ID);
        if (str_contains($tpl, 'template-about')) {
            return true;
        }
        $configuredId = (int) ThemeOptions::get('about_page_id', 0);
        return $configuredId > 0 && $configuredId === (int) $post->ID;
    }

    public static function isServicesPage(\WP_Post $post): bool
    {
        $slug = urldecode((string) $post->post_name);
        if (in_array($slug, ['services', 'our-services', 'خدمات'], true)) {
            return true;
        }
        $tpl = (string) get_page_template_slug($post->ID);
        if (str_contains($tpl, 'template-services')) {
            return true;
        }
        $configuredId = (int) ThemeOptions::get('services_page_id', 0);
        return $configuredId > 0 && $configuredId === (int) $post->ID;
    }

    /**
     * @param array<int, string> $classes
     * @return array<int, string>
     */
    public function filterAboutMetaBoxClasses(array $classes): array
    {
        global $post;
        if ($post instanceof \WP_Post && self::isAboutPage($post)) {
            $classes[] = 'is-about-page-active';
        } else {
            $classes[] = 'is-about-page-hidden';
        }
        return $classes;
    }

    /**
     * @param array<int, string> $classes
     * @return array<int, string>
     */
    public function filterServicesMetaBoxClasses(array $classes): array
    {
        global $post;
        if ($post instanceof \WP_Post && self::isServicesPage($post)) {
            $classes[] = 'is-services-page-active';
        } else {
            $classes[] = 'is-services-page-hidden';
        }
        return $classes;
    }

    public function registerMetaBoxes(): void
    {
        $enEnabled = ThemeOptions::isEnglishEnabled();

        add_meta_box(
            'laico_about_page_meta',
            'تنظیمات اختصاصی صفحه درباره ما (متن معرفی about-lead، تصاویر کلاژ و تصویر تیم)',
            [$this, 'renderAboutPageMetaBox'],
            'page',
            'normal',
            'high'
        );

        add_meta_box(
            'laico_services_page_meta',
            'تنظیمات اختصاصی صفحه خدمات (لیست خدمات svc-list و بخش چرا لایکو svc-why)',
            [$this, 'renderServicesPageMetaBox'],
            'page',
            'normal',
            'high'
        );

        add_meta_box(
            'laico_page_hero_meta',
            $enEnabled ? 'تنظیمات بخش هیرو (Hero) و دوزبانگی برگه' : 'تنظیمات بخش هیرو (Hero) برگه',
            [$this, 'renderPageHeroMetaBox'],
            'page',
            'normal',
            'high'
        );

        add_meta_box(
            'laico_post_journal_meta',
            $enEnabled ? 'تنظیمات تکمیلی مجله (زمان مطالعه و نسخهٔ انگلیسی)' : 'تنظیمات تکمیلی مجله (زمان مطالعه)',
            [$this, 'renderPostJournalMetaBox'],
            'post',
            'normal',
            'default'
        );
    }

    /**
     * Resolve all editable & controllable About Page data for `template-about.blade.php`.
     *
     * @return array<string, mixed>
     */
    public static function resolveAboutPageData(int $pageId): array
    {
        $hasMeta = static fn (string $key): bool => $pageId > 0 && metadata_exists('post', $pageId, $key);
        $boolMeta = static function (string $key, bool $default = true) use ($pageId, $hasMeta): bool {
            if (! $hasMeta($key)) {
                return $default;
            }
            return get_post_meta($pageId, $key, true) === '1';
        };

        $showLead = $boolMeta('_laico_about_show_lead', true);
        $rawLeadFa = $pageId > 0 ? trim((string) get_post_meta($pageId, '_laico_about_lead_fa', true)) : '';
        $rawLeadEn = $pageId > 0 ? trim((string) get_post_meta($pageId, '_laico_about_lead_en', true)) : '';

        $leadFa = $rawLeadFa !== '' ? self::formatLeadHtml($rawLeadFa) : self::DEFAULT_ABOUT_LEAD_FA;
        $leadEn = $rawLeadEn !== '' ? self::formatLeadHtml($rawLeadEn) : self::DEFAULT_ABOUT_LEAD_EN;

        $showCollage = $boolMeta('_laico_about_show_collage', true);
        $collageItems = [];
        foreach (self::aboutCollageDefaults() as $idx => $def) {
            $itemShow = $boolMeta("_laico_about_collage_{$idx}_show", true);
            $attId = $pageId > 0 ? (int) get_post_meta($pageId, "_laico_about_collage_{$idx}_id", true) : 0;
            $customUrl = $pageId > 0 ? trim((string) get_post_meta($pageId, "_laico_about_collage_{$idx}_url", true)) : '';
            $customAlt = $pageId > 0 ? trim((string) get_post_meta($pageId, "_laico_about_collage_{$idx}_alt", true)) : '';

            $resolved = self::resolveMediaImage(
                $attId,
                $customUrl,
                ViteAssets::assetUrl($def['asset']),
                $def['width'],
                $def['height'],
                $customAlt !== '' ? $customAlt : $def['alt']
            );

            $collageItems[$idx] = array_merge($resolved, [
                'show' => $itemShow,
                'class' => $def['class'],
            ]);
        }

        $showTeam = $boolMeta('_laico_about_show_team', true);
        $showTeamImage = $boolMeta('_laico_about_show_team_image', true);

        $teamTitleFa = $pageId > 0 ? trim((string) get_post_meta($pageId, '_laico_about_team_title_fa', true)) : '';
        $teamTitleEn = $pageId > 0 ? trim((string) get_post_meta($pageId, '_laico_about_team_title_en', true)) : '';
        if ($teamTitleFa === '') {
            $teamTitleFa = 'گروه معماران و طراحان';
        }
        if ($teamTitleEn === '') {
            $teamTitleEn = 'Architects and designers';
        }

        $teamAttId = $pageId > 0 ? (int) get_post_meta($pageId, '_laico_about_team_img_id', true) : 0;
        $teamUrl = $pageId > 0 ? trim((string) get_post_meta($pageId, '_laico_about_team_img_url', true)) : '';
        $teamAlt = $pageId > 0 ? trim((string) get_post_meta($pageId, '_laico_about_team_img_alt', true)) : '';

        $teamImage = self::resolveMediaImage(
            $teamAttId,
            $teamUrl,
            ViteAssets::assetUrl('images/about-team.webp'),
            1200,
            675,
            $teamAlt !== '' ? $teamAlt : 'استودیوی معماری و طراحی لایکو'
        );

        return [
            'show_lead' => $showLead,
            'lead_fa' => $leadFa,
            'lead_en' => $leadEn,
            'show_collage' => $showCollage,
            'collage' => $collageItems,
            'show_team' => $showTeam,
            'show_team_image' => $showTeamImage,
            'team_title_fa' => $teamTitleFa,
            'team_title_en' => $teamTitleEn,
            'team_image' => $teamImage,
        ];
    }

    /**
     * Default repeatable service rows for `/services` (`svc-list` / `svc-row`).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function servicesDefaultRows(): array
    {
        return [
            [
                'show' => true,
                'title_fa' => 'طراحی مطب و فضای درمانی',
                'title_en' => 'Clinics & healthcare spaces',
                'body_fa' => 'طراحی مطب و فضای درمانی با تمرکز بر آرامش بیماران، رعایت استانداردهای بهداشتی، بهینه‌سازی عملکرد فضا و ایجاد محیطی مدرن و حرفه‌ای.',
                'body_en' => 'Clinics designed around patient calm, hygiene standards, efficient circulation and a modern, professional atmosphere.',
                'space_slug' => 'office',
                'btn_fa' => 'مشاهده پروژه‌ها',
                'btn_en' => 'View projects',
                'img_id' => 0,
                'img_url' => '',
                'img_alt' => 'نمای داخلی مطب دندانپزشکی با یونیت سفید و دیوار شیشه‌ای',
                'default_asset' => 'images/services-clinic.webp',
            ],
            [
                'show' => true,
                'title_fa' => 'طراحی محوطه‌ی ورزشی و باشگاهی',
                'title_en' => 'Sports & gym facilities',
                'body_fa' => 'طراحی محوطه ورزشی و باشگاهی با تمرکز بر عملکرد بهینه، ایمنی، جانمایی اصولی تجهیزات و ایجاد فضایی پویا، مدرن و استاندارد برای فعالیت‌های ورزشی.',
                'body_en' => 'Sports facilities planned for performance, safety and correct equipment zoning — dynamic, modern and built to standard.',
                'space_slug' => 'sports',
                'btn_fa' => 'مشاهده پروژه‌ها',
                'btn_en' => 'View projects',
                'img_id' => 0,
                'img_url' => '',
                'img_alt' => 'سالن بدنسازی مدرن با نورپردازی خطی',
                'default_asset' => 'images/services-sport.webp',
            ],
            [
                'show' => true,
                'title_fa' => 'طراحی مجتمع‌های تفریحی',
                'title_en' => 'Leisure & entertainment complexes',
                'body_fa' => 'طراحی مجتمع‌های تفریحی با هدف خلق فضاهایی جذاب، پویا و کاربردی برای سرگرمی، تعامل اجتماعی و تجربه‌ای لذت‌بخش در محیطی هماهنگ با طبیعت و معماری مدرن.',
                'body_en' => 'Leisure complexes that invite social interaction and play, in a setting that balances nature with modern architecture.',
                'space_slug' => 'architecture',
                'btn_fa' => 'مشاهده پروژه‌ها',
                'btn_en' => 'View projects',
                'img_id' => 0,
                'img_url' => '',
                'img_alt' => 'نمای شبانه مجتمع تفریحی با نورپردازی گرم',
                'default_asset' => 'images/services-complex.webp',
            ],
            [
                'show' => true,
                'title_fa' => 'طراحی کافه و رستوران',
                'title_en' => 'Cafés & restaurants',
                'body_fa' => 'طراحی کافه و رستوران با تمرکز بر خلق فضایی جذاب و منحصربه‌فرد، چیدمان اصولی، نورپردازی حرفه‌ای و هماهنگی دکوراسیون با هویت برند و تجربه مشتریان.',
                'body_en' => 'Cafés and restaurants shaped around brand identity: considered layouts, professional lighting and a memorable guest experience.',
                'space_slug' => 'hall',
                'btn_fa' => 'مشاهده پروژه‌ها',
                'btn_en' => 'View projects',
                'img_id' => 0,
                'img_url' => '',
                'img_alt' => 'طراحی داخلی کافه و رستوران',
                'default_asset' => 'images/services-cafe.webp',
            ],
            [
                'show' => true,
                'title_fa' => 'طراحی آشپزخانه و فضای مسکونی',
                'title_en' => 'Kitchens & living spaces',
                'body_fa' => 'طراحی آشپزخانه و فضای مسکونی با تمرکز بر زیبایی‌شناسی، بهینه‌سازی فضا، چیدمان کاربردی و ایجاد محیطی هماهنگ، راحت و متناسب با سبک زندگی ساکنان.',
                'body_en' => 'Kitchens and homes tuned to the way people live: honest materials, smart layouts and a calm, coherent space.',
                'space_slug' => 'kitchen',
                'btn_fa' => 'مشاهده پروژه‌ها',
                'btn_en' => 'View projects',
                'img_id' => 0,
                'img_url' => '',
                'img_alt' => 'طراحی آشپزخانه و فضای مسکونی',
                'default_asset' => 'images/services-residential.webp',
            ],
        ];
    }

    /**
     * Default values for `svc-why` section on `/services`.
     *
     * @return array<string, string>
     */
    public static function servicesWhyDefaults(): array
    {
        return [
            'title_fa' => 'چرا لایکودیزاین؟',
            'title_en' => 'Why Laico Design?',
            'body_1_fa' => 'ترکیبی از خلاقیت، زیبایی‌شناسی و عملکرد در کنار توجه به جزییات، شناخت نیازهای کارفرما و ارائه راهکارهای معماری منحصربه‌فرد که هر فضا را به تجربه‌ای ماندگار و کاربردی تبدیل می‌کند.',
            'body_1_en' => 'Creativity, aesthetics and function, paired with attention to detail and a real understanding of the client — architectural solutions that turn every space into a lasting, usable experience.',
            'body_2_fa' => '',
            'body_2_en' => '',
            'asset' => 'images/services-why.webp',
            'alt' => 'نشیمن سرمه‌ای لایکو دیزاین',
        ];
    }

    /**
     * Resolve all editable Services Page data (`svc-list` repeatable rows & `svc-why`) for `template-services.blade.php`.
     *
     * @return array<string, mixed>
     */
    public static function resolveServicesPageData(int $pageId): array
    {
        $hasMeta = static fn (string $key): bool => $pageId > 0 && metadata_exists('post', $pageId, $key);
        $boolMeta = static function (string $key, bool $default = true) use ($pageId, $hasMeta): bool {
            if (! $hasMeta($key)) {
                return $default;
            }
            return get_post_meta($pageId, $key, true) === '1';
        };

        $showList = $boolMeta('_laico_svc_show_list', true);
        $defaults = self::servicesDefaultRows();
        $savedCustomized = $pageId > 0 && get_post_meta($pageId, '_laico_svc_rows_customized', true) === '1';
        $rawRows = $pageId > 0 ? get_post_meta($pageId, '_laico_svc_rows', true) : null;

        if ($savedCustomized && is_array($rawRows)) {
            $sourceRows = $rawRows;
        } elseif (is_array($rawRows) && ! empty($rawRows)) {
            $sourceRows = $rawRows;
        } else {
            $sourceRows = $defaults;
        }

        $projectsBaseUrl = laico_page_url('projects', '/projects/');
        $rows = [];

        foreach (array_values($sourceRows) as $idx => $r) {
            if (! is_array($r)) {
                continue;
            }
            $fallbackDef = $defaults[$idx % count($defaults)];
            $showRow = ! isset($r['show']) || ($r['show'] !== false && $r['show'] !== '0' && $r['show'] !== 0);
            if (! $showRow) {
                continue;
            }

            $titleFa = trim((string) ($r['title_fa'] ?? ''));
            if ($titleFa === '') {
                continue;
            }
            $titleEn = trim((string) ($r['title_en'] ?? '')) ?: $titleFa;
            $bodyFa = trim((string) ($r['body_fa'] ?? ''));
            $bodyEn = trim((string) ($r['body_en'] ?? '')) ?: $bodyFa;
            $btnFa = trim((string) ($r['btn_fa'] ?? '')) ?: 'مشاهده پروژه‌ها';
            $btnEn = trim((string) ($r['btn_en'] ?? '')) ?: 'View projects';
            $spaceSlug = sanitize_title((string) ($r['space_slug'] ?? ''));
            $defaultAsset = ! empty($r['default_asset']) ? (string) $r['default_asset'] : (string) $fallbackDef['default_asset'];

            $attId = (int) ($r['img_id'] ?? 0);
            $customUrl = trim((string) ($r['img_url'] ?? ''));
            $alt = trim((string) ($r['img_alt'] ?? '')) ?: $titleFa;

            $img = self::resolveMediaImage(
                $attId,
                $customUrl,
                ViteAssets::assetUrl($defaultAsset),
                600,
                600,
                $alt
            );

            $btnUrl = $spaceSlug !== ''
                ? add_query_arg('space', $spaceSlug, $projectsBaseUrl) . '#prj-list'
                : $projectsBaseUrl . '#prj-list';

            $rows[] = [
                'title_fa' => $titleFa,
                'title_en' => $titleEn,
                'body_fa' => $bodyFa,
                'body_en' => $bodyEn,
                'btn_fa' => $btnFa,
                'btn_en' => $btnEn,
                'space_slug' => $spaceSlug,
                'btn_url' => $btnUrl,
                'image' => $img,
            ];
        }

        $whyDef = self::servicesWhyDefaults();
        $showWhy = $boolMeta('_laico_svc_show_why', true);
        $showWhyImg = $boolMeta('_laico_svc_show_why_img', true);
        $whyTitleFa = $pageId > 0 ? trim((string) get_post_meta($pageId, '_laico_svc_why_title_fa', true)) : '';
        $whyTitleEn = $pageId > 0 ? trim((string) get_post_meta($pageId, '_laico_svc_why_title_en', true)) : '';
        $whyBody1Fa = $pageId > 0 ? trim((string) get_post_meta($pageId, '_laico_svc_why_body_1_fa', true)) : '';
        $whyBody1En = $pageId > 0 ? trim((string) get_post_meta($pageId, '_laico_svc_why_body_1_en', true)) : '';
        $whyBody2Fa = $pageId > 0 ? trim((string) get_post_meta($pageId, '_laico_svc_why_body_2_fa', true)) : '';
        $whyBody2En = $pageId > 0 ? trim((string) get_post_meta($pageId, '_laico_svc_why_body_2_en', true)) : '';

        $whyAttId = $pageId > 0 ? (int) get_post_meta($pageId, '_laico_svc_why_img_id', true) : 0;
        $whyUrl = $pageId > 0 ? trim((string) get_post_meta($pageId, '_laico_svc_why_img_url', true)) : '';
        $whyAlt = $pageId > 0 ? trim((string) get_post_meta($pageId, '_laico_svc_why_img_alt', true)) : '';

        $whyImage = self::resolveMediaImage(
            $whyAttId,
            $whyUrl,
            ViteAssets::assetUrl($whyDef['asset']),
            370,
            370,
            $whyAlt !== '' ? $whyAlt : $whyDef['alt']
        );

        return [
            'show_list' => $showList,
            'rows' => $rows,
            'show_why' => $showWhy,
            'show_why_image' => $showWhyImg,
            'why_title_fa' => $whyTitleFa !== '' ? $whyTitleFa : $whyDef['title_fa'],
            'why_title_en' => $whyTitleEn !== '' ? $whyTitleEn : $whyDef['title_en'],
            'why_body_1_fa' => $whyBody1Fa !== '' ? $whyBody1Fa : $whyDef['body_1_fa'],
            'why_body_1_en' => $whyBody1En !== '' ? $whyBody1En : $whyDef['body_1_en'],
            'why_body_2_fa' => $whyBody2Fa !== '' ? $whyBody2Fa : $whyDef['body_2_fa'],
            'why_body_2_en' => $whyBody2En !== '' ? $whyBody2En : $whyDef['body_2_en'],
            'why_image' => $whyImage,
        ];
    }

    /**
     * Format custom `about-lead` text, preserving `<strong>`, `<em>`, `<br>` and converting plain line breaks.
     */
    private static function formatLeadHtml(string $raw): string
    {
        $sanitized = wp_kses($raw, [
            'strong' => [],
            'b' => [],
            'em' => [],
            'i' => [],
            'br' => ['class' => []],
            'span' => ['class' => []],
        ]);

        if (! str_contains($sanitized, '<br')) {
            $sanitized = nl2br($sanitized, false);
        }

        return $sanitized;
    }

    /**
     * @return array{url: string, srcset: string, width: int, height: int, alt: string}
     */
    private static function resolveMediaImage(
        int $attachmentId,
        string $customUrl,
        string $defaultUrl,
        int $defaultWidth,
        int $defaultHeight,
        string $alt
    ): array {
        if ($attachmentId > 0) {
            $orig = Hero::resolveOriginalAttachment($attachmentId);
            if ($orig !== null && $orig['url'] !== '') {
                return [
                    'url' => $orig['url'],
                    'srcset' => '',
                    'width' => (int) ($orig['width'] ?: $defaultWidth),
                    'height' => (int) ($orig['height'] ?: $defaultHeight),
                    'alt' => $alt !== '' ? $alt : ($orig['alt'] !== '' ? $orig['alt'] : ''),
                ];
            }
        }

        if ($customUrl !== '') {
            return [
                'url' => $customUrl,
                'srcset' => '',
                'width' => $defaultWidth,
                'height' => $defaultHeight,
                'alt' => $alt,
            ];
        }

        return [
            'url' => $defaultUrl,
            'srcset' => '',
            'width' => $defaultWidth,
            'height' => $defaultHeight,
            'alt' => $alt,
        ];
    }

    public function renderAboutPageMetaBox(\WP_Post $post): void
    {
        wp_nonce_field('laico_save_about_meta', '_laico_about_nonce');

        $enEnabled = ThemeOptions::isEnglishEnabled();
        $id = (int) $post->ID;
        $hasMeta = static fn (string $key): bool => $id > 0 && metadata_exists('post', $id, $key);
        $boolVal = static fn (string $key, bool $def = true): bool => $hasMeta($key) ? get_post_meta($id, $key, true) === '1' : $def;

        $showLead = $boolVal('_laico_about_show_lead', true);
        $leadFa = $hasMeta('_laico_about_lead_fa')
            ? (string) get_post_meta($id, '_laico_about_lead_fa', true)
            : str_replace('<br class="about-br">', "\n", self::DEFAULT_ABOUT_LEAD_FA);
        $leadEn = $hasMeta('_laico_about_lead_en')
            ? (string) get_post_meta($id, '_laico_about_lead_en', true)
            : self::DEFAULT_ABOUT_LEAD_EN;

        $showCollage = $boolVal('_laico_about_show_collage', true);
        $showTeam = $boolVal('_laico_about_show_team', true);
        $showTeamImage = $boolVal('_laico_about_show_team_image', true);
        $teamTitleFa = (string) get_post_meta($id, '_laico_about_team_title_fa', true);
        $teamTitleEn = (string) get_post_meta($id, '_laico_about_team_title_en', true);
        if ($teamTitleFa === '') {
            $teamTitleFa = 'گروه معماران و طراحان';
        }
        if ($teamTitleEn === '') {
            $teamTitleEn = 'Architects and designers';
        }

        $teamImgId = (int) get_post_meta($id, '_laico_about_team_img_id', true);
        $teamImgUrl = trim((string) get_post_meta($id, '_laico_about_team_img_url', true));
        $teamImgAlt = (string) get_post_meta($id, '_laico_about_team_img_alt', true);
        $defaultTeamUrl = ViteAssets::assetUrl('images/about-team.webp');
        $teamPreviewUrl = $teamImgUrl !== '' ? $teamImgUrl : ($teamImgId > 0 ? (string) (wp_get_attachment_image_url($teamImgId, 'full') ?: $defaultTeamUrl) : $defaultTeamUrl);
        ?>
        <input type="hidden" name="laico_about_meta_present" value="1">

        <div class="laico-meta-grid" dir="rtl">
          <div class="laico-meta-banner">
            <strong>کنترل کامل بخش‌های صفحه درباره ما (<code>/about</code>):</strong>
            از این بخش می‌توانید متن معرفی <code>about-lead</code>، هر ۴ تصویر کلاژ <code>about-collage-stage</code> و تصویر بخش <code>about-team-inner</code> را مستقیماً تغییر دهید یا نمایش هر بخش را روشن/خاموش کنید.
          </div>

          <!-- 1. about-lead -->
          <div class="laico-about-admin-sec">
            <div class="laico-about-admin-head">
              <h3>۱. متن معرفی استودیو (<code>about-lead</code>)</h3>
              <label>
                <input type="checkbox" name="laico_about_show_lead" value="1" <?php checked($showLead); ?>>
                نمایش متن معرفی <code>about-lead</code> در صفحه
              </label>
            </div>
            <div class="laico-meta-field">
              <label for="laico_about_lead_fa"><strong>متن <code>about-lead</code> (فارسی — تگ <code>&lt;strong&gt;</code> برای بولد کردن مجاز است)</strong></label>
              <textarea id="laico_about_lead_fa" name="laico_about_lead_fa" rows="4" class="widefat"><?php echo esc_textarea($leadFa); ?></textarea>
              <p class="description">در صورت پاک کردن کامل متن و ذخیره، متن استاندارد پیش‌فرض قالب نمایش داده می‌شود.</p>
            </div>
            <?php if ($enEnabled) : ?>
              <div class="laico-meta-field" style="margin-top:10px;">
                <label for="laico_about_lead_en"><strong>متن <code>about-lead</code> (انگلیسی)</strong></label>
                <textarea id="laico_about_lead_en" name="laico_about_lead_en" rows="3" class="widefat" dir="ltr"><?php echo esc_textarea($leadEn); ?></textarea>
              </div>
            <?php endif; ?>
          </div>

          <!-- 2. about-collage-stage -->
          <div class="laico-about-admin-sec">
            <div class="laico-about-admin-head">
              <h3>۲. تصاویر ۴گانه کلاژ (<code>about-collage-stage</code>)</h3>
              <label>
                <input type="checkbox" name="laico_about_show_collage" value="1" <?php checked($showCollage); ?>>
                نمایش بخش کلاژ <code>about-collage-stage</code>
              </label>
            </div>
            <div class="laico-meta-grid laico-meta-grid--2col">
              <?php foreach (self::aboutCollageDefaults() as $idx => $def) :
                  $cShow = $boolVal("_laico_about_collage_{$idx}_show", true);
                  $cId = (int) get_post_meta($id, "_laico_about_collage_{$idx}_id", true);
                  $cUrl = trim((string) get_post_meta($id, "_laico_about_collage_{$idx}_url", true));
                  $cAlt = (string) get_post_meta($id, "_laico_about_collage_{$idx}_alt", true);
                  $defAssetUrl = ViteAssets::assetUrl($def['asset']);
                  $previewUrl = $cUrl !== '' ? $cUrl : ($cId > 0 ? (string) (wp_get_attachment_image_url($cId, 'full') ?: $defAssetUrl) : $defAssetUrl);
              ?>
                <div class="laico-media-card" data-media-card>
                  <div class="laico-media-card-top">
                    <strong><?php echo esc_html($def['label']); ?></strong>
                    <label>
                      <input type="checkbox" name="laico_about_collage_<?php echo (int) $idx; ?>_show" value="1" <?php checked($cShow); ?>>
                      فعال
                    </label>
                  </div>
                  <div class="laico-media-card-preview">
                    <img src="<?php echo esc_url($previewUrl); ?>" data-default-src="<?php echo esc_url($defAssetUrl); ?>" alt="">
                  </div>
                  <input type="hidden" name="laico_about_collage_<?php echo (int) $idx; ?>_id" value="<?php echo esc_attr($cId > 0 ? (string) $cId : ''); ?>" data-media-id>
                  <input type="url" name="laico_about_collage_<?php echo (int) $idx; ?>_url" value="<?php echo esc_attr($cUrl); ?>" class="widefat" dir="ltr" placeholder="پیش‌فرض قالب (یا انتخاب از کتابخانه رسانه)" data-media-url>
                  <input type="text" name="laico_about_collage_<?php echo (int) $idx; ?>_alt" value="<?php echo esc_attr($cAlt !== '' ? $cAlt : $def['alt']); ?>" class="widefat" placeholder="متن جایگزین (Alt)" style="margin-top:6px;" data-media-alt>
                  <div class="laico-media-card-actions">
                    <button type="button" class="button button-secondary laico-pick-media-card">انتخاب / تغییر تصویر</button>
                    <button type="button" class="button-link-delete laico-reset-media-card">بازنشانی به پیش‌فرض</button>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- 3. about-team-inner -->
          <div class="laico-about-admin-sec">
            <div class="laico-about-admin-head">
              <h3>۳. بخش و تصویر تیم (<code>about-team-inner</code>)</h3>
              <label>
                <input type="checkbox" name="laico_about_show_team" value="1" <?php checked($showTeam); ?>>
                نمایش بخش <code>about-team-inner</code>
              </label>
            </div>

            <div class="laico-meta-grid laico-meta-grid--2col" style="margin-bottom:12px;">
              <div class="laico-meta-field">
                <label for="laico_about_team_title_fa"><strong>عنوان بخش تیم (فارسی)</strong></label>
                <input type="text" id="laico_about_team_title_fa" name="laico_about_team_title_fa" value="<?php echo esc_attr($teamTitleFa); ?>" class="widefat">
              </div>
              <?php if ($enEnabled) : ?>
                <div class="laico-meta-field">
                  <label for="laico_about_team_title_en"><strong>عنوان بخش تیم (انگلیسی)</strong></label>
                  <input type="text" id="laico_about_team_title_en" name="laico_about_team_title_en" value="<?php echo esc_attr($teamTitleEn); ?>" class="widefat" dir="ltr">
                </div>
              <?php endif; ?>
            </div>

            <div class="laico-media-card" data-media-card>
              <div class="laico-media-card-top">
                <strong>تصویر اصلی داخل <code>about-team-inner</code></strong>
                <label>
                  <input type="checkbox" name="laico_about_show_team_image" value="1" <?php checked($showTeamImage); ?>>
                  نمایش تصویر تیم
                </label>
              </div>
              <div class="laico-media-card-preview laico-media-card-preview--wide">
                <img src="<?php echo esc_url($teamPreviewUrl); ?>" data-default-src="<?php echo esc_url($defaultTeamUrl); ?>" alt="">
              </div>
              <input type="hidden" name="laico_about_team_img_id" value="<?php echo esc_attr($teamImgId > 0 ? (string) $teamImgId : ''); ?>" data-media-id>
              <input type="url" name="laico_about_team_img_url" value="<?php echo esc_attr($teamImgUrl); ?>" class="widefat" dir="ltr" placeholder="پیش‌فرض قالب: about-team.webp (یا انتخاب از کتابخانه رسانه)" data-media-url>
              <input type="text" name="laico_about_team_img_alt" value="<?php echo esc_attr($teamImgAlt !== '' ? $teamImgAlt : 'استودیوی معماری و طراحی لایکو'); ?>" class="widefat" placeholder="متن جایگزین (Alt)" style="margin-top:6px;" data-media-alt>
              <div class="laico-media-card-actions">
                <button type="button" class="button button-secondary laico-pick-media-card">انتخاب / تغییر تصویر تیم</button>
                <button type="button" class="button-link-delete laico-reset-media-card">بازنشانی به پیش‌فرض</button>
              </div>
            </div>
          </div>
        </div>
        <?php
    }

    public function renderServicesPageMetaBox(\WP_Post $post): void
    {
        wp_nonce_field('laico_save_services_meta', '_laico_services_nonce');

        $id = (int) $post->ID;
        $enEnabled = ThemeOptions::isEnglishEnabled();

        $boolVal = static function (string $key, bool $default) use ($id): bool {
            $raw = get_post_meta($id, $key, true);
            if ($raw === '' || $raw === null) {
                return $default;
            }
            return $raw !== '0' && $raw !== 0 && $raw !== false;
        };

        $showList = $boolVal('_laico_svc_show_list', true);
        $defaults = self::servicesDefaultRows();
        $savedCustomized = get_post_meta($id, '_laico_svc_rows_customized', true) === '1';
        $rawRows = get_post_meta($id, '_laico_svc_rows', true);
        if ($savedCustomized && is_array($rawRows)) {
            $rows = array_values($rawRows);
        } elseif (is_array($rawRows) && ! empty($rawRows)) {
            $rows = array_values($rawRows);
        } else {
            $rows = $defaults;
        }

        // Load all `project_space` terms (`edit-tags.php?taxonomy=project_space&post_type=laico_project`)
        $spaceTerms = get_terms([
            'taxonomy' => ProjectPostType::TAX_SPACE,
            'hide_empty' => false,
        ]);
        $spaceOptions = [];
        if (is_array($spaceTerms)) {
            foreach ($spaceTerms as $t) {
                if ($t instanceof \WP_Term) {
                    $spaceOptions[$t->slug] = $t->name . ' (' . $t->slug . ')';
                }
            }
        }
        $manageSpacesUrl = admin_url('edit-tags.php?taxonomy=' . ProjectPostType::TAX_SPACE . '&post_type=' . ProjectPostType::POST_TYPE);

        $whyDef = self::servicesWhyDefaults();
        $showWhy = $boolVal('_laico_svc_show_why', true);
        $showWhyImg = $boolVal('_laico_svc_show_why_img', true);
        $whyTitleFa = (string) get_post_meta($id, '_laico_svc_why_title_fa', true) ?: $whyDef['title_fa'];
        $whyTitleEn = (string) get_post_meta($id, '_laico_svc_why_title_en', true) ?: $whyDef['title_en'];
        $whyBody1Fa = (string) get_post_meta($id, '_laico_svc_why_body_1_fa', true) ?: $whyDef['body_1_fa'];
        $whyBody1En = (string) get_post_meta($id, '_laico_svc_why_body_1_en', true) ?: $whyDef['body_1_en'];
        $whyBody2Fa = (string) get_post_meta($id, '_laico_svc_why_body_2_fa', true) ?: $whyDef['body_2_fa'];
        $whyBody2En = (string) get_post_meta($id, '_laico_svc_why_body_2_en', true) ?: $whyDef['body_2_en'];
        $whyImgId = (int) get_post_meta($id, '_laico_svc_why_img_id', true);
        $whyImgUrl = trim((string) get_post_meta($id, '_laico_svc_why_img_url', true));
        $whyImgAlt = (string) get_post_meta($id, '_laico_svc_why_img_alt', true) ?: $whyDef['alt'];
        $defaultWhyAssetUrl = ViteAssets::assetUrl($whyDef['asset']);
        $whyPreviewUrl = $whyImgUrl !== '' ? $whyImgUrl : ($whyImgId > 0 ? (string) (wp_get_attachment_image_url($whyImgId, 'full') ?: $defaultWhyAssetUrl) : $defaultWhyAssetUrl);

        $renderRowCard = static function (string $idxToken, array $row, array $spaceOptions, bool $enEnabled, string $manageSpacesUrl): void {
            $rShow = ! isset($row['show']) || ($row['show'] !== false && $row['show'] !== '0' && $row['show'] !== 0);
            $rTitleFa = (string) ($row['title_fa'] ?? '');
            $rTitleEn = (string) ($row['title_en'] ?? '');
            $rBodyFa = (string) ($row['body_fa'] ?? '');
            $rBodyEn = (string) ($row['body_en'] ?? '');
            $rSpace = (string) ($row['space_slug'] ?? '');
            $rBtnFa = (string) ($row['btn_fa'] ?? 'مشاهده پروژه‌ها');
            $rBtnEn = (string) ($row['btn_en'] ?? 'View projects');
            $rImgId = (int) ($row['img_id'] ?? 0);
            $rImgUrl = trim((string) ($row['img_url'] ?? ''));
            $rImgAlt = (string) ($row['img_alt'] ?? '');
            $defAsset = ! empty($row['default_asset']) ? (string) $row['default_asset'] : 'images/services-clinic.webp';
            $defAssetUrl = ViteAssets::assetUrl($defAsset);
            $previewUrl = $rImgUrl !== '' ? $rImgUrl : ($rImgId > 0 ? (string) (wp_get_attachment_image_url($rImgId, 'full') ?: $defAssetUrl) : $defAssetUrl);
            ?>
            <div class="laico-svc-admin-row" data-svc-row>
              <div class="laico-svc-admin-row-head">
                <div class="laico-svc-admin-row-title">
                  <span class="laico-svc-row-badge" data-svc-row-num>#</span>
                  <strong data-svc-row-heading><?php echo esc_html($rTitleFa !== '' ? $rTitleFa : 'ردیف خدمت جدید'); ?></strong>
                </div>
                <div class="laico-svc-admin-row-actions">
                  <label style="margin-left:10px;">
                    <input type="checkbox" name="laico_svc_rows[<?php echo esc_attr($idxToken); ?>][show]" value="1" <?php checked($rShow); ?>>
                    نمایش
                  </label>
                  <button type="button" class="button button-small" data-svc-move="up" title="انتقال به بالا">↑</button>
                  <button type="button" class="button button-small" data-svc-move="down" title="انتقال به پایین">↓</button>
                  <button type="button" class="button button-small button-link-delete" data-svc-remove>حذف ردیف ×</button>
                </div>
              </div>

              <input type="hidden" name="laico_svc_rows[<?php echo esc_attr($idxToken); ?>][default_asset]" value="<?php echo esc_attr($defAsset); ?>">

              <div class="laico-meta-grid laico-meta-grid--2col">
                <div class="laico-meta-field">
                  <label><strong>عنوان خدمت (فارسی)</strong></label>
                  <input type="text" name="laico_svc_rows[<?php echo esc_attr($idxToken); ?>][title_fa]" value="<?php echo esc_attr($rTitleFa); ?>" class="widefat" data-svc-title-input placeholder="مثلاً: طراحی داخلی تجاری و اداری">
                  <label style="margin-top:8px;display:block;"><strong>توضیح خدمت (فارسی)</strong></label>
                  <textarea name="laico_svc_rows[<?php echo esc_attr($idxToken); ?>][body_fa]" rows="3" class="widefat"><?php echo esc_textarea($rBodyFa); ?></textarea>

                  <?php if ($enEnabled) : ?>
                    <label style="margin-top:8px;display:block;"><strong>عنوان خدمت (انگلیسی)</strong></label>
                    <input type="text" name="laico_svc_rows[<?php echo esc_attr($idxToken); ?>][title_en]" value="<?php echo esc_attr($rTitleEn); ?>" class="widefat" dir="ltr">
                    <label style="margin-top:8px;display:block;"><strong>توضیح خدمت (انگلیسی)</strong></label>
                    <textarea name="laico_svc_rows[<?php echo esc_attr($idxToken); ?>][body_en]" rows="3" class="widefat" dir="ltr"><?php echo esc_textarea($rBodyEn); ?></textarea>
                  <?php endif; ?>

                  <label style="margin-top:10px;display:block;">
                    <strong>دسته‌بندی / فضای مقصد دکمه <code>svc-btn</code> در صفحه پروژه‌ها (<code>prj-spaces</code>)</strong>
                    <a href="<?php echo esc_url($manageSpacesUrl); ?>" target="_blank" style="font-weight:400;margin-right:6px;">مدیریت فضاها ↗</a>
                  </label>
                  <select name="laico_svc_rows[<?php echo esc_attr($idxToken); ?>][space_slug]" class="widefat">
                    <option value="" <?php selected($rSpace, ''); ?>>— همه فضاها (نمایش کل پروژه‌ها) —</option>
                    <?php foreach ($spaceOptions as $slug => $label) : ?>
                      <option value="<?php echo esc_attr($slug); ?>" <?php selected($rSpace, $slug); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                  </select>

                  <div class="laico-meta-grid laico-meta-grid--2col" style="margin-top:8px;">
                    <div class="laico-meta-field">
                      <label><strong>متن دکمه <code>svc-btn</code> (فارسی)</strong></label>
                      <input type="text" name="laico_svc_rows[<?php echo esc_attr($idxToken); ?>][btn_fa]" value="<?php echo esc_attr($rBtnFa); ?>" class="widefat">
                    </div>
                    <?php if ($enEnabled) : ?>
                      <div class="laico-meta-field">
                        <label><strong>متن دکمه (انگلیسی)</strong></label>
                        <input type="text" name="laico_svc_rows[<?php echo esc_attr($idxToken); ?>][btn_en]" value="<?php echo esc_attr($rBtnEn); ?>" class="widefat" dir="ltr">
                      </div>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="laico-media-card" data-media-card>
                  <div class="laico-media-card-top">
                    <strong>تصویر خدمت (<code>svc-media</code>)</strong>
                  </div>
                  <div class="laico-media-card-preview">
                    <img src="<?php echo esc_url($previewUrl); ?>" data-default-src="<?php echo esc_url($defAssetUrl); ?>" alt="">
                  </div>
                  <input type="hidden" name="laico_svc_rows[<?php echo esc_attr($idxToken); ?>][img_id]" value="<?php echo esc_attr($rImgId > 0 ? (string) $rImgId : ''); ?>" data-media-id>
                  <input type="url" name="laico_svc_rows[<?php echo esc_attr($idxToken); ?>][img_url]" value="<?php echo esc_attr($rImgUrl); ?>" class="widefat" dir="ltr" placeholder="انتخاب تصویر از کتابخانه رسانه یا ورود لینک" data-media-url>
                  <input type="text" name="laico_svc_rows[<?php echo esc_attr($idxToken); ?>][img_alt]" value="<?php echo esc_attr($rImgAlt); ?>" class="widefat" placeholder="متن جایگزین تصویر (Alt)" style="margin-top:6px;" data-media-alt>
                  <div class="laico-media-card-actions">
                    <button type="button" class="button button-secondary laico-pick-media-card">انتخاب / تغییر تصویر</button>
                    <button type="button" class="button-link-delete laico-reset-media-card">بازنشانی تصویر</button>
                  </div>
                </div>
              </div>
            </div>
            <?php
        };
        ?>
        <input type="hidden" name="laico_services_meta_present" value="1">

        <div class="laico-meta-grid" dir="rtl">
          <div class="laico-meta-banner">
            <strong>کنترل کامل صفحه خدمات (<code>/services</code>):</strong>
            در این بخش می‌توانید آیتم‌های <code>svc-row</code> داخل <code>svc-list</code> را ویرایش، کم یا زیاد و جابه‌جا کنید، مقصد دکمهٔ «مشاهده پروژه‌ها» هر خدمت را به فضای متناظر در صفحهٔ پروژه‌ها (<code>prj-spaces</code>) متصل نمایید، و محتوای بخش <code>svc-why</code> را تغییر دهید.
          </div>

          <!-- 1. svc-list & repeatable svc-row -->
          <div class="laico-about-admin-sec">
            <div class="laico-about-admin-head">
              <h3>۱. لیست خدمات و آیتم‌های <code>svc-row</code> (<code>svc-list</code>)</h3>
              <label>
                <input type="checkbox" name="laico_svc_show_list" value="1" <?php checked($showList); ?>>
                نمایش بخش لیست خدمات (<code>svc-list</code>) در صفحه
              </label>
            </div>

            <div class="laico-svc-rows-wrap" id="laico-svc-rows-wrap" data-next-index="<?php echo (int) count($rows); ?>">
              <?php foreach ($rows as $idx => $row) : ?>
                <?php $renderRowCard((string) $idx, is_array($row) ? $row : [], $spaceOptions, $enEnabled, $manageSpacesUrl); ?>
              <?php endforeach; ?>
            </div>

            <div style="margin-top:12px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
              <button type="button" class="button button-primary" id="laico-add-svc-row">+ افزودن ردیف خدمت جدید (<code>svc-row</code>)</button>
              <span class="description">می‌توانید به تعداد دلخواه ردیف خدمت اضافه کنید، ترتیب آن‌ها را با دکمه‌های ↑ ↓ تغییر دهید یا هر ردیف را حذف کنید.</span>
            </div>

            <template id="laico-svc-row-template">
              <?php
              $renderRowCard(
                  '__INDEX__',
                  [
                      'show' => true,
                      'title_fa' => '',
                      'title_en' => '',
                      'body_fa' => '',
                      'body_en' => '',
                      'space_slug' => '',
                      'btn_fa' => 'مشاهده پروژه‌ها',
                      'btn_en' => 'View projects',
                      'img_id' => 0,
                      'img_url' => '',
                      'img_alt' => '',
                      'default_asset' => 'images/services-clinic.webp',
                  ],
                  $spaceOptions,
                  $enEnabled,
                  $manageSpacesUrl
              );
              ?>
            </template>
          </div>

          <!-- 2. svc-why -->
          <div class="laico-about-admin-sec">
            <div class="laico-about-admin-head">
              <h3>۲. بخش «چرا انتخاب مجموعه‌ی لایکودیزاین؟» (<code>svc-why</code>)</h3>
              <label>
                <input type="checkbox" name="laico_svc_show_why" value="1" <?php checked($showWhy); ?>>
                نمایش بخش <code>svc-why</code> در صفحه
              </label>
            </div>

            <div class="laico-meta-grid laico-meta-grid--2col" style="margin-bottom:12px;">
              <div class="laico-meta-field">
                <label><strong>عنوان بخش <code>svc-why</code> (فارسی)</strong></label>
                <input type="text" name="laico_svc_why_title_fa" value="<?php echo esc_attr($whyTitleFa); ?>" class="widefat">
                <label style="margin-top:8px;display:block;"><strong>پاراگراف اول (فارسی)</strong></label>
                <textarea name="laico_svc_why_body_1_fa" rows="4" class="widefat"><?php echo esc_textarea($whyBody1Fa); ?></textarea>
                <label style="margin-top:8px;display:block;"><strong>پاراگراف دوم (فارسی)</strong></label>
                <textarea name="laico_svc_why_body_2_fa" rows="4" class="widefat"><?php echo esc_textarea($whyBody2Fa); ?></textarea>

                <?php if ($enEnabled) : ?>
                  <label style="margin-top:10px;display:block;"><strong>عنوان بخش <code>svc-why</code> (انگلیسی)</strong></label>
                  <input type="text" name="laico_svc_why_title_en" value="<?php echo esc_attr($whyTitleEn); ?>" class="widefat" dir="ltr">
                  <label style="margin-top:8px;display:block;"><strong>پاراگراف اول (انگلیسی)</strong></label>
                  <textarea name="laico_svc_why_body_1_en" rows="3" class="widefat" dir="ltr"><?php echo esc_textarea($whyBody1En); ?></textarea>
                  <label style="margin-top:8px;display:block;"><strong>پاراگراف دوم (انگلیسی)</strong></label>
                  <textarea name="laico_svc_why_body_2_en" rows="3" class="widefat" dir="ltr"><?php echo esc_textarea($whyBody2En); ?></textarea>
                <?php endif; ?>
              </div>

              <div class="laico-media-card" data-media-card>
                <div class="laico-media-card-top">
                  <strong>تصویر بخش <code>svc-why</code></strong>
                  <label>
                    <input type="checkbox" name="laico_svc_show_why_img" value="1" <?php checked($showWhyImg); ?>>
                    نمایش تصویر
                  </label>
                </div>
                <div class="laico-media-card-preview">
                  <img src="<?php echo esc_url($whyPreviewUrl); ?>" data-default-src="<?php echo esc_url($defaultWhyAssetUrl); ?>" alt="">
                </div>
                <input type="hidden" name="laico_svc_why_img_id" value="<?php echo esc_attr($whyImgId > 0 ? (string) $whyImgId : ''); ?>" data-media-id>
                <input type="url" name="laico_svc_why_img_url" value="<?php echo esc_attr($whyImgUrl); ?>" class="widefat" dir="ltr" placeholder="پیش‌فرض قالب: services-why.webp" data-media-url>
                <input type="text" name="laico_svc_why_img_alt" value="<?php echo esc_attr($whyImgAlt); ?>" class="widefat" placeholder="متن جایگزین تصویر (Alt)" style="margin-top:6px;" data-media-alt>
                <div class="laico-media-card-actions">
                  <button type="button" class="button button-secondary laico-pick-media-card">انتخاب / تغییر تصویر</button>
                  <button type="button" class="button-link-delete laico-reset-media-card">بازنشانی به پیش‌فرض</button>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php
    }

    public function renderPageHeroMetaBox(\WP_Post $post): void
    {
        wp_nonce_field('laico_save_page_meta', '_laico_page_nonce');

        $enEnabled = ThemeOptions::isEnglishEnabled();
        $kickerFa = (string) get_post_meta($post->ID, '_laico_hero_kicker_fa', true);
        $kickerEn = (string) get_post_meta($post->ID, '_laico_hero_kicker_en', true);
        $titleFa = (string) get_post_meta($post->ID, '_laico_hero_title_fa', true);
        $titleEn = (string) get_post_meta($post->ID, '_laico_hero_title_en', true);
        $ledeFa = (string) get_post_meta($post->ID, '_laico_hero_lede_fa', true);
        $ledeEn = (string) get_post_meta($post->ID, '_laico_hero_lede_en', true);
        $contentEn = (string) get_post_meta($post->ID, '_laico_content_en', true);
        ?>
        <div class="laico-meta-grid laico-meta-grid--2col" dir="rtl">
          <div class="laico-meta-banner" style="grid-column: 1 / -1;">
            <strong>نکتهٔ مهم تصویر هیرو:</strong> در برگه‌های <em>صفحه اصلی، خدمات، پروژه‌ها، مجله، درباره ما و تماس با ما</em> (و سایر برگه‌های داخلی)، تصویر تمام‌صفحهٔ بالای صفحه مستقیماً از <strong>«تصویر شاخص برگه» (Featured Image)</strong> در ستون کناری خوانده می‌شود. فیلدهای متنی زیر اختیاری هستند و در صورت خالی بودن از مقادیر استاندارد هر بخش استفاده می‌شود.
          </div>

          <div class="laico-meta-field">
            <label for="laico_hero_kicker_fa"><strong>کیکر بالای عنوان هیرو (فارسی)</strong></label>
            <input type="text" id="laico_hero_kicker_fa" name="laico_hero_kicker_fa" value="<?php echo esc_attr($kickerFa); ?>" class="widefat" placeholder="مثلاً: خدمات">
          </div>
          <?php if ($enEnabled) : ?>
            <div class="laico-meta-field">
              <label for="laico_hero_kicker_en"><strong>کیکر بالای عنوان هیرو (انگلیسی)</strong></label>
              <input type="text" id="laico_hero_kicker_en" name="laico_hero_kicker_en" value="<?php echo esc_attr($kickerEn); ?>" class="widefat" dir="ltr" placeholder="e.g. Services">
            </div>
          <?php endif; ?>

          <div class="laico-meta-field">
            <label for="laico_hero_title_fa"><strong>عنوان H1 هیرو (فارسی — اختیاری)</strong></label>
            <input type="text" id="laico_hero_title_fa" name="laico_hero_title_fa" value="<?php echo esc_attr($titleFa); ?>" class="widefat" placeholder="پیش‌فرض: عنوان برگه">
          </div>
          <?php if ($enEnabled) : ?>
            <div class="laico-meta-field">
              <label for="laico_hero_title_en"><strong>عنوان H1 هیرو (انگلیسی — اختیاری)</strong></label>
              <input type="text" id="laico_hero_title_en" name="laico_hero_title_en" value="<?php echo esc_attr($titleEn); ?>" class="widefat" dir="ltr" placeholder="English H1">
            </div>
          <?php endif; ?>

          <div class="laico-meta-field">
            <label for="laico_hero_lede_fa"><strong>زیرعنوان / توضیح هیرو (فارسی)</strong></label>
            <textarea id="laico_hero_lede_fa" name="laico_hero_lede_fa" rows="2" class="widefat"><?php echo esc_textarea($ledeFa); ?></textarea>
          </div>
          <?php if ($enEnabled) : ?>
            <div class="laico-meta-field">
              <label for="laico_hero_lede_en"><strong>زیرعنوان / توضیح هیرو (انگلیسی)</strong></label>
              <textarea id="laico_hero_lede_en" name="laico_hero_lede_en" rows="2" class="widefat" dir="ltr"><?php echo esc_textarea($ledeEn); ?></textarea>
            </div>

            <div class="laico-meta-field" style="grid-column: 1 / -1;">
              <label for="laico_page_content_en"><strong>محتوای انگلیسی برگه (اختیاری — برای برگه‌های متنی عمومی مثل حریم خصوصی / کوکی‌ها)</strong></label>
              <textarea id="laico_page_content_en" name="laico_content_en" rows="4" class="widefat" dir="ltr"><?php echo esc_textarea($contentEn); ?></textarea>
            </div>
          <?php endif; ?>
        </div>
        <?php
    }

    public function renderPostJournalMetaBox(\WP_Post $post): void
    {
        wp_nonce_field('laico_save_post_meta', '_laico_post_nonce');

        $enEnabled = ThemeOptions::isEnglishEnabled();
        $readTime = (string) get_post_meta($post->ID, '_laico_read_time', true);
        $titleEn = (string) get_post_meta($post->ID, '_laico_title_en', true);
        $excerptEn = (string) get_post_meta($post->ID, '_laico_excerpt_en', true);
        $contentEn = (string) get_post_meta($post->ID, '_laico_content_en', true);
        ?>
        <div class="laico-meta-grid" dir="rtl">
          <div class="laico-meta-field">
            <label for="laico_read_time"><strong>زمان مطالعه به دقیقه (اختیاری — در صورت خالی بودن خودکار از طول متن محاسبه می‌شود)</strong></label>
            <input type="number" id="laico_read_time" name="laico_read_time" min="1" max="60" value="<?php echo esc_attr($readTime); ?>" style="width:120px;" dir="ltr" placeholder="4">
          </div>
          <?php if ($enEnabled) : ?>
            <div class="laico-meta-field">
              <label for="laico_post_title_en"><strong>عنوان انگلیسی مقاله (اختیاری)</strong></label>
              <input type="text" id="laico_post_title_en" name="laico_post_title_en" value="<?php echo esc_attr($titleEn); ?>" class="widefat" dir="ltr" placeholder="English article title">
            </div>
            <div class="laico-meta-field">
              <label for="laico_post_excerpt_en"><strong>چکیدهٔ انگلیسی مقاله (اختیاری)</strong></label>
              <textarea id="laico_post_excerpt_en" name="laico_excerpt_en" rows="2" class="widefat" dir="ltr"><?php echo esc_textarea($excerptEn); ?></textarea>
            </div>
            <div class="laico-meta-field">
              <label for="laico_post_content_en"><strong>متن انگلیسی مقاله (HTML / پاراگراف‌ها — اختیاری)</strong></label>
              <textarea id="laico_post_content_en" name="laico_content_en" rows="5" class="widefat" dir="ltr"><?php echo esc_textarea($contentEn); ?></textarea>
            </div>
          <?php endif; ?>
        </div>
        <?php
    }

    public function savePageMeta(int $postId, \WP_Post $post): void
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (! current_user_can('edit_post', $postId)) {
            return;
        }

        // 1. Save Hero & Bilingual Page Meta
        if (isset($_POST['_laico_page_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['_laico_page_nonce'])), 'laico_save_page_meta')) {
            update_post_meta($postId, '_laico_hero_kicker_fa', isset($_POST['laico_hero_kicker_fa']) ? sanitize_text_field(wp_unslash((string) $_POST['laico_hero_kicker_fa'])) : '');
            update_post_meta($postId, '_laico_hero_title_fa', isset($_POST['laico_hero_title_fa']) ? sanitize_text_field(wp_unslash((string) $_POST['laico_hero_title_fa'])) : '');
            update_post_meta($postId, '_laico_hero_lede_fa', isset($_POST['laico_hero_lede_fa']) ? sanitize_textarea_field(wp_unslash((string) $_POST['laico_hero_lede_fa'])) : '');

            if (array_key_exists('laico_hero_kicker_en', $_POST)) {
                update_post_meta($postId, '_laico_hero_kicker_en', sanitize_text_field(wp_unslash((string) $_POST['laico_hero_kicker_en'])));
            }
            if (array_key_exists('laico_hero_title_en', $_POST)) {
                update_post_meta($postId, '_laico_hero_title_en', sanitize_text_field(wp_unslash((string) $_POST['laico_hero_title_en'])));
            }
            if (array_key_exists('laico_hero_lede_en', $_POST)) {
                update_post_meta($postId, '_laico_hero_lede_en', sanitize_textarea_field(wp_unslash((string) $_POST['laico_hero_lede_en'])));
            }
            if (array_key_exists('laico_content_en', $_POST)) {
                update_post_meta($postId, '_laico_content_en', wp_kses_post((string) wp_unslash($_POST['laico_content_en'])));
            }
        }

        // 2. Save About Page Meta (`about-lead`, `about-collage-stage`, `about-team-inner`)
        if (
            ! empty($_POST['laico_about_meta_present'])
            && isset($_POST['_laico_about_nonce'])
            && wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['_laico_about_nonce'])), 'laico_save_about_meta')
        ) {
            update_post_meta($postId, '_laico_about_show_lead', ! empty($_POST['laico_about_show_lead']) ? '1' : '0');
            update_post_meta($postId, '_laico_about_show_collage', ! empty($_POST['laico_about_show_collage']) ? '1' : '0');
            update_post_meta($postId, '_laico_about_show_team', ! empty($_POST['laico_about_show_team']) ? '1' : '0');
            update_post_meta($postId, '_laico_about_show_team_image', ! empty($_POST['laico_about_show_team_image']) ? '1' : '0');

            $allowedLeadTags = [
                'strong' => [],
                'b' => [],
                'em' => [],
                'i' => [],
                'br' => ['class' => []],
                'span' => ['class' => []],
            ];

            if (array_key_exists('laico_about_lead_fa', $_POST)) {
                $leadFa = wp_kses((string) wp_unslash($_POST['laico_about_lead_fa']), $allowedLeadTags);
                update_post_meta($postId, '_laico_about_lead_fa', trim($leadFa));
            }
            if (array_key_exists('laico_about_lead_en', $_POST)) {
                $leadEn = wp_kses((string) wp_unslash($_POST['laico_about_lead_en']), $allowedLeadTags);
                update_post_meta($postId, '_laico_about_lead_en', trim($leadEn));
            }

            for ($i = 1; $i <= 4; $i++) {
                update_post_meta($postId, "_laico_about_collage_{$i}_show", ! empty($_POST["laico_about_collage_{$i}_show"]) ? '1' : '0');

                $attId = isset($_POST["laico_about_collage_{$i}_id"]) ? max(0, (int) $_POST["laico_about_collage_{$i}_id"]) : 0;
                $url = isset($_POST["laico_about_collage_{$i}_url"]) ? esc_url_raw(trim((string) wp_unslash($_POST["laico_about_collage_{$i}_url"]))) : '';
                $alt = isset($_POST["laico_about_collage_{$i}_alt"]) ? sanitize_text_field(wp_unslash((string) $_POST["laico_about_collage_{$i}_alt"])) : '';

                update_post_meta($postId, "_laico_about_collage_{$i}_id", $attId > 0 ? (string) $attId : '');
                update_post_meta($postId, "_laico_about_collage_{$i}_url", $url);
                update_post_meta($postId, "_laico_about_collage_{$i}_alt", $alt);
            }

            if (array_key_exists('laico_about_team_title_fa', $_POST)) {
                update_post_meta($postId, '_laico_about_team_title_fa', sanitize_text_field(wp_unslash((string) $_POST['laico_about_team_title_fa'])));
            }
            if (array_key_exists('laico_about_team_title_en', $_POST)) {
                update_post_meta($postId, '_laico_about_team_title_en', sanitize_text_field(wp_unslash((string) $_POST['laico_about_team_title_en'])));
            }

            $teamImgId = isset($_POST['laico_about_team_img_id']) ? max(0, (int) $_POST['laico_about_team_img_id']) : 0;
            $teamImgUrl = isset($_POST['laico_about_team_img_url']) ? esc_url_raw(trim((string) wp_unslash($_POST['laico_about_team_img_url']))) : '';
            $teamImgAlt = isset($_POST['laico_about_team_img_alt']) ? sanitize_text_field(wp_unslash((string) $_POST['laico_about_team_img_alt'])) : '';

            update_post_meta($postId, '_laico_about_team_img_id', $teamImgId > 0 ? (string) $teamImgId : '');
            update_post_meta($postId, '_laico_about_team_img_url', $teamImgUrl);
            update_post_meta($postId, '_laico_about_team_img_alt', $teamImgAlt);
        }

        // 3. Save Services Page Meta (`svc-list` repeatable rows & `svc-why`)
        if (
            ! empty($_POST['laico_services_meta_present'])
            && isset($_POST['_laico_services_nonce'])
            && wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['_laico_services_nonce'])), 'laico_save_services_meta')
        ) {
            update_post_meta($postId, '_laico_svc_show_list', ! empty($_POST['laico_svc_show_list']) ? '1' : '0');
            update_post_meta($postId, '_laico_svc_show_why', ! empty($_POST['laico_svc_show_why']) ? '1' : '0');
            update_post_meta($postId, '_laico_svc_show_why_img', ! empty($_POST['laico_svc_show_why_img']) ? '1' : '0');

            $cleanRows = [];
            if (isset($_POST['laico_svc_rows']) && is_array($_POST['laico_svc_rows'])) {
                $postedRows = wp_unslash($_POST['laico_svc_rows']);
                foreach ($postedRows as $rawRow) {
                    if (! is_array($rawRow)) {
                        continue;
                    }
                    $titleFa = sanitize_text_field((string) ($rawRow['title_fa'] ?? ''));
                    $bodyFa = sanitize_textarea_field((string) ($rawRow['body_fa'] ?? ''));
                    if ($titleFa === '' && $bodyFa === '') {
                        continue;
                    }
                    $cleanRows[] = [
                        'show' => ! empty($rawRow['show']),
                        'title_fa' => $titleFa,
                        'title_en' => sanitize_text_field((string) ($rawRow['title_en'] ?? '')),
                        'body_fa' => $bodyFa,
                        'body_en' => sanitize_textarea_field((string) ($rawRow['body_en'] ?? '')),
                        'space_slug' => sanitize_title((string) ($rawRow['space_slug'] ?? '')),
                        'btn_fa' => sanitize_text_field((string) ($rawRow['btn_fa'] ?? 'مشاهده پروژه‌ها')),
                        'btn_en' => sanitize_text_field((string) ($rawRow['btn_en'] ?? 'View projects')),
                        'img_id' => max(0, (int) ($rawRow['img_id'] ?? 0)),
                        'img_url' => esc_url_raw(trim((string) ($rawRow['img_url'] ?? ''))),
                        'img_alt' => sanitize_text_field((string) ($rawRow['img_alt'] ?? '')),
                        'default_asset' => sanitize_text_field((string) ($rawRow['default_asset'] ?? 'images/services-clinic.webp')),
                    ];
                }
            }

            update_post_meta($postId, '_laico_svc_rows_customized', '1');
            update_post_meta($postId, '_laico_svc_rows', $cleanRows);

            $whyTextKeys = [
                'laico_svc_why_title_fa' => '_laico_svc_why_title_fa',
                'laico_svc_why_title_en' => '_laico_svc_why_title_en',
            ];
            foreach ($whyTextKeys as $postKey => $metaKey) {
                if (array_key_exists($postKey, $_POST)) {
                    update_post_meta($postId, $metaKey, sanitize_text_field(wp_unslash((string) $_POST[$postKey])));
                }
            }

            $whyAreaKeys = [
                'laico_svc_why_body_1_fa' => '_laico_svc_why_body_1_fa',
                'laico_svc_why_body_1_en' => '_laico_svc_why_body_1_en',
                'laico_svc_why_body_2_fa' => '_laico_svc_why_body_2_fa',
                'laico_svc_why_body_2_en' => '_laico_svc_why_body_2_en',
            ];
            foreach ($whyAreaKeys as $postKey => $metaKey) {
                if (array_key_exists($postKey, $_POST)) {
                    update_post_meta($postId, $metaKey, sanitize_textarea_field(wp_unslash((string) $_POST[$postKey])));
                }
            }

            $whyImgId = isset($_POST['laico_svc_why_img_id']) ? max(0, (int) $_POST['laico_svc_why_img_id']) : 0;
            $whyImgUrl = isset($_POST['laico_svc_why_img_url']) ? esc_url_raw(trim((string) wp_unslash($_POST['laico_svc_why_img_url']))) : '';
            $whyImgAlt = isset($_POST['laico_svc_why_img_alt']) ? sanitize_text_field(wp_unslash((string) $_POST['laico_svc_why_img_alt'])) : '';

            update_post_meta($postId, '_laico_svc_why_img_id', $whyImgId > 0 ? (string) $whyImgId : '');
            update_post_meta($postId, '_laico_svc_why_img_url', $whyImgUrl);
            update_post_meta($postId, '_laico_svc_why_img_alt', $whyImgAlt);
        }
    }

    public function savePostMeta(int $postId, \WP_Post $post): void
    {
        if (! isset($_POST['_laico_post_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['_laico_post_nonce'])), 'laico_save_post_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (! current_user_can('edit_post', $postId)) {
            return;
        }

        $read = isset($_POST['laico_read_time']) ? max(0, (int) $_POST['laico_read_time']) : 0;
        update_post_meta($postId, '_laico_read_time', $read > 0 ? (string) $read : '');

        if (array_key_exists('laico_post_title_en', $_POST)) {
            update_post_meta($postId, '_laico_title_en', sanitize_text_field(wp_unslash((string) $_POST['laico_post_title_en'])));
        } elseif (array_key_exists('laico_title_en', $_POST)) {
            update_post_meta($postId, '_laico_title_en', sanitize_text_field(wp_unslash((string) $_POST['laico_title_en'])));
        }
        if (array_key_exists('laico_excerpt_en', $_POST)) {
            update_post_meta($postId, '_laico_excerpt_en', sanitize_textarea_field(wp_unslash((string) $_POST['laico_excerpt_en'])));
        }
        if (array_key_exists('laico_content_en', $_POST)) {
            update_post_meta($postId, '_laico_content_en', wp_kses_post((string) wp_unslash($_POST['laico_content_en'])));
        }
    }
}
