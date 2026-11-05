<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Jalali;

/**
 * Handles Consultation Form Database Storage, Validation, Anti-Spam,
 * REST API + Admin-AJAX Endpoints, Notifications, and Admin Table Queries.
 */
final class FormSubmissionsService
{
    public const DB_VERSION = '1.0.0';
    public const DB_VERSION_OPTION = 'laico_submissions_db_version';

    public function register(): void
    {
        add_action('after_switch_theme', [$this, 'ensureTableExists']);
        add_action('init', [$this, 'maybeUpgradeTable'], 5);
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
        add_action('wp_ajax_laico_submit_form', [$this, 'handleAjaxSubmission']);
        add_action('wp_ajax_nopriv_laico_submit_form', [$this, 'handleAjaxSubmission']);
    }

    public static function tableName(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'laico_submissions';
    }

    public function maybeUpgradeTable(): void
    {
        if (get_option(self::DB_VERSION_OPTION) !== self::DB_VERSION) {
            $this->ensureTableExists();
        }
    }

    public function ensureTableExists(): void
    {
        global $wpdb;

        $table = self::tableName();
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            full_name varchar(190) NOT NULL,
            phone varchar(60) NOT NULL,
            project_type varchar(80) NOT NULL DEFAULT '',
            message text NOT NULL,
            source_page varchar(255) NOT NULL DEFAULT '',
            source_url varchar(500) NOT NULL DEFAULT '',
            lang varchar(10) NOT NULL DEFAULT 'fa',
            status varchar(30) NOT NULL DEFAULT 'new',
            admin_note text NULL,
            ip_address varchar(64) NOT NULL DEFAULT '',
            user_agent varchar(255) NOT NULL DEFAULT '',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status_idx (status),
            KEY created_at_idx (created_at),
            KEY phone_idx (phone)
        ) {$charsetCollate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        update_option(self::DB_VERSION_OPTION, self::DB_VERSION, true);
    }

