<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Unified Security & Captcha Integration Service for Laico Sage.
 *
 * Integrates seamlessly with:
 * - Novin Security Suite (`NSH_Plugin`, `NSH_Logger`, `NSH_IP_Block`, `NSH_Rest_Guard`, `NSH_Stealth`, `NSH_Settings`)
 * - WP Math Captcha (`WP_MC_Captcha`, `WP_MC_Core`)
 */
final class SecurityIntegrationService
{
    public function register(): void
    {
        // Allow verified public theme REST endpoints when Novin Security's `rest_require_auth` is enabled
        add_filter('rest_authentication_errors', [$this, 'allowVerifiedThemeRestRoutes'], 95);
    }

    /**
     * Whether Novin Security Suite is installed and active.
     */
    public static function isNovinSecurityActive(): bool
    {
        return class_exists('NSH_Plugin') && class_exists('NSH_Settings') && class_exists('NSH_Logger');
    }

    /**
     * Whether Novin Security Suite is running in emergency `NSH_SAFE_MODE`.
     */
    public static function isNovinSafeMode(): bool
    {
        return defined('NSH_SAFE_MODE') && (bool) constant('NSH_SAFE_MODE');
    }

    /**
     * Whether Novin Security Suite is actively sending HTTP security headers.
     */
    public static function novinHandlesSecurityHeaders(): bool
    {
        if (! self::isNovinSecurityActive() || self::isNovinSafeMode()) {
            return false;
        }

        return method_exists('NSH_Settings', 'enabled') && (bool) \NSH_Settings::enabled('security_headers');
    }

    /**
     * Whether WP Math Captcha is active and enabled on the site.
     */
    public static function isMathCaptchaActive(): bool
    {
        if (! class_exists('WP_MC_Captcha')) {
            return false;
        }

        if (class_exists('WP_MC_Core') && method_exists('WP_MC_Core', 'get')) {
            return (bool) \WP_MC_Core::get('enabled', 1);
        }

        return true;
    }

    /**
     * Render WP Math Captcha HTML inside a custom theme `<form>` when enabled.
     */
    public static function renderMathCaptcha(array $opts = []): string
    {
        if (! self::isMathCaptchaActive()) {
            return '';
        }

        return (string) \WP_MC_Captcha::output($opts);
    }

    /**
     * Verify WP Math Captcha submission server-side (fail-closed when plugin is active).
     *
     * @param array<string, mixed> $input
     * @return array{ok: bool, code: string, message: string, refresh: bool}
     */
    public static function verifyMathCaptcha(array $input, string $context = 'laico-consult-form'): array
    {
        if (! self::isMathCaptchaActive()) {
            return ['ok' => true, 'code' => '', 'message' => '', 'refresh' => false];
        }

        $answer = isset($input['wpmc_answer']) ? (string) $input['wpmc_answer'] : '';
        $token = isset($input['wpmc_token']) ? (string) $input['wpmc_token'] : '';
        $honeypot = isset($input['wpmc_website']) ? (string) $input['wpmc_website'] : '';

        $result = \WP_MC_Captcha::verify($answer, $token, $honeypot, $context);
        if (! is_wp_error($result)) {
            return ['ok' => true, 'code' => '', 'message' => '', 'refresh' => false];
        }

        $code = (string) $result->get_error_code();
        $message = (string) $result->get_error_message();

        if (in_array($code, ['honeypot', 'ip_locked', 'bot_ua', 'bad_token', 'wrong_final'], true)) {
            self::logSecurityEvent(
                $code === 'honeypot' ? 'comment_spam' : 'suspicious_request',
                $code === 'ip_locked' ? 'warning' : 'notice',
                sprintf('رد کپچای ریاضی در فرم قالب (%s)', $code),
                ['context' => $context, 'wpmc_code' => $code],
                $code === 'honeypot'
            );
        }

        return [
            'ok' => false,
            'code' => $code,
            'message' => $message,
            'refresh' => in_array($code, ['expired', 'bad_token', 'wrong_final'], true),
        ];
    }

