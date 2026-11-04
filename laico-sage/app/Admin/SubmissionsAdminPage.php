<?php

declare(strict_types=1);

namespace App\Admin;

use App\Services\FormSubmissionsService;
use App\Support\Jalali;
use App\Support\ViteAssets;

/**
 * Registers the Dedicated WordPress Admin Page for Form Submissions
 * (`ارسالی‌های فرم`), handling Single View, Admin Notes, Row/Bulk Actions & CSV Export.
 */
final class SubmissionsAdminPage
{
    public const MENU_SLUG = 'laico-submissions';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_init', [$this, 'handleActions']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function registerMenu(): void
    {
        $unread = FormSubmissionsService::unreadCount();
        $badge = $unread > 0
            ? sprintf(' <span class="awaiting-mod count-%1$d"><span class="pending-count">%2$s</span></span>', $unread, Jalali::toPersianDigits($unread))
            : '';

        add_menu_page(
            'ارسالی‌های فرم مشاوره — لایکو دیزاین',
            'ارسالی‌های فرم' . $badge,
            'manage_options',
            self::MENU_SLUG,
            [$this, 'renderPage'],
            'dashicons-email-alt2',
            26
        );
    }

    public function enqueueAssets(string $hookSuffix): void
    {
        if (! str_contains($hookSuffix, self::MENU_SLUG)) {
            return;
        }
        $css = ViteAssets::entryCss('resources/css/admin.css');
        if ($css !== null) {
            wp_enqueue_style('laico-admin-styles', $css, [], LAICO_THEME_VERSION);
        }
    }

    public function handleActions(): void
    {
        if (! isset($_GET['page']) || $_GET['page'] !== self::MENU_SLUG) {
            return;
        }
        if (! current_user_can('manage_options')) {
            return;
        }

        // 1. Export CSV (UTF-8 with BOM for Excel compatibility)
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            check_admin_referer('laico_export_submissions_csv');
            $this->streamCsvExport();
        }

        // 2. Save Single Submission Note + Status
        if (isset($_POST['laico_save_submission_detail'])) {
            $subId = isset($_POST['submission_id']) ? (int) $_POST['submission_id'] : 0;
            check_admin_referer('laico_update_submission_' . $subId);

            $status = isset($_POST['status']) ? sanitize_key(wp_unslash((string) $_POST['status'])) : 'read';
            $note = isset($_POST['admin_note']) ? (string) wp_unslash($_POST['admin_note']) : '';
            FormSubmissionsService::updateNoteAndStatus($subId, $status, $note);

            wp_safe_redirect(admin_url('admin.php?page=' . self::MENU_SLUG . '&view=' . $subId . '&updated=1'));
            exit;
        }

        // 3. Single Row Quick Actions
        if (isset($_GET['action'], $_GET['id']) && ! isset($_POST['submission_ids'])) {
            $id = (int) $_GET['id'];
            $action = sanitize_key(wp_unslash((string) $_GET['action']));
            check_admin_referer('laico_sub_row_' . $id);

            if ($action === 'mark_read') {
                FormSubmissionsService::updateStatus([$id], 'read');
            } elseif ($action === 'mark_contacted') {
                FormSubmissionsService::updateStatus([$id], 'contacted');
            } elseif ($action === 'delete') {
                FormSubmissionsService::deleteByIds([$id]);
            }

            wp_safe_redirect(admin_url('admin.php?page=' . self::MENU_SLUG . '&done=1'));
            exit;
        }

        // 4. Bulk Actions
        $bulkAction = '';
        if (isset($_POST['action']) && $_POST['action'] !== '-1') {
            $bulkAction = sanitize_key(wp_unslash((string) $_POST['action']));
        } elseif (isset($_POST['action2']) && $_POST['action2'] !== '-1') {
            $bulkAction = sanitize_key(wp_unslash((string) $_POST['action2']));
        }

