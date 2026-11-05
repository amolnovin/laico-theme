<?php

declare(strict_types=1);

namespace App\PostTypes;

use App\Services\ThemeOptions;
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
                'asset' => 'images/about-k-sink.jpg',
                'alt' => 'سینک و کابینت سرمه‌ای',
                'width' => 476,
                'height' => 336,
            ],
            2 => [
                'class' => 'c-run',
                'label' => 'تصویر ۲ کلاژ — راست بالا عریض (c-run)',
                'asset' => 'images/about-k-run.jpg',
                'alt' => 'کابینت سرمه‌ای با صفحه سنگ',
                'width' => 748,
                'height' => 394,
            ],
            3 => [
                'class' => 'c-base',
                'label' => 'تصویر ۳ کلاژ — پایین وسط/چپ (c-base)',
                'asset' => 'images/about-k-base.jpg',
                'alt' => 'جزئیات کشوها و دستگیره‌ها',
                'width' => 654,
                'height' => 548,
            ],
            4 => [
                'class' => 'c-glass',
                'label' => 'تصویر ۴ کلاژ — راست پایین (c-glass)',
                'asset' => 'images/about-k-glass.jpg',
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
            ViteAssets::assetUrl('images/about-team.jpg'),
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
            $img = wp_get_attachment_image_src($attachmentId, 'full') ?: wp_get_attachment_image_src($attachmentId, 'large');
            if (is_array($img) && ! empty($img[0])) {
                $wpAlt = trim((string) get_post_meta($attachmentId, '_wp_attachment_image_alt', true));
                return [
                    'url' => (string) $img[0],
                    'srcset' => (string) (wp_get_attachment_image_srcset($attachmentId, 'large') ?: ''),
                    'width' => (int) ($img[1] ?: $defaultWidth),
                    'height' => (int) ($img[2] ?: $defaultHeight),
                    'alt' => $alt !== '' ? $alt : ($wpAlt !== '' ? $wpAlt : ''),
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
        $defaultTeamUrl = ViteAssets::assetUrl('images/about-team.jpg');
        $teamPreviewUrl = $teamImgUrl !== '' ? $teamImgUrl : ($teamImgId > 0 ? (string) (wp_get_attachment_image_url($teamImgId, 'large') ?: $defaultTeamUrl) : $defaultTeamUrl);
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
                  $previewUrl = $cUrl !== '' ? $cUrl : ($cId > 0 ? (string) (wp_get_attachment_image_url($cId, 'medium_large') ?: $defAssetUrl) : $defAssetUrl);
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
              <input type="url" name="laico_about_team_img_url" value="<?php echo esc_attr($teamImgUrl); ?>" class="widefat" dir="ltr" placeholder="پیش‌فرض قالب: about-team.jpg (یا انتخاب از کتابخانه رسانه)" data-media-url>
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
