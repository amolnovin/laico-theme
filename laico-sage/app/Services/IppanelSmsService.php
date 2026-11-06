<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Jalali;

/**
 * IPPanel Core Integration Service for Laico Design Theme (`laico-sage`).
 *
 * Built on the official `IPPanel_Core_Addon_Client` specification for `ippanel-core`:
 * - Registers `laico-theme` (`AMN-LAICO-THEME`) in `IPPanel_Registry` on every request (`init`)
 * - Exposes signed REST callback `/wp-json/laico-theme/v1/receive-key` for automatic token provisioning
 * - Dispatches Admin & Customer SMS notifications (Pattern or Manual Text) upon form submission
 */
final class IppanelSmsService
{
    public const SLUG = 'laico-theme';
    public const NAME = 'قالب لایکو دیزاین';
    public const INTEGRATION_CODE = 'AMN-LAICO-THEME';
    public const REST_NAMESPACE = 'laico-theme/v1';

    public function register(): void
    {
        add_action('init', [$this, 'registerInIppanelCore'], 5);
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
    }

    /**
     * Register theme as an external integration inside `ippanel-core`.
     * Runs on `init` (priority 5) because themes load after `plugins_loaded`.
     */
    public function registerInIppanelCore(): void
    {
        $payload = [
            'slug' => self::SLUG,
            'name' => self::NAME,
            'integration_code' => self::INTEGRATION_CODE,
            'callback_url' => home_url('/wp-json/' . self::REST_NAMESPACE . '/receive-key'),
        ];

        if (function_exists('ippanel_register_external_plugin')) {
            ippanel_register_external_plugin($payload);
        } elseif (has_action('ippanel_register_plugin')) {
            do_action('ippanel_register_plugin', $payload);
        }
    }