        if ($bulkAction !== '' && ! empty($_POST['submission_ids']) && is_array($_POST['submission_ids'])) {
            check_admin_referer('bulk-laico_submissions');
            $ids = array_map('intval', wp_unslash($_POST['submission_ids']));

            if ($bulkAction === 'mark_read') {
                FormSubmissionsService::updateStatus($ids, 'read');
            } elseif ($bulkAction === 'mark_contacted') {
                FormSubmissionsService::updateStatus($ids, 'contacted');
            } elseif ($bulkAction === 'mark_archived') {
                FormSubmissionsService::updateStatus($ids, 'archived');
            } elseif ($bulkAction === 'delete') {
                FormSubmissionsService::deleteByIds($ids);
            }

            wp_safe_redirect(admin_url('admin.php?page=' . self::MENU_SLUG . '&bulk_done=1'));
            exit;
        }
    }

    public function renderPage(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die('شما اجازه دسترسی به این بخش را ندارید.');
        }

        $viewId = isset($_GET['view']) ? (int) $_GET['view'] : 0;
        if ($viewId > 0) {
            $entry = FormSubmissionsService::find($viewId);
            if ($entry !== null) {
                if (($entry['status'] ?? '') === 'new') {
                    FormSubmissionsService::updateStatus([$viewId], 'read');
                    $entry['status'] = 'read';
                }
                $this->renderSingleDetail($entry);
                return;
            }
        }

        $counts = FormSubmissionsService::countsByStatus();
        $currentStatus = isset($_GET['status']) ? sanitize_key(wp_unslash((string) $_GET['status'])) : '';
        $baseUrl = admin_url('admin.php?page=' . self::MENU_SLUG);
        $csvUrl = wp_nonce_url(add_query_arg(['export' => 'csv', 'status' => $currentStatus], $baseUrl), 'laico_export_submissions_csv');

        $table = new SubmissionsListTable();
        $table->prepare_items();
        ?>
        <div class="wrap laico-admin-wrap" dir="rtl">
          <div class="laico-admin-header">
            <div>
              <h1>ارسالی‌های فرم مشاوره و تماس</h1>
              <p>تمام درخواست‌های ثبت‌شده در فرم‌های ۶ صفحهٔ سایت در این جدول ذخیره و مدیریت می‌شوند.</p>
            </div>
            <div class="laico-admin-header-actions">
              <a href="<?php echo esc_url($csvUrl); ?>" class="button button-secondary">⬇ خروجی اکسل (CSV)</a>
              <a href="<?php echo esc_url(admin_url('admin.php?page=laico-settings&tab=forms')); ?>" class="button">تنظیمات اعلان فرم‌ها</a>
            </div>
          </div>

          <?php if (! empty($_GET['done']) || ! empty($_GET['bulk_done'])) : ?>
            <div class="notice notice-success is-dismissible"><p>عملیات با موفقیت انجام شد.</p></div>
          <?php endif; ?>

          <ul class="subsubsub">
            <li>
              <a href="<?php echo esc_url($baseUrl); ?>" class="<?php echo $currentStatus === '' ? 'current' : ''; ?>">
                همه <span class="count">(<?php echo esc_html(Jalali::toPersianDigits($counts['all'])); ?>)</span>
              </a> |
            </li>
            <?php
            $statusItems = FormSubmissionsService::statusLabels();
            $idx = 0;
            $totalSt = count($statusItems);
            foreach ($statusItems as $stKey => $stLabel) :
                $idx++;
                $stUrl = add_query_arg(['status' => $stKey], $baseUrl);
            ?>
              <li>
                <a href="<?php echo esc_url($stUrl); ?>" class="<?php echo $currentStatus === $stKey ? 'current' : ''; ?>">
                  <?php echo esc_html($stLabel); ?> <span class="count">(<?php echo esc_html(Jalali::toPersianDigits($counts[$stKey] ?? 0)); ?>)</span>
                </a><?php echo $idx < $totalSt ? ' |' : ''; ?>
              </li>
            <?php endforeach; ?>
          </ul>

          <form method="post" action="<?php echo esc_url($baseUrl); ?>">
            <input type="hidden" name="page" value="<?php echo esc_attr(self::MENU_SLUG); ?>">
            <?php
            $table->search_box('جستجو در ارسالی‌ها', 'laico-submission-search');
            $table->display();
            ?>
          </form>
        </div>
        <?php
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function renderSingleDetail(array $entry): void
    {
        $id = (int) $entry['id'];
        $backUrl = admin_url('admin.php?page=' . self::MENU_SLUG);
        $ts = strtotime((string) ($entry['created_at'] ?? '')) ?: time();
        $jalaliDate = Jalali::fromTimestamp($ts) . ' — ساعت ' . Jalali::toPersianDigits(gmdate('H:i', $ts));
        $digits = ltrim((string) $entry['phone'], '+');
        ?>
        <div class="wrap laico-admin-wrap" dir="rtl">
          <div class="laico-admin-header">
            <div>
              <h1>جزئیات درخواست مشاوره #<?php echo esc_html(Jalali::toPersianDigits($id)); ?></h1>
              <p>ثبت‌شده در تاریخ <?php echo esc_html($jalaliDate); ?></p>
            </div>
            <div class="laico-admin-header-actions">
              <a href="<?php echo esc_url($backUrl); ?>" class="button">→ بازگشت به لیست ارسالی‌ها</a>
            </div>
          </div>

          <?php if (! empty($_GET['updated'])) : ?>
            <div class="notice notice-success is-dismissible"><p>وضعیت و یادداشت مدیریتی با موفقیت ذخیره شد.</p></div>
          <?php endif; ?>

          <div class="laico-detail-grid">
            <div class="laico-card">
              <h2>اطلاعات متقاضی و شرح پروژه</h2>
              <table class="widefat striped" style="border:0;">
                <tbody>
                  <tr>
                    <th style="width:160px;">نام و نام خانوادگی</th>
                    <td><strong><?php echo esc_html((string) $entry['full_name']); ?></strong></td>
                  </tr>
                  <tr>
                    <th>شماره تماس</th>
                    <td>
                      <a href="tel:<?php echo esc_attr((string) $entry['phone']); ?>" dir="ltr" style="font-size:15px;font-weight:700;"><?php echo esc_html((string) $entry['phone']); ?></a>
                      &nbsp;
                      <a href="https://wa.me/<?php echo esc_attr($digits); ?>" target="_blank" rel="noopener" class="button button-small">ارسال پیام واتس‌اپ</a>
                    </td>
                  </tr>
                  <tr>
                    <th>نوع فضا / پروژه</th>
                    <td><?php echo esc_html(FormSubmissionsService::projectTypeLabel((string) $entry['project_type'])); ?></td>
                  </tr>
                  <tr>
                    <th>صفحه مبدأ</th>
                    <td>
                      <?php echo esc_html((string) ($entry['source_page'] ?: '—')); ?>
                      <?php if (! empty($entry['source_url'])) : ?>
                        — <a href="<?php echo esc_url((string) $entry['source_url']); ?>" target="_blank" rel="noopener">مشاهده صفحه</a>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <tr>
                    <th>زبان فرم کاربر</th>
                    <td><?php echo ($entry['lang'] ?? 'fa') === 'en' ? 'انگلیسی (EN)' : 'فارسی (FA)'; ?></td>
                  </tr>
                  <tr>
                    <th>متن کامل پیام</th>
                    <td style="white-space:pre-wrap;line-height:1.8;"><?php echo esc_html((string) $entry['message']); ?></td>
                  </tr>
                  <?php if (! empty($entry['ip_address'])) : ?>
                    <tr>
                      <th>آی‌پی ثبت‌کننده</th>
                      <td><code dir="ltr"><?php echo esc_html((string) $entry['ip_address']); ?></code></td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <div class="laico-card">
              <h2>مدیریت پیگیری و یادداشت داخلی</h2>
              <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=' . self::MENU_SLUG . '&view=' . $id)); ?>">
                <?php wp_nonce_field('laico_update_submission_' . $id); ?>
                <input type="hidden" name="submission_id" value="<?php echo esc_attr((string) $id); ?>">

                <p>
                  <label for="laico_sub_status"><strong>وضعیت پیگیری:</strong></label><br>
                  <select id="laico_sub_status" name="status" class="widefat" style="margin-top:6px;">
                    <?php foreach (FormSubmissionsService::statusLabels() as $stKey => $stLabel) : ?>
                      <option value="<?php echo esc_attr($stKey); ?>" <?php selected((string) $entry['status'], $stKey); ?>>
                        <?php echo esc_html($stLabel); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </p>

                <p style="margin-top:14px;">
                  <label for="laico_admin_note"><strong>یادداشت همکاران فروش / استودیو:</strong></label><br>
                  <textarea id="laico_admin_note" name="admin_note" rows="6" class="widefat" style="margin-top:6px;" placeholder="نتیجه تماس تلفنی، زمان بازدید شو روم، برآورد اولیه..."><?php echo esc_textarea((string) ($entry['admin_note'] ?? '')); ?></textarea>
                </p>

                <p style="margin-top:16px;">
                  <button type="submit" name="laico_save_submission_detail" value="1" class="button button-primary button-large">ذخیره تغییرات</button>
                </p>
              </form>
            </div>
          </div>
        </div>
        <?php
    }

    private function streamCsvExport(): never
    {
        global $wpdb;
        $table = FormSubmissionsService::tableName();

        $statusFilter = isset($_GET['status']) ? sanitize_key(wp_unslash((string) $_GET['status'])) : '';
        if ($statusFilter !== '' && array_key_exists($statusFilter, FormSubmissionsService::statusLabels())) {
            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE status = %s ORDER BY created_at DESC", $statusFilter), ARRAY_A);
        } else {
            $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC", ARRAY_A);
        }

        $filename = 'laico-submissions-' . gmdate('Y-m-d') . '.csv';
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        if ($out !== false) {
            // UTF-8 BOM so Microsoft Excel renders Persian script cleanly
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['شناسه', 'نام و نام خانوادگی', 'شماره تماس', 'نوع پروژه', 'پیام', 'صفحه مبدأ', 'زبان', 'وضعیت', 'یادداشت مدیر', 'تاریخ ثبت (شمسی)', 'تاریخ میلادی']);

            $statusLabels = FormSubmissionsService::statusLabels();
            foreach (($rows ?: []) as $r) {
                $ts = strtotime((string) ($r['created_at'] ?? ''));
                fputcsv($out, [
                    $r['id'],
                    $r['full_name'],
                    $r['phone'],
                    FormSubmissionsService::projectTypeLabel((string) ($r['project_type'] ?? '')),
                    $r['message'],
                    $r['source_page'],
                    $r['lang'],
                    $statusLabels[$r['status'] ?? ''] ?? ($r['status'] ?? ''),
                    $r['admin_note'] ?? '',
                    $ts ? Jalali::fromTimestamp($ts) : '',
                    $r['created_at'],
                ]);
            }
            fclose($out);
        }
        exit;
    }
}
