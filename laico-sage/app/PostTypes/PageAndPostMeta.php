<?php

declare(strict_types=1);

namespace App\PostTypes;

/**
 * Registers Hero & Bilingual Meta Boxes on WordPress Pages (`page`)
 * and Journal Meta Boxes (Reading Time + English fields) on Posts (`post`).
 */
final class PageAndPostMeta
{
    public function register(): void
    {
        add_action('add_meta_boxes', [$this, 'registerMetaBoxes']);
        add_action('save_post_page', [$this, 'savePageMeta'], 10, 2);
        add_action('save_post_post', [$this, 'savePostMeta'], 10, 2);
    }

    public function registerMetaBoxes(): void
    {
        add_meta_box(
            'laico_page_hero_meta',
            'تنظیمات بخش هیرو (Hero) و دوزبانگی برگه',
            [$this, 'renderPageHeroMetaBox'],
            'page',
            'normal',
            'high'
        );

        add_meta_box(
            'laico_post_journal_meta',
            'تنظیمات تکمیلی مجله (زمان مطالعه و نسخهٔ انگلیسی)',
            [$this, 'renderPostJournalMetaBox'],
            'post',
            'normal',
            'default'
        );
    }

    public function renderPageHeroMetaBox(\WP_Post $post): void
    {
        wp_nonce_field('laico_save_page_meta', '_laico_page_nonce');

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
          <div class="laico-meta-field">
            <label for="laico_hero_kicker_en"><strong>کیکر بالای عنوان هیرو (انگلیسی)</strong></label>
            <input type="text" id="laico_hero_kicker_en" name="laico_hero_kicker_en" value="<?php echo esc_attr($kickerEn); ?>" class="widefat" dir="ltr" placeholder="e.g. Services">
          </div>

          <div class="laico-meta-field">
            <label for="laico_hero_title_fa"><strong>عنوان H1 هیرو (فارسی — اختیاری)</strong></label>
            <input type="text" id="laico_hero_title_fa" name="laico_hero_title_fa" value="<?php echo esc_attr($titleFa); ?>" class="widefat" placeholder="پیش‌فرض: عنوان برگه">
          </div>
          <div class="laico-meta-field">
            <label for="laico_hero_title_en"><strong>عنوان H1 هیرو (انگلیسی — اختیاری)</strong></label>
            <input type="text" id="laico_hero_title_en" name="laico_hero_title_en" value="<?php echo esc_attr($titleEn); ?>" class="widefat" dir="ltr" placeholder="English H1">
          </div>

          <div class="laico-meta-field">
            <label for="laico_hero_lede_fa"><strong>زیرعنوان / توضیح هیرو (فارسی)</strong></label>
            <textarea id="laico_hero_lede_fa" name="laico_hero_lede_fa" rows="2" class="widefat"><?php echo esc_textarea($ledeFa); ?></textarea>
          </div>
          <div class="laico-meta-field">
            <label for="laico_hero_lede_en"><strong>زیرعنوان / توضیح هیرو (انگلیسی)</strong></label>
            <textarea id="laico_hero_lede_en" name="laico_hero_lede_en" rows="2" class="widefat" dir="ltr"><?php echo esc_textarea($ledeEn); ?></textarea>
          </div>

          <div class="laico-meta-field" style="grid-column: 1 / -1;">
            <label for="laico_page_content_en"><strong>محتوای انگلیسی برگه (اختیاری — برای برگه‌های متنی عمومی مثل حریم خصوصی / کوکی‌ها)</strong></label>
            <textarea id="laico_page_content_en" name="laico_content_en" rows="4" class="widefat" dir="ltr"><?php echo esc_textarea($contentEn); ?></textarea>
          </div>
        </div>
        <?php
    }

    public function renderPostJournalMetaBox(\WP_Post $post): void
    {
        wp_nonce_field('laico_save_post_meta', '_laico_post_nonce');

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
          <div class="laico-meta-field">
            <label for="laico_post_title_en"><strong>عنوان انگلیسی مقاله (اختیاری)</strong></label>
            <input type="text" id="laico_post_title_en" name="laico_title_en" value="<?php echo esc_attr($titleEn); ?>" class="widefat" dir="ltr" placeholder="English article title">
          </div>
          <div class="laico-meta-field">
            <label for="laico_post_excerpt_en"><strong>چکیدهٔ انگلیسی مقاله (اختیاری)</strong></label>
            <textarea id="laico_post_excerpt_en" name="laico_excerpt_en" rows="2" class="widefat" dir="ltr"><?php echo esc_textarea($excerptEn); ?></textarea>
          </div>
          <div class="laico-meta-field">
            <label for="laico_post_content_en"><strong>متن انگلیسی مقاله (HTML / پاراگراف‌ها — اختیاری)</strong></label>
            <textarea id="laico_post_content_en" name="laico_content_en" rows="5" class="widefat" dir="ltr"><?php echo esc_textarea($contentEn); ?></textarea>
          </div>
        </div>
        <?php
    }

    public function savePageMeta(int $postId, \WP_Post $post): void
    {
        if (! isset($_POST['_laico_page_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['_laico_page_nonce'])), 'laico_save_page_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (! current_user_can('edit_post', $postId)) {
            return;
        }

        $fields = [
            'laico_hero_kicker_fa' => '_laico_hero_kicker_fa',
            'laico_hero_kicker_en' => '_laico_hero_kicker_en',
            'laico_hero_title_fa' => '_laico_hero_title_fa',
            'laico_hero_title_en' => '_laico_hero_title_en',
        ];
        foreach ($fields as $in => $meta) {
            update_post_meta($postId, $meta, isset($_POST[$in]) ? sanitize_text_field(wp_unslash((string) $_POST[$in])) : '');
        }

        update_post_meta($postId, '_laico_hero_lede_fa', isset($_POST['laico_hero_lede_fa']) ? sanitize_textarea_field(wp_unslash((string) $_POST['laico_hero_lede_fa'])) : '');
        update_post_meta($postId, '_laico_hero_lede_en', isset($_POST['laico_hero_lede_en']) ? sanitize_textarea_field(wp_unslash((string) $_POST['laico_hero_lede_en'])) : '');
        update_post_meta($postId, '_laico_content_en', isset($_POST['laico_content_en']) ? wp_kses_post((string) wp_unslash($_POST['laico_content_en'])) : '');
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
        update_post_meta($postId, '_laico_title_en', isset($_POST['laico_title_en']) ? sanitize_text_field(wp_unslash((string) $_POST['laico_title_en'])) : '');
        update_post_meta($postId, '_laico_excerpt_en', isset($_POST['laico_excerpt_en']) ? sanitize_textarea_field(wp_unslash((string) $_POST['laico_excerpt_en'])) : '');
        update_post_meta($postId, '_laico_content_en', isset($_POST['laico_content_en']) ? wp_kses_post((string) wp_unslash($_POST['laico_content_en'])) : '');
    }
}