    public function registerRestRoutes(): void
    {
        register_rest_route('laico/v1', '/submissions', [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => [$this, 'handleRestSubmission'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handleRestSubmission(\WP_REST_Request $request): \WP_REST_Response
    {
        $params = $request->get_json_params();
        if (! is_array($params) || empty($params)) {
            $params = $request->get_body_params();
        }

        $nonce = (string) ($request->get_header('X-Laico-Nonce') ?: ($params['_laico_nonce'] ?? ''));
        $result = $this->processSubmission(is_array($params) ? $params : [], $nonce);

        return new \WP_REST_Response($result['body'], $result['status']);
    }

    public function handleAjaxSubmission(): void
    {
        $raw = wp_unslash($_POST);
        $nonce = isset($raw['_laico_nonce']) ? (string) $raw['_laico_nonce'] : '';
        $result = $this->processSubmission(is_array($raw) ? $raw : [], $nonce);

        wp_send_json($result['body'], $result['status']);
    }

    /**
     * Validate, rate-limit, normalize, store, and notify on a form submission.
     *
     * @param array<string, mixed> $input
     * @return array{status: int, body: array<string, mixed>}
     */
    public function processSubmission(array $input, string $nonce): array
    {
        $lang = isset($input['lang']) && $input['lang'] === 'en' ? 'en' : 'fa';

        // 1. Verify WordPress form nonce (protects against CSRF)
        if ($nonce === '' || ! wp_verify_nonce($nonce, 'laico_public_form')) {
            return [
                'status' => 403,
                'body' => [
                    'ok' => false,
                    'message' => $lang === 'en'
                        ? 'Security token expired. Please refresh the page and try again.'
                        : 'نشست امنیتی منقضی شده است. لطفاً صفحه را نوسازی کرده و دوباره تلاش کنید.',
                ],
            ];
        }

        // 2. Honeypot anti-bot trap (silent success to fool bots without storing spam)
        if (! empty($input['website_url'])) {
            return [
                'status' => 200,
                'body' => [
                    'ok' => true,
                    'title' => (string) ThemeOptions::get("form_success_title_{$lang}"),
                    'message' => (string) ThemeOptions::get("form_success_body_{$lang}"),
                ],
            ];
        }

        // 3. Per-IP Rate Limit
        $ip = $this->clientIp();
        $maxPerWindow = max(1, (int) ThemeOptions::get('form_rate_limit_max', 5));
        $rateKey = 'laico_rl_' . substr(hash('sha256', $ip . '|' . NONCE_SALT), 0, 24);
        $attempts = (int) get_transient($rateKey);
        if ($attempts >= $maxPerWindow) {
            return [
                'status' => 429,
                'body' => [
                    'ok' => false,
                    'message' => $lang === 'en'
                        ? 'Too many requests. Please wait a few minutes before submitting again.'
                        : 'تعداد درخواست‌های ارسالی بیش از حد مجاز است. لطفاً چند دقیقه بعد تلاش کنید.',
                ],
            ];
        }

        // 4. Field extraction & validation
        $name = sanitize_text_field((string) ($input['name'] ?? ''));
        $rawPhone = sanitize_text_field((string) ($input['phone'] ?? ''));
        $type = sanitize_key((string) ($input['type'] ?? ''));
        $note = sanitize_textarea_field((string) ($input['note'] ?? ''));
        $sourcePage = sanitize_text_field((string) ($input['source_page'] ?? ''));
        $sourceUrl = esc_url_raw((string) ($input['source_url'] ?? ''));

        $errors = [];
        if (mb_strlen($name) < 2) {
            $errors['name'] = $lang === 'en' ? 'Please enter your name.' : 'نام را بنویسید.';
        }

        $normalizedPhone = self::normalizePhone($rawPhone);
        if ($normalizedPhone === null) {
            $errors['phone'] = $lang === 'en' ? 'Enter a valid phone number.' : 'شماره تماس معتبر وارد کنید.';
        }

        if (mb_strlen($note) < 4) {
            $errors['note'] = $lang === 'en' ? 'Tell us a little about the space.' : 'چند خط درباره فضا بنویسید.';
        }

        if (! empty($errors)) {
            return [
                'status' => 422,
                'body' => [
                    'ok' => false,
                    'errors' => $errors,
                    'message' => reset($errors),
                ],
            ];
        }

        // 5. Persist to DB
        $this->maybeUpgradeTable();
        global $wpdb;

        $storeIp = (bool) ThemeOptions::get('form_store_ip', true) ? $ip : '';
        $userAgent = isset($_SERVER['HTTP_USER_AGENT'])
            ? mb_substr(sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_USER_AGENT'])), 0, 250)
            : '';
        $now = current_time('mysql');

        $inserted = $wpdb->insert(
            self::tableName(),
            [
                'full_name' => $name,
                'phone' => $normalizedPhone,
                'project_type' => $type,
                'message' => $note,
                'source_page' => $sourcePage !== '' ? $sourcePage : 'وب‌سایت',
                'source_url' => $sourceUrl,
                'lang' => $lang,
                'status' => 'new',
                'ip_address' => $storeIp,
                'user_agent' => $userAgent,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($inserted === false) {
            return [
                'status' => 500,
                'body' => [
                    'ok' => false,
                    'message' => $lang === 'en'
                        ? 'Could not save your request right now. Please call us directly.'
                        : 'خطا در ذخیرهٔ درخواست. لطفاً مستقیماً با شمارهٔ دفتر تماس بگیرید.',
                ],
            ];
        }

        $submissionId = (int) $wpdb->insert_id;
        set_transient($rateKey, $attempts + 1, 10 * MINUTE_IN_SECONDS);
        delete_transient('laico_unread_submissions_count');

        // 6. Dispatch optional notifications (Email + Telegram)
        $this->sendNotifications([
            'id' => $submissionId,
            'full_name' => $name,
            'phone' => $normalizedPhone,
            'project_type' => self::projectTypeLabel($type),
            'message' => $note,
            'source_page' => $sourcePage,
            'source_url' => $sourceUrl,
        ]);

        return [
            'status' => 200,
            'body' => [
                'ok' => true,
                'id' => $submissionId,
                'title' => (string) ThemeOptions::get("form_success_title_{$lang}"),
                'message' => (string) ThemeOptions::get("form_success_body_{$lang}"),
            ],
        ];
    }

    /**
     * Normalize Persian/Arabic digits and format Iranian/international phone numbers cleanly
     * (fixes the `+98 0912...` leading-zero bug from the static template).
     */
    public static function normalizePhone(string $raw): ?string
    {
        $english = Jalali::toEnglishDigits($raw);
        $digits = preg_replace('/\D+/', '', $english) ?: '';

        if (strlen($digits) < 8 || strlen($digits) > 15) {
            return null;
        }

        if (str_starts_with($digits, '0098')) {
            $digits = substr($digits, 4);
        } elseif (str_starts_with($digits, '98') && strlen($digits) >= 11) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) < 8) {
            return null;
        }

        return '+98' . $digits;
    }

    public static function projectTypeLabel(string $slug): string
    {
        $map = [
            'kitchen' => 'آشپزخانه',
            'living' => 'پذیرایی و اتاق',
            'bath' => 'حمام',
            'cafe' => 'کافه و رستوران',
            'wardrobe' => 'کمد و سیستم',
            'full' => 'بازسازی کامل',
            '' => 'مشاوره عمومی',
        ];

        return $map[$slug] ?? $slug;
    }

    public static function statusLabels(): array
    {
        return [
            'new' => 'جدید',
            'read' => 'بررسی‌شده',
            'contacted' => 'تماس‌گرفته‌شده',
            'archived' => 'بایگانی',
        ];
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function sendNotifications(array $entry): void
    {
        if ((bool) ThemeOptions::get('form_enable_email', true)) {
            $to = trim((string) ThemeOptions::get('form_notify_email', ''));
            if ($to === '') {
                $to = (string) get_option('admin_email');
            }
            if (is_email($to)) {
                $subject = sprintf('[لایکو دیزاین] درخواست مشاوره جدید از %s (#%d)', $entry['full_name'], $entry['id']);
                $body = implode("\n", [
                    'یک درخواست مشاوره جدید در وب‌سایت ثبت شد:',
                    '----------------------------------------',
                    'شناسه: #' . $entry['id'],
                    'نام و نام خانوادگی: ' . $entry['full_name'],
                    'شماره تماس: ' . $entry['phone'],
                    'نوع پروژه: ' . $entry['project_type'],
                    'صفحه مبدأ: ' . ($entry['source_page'] ?: '—'),
                    'پیام کاربر:',
                    $entry['message'],
                    '----------------------------------------',
                    'مدیریت ارسالی‌ها: ' . admin_url('admin.php?page=laico-submissions&view=' . $entry['id']),
                ]);
                wp_mail($to, $subject, $body);
            }
        }

        $botToken = trim((string) ThemeOptions::get('form_telegram_bot_token', ''));
        $chatId = trim((string) ThemeOptions::get('form_telegram_chat_id', ''));
        if ($botToken !== '' && $chatId !== '') {
            $text = sprintf(
                "📩 درخواست مشاوره جدید (#%d)\n👤 نام: %s\n📞 تلفن: %s\n🏗 نوع پروژه: %s\n📄 صفحه: %s\n📝 پیام:\n%s",
                $entry['id'],
                $entry['full_name'],
                $entry['phone'],
                $entry['project_type'],
                $entry['source_page'] ?: '—',
                $entry['message']
            );
            wp_remote_post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'timeout' => 4,
                'blocking' => false,
                'body' => [
                    'chat_id' => $chatId,
                    'text' => $text,
                ],
            ]);
        }
    }

    private function clientIp(): string
    {
        $remote = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash((string) $_SERVER['REMOTE_ADDR'])) : '';
        return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '0.0.0.0';
    }

    public static function unreadCount(): int
    {
        $cached = get_transient('laico_unread_submissions_count');
        if ($cached !== false) {
            return (int) $cached;
        }

        global $wpdb;
        $table = self::tableName();
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        if ($exists !== $table) {
            return 0;
        }

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'new'");
        set_transient('laico_unread_submissions_count', $count, 5 * MINUTE_IN_SECONDS);
        return $count;
    }

    /**
     * @return array<string, int>
     */
    public static function countsByStatus(): array
    {
        global $wpdb;
        $table = self::tableName();
        $counts = [
            'all' => 0,
            'new' => 0,
            'read' => 0,
            'contacted' => 0,
            'archived' => 0,
        ];

        $rows = $wpdb->get_results("SELECT status, COUNT(*) AS total FROM {$table} GROUP BY status", ARRAY_A);
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $st = (string) ($row['status'] ?? '');
                $n = (int) ($row['total'] ?? 0);
                $counts['all'] += $n;
                if (isset($counts[$st])) {
                    $counts[$st] = $n;
                }
            }
        }

        return $counts;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        global $wpdb;
        $table = self::tableName();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);
        return is_array($row) ? $row : null;
    }

