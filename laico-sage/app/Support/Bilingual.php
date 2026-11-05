<?php

declare(strict_types=1);

namespace App\Support;

final class Bilingual
{
    /**
     * Render a bilingual span pair compatible with Laico's client-side language switcher.
     * If English text is omitted, falls back cleanly to Persian text so content is never blank.
     */
    public static function pair(string $fa, string $en = '', bool $allowInlineHtml = false): string
    {
        $enText = trim($en) !== '' ? $en : $fa;
        $bilingualEnabled = \App\Services\ThemeOptions::isEnglishEnabled();
        $defaultLang = $bilingualEnabled ? (string) \App\Services\ThemeOptions::get('default_lang', 'fa') : 'fa';

        if (! $bilingualEnabled) {
            return $allowInlineHtml ? wp_kses_post($fa) : esc_html($fa);
        }

        $safeFa = $allowInlineHtml ? wp_kses_post($fa) : esc_html($fa);
        $safeEn = $allowInlineHtml ? wp_kses_post($enText) : esc_html($enText);

        $faHidden = $defaultLang === 'en' ? ' hidden' : '';
        $enHidden = $defaultLang === 'en' ? '' : ' hidden';

        return sprintf(
            '<span class="lang-fa"%s>%s</span><span class="lang-en"%s>%s</span>',
            $faHidden,
            $safeFa,
            $enHidden,
            $safeEn
        );
    }

