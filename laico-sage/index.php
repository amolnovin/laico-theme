<?php

/**
 * Sage main template entry point.
 *
 * Renders the resolved Blade template via Acorn's view container.
 * If Acorn is not yet installed, renders a clean setup guide instead of a fatal error.
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

if (function_exists('view') && function_exists('app') && app()->bound('sage.view')) {
    echo view(app('sage.view'), app('sage.data'))->render();
    return;
}

get_header();
?>
<main id="main" style="max-width:780px;margin:80px auto;padding:32px;font-family:Tahoma,sans-serif;direction:rtl;line-height:2;border:1px solid #d5dae1;border-radius:16px;background:#f5f6f8;color:#0b1d33;">
  <h1 style="margin-top:0;">قالب لایکو دیزاین (Sage) آمادهٔ راه‌اندازی است</h1>
  <p>برای فعال‌سازی موتور قالب Blade (پکیج Roots Acorn)، یکی از دو روش زیر را انجام دهید:</p>
  <ol>
    <li>در پوشهٔ قالب دستور <code>composer install --no-dev --optimize-autoloader</code> را اجرا کنید؛ <strong>یا</strong></li>
    <li>افزونهٔ رسمی <strong>Acorn</strong> را در وردپرس نصب و فعال نمایید.</li>
  </ol>
  <p style="margin-bottom:0;color:#6d7e90;">نکته: پنل تنظیمات قالب، مدیریت پروژه‌ها، جدول ارسالی‌های فرم و سیستم نصب Patch همین حالا در پیشخوان وردپرس فعال هستند.</p>
</main>
<?php
get_footer();