    /**
     * @param array<int, int> $ids
     */
    public static function updateStatus(array $ids, string $status): int
    {
        $validStatuses = array_keys(self::statusLabels());
        if (! in_array($status, $validStatuses, true) || empty($ids)) {
            return 0;
        }

        global $wpdb;
        $table = self::tableName();
        $cleanIds = array_values(array_filter(array_map('intval', $ids), static fn (int $v): bool => $v > 0));
        if (empty($cleanIds)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($cleanIds), '%d'));
        $params = array_merge([$status, current_time('mysql')], $cleanIds);
        $updated = (int) $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET status = %s, updated_at = %s WHERE id IN ({$placeholders})",
            $params
        ));

        delete_transient('laico_unread_submissions_count');
        return $updated;
    }

    public static function updateNoteAndStatus(int $id, string $status, string $adminNote): bool
    {
        $validStatuses = array_keys(self::statusLabels());
        if (! in_array($status, $validStatuses, true) || $id <= 0) {
            return false;
        }

        global $wpdb;
        $result = $wpdb->update(
            self::tableName(),
            [
                'status' => $status,
                'admin_note' => sanitize_textarea_field($adminNote),
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id],
            ['%s', '%s', '%s'],
            ['%d']
        );

        delete_transient('laico_unread_submissions_count');
        return $result !== false;
    }

    /**
     * @param array<int, int> $ids
     */
    public static function deleteByIds(array $ids): int
    {
        global $wpdb;
        $table = self::tableName();
        $cleanIds = array_values(array_filter(array_map('intval', $ids), static fn (int $v): bool => $v > 0));
        if (empty($cleanIds)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($cleanIds), '%d'));
        $deleted = (int) $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE id IN ({$placeholders})",
            $cleanIds
        ));

        delete_transient('laico_unread_submissions_count');
        return $deleted;
    }
}
