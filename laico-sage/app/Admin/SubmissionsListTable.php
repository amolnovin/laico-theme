<?php

declare(strict_types=1);

namespace App\Admin;

use App\Services\FormSubmissionsService;
use App\Support\Jalali;

if (! class_exists(\WP_List_Table::class)) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Native WordPress List Table for Consultation Form Submissions.
 */
final class SubmissionsListTable extends \WP_List_Table
{
    public function __construct()
    {
        parent::__construct([
            'singular' => 'laico_submission',
            'plural' => 'laico_submissions',
            'ajax' => false,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function get_columns(): array
    {
        return [
            'cb' => '<input type="checkbox" />',
            'full_name' => 'نام متقاضی',
            'phone' => 'شماره تماس',
            'project_type' => 'نوع پروژه',
            'message' => 'خلاصه پیام',
            'source_page' => 'صفحه مبدأ',
            'status' => 'وضعیت',
            'created_at' => 'تاریخ ثبت',
        ];
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    protected function get_sortable_columns(): array
    {
        return [
            'full_name' => ['full_name', false],
            'project_type' => ['project_type', false],
            'status' => ['status', false],
            'created_at' => ['created_at', true],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function get_bulk_actions(): array
    {
        return [
            'mark_read' => 'علامت‌گذاری به‌عنوان بررسی‌شده',
            'mark_contacted' => 'علامت‌گذاری به‌عنوان تماس‌گرفته‌شده',
            'mark_archived' => 'انتقال به بایگانی',
            'delete' => 'حذف دائمی',
        ];
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function column_cb($item): string
    {
        return sprintf('<input type="checkbox" name="submission_ids[]" value="%d" />', (int) $item['id']);
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function column_full_name(array $item): string
    {
        $id = (int) $item['id'];
        $baseUrl = admin_url('admin.php?page=laico-submissions');
        $viewUrl = add_query_arg(['view' => $id], $baseUrl);

        $readUrl = wp_nonce_url(add_query_arg(['action' => 'mark_read', 'id' => $id], $baseUrl), 'laico_sub_row_' . $id);
        $contactedUrl = wp_nonce_url(add_query_arg(['action' => 'mark_contacted', 'id' => $id], $baseUrl), 'laico_sub_row_' . $id);
        $delUrl = wp_nonce_url(add_query_arg(['action' => 'delete', 'id' => $id], $baseUrl), 'laico_sub_row_' . $id);

        $actions = [
            'view' => sprintf('<a href="%s"><strong>مشاهده کامل و یادداشت</strong></a>', esc_url($viewUrl)),
            'mark_read' => sprintf('<a href="%s">بررسی‌شده</a>', esc_url($readUrl)),
            'mark_contacted' => sprintf('<a href="%s">تماس‌گرفته‌شده</a>', esc_url($contactedUrl)),
            'delete' => sprintf('<a href="%s" class="submitdelete" onclick="return confirm(\'آیا از حذف این درخواست مطمئن هستید؟\');">حذف</a>', esc_url($delUrl)),
        ];

        $isNew = ($item['status'] ?? '') === 'new';
        $titleHtml = sprintf(
            '<a class="row-title" href="%s">%s%s</a>',
            esc_url($viewUrl),
            $isNew ? '<span class="laico-dot-new" title="جدید"></span> ' : '',
            esc_html((string) $item['full_name'])
        );

        return $titleHtml . $this->row_actions($actions);
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function column_phone(array $item): string
    {
        $phone = (string) ($item['phone'] ?? '');
        $digits = ltrim($phone, '+');
        return sprintf(
            '<a href="tel:%1$s" dir="ltr" style="font-weight:600;">%2$s</a> <a href="https://wa.me/%3$s" target="_blank" rel="noopener" class="laico-wa-mini" title="ارسال پیام در واتس‌اپ">واتس‌اپ</a>',
            esc_attr($phone),
            esc_html($phone),
            esc_attr($digits)
        );
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function column_project_type(array $item): string
    {
        return esc_html(FormSubmissionsService::projectTypeLabel((string) ($item['project_type'] ?? '')));
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function column_message(array $item): string
    {
        $msg = (string) ($item['message'] ?? '');
        $trimmed = wp_trim_words($msg, 14, '…');
        $note = trim((string) ($item['admin_note'] ?? ''));
        $noteHtml = $note !== ''
            ? '<div style="margin-top:4px;font-size:11px;color:#52657a;">📝 یادداشت مدیر: ' . esc_html(wp_trim_words($note, 8, '…')) . '</div>'
            : '';
        return esc_html($trimmed) . $noteHtml;
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function column_source_page(array $item): string
    {
        $label = esc_html((string) ($item['source_page'] ?: '—'));
        $url = trim((string) ($item['source_url'] ?? ''));
        $langBadge = strtoupper((string) ($item['lang'] ?? 'fa'));
        if ($url !== '') {
            return sprintf('<a href="%s" target="_blank" rel="noopener">%s</a> <code style="font-size:10px;">%s</code>', esc_url($url), $label, esc_html($langBadge));
        }
        return $label . ' <code style="font-size:10px;">' . esc_html($langBadge) . '</code>';
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function column_status(array $item): string
    {
        $st = (string) ($item['status'] ?? 'new');
        $labels = FormSubmissionsService::statusLabels();
        $label = $labels[$st] ?? $st;
        return sprintf('<span class="laico-status-badge laico-status-badge--%s">%s</span>', esc_attr($st), esc_html($label));
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function column_created_at(array $item): string
    {
        $ts = strtotime((string) ($item['created_at'] ?? ''));
        if (! $ts) {
            return esc_html((string) ($item['created_at'] ?? '—'));
        }
        $jalali = Jalali::fromTimestamp($ts);
        $clock = Jalali::toPersianDigits(gmdate('H:i', $ts));
        return sprintf('<span>%s</span><br><small style="color:#6d7e90;">ساعت %s</small>', esc_html($jalali), esc_html($clock));
    }

    public function no_items(): void
    {
        echo 'هنوز هیچ درخواست مشاوره‌ای ثبت نشده است.';
    }

    public function prepare_items(): void
    {
        global $wpdb;
        $table = FormSubmissionsService::tableName();

        $perPage = 20;
        $currentPage = max(1, $this->get_pagenum());
        $offset = ($currentPage - 1) * $perPage;

        $statusFilter = isset($_GET['status']) ? sanitize_key(wp_unslash((string) $_GET['status'])) : '';
        $search = isset($_REQUEST['s']) ? sanitize_text_field(wp_unslash((string) $_REQUEST['s'])) : '';

        $allowedSort = ['id', 'full_name', 'project_type', 'status', 'created_at'];
        $orderby = isset($_GET['orderby']) && in_array($_GET['orderby'], $allowedSort, true)
            ? (string) $_GET['orderby']
            : 'created_at';
        $order = isset($_GET['order']) && strtolower((string) $_GET['order']) === 'asc' ? 'ASC' : 'DESC';

        $where = ['1=1'];
        $params = [];

        if ($statusFilter !== '' && array_key_exists($statusFilter, FormSubmissionsService::statusLabels())) {
            $where[] = 'status = %s';
            $params[] = $statusFilter;
        }

        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where[] = '(full_name LIKE %s OR phone LIKE %s OR message LIKE %s OR source_page LIKE %s)';
            array_push($params, $like, $like, $like, $like);
        }

        $whereSql = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) FROM {$table} WHERE {$whereSql}";
        $totalItems = empty($params)
            ? (int) $wpdb->get_var($countSql)
            : (int) $wpdb->get_var($wpdb->prepare($countSql, $params));

        $querySql = "SELECT * FROM {$table} WHERE {$whereSql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $queryParams = array_merge($params, [$perPage, $offset]);
        $rows = $wpdb->get_results($wpdb->prepare($querySql, $queryParams), ARRAY_A);

        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];
        $this->items = is_array($rows) ? $rows : [];

        $this->set_pagination_args([
            'total_items' => $totalItems,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($totalItems / $perPage),
        ]);
    }
}