    public function registerRestRoutes(): void
    {
        register_rest_route(self::REST_NAMESPACE, '/receive-key', [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => [$this, 'receiveKeyCallback'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Receive and persist access token provisioned by `ippanel-core` (`IPPanel_Registry::send_key_to_plugin`).
     */
    public function receiveKeyCallback(\WP_REST_Request $request): \WP_REST_Response
    {
        $action = sanitize_text_field((string) $request->get_param('action'));
        $pluginSlug = sanitize_key((string) $request->get_param('plugin_slug'));
        $integrationCode = self::normalizeIntegrationCode((string) $request->get_param('integration_code'));
        $apiKey = sanitize_text_field((string) $request->get_param('api_key'));

        if ($action !== 'receive_api_key') {
            return new \WP_REST_Response(['success' => false, 'message' => 'invalid action'], 400);
        }

        if ($pluginSlug !== self::SLUG) {
            return new \WP_REST_Response(['success' => false, 'message' => 'invalid plugin slug'], 400);
        }

        if ($integrationCode !== self::INTEGRATION_CODE) {
            return new \WP_REST_Response(['success' => false, 'message' => 'invalid integration code'], 400);
        }

        if ($apiKey === '') {
            return new \WP_REST_Response(['success' => false, 'message' => 'empty api key'], 400);
        }

        // Verify HMAC signature sent by `IPPanel_Registry::send_key_to_plugin` (or already-saved DB option match)
        $sigHeader = (string) ($request->get_header('X-IPPanel-Signature') ?: $request->get_param('signature') ?: '');
        $tsHeader = (string) ($request->get_header('X-IPPanel-Timestamp') ?: $request->get_param('signature_ts') ?: '');

        if (! $this->isValidCallbackSignature($apiKey, $sigHeader, $tsHeader)) {
            SecurityIntegrationService::logSecurityEvent(
                'suspicious_request',
                'warning',
                'تلاش غیرمجاز برای ثبت کلید IPPanel در قالب لایکو',
                ['slug' => $pluginSlug],
                true
            );
            return new \WP_REST_Response(['success' => false, 'message' => 'invalid signature'], 403);
        }

        update_option(self::tokenOptionName(), $apiKey);

        return new \WP_REST_Response([
            'success' => true,
            'message' => 'API key saved successfully',
        ], 200);
    }

    /**
     * Validate HMAC-SHA256 callback signature matching `IPPanel_Registry::build_callback_signature`,
     * or confirm the key already exists in `ippanel_external_api_keys` option on the same WordPress DB.
     */
    private function isValidCallbackSignature(string $apiKey, string $signature, string $timestamp): bool
    {
        if ($signature !== '' && $timestamp !== '' && ctype_digit($timestamp)) {
            if (abs(time() - (int) $timestamp) <= 600) {
                $secret = defined('AUTH_SALT') && constant('AUTH_SALT') ? (string) constant('AUTH_SALT') : wp_salt('auth');
                $secret = (string) apply_filters('ippanel_registry_callback_secret', $secret);
                $payload = implode('|', [
                    self::SLUG,
                    $apiKey,
                    $timestamp,
                    self::INTEGRATION_CODE,
                    home_url('/'),
                ]);
                $expected = hash_hmac('sha256', $payload, $secret);
                if (hash_equals($expected, $signature)) {
                    return true;
                }
            }
        }

        // Same-database verification fallback if `ippanel-core` saved key directly in `ippanel_external_api_keys`
        $externalKeys = get_option('ippanel_external_api_keys', []);
        if (is_array($externalKeys) && isset($externalKeys[$apiKey])) {
            $keyData = $externalKeys[$apiKey];
            if (is_array($keyData) && ($keyData['plugin_slug'] ?? '') === self::SLUG && ($keyData['status'] ?? '') === 'active') {
                return true;
            }
        }

        return current_user_can('manage_options');
    }

    public static function tokenOptionName(): string
    {
        if (function_exists('ippanel_get_external_plugin_token_option_name')) {
            return (string) ippanel_get_external_plugin_token_option_name(self::SLUG);
        }

        return 'ippanel_api_key_' . self::SLUG;
    }

    public static function getToken(): string
    {
        $manual = trim((string) ThemeOptions::get('sms_ippanel_token', ''));
        if ($manual !== '') {
            return $manual;
        }

        $saved = trim((string) get_option(self::tokenOptionName(), ''));
        if ($saved !== '') {
            return $saved;
        }

        // Auto-discover active token generated for `laico-theme` inside `ippanel_external_api_keys`
        $externalKeys = get_option('ippanel_external_api_keys', []);
        if (is_array($externalKeys)) {
            foreach ($externalKeys as $token => $info) {
                if (
                    is_array($info)
                    && ($info['plugin_slug'] ?? '') === self::SLUG
                    && ($info['status'] ?? '') === 'active'
                    && is_string($token)
                    && $token !== ''
                ) {
                    update_option(self::tokenOptionName(), $token);
                    return $token;
                }
            }
        }

        return '';
    }

    public static function hasToken(): bool
    {
        return self::getToken() !== '';
    }

    public static function isCorePluginLoaded(): bool
    {
        return function_exists('ippanel_send_external_sms');
    }

    /**
     * Send Pattern SMS through `ippanel-core`.
     *
     * @param array<string, scalar> $params
     * @return mixed
     */
    public static function sendPatternSms(string $mobile, string $patternCode, array $params = []): mixed
    {
        if (! function_exists('ippanel_send_external_sms')) {
            return new \WP_Error('ippanel_core_not_loaded', 'افزونه ippanel-core در سایت فعال یا بارگذاری نشده است.');
        }

        $token = self::getToken();
        if ($token === '') {
            return new \WP_Error('ippanel_core_token_missing', 'کلید دسترسی قالب لایکو از ippanel-core دریافت نشده است.');
        }

        return ippanel_send_external_sms($token, $mobile, trim($patternCode), $params, 'pattern');
    }

    /**
     * Send Manual / Simple Text SMS through `ippanel-core`.
     *
     * @return mixed
     */
    public static function sendTextSms(string $mobile, string $message): mixed
    {
        if (! function_exists('ippanel_send_external_sms')) {
            return new \WP_Error('ippanel_core_not_loaded', 'افزونه ippanel-core در سایت فعال یا بارگذاری نشده است.');
        }

        $token = self::getToken();
        if ($token === '') {
            return new \WP_Error('ippanel_core_token_missing', 'کلید دسترسی قالب لایکو از ippanel-core دریافت نشده است.');
        }

        return ippanel_send_external_sms($token, $mobile, $message, [], 'text');
    }

    /**
     * Dispatch configured Admin & Customer SMS notifications after a consultation form submission.
     *
     * @param array<string, mixed> $entry
     */
    public function dispatchFormNotifications(array $entry): void
    {
        if (! self::isCorePluginLoaded()) {
            return;
        }

        $adminEnabled = (bool) ThemeOptions::get('sms_admin_enable', false);
        $customerEnabled = (bool) ThemeOptions::get('sms_customer_enable', false);

        if (! $adminEnabled && ! $customerEnabled) {
            return;
        }

        if (! self::hasToken()) {
            if (function_exists('ippanel_debug_log')) {
                ippanel_debug_log('[Laico SMS] Token missing for laico-theme; skipping SMS dispatch.');
            }
            return;
        }

        $context = $this->buildPlaceholderMap($entry);

        // 1. Admin SMS Notification (supports comma/newline-separated list of admin numbers)
        if ($adminEnabled) {
            $rawAdminPhones = trim((string) ThemeOptions::get('sms_admin_phones', ''));
            if ($rawAdminPhones === '') {
                $rawAdminPhones = (string) ThemeOptions::get('phone_primary_tel', '');
            }

            $adminMode = (string) ThemeOptions::get('sms_admin_mode', 'pattern');
            $adminRecipients = self::parsePhoneList($rawAdminPhones);

            foreach ($adminRecipients as $adminMobile) {
                try {
                    if ($adminMode === 'pattern') {
                        $patternCode = trim((string) ThemeOptions::get('sms_admin_pattern_code', ''));
                        if ($patternCode !== '') {
                            $varsRaw = (string) ThemeOptions::get('sms_admin_pattern_vars', '');
                            $params = $this->buildPatternVariables($varsRaw, $context);
                            self::sendPatternSms($adminMobile, $patternCode, $params);
                        }
                    } else {
                        $tpl = trim((string) ThemeOptions::get('sms_admin_text_template', ''));
                        if ($tpl !== '') {
                            $msg = $this->interpolateText($tpl, $context);
                            self::sendTextSms($adminMobile, $msg);
                        }
                    }
                } catch (\Throwable $e) {
                    if (function_exists('ippanel_debug_log')) {
                        ippanel_debug_log('[Laico SMS] Admin SMS exception: ' . $e->getMessage());
                    }
                }
            }
        }

        // 2. Customer Confirmation SMS
        if ($customerEnabled) {
            $customerPhone = self::toLocalIranMobile((string) ($entry['phone'] ?? ''));
            if ($customerPhone !== '') {
                $customerMode = (string) ThemeOptions::get('sms_customer_mode', 'pattern');
                try {
                    if ($customerMode === 'pattern') {
                        $patternCode = trim((string) ThemeOptions::get('sms_customer_pattern_code', ''));
                        if ($patternCode !== '') {
                            $varsRaw = (string) ThemeOptions::get('sms_customer_pattern_vars', '');
                            $params = $this->buildPatternVariables($varsRaw, $context);
                            self::sendPatternSms($customerPhone, $patternCode, $params);
                        }
                    } else {
                        $tpl = trim((string) ThemeOptions::get('sms_customer_text_template', ''));
                        if ($tpl !== '') {
                            $msg = $this->interpolateText($tpl, $context);
                            self::sendTextSms($customerPhone, $msg);
                        }
                    }
                } catch (\Throwable $e) {
                    if (function_exists('ippanel_debug_log')) {
                        ippanel_debug_log('[Laico SMS] Customer SMS exception: ' . $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $entry
     * @return array<string, string>
     */
    private function buildPlaceholderMap(array $entry): array
    {
        $phoneE164 = (string) ($entry['phone'] ?? '');
        $phoneLocal = self::toLocalIranMobile($phoneE164);
        $message = trim(preg_replace('/\s+/u', ' ', (string) ($entry['message'] ?? '')) ?: '');
        if (mb_strlen($message) > 120) {
            $message = mb_substr($message, 0, 117) . '...';
        }

        return [
            'id' => (string) ($entry['id'] ?? ''),
            'name' => (string) ($entry['full_name'] ?? ''),
            'full_name' => (string) ($entry['full_name'] ?? ''),
            'phone' => $phoneLocal !== '' ? $phoneLocal : $phoneE164,
            'phone_e164' => $phoneE164,
            'phone_local' => $phoneLocal !== '' ? $phoneLocal : $phoneE164,
            'project_type' => (string) ($entry['project_type'] ?? 'مشاوره'),
            'type' => (string) ($entry['project_type'] ?? 'مشاوره'),
            'message' => $message,
            'note' => $message,
            'source_page' => (string) (($entry['source_page'] ?? '') ?: 'وب‌سایت'),
            'page' => (string) (($entry['source_page'] ?? '') ?: 'وب‌سایت'),
            'date' => Jalali::fromTimestamp(time()),
            'site_name' => (string) (get_bloginfo('name') ?: 'لایکو دیزاین'),
        ];
    }

    /**
     * Parse lines like `name={name}` into associative array `['name' => 'علی رضایی']` for IPPanel Pattern SMS.
     *
     * @param array<string, string> $context
     * @return array<string, string>
     */
    private function buildPatternVariables(string $rawMapping, array $context): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($rawMapping)) ?: [];
        $vars = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }
            $varKey = trim($parts[0]);
            $tplVal = trim($parts[1]);
            if ($varKey === '') {
                continue;
            }
            $vars[$varKey] = $this->interpolateText($tplVal, $context);
        }

        if (empty($vars)) {
            $vars['name'] = $context['name'] ?? '';
        }

        return $vars;
    }

    /**
     * Replace `{placeholder}` tokens in manual SMS text or pattern variable templates.
     *
     * @param array<string, string> $context
     */
    private function interpolateText(string $template, array $context): string
    {
        $replacements = [];
        foreach ($context as $k => $v) {
            $replacements['{' . $k . '}'] = $v;
            $replacements['%' . $k . '%'] = $v;
        }

        return strtr($template, $replacements);
    }

    /**
     * @return array<int, string>
     */
    public static function parsePhoneList(string $raw): array
    {
        $chunks = preg_split('/[\r\n,،;]+/u', $raw) ?: [];
        $phones = [];
        foreach ($chunks as $chunk) {
            $normalized = self::toLocalIranMobile($chunk);
            if ($normalized !== '') {
                $phones[] = $normalized;
            }
        }
        return array_values(array_unique($phones));
    }

    public static function toLocalIranMobile(string $raw): string
    {
        $english = Jalali::toEnglishDigits($raw);
        $digits = preg_replace('/\D+/', '', $english) ?: '';
        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '0098')) {
            $digits = '0' . substr($digits, 4);
        } elseif (str_starts_with($digits, '98') && strlen($digits) >= 11) {
            $digits = '0' . substr($digits, 2);
        } elseif (! str_starts_with($digits, '0') && strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0' . $digits;
        }

        return strlen($digits) >= 10 ? $digits : '';
    }

    private static function normalizeIntegrationCode(string $code): string
    {
        $code = strtoupper(trim($code));
        $code = (string) preg_replace('/[^A-Z0-9\-_]/', '-', $code);
        return trim($code, '-_');
    }

    /**
     * Status snapshot for Theme Settings admin UI (`page=laico-settings&tab=forms`).
     *
     * @return array<string, mixed>
     */
    public static function statusSummary(): array
    {
        return [
            'plugin_active' => self::isCorePluginLoaded(),
            'plugin_version' => defined('IPPANEL_CORE_VERSION') ? (string) constant('IPPANEL_CORE_VERSION') : '',
            'has_token' => self::hasToken(),
            'slug' => self::SLUG,
            'integration_code' => self::INTEGRATION_CODE,
            'callback_url' => home_url('/wp-json/' . self::REST_NAMESPACE . '/receive-key'),
            'panel_url' => admin_url('admin.php?page=ippanel-settings'),
        ];
    }
}