    /**
     * Resolve client IP via Novin Security Suite (`nsh_client_ip` filter), WP Math Captcha, or `REMOTE_ADDR`.
     */
    public static function clientIp(): string
    {
        if (self::isNovinSecurityActive() && method_exists('NSH_Logger', 'client_ip')) {
            $nshIp = (string) \NSH_Logger::client_ip();
            if ($nshIp !== '' && filter_var($nshIp, FILTER_VALIDATE_IP)) {
                return $nshIp;
            }
        }

        if (class_exists('WP_MC_Core') && method_exists('WP_MC_Core', 'ip')) {
            $wpmcIp = (string) \WP_MC_Core::ip();
            if ($wpmcIp !== '' && filter_var($wpmcIp, FILTER_VALIDATE_IP)) {
                return $wpmcIp;
            }
        }

        $remote = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash((string) $_SERVER['REMOTE_ADDR'])) : '';
        return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '0.0.0.0';
    }

    /**
     * Check whether the current IP is blocked by Novin Security Suite or WP Math Captcha lockout.
     */
    public static function isIpBlocked(?string $ip = null): bool
    {
        $targetIp = $ip ?? self::clientIp();
        if ($targetIp === '' || $targetIp === '0.0.0.0') {
            return false;
        }

        if (self::isNovinSecurityActive() && ! self::isNovinSafeMode() && class_exists('NSH_IP_Block') && method_exists('NSH_IP_Block', 'is_blocked')) {
            if (\NSH_IP_Block::is_blocked($targetIp)) {
                return true;
            }
        }

        if (class_exists('WP_MC_Captcha') && method_exists('WP_MC_Captcha', 'is_ip_locked')) {
            if (\WP_MC_Captcha::is_ip_locked($targetIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Record a security event into Novin Security Suite's `nsh_events` monitor table,
     * optionally counting toward automatic IP lockout (`NSH_IP_Block::maybe_autoblock`).
     *
     * @param array<string, mixed> $context
     */
    public static function logSecurityEvent(
        string $type,
        string $severity,
        string $message,
        array $context = [],
        bool $countForAutoBlock = false
    ): void {
        if (! self::isNovinSecurityActive() || ! method_exists('NSH_Logger', 'log')) {
            return;
        }

        \NSH_Logger::log($type, $severity, $message, $context);

        if ($countForAutoBlock && ! self::isNovinSafeMode() && class_exists('NSH_IP_Block') && method_exists('NSH_IP_Block', 'maybe_autoblock')) {
            \NSH_IP_Block::maybe_autoblock([
                'type' => $type,
                'ip' => self::clientIp(),
            ]);
        }
    }

    /**
     * When Novin Security Suite's `rest_require_auth` is enabled, keep only Laico's
     * nonce/captcha-verified form endpoint and HMAC-signed IPPanel key receiver reachable.
     *
     * @param mixed $result
     * @return mixed
     */
    public function allowVerifiedThemeRestRoutes(mixed $result): mixed
    {
        if (! ($result instanceof \WP_Error) || $result->get_error_code() !== 'nsh_rest_auth_required') {
            return $result;
        }

        if (self::isIpBlocked()) {
            return $result;
        }

        $route = $this->currentRestRoute();
        $allowedPublicRoutes = [
            '/laico/v1/submissions',
            '/laico-theme/v1/receive-key',
        ];

        foreach ($allowedPublicRoutes as $allowed) {
            if ($route === $allowed || str_starts_with($route, $allowed . '/')) {
                return null;
            }
        }

        return $result;
    }

    private function currentRestRoute(): string
    {
        if (isset($GLOBALS['wp']->query_vars['rest_route']) && is_string($GLOBALS['wp']->query_vars['rest_route'])) {
            return '/' . ltrim($GLOBALS['wp']->query_vars['rest_route'], '/');
        }

        if (isset($_GET['rest_route']) && is_string($_GET['rest_route'])) {
            return '/' . ltrim(sanitize_text_field(wp_unslash($_GET['rest_route'])), '/');
        }

        $reqUri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        $path = (string) wp_parse_url($reqUri, PHP_URL_PATH);
        $prefix = '/' . trim(rest_get_url_prefix(), '/') . '/';
        $pos = strpos($path, $prefix);
        if ($pos !== false) {
            return '/' . ltrim(substr($path, $pos + strlen($prefix)), '/');
        }

        return '';
    }

    /**
     * Status snapshot for Theme Settings admin UI (`page=laico-settings&tab=forms`).
     *
     * @return array<string, mixed>
     */
    public static function statusSummary(): array
    {
        return [
            'novin_active' => self::isNovinSecurityActive(),
            'novin_version' => defined('NSH_VERSION') ? (string) constant('NSH_VERSION') : '',
            'novin_safe_mode' => self::isNovinSafeMode(),
            'novin_url' => admin_url('admin.php?page=novin-security'),
            'captcha_active' => self::isMathCaptchaActive(),
            'captcha_installed' => class_exists('WP_MC_Captcha'),
            'captcha_version' => defined('WPMC_VERSION') ? (string) constant('WPMC_VERSION') : '',
            'captcha_url' => admin_url('admin.php?page=wp-math-captcha'),
        ];
    }
}