    /**
     * Full FA/EN UI dictionary exported to front-end JS (`window.LAICO_CONFIG.i18n`).
     * Includes fixed tags for architectural, educational, and sports projects too.
     *
     * @return array{fa: array<string, string>, en: array<string, string>}
     */
    public static function dictionary(): array
    {
        $ctaFa = (string) \App\Services\ThemeOptions::get('header_cta_text_fa', 'درخواست مشاوره');
        $ctaEn = (string) \App\Services\ThemeOptions::get('header_cta_text_en', 'Book a consultation');
        $okTitleFa = (string) \App\Services\ThemeOptions::get('form_success_title_fa', 'درخواست شما ثبت شد.');
        $okTitleEn = (string) \App\Services\ThemeOptions::get('form_success_title_en', 'Your request is in.');
        $okBodyFa = (string) \App\Services\ThemeOptions::get('form_success_body_fa', 'تیم طراحی ظرف ۴۸ ساعت کاری برای جلسه اول با شما تماس می‌گیرد.');
        $okBodyEn = (string) \App\Services\ThemeOptions::get('form_success_body_en', 'The design team will call within 48 working hours to arrange the first meeting.');

        return [
            'fa' => [
                'nav-home' => 'صفحه اصلی',
                'nav-services' => 'خدمات',
                'nav-projects' => 'پروژه‌ها',
                'nav-journal' => 'مجله',
                'nav-about' => 'درباره ما',
                'nav-contact' => 'تماس با ما',
                'cta' => $ctaFa,
                'menu' => 'منو',
                'close' => 'بستن',
                'col-eye' => '۰۱ — مجموعه‌ها',
                'col-title' => 'چهار فضا، یک استاندارد ساخت',
                'col-all' => 'همه مجموعه‌ها',
                'col-kitchen' => 'آشپزخانه',
                'col-living' => 'پذیرایی و اتاق',
                'col-bath' => 'حمام',
                'col-cafe' => 'کافه و رستوران',
                'prj-eye' => '۰۲ — پروژه‌های منتخب',
                'prj-title' => 'نمونه کارها، امضای کیفیت',
                'prj-all' => 'آرشیو کامل',
                'tag-kitchen' => 'آشپزخانه',
                'tag-interior' => 'معماری داخلی',
                'tag-cafe' => 'کافه و رستوران',
                'tag-bath' => 'حمام',
                'tag-architecture' => 'معماری',
                'tag-educational' => 'آموزشی',
                'tag-sports' => 'ورزشی',
                'st-projects' => 'پروژه اجراشده',
                'st-years' => 'سال تجربه',
                'st-brands' => 'برند اروپایی',
                'spec-finish' => 'روکش',
                'spec-hw' => 'سخت‌افزار',
                'spec-w' => 'ضمانت',
                'addr' => 'آدرس',
                'hours' => 'ساعات',
                'phone' => 'تماس',
                'form-eye' => '۰۶ — شروع پروژه',
                'form-1' => 'چطور می‌توانیم',
                'form-2' => 'همکاری کنیم؟',
                'form-body' => 'فرم را پر کنید؛ تیم طراحی ظرف ۴۸ ساعت کاری برای جلسه اول با شما تماس می‌گیرد.',
                'f-name' => 'نام و نام خانوادگی',
                'f-phone' => 'شماره تماس',
                'f-type' => 'نوع پروژه',
                'f-note' => 'توضیح کوتاه',
                'f-note-ph' => 'متراژ، شهر، بازه زمانی',
                'about-ph-note' => 'متن پیام را بنویسید..',
                'f-send' => 'ارسال درخواست',
                'f-sending' => 'در حال ارسال...',
                'f-ok-title' => $okTitleFa,
                'f-ok-body' => $okBodyFa,
                'err-name' => 'نام را بنویسید.',
                'err-phone' => 'شماره تماس معتبر وارد کنید.',
                'err-note' => 'چند خط درباره فضا بنویسید.',
                'err-server' => 'خطایی در ارسال رخ داد. لطفاً دوباره تلاش کنید.',
                'opt-kitchen' => 'آشپزخانه',
                'opt-living' => 'پذیرایی و اتاق',
                'opt-bath' => 'حمام',
                'opt-cafe' => 'کافه و رستوران',
                'opt-wardrobe' => 'کمد و سیستم',
                'opt-full' => 'بازسازی کامل',
                'privacy' => 'حریم خصوصی',
                'cookies' => 'کوکی‌ها',
            ],
            'en' => [
                'nav-home' => 'Home',
                'nav-services' => 'Services',
                'nav-projects' => 'Projects',
                'nav-journal' => 'Journal',
                'nav-about' => 'About',
                'nav-contact' => 'Contact',
                'cta' => $ctaEn,
                'menu' => 'Menu',
                'close' => 'Close',
                'col-eye' => '01 — Collections',
                'col-title' => 'Four rooms, one standard of making',
                'col-all' => 'All collections',
                'col-kitchen' => 'Kitchen',
                'col-living' => 'Living',
                'col-bath' => 'Bath',
                'col-cafe' => 'Café & restaurant',
                'prj-eye' => '02 — Selected work',
                'prj-title' => 'Projects, signed by quality',
                'prj-all' => 'Full archive',
                'tag-kitchen' => 'Kitchen',
                'tag-interior' => 'Interior',
                'tag-cafe' => 'Café & restaurant',
                'tag-bath' => 'Bath',
                'tag-architecture' => 'Architecture',
                'tag-educational' => 'Educational',
                'tag-sports' => 'Sports',
                'st-projects' => 'Completed projects',
                'st-years' => 'Years of practice',
                'st-brands' => 'European brands',
                'spec-finish' => 'Finish',
                'spec-hw' => 'Hardware',
                'spec-w' => 'Warranty',
                'addr' => 'Address',
                'hours' => 'Hours',
                'phone' => 'Phone',
                'form-eye' => '06 — Start a project',
                'form-1' => 'How should we',
                'form-2' => 'work together?',
                'form-body' => 'Send the form. The design team will call within 48 working hours to set the first meeting.',
                'f-name' => 'Full name',
                'f-phone' => 'Phone',
                'f-type' => 'Project type',
                'f-note' => 'A short note',
                'f-note-ph' => 'Area, city, timeline',
                'about-ph-note' => 'Write your message..',
                'f-send' => 'Send request',
                'f-sending' => 'Sending...',
                'f-ok-title' => $okTitleEn,
                'f-ok-body' => $okBodyEn,
                'err-name' => 'Please enter your name.',
                'err-phone' => 'Enter a valid phone number.',
                'err-note' => 'Tell us a little about the space.',
                'err-server' => 'Submission failed. Please try again.',
                'opt-kitchen' => 'Kitchen',
                'opt-living' => 'Living',
                'opt-bath' => 'Bath',
                'opt-cafe' => 'Café & restaurant',
                'opt-wardrobe' => 'Wardrobe system',
                'opt-full' => 'Full renovation',
                'privacy' => 'Privacy',
                'cookies' => 'Cookies',
            ],
        ];
    }
}
