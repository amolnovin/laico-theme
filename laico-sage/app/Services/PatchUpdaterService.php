<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Secure, Atomic Theme Patch Updater Service for Laico Sage.
 *
 * Capabilities & Safety Guarantees:
 * 1. Dual Format Support:
 *    - Structured `.zip` patch packages with `patch.json` manifest + per-file SHA-256 checksums.
 *    - Standard unified diff `.patch` / `.diff` files (pure-PHP parser & hunk verifier — no `exec()` required).
 * 2. Cryptographic Verification:
 *    - Ed25519 detached signature verification (`sodium_crypto_sign_verify_detached`) and/or HMAC-SHA256.
 * 3. Path Traversal, Symlink & File-Type Hardening:
 *    - Blocks `..`, null bytes, absolute paths, symlinks, `.git`, `.env`, `vendor`, `.htaccess`, and non-theme extensions.
 * 4. Zero-WSOD PHP Syntax Guard:
 *    - Parses every patched `.php` file in memory via `token_get_all($code, TOKEN_PARSE)` before touching disk.
 * 5. Pre-flight Dry-Run, Protected Automatic Backup, Atomic Write & 1-Click Rollback:
 *    - Creates HTTP-protected rollback snapshots in `uploads/laico-patches/backups/`, writes via temp+rename,
 *      flushes OPcache & Acorn compiled Blade views, and allows instant 1-click rollback.
 */
final class PatchUpdaterService
{
    public const HISTORY_OPTION = 'laico_patch_history';
    public const STAGED_TRANSIENT_PREFIX = 'laico_staged_patch_';

    private const ALLOWED_EXTENSIONS = [
        'php', 'css', 'js', 'mjs', 'json', 'svg', 'png', 'jpg', 'jpeg',
        'webp', 'woff2', 'mp4', 'po', 'mo', 'pot', 'md', 'txt',
    ];

    private const FORBIDDEN_PREFIXES = [
        '.git/', '.github/', '.env', 'vendor/', 'node_modules/',
        '.htaccess', '.user.ini', 'php.ini', 'wp-config.php',
    ];

    /**
     * Check whether the current environment & user are allowed to apply theme patches.
     *
     * @return array{allowed: bool, reason: string}
     */
    public static function checkEnvironmentPermissions(): array
    {
        if (! current_user_can('update_themes') || ! current_user_can('manage_options')) {
            return ['allowed' => false, 'reason' => 'دسترسی کافی برای بروزرسانی قالب وجود ندارد (نیازمند سطح دسترسی مدیرکل و مجوز update_themes).'];
        }

        if (function_exists('wp_is_file_mod_allowed') && ! wp_is_file_mod_allowed('laico_patch_updater')) {
            return ['allowed' => false, 'reason' => 'ویرایش فایل‌ها در تنظیمات امنیتی وردپرس (DISALLOW_FILE_MODS) غیرفعال شده است.'];
        }

        if (! is_writable(LAICO_THEME_DIR)) {
            return ['allowed' => false, 'reason' => 'پوشهٔ قالب در سرور دسترسی نوشتن (Write Permission) ندارد.'];
        }

        return ['allowed' => true, 'reason' => 'آمادهٔ نصب پچ'];
    }

    /**
     * Ensure protected storage directory under wp-content/uploads/laico-patches/{staging,backups} exists
     * and cannot be listed or downloaded directly via HTTP.
     */
    public static function ensureProtectedStorageDir(string $sub = 'backups'): string
    {
        $uploads = wp_upload_dir();
        $baseDir = trailingslashit((string) $uploads['basedir']) . 'laico-patches';
        $targetDir = $baseDir . '/' . trim($sub, '/');

        if (! is_dir($baseDir)) {
            wp_mkdir_p($baseDir);
        }
        if (! is_dir($targetDir)) {
            wp_mkdir_p($targetDir);
        }

        $htaccess = $baseDir . '/.htaccess';
        if (! is_file($htaccess)) {
            @file_put_contents(
                $htaccess,
                "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Order deny,allow\n  Deny from all\n</IfModule>\n"
            );
        }

        $webConfig = $baseDir . '/web.config';
        if (! is_file($webConfig)) {
            @file_put_contents(
                $webConfig,
                "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration><system.webServer><authorization><deny users=\"*\" /></authorization></system.webServer></configuration>\n"
            );
        }

        foreach ([$baseDir, $targetDir] as $dir) {
            $indexFile = $dir . '/index.php';
            if (! is_file($indexFile)) {
                @file_put_contents($indexFile, "<?php\n// Silence is golden.\n");
            }
        }

        return $targetDir;
    }

    /**
     * Stage and Dry-Run a patch file (`.zip`, `.patch`, or `.diff`) without modifying any theme file.
     * Returns a detailed pre-flight report + a short-lived staging token for 1-click confirmation.
     *
     * @return array<string, mixed>
     */
    public function stageAndAnalyzePatch(string $uploadedFilePath, string $originalFilename): array
    {
        $perm = self::checkEnvironmentPermissions();
        if (! $perm['allowed']) {
            return ['ok' => false, 'error' => $perm['reason']];
        }

        if (! is_file($uploadedFilePath) || ! is_readable($uploadedFilePath)) {
            return ['ok' => false, 'error' => 'فایل پچ قابل خواندن نیست.'];
        }

        $ext = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        if (! in_array($ext, ['zip', 'patch', 'diff'], true)) {
            return ['ok' => false, 'error' => 'فرمت فایل نامعتبر است. فقط بسته‌های .zip یا فایل‌های .patch / .diff مجاز هستند.'];
        }

        if (filesize($uploadedFilePath) > 32 * 1024 * 1024) {
            return ['ok' => false, 'error' => 'حجم بستهٔ پچ نباید بیشتر از ۳۲ مگابایت باشد.'];
        }

        $plan = $ext === 'zip'
            ? $this->analyzeZipPatch($uploadedFilePath, $originalFilename)
            : $this->analyzeUnifiedDiffPatch($uploadedFilePath, $originalFilename);

        if (! ($plan['ok'] ?? false)) {
            return $plan;
        }

        // Validate every target path, symlink safety, write permissions & PHP syntax in memory
        $validationError = $this->validateOperationsPlan($plan['operations'], $plan['deleted_files']);
        if ($validationError !== null) {
            return ['ok' => false, 'error' => $validationError];
        }

        // Persist staged bundle in protected staging directory for 15 minutes
        $stagingDir = self::ensureProtectedStorageDir('staging');
        $token = wp_generate_password(24, false, false);
        $stagedJsonPath = $stagingDir . '/staged-' . $token . '.json';

        $encodedOperations = [];
        foreach ($plan['operations'] as $relPath => $content) {
            $encodedOperations[$relPath] = [
                'action' => is_file(LAICO_THEME_DIR . '/' . $relPath) ? 'modify' : 'add',
                'size' => strlen($content),
                'sha256' => hash('sha256', $content),
                'content_b64' => base64_encode($content),
            ];
        }

        $stagedPayload = [
            'token' => $token,
            'created_by' => get_current_user_id(),
            'created_at' => time(),
            'filename' => sanitize_file_name($originalFilename),
            'format' => $ext,
            'meta' => $plan['meta'],
            'signature_verified' => $plan['signature_verified'],
            'operations' => $encodedOperations,
            'deleted_files' => array_values($plan['deleted_files']),
        ];

        if (file_put_contents($stagedJsonPath, wp_json_encode($stagedPayload, JSON_UNESCAPED_UNICODE)) === false) {
            return ['ok' => false, 'error' => 'خطا در ایجاد فایل موقت بررسی پچ در سرور.'];
        }

        set_transient(self::STAGED_TRANSIENT_PREFIX . $token, $stagedJsonPath, 15 * MINUTE_IN_SECONDS);

        $summaryFiles = [];
        foreach ($encodedOperations as $relPath => $info) {
            $summaryFiles[] = [
                'path' => $relPath,
                'action' => $info['action'],
                'size' => $info['size'],
                'sha256' => substr($info['sha256'], 0, 12),
            ];
        }
        foreach ($plan['deleted_files'] as $delPath) {
            $summaryFiles[] = [
                'path' => $delPath,
                'action' => 'delete',
                'size' => 0,
                'sha256' => '—',
            ];
        }

        return [
            'ok' => true,
            'token' => $token,
            'meta' => $plan['meta'],
            'signature_verified' => $plan['signature_verified'],
            'files' => $summaryFiles,
            'counts' => [
                'total' => count($summaryFiles),
                'modified' => count(array_filter($summaryFiles, static fn (array $f): bool => $f['action'] === 'modify')),
                'added' => count(array_filter($summaryFiles, static fn (array $f): bool => $f['action'] === 'add')),
                'deleted' => count($plan['deleted_files']),
            ],
        ];
    }

    /**
     * Apply a previously staged & dry-run-verified patch atomically, creating a rollback backup first.
     *
     * @return array<string, mixed>
     */
    public function applyStagedPatch(string $token): array
    {
        $perm = self::checkEnvironmentPermissions();
        if (! $perm['allowed']) {
            return ['ok' => false, 'error' => $perm['reason']];
        }

        $cleanToken = preg_replace('/[^a-zA-Z0-9]/', '', $token) ?: '';
        $stagedPath = (string) get_transient(self::STAGED_TRANSIENT_PREFIX . $cleanToken);
        if ($stagedPath === '' || ! is_file($stagedPath)) {
            return ['ok' => false, 'error' => 'نشست بررسی پچ منقضی شده است. لطفاً فایل پچ را مجدداً بارگذاری و بررسی کنید.'];
        }

        $raw = file_get_contents($stagedPath);
        $staged = is_string($raw) ? json_decode($raw, true) : null;
        @unlink($stagedPath);
        delete_transient(self::STAGED_TRANSIENT_PREFIX . $cleanToken);

        if (! is_array($staged) || (int) ($staged['created_by'] ?? 0) !== get_current_user_id()) {
            return ['ok' => false, 'error' => 'اطلاعات بستهٔ آمادهٔ نصب معتبر نیست.'];
        }

        /** @var array<string, string> $operations */
        $operations = [];
        foreach (($staged['operations'] ?? []) as $relPath => $op) {
            $decoded = base64_decode((string) ($op['content_b64'] ?? ''), true);
            if ($decoded === false || hash('sha256', $decoded) !== ($op['sha256'] ?? '')) {
                return ['ok' => false, 'error' => "عدم تطابق هش فایل آمادهٔ نصب: {$relPath}"];
            }
            $operations[(string) $relPath] = $decoded;
        }

        /** @var array<int, string> $deletedFiles */
        $deletedFiles = array_values(array_map('strval', $staged['deleted_files'] ?? []));

        // Re-validate immediately before writing (guards against TOCTOU race conditions)
        $validationError = $this->validateOperationsPlan($operations, $deletedFiles);
        if ($validationError !== null) {
            return ['ok' => false, 'error' => $validationError];
        }

        // 1. Create automatic backup snapshot if enabled (default: true)
        $backupFile = '';
        $patchId = sanitize_key((string) ($staged['meta']['id'] ?? 'patch-' . gmdate('YmdHis')));
        if ((bool) ThemeOptions::get('patch_auto_backup', true)) {
            $backupResult = $this->createRollbackBackup($patchId, array_keys($operations), $deletedFiles);
            if (! $backupResult['ok']) {
                return ['ok' => false, 'error' => 'عملیات نصب پچ متوقف شد؛ خطا در تهیهٔ نسخه پشتیبان خودکار: ' . $backupResult['error']];
            }
            $backupFile = (string) $backupResult['backup_file'];
        }

        // 2. Apply file writes atomically (write to temp file in same directory -> rename)
        $writtenPaths = [];
        try {
            foreach ($operations as $relPath => $content) {
                $absPath = LAICO_THEME_DIR . '/' . $relPath;
                $parentDir = dirname($absPath);
                if (! is_dir($parentDir) && ! wp_mkdir_p($parentDir)) {
                    throw new \RuntimeException("امکان ایجاد پوشهٔ مقصد وجود ندارد: {$relPath}");
                }

                $tmpFile = $parentDir . '/.laico-tmp-' . wp_generate_password(12, false, false);
                if (file_put_contents($tmpFile, $content, LOCK_EX) === false) {
                    throw new \RuntimeException("خطا در نوشتن فایل موقت: {$relPath}");
                }

                $existingPerms = is_file($absPath) ? (fileperms($absPath) & 0777) : 0644;
                @chmod($tmpFile, $existingPerms ?: 0644);

                if (! @rename($tmpFile, $absPath)) {
                    @unlink($tmpFile);
                    throw new \RuntimeException("خطا در جایگزینی اتمیک فایل: {$relPath}");
                }

                $writtenPaths[] = $absPath;
            }

            foreach ($deletedFiles as $delRel) {
                $absDel = LAICO_THEME_DIR . '/' . $delRel;
                if (is_file($absDel) && ! @unlink($absDel)) {
                    throw new \RuntimeException("خطا در حذف فایل قدیمی: {$delRel}");
                }
            }

            // Update Version header in style.css if `to_version` was provided in patch.json and style.css wasn't already in operations
            $toVersion = trim((string) ($staged['meta']['to_version'] ?? ''));
            if ($toVersion !== '' && ! isset($operations['style.css']) && preg_match('/^\d+\.\d+\.\d+(?:-[a-zA-Z0-9._-]+)?$/', $toVersion)) {
                $this->bumpStyleCssVersion($toVersion);
                $writtenPaths[] = LAICO_THEME_DIR . '/style.css';
            }
        } catch (\Throwable $e) {
            // Automatic self-healing rollback if an I/O failure occurred mid-apply!
            if ($backupFile !== '') {
                $this->restoreFromBackupArchive($backupFile);
            }
            return ['ok' => false, 'error' => 'خطا حین اعمال پچ (تغییرات به نسخهٔ قبل بازگردانی شد): ' . $e->getMessage()];
        }

        // 3. Flush OPcache, Acorn Blade view cache, and WP caches
        $this->flushRuntimeCaches($writtenPaths);

        // 4. Record in patch audit history
        $historyRecord = [
            'record_id' => wp_generate_password(12, false, false),
            'patch_id' => $patchId,
            'filename' => (string) ($staged['filename'] ?? ''),
            'from_version' => self::currentThemeVersion(),
            'to_version' => ($staged['meta']['to_version'] ?? '') ?: self::currentThemeVersion(),
            'description' => (string) ($staged['meta']['description'] ?? ''),
            'files_count' => count($operations) + count($deletedFiles),
            'signature_verified' => (bool) ($staged['signature_verified'] ?? false),
            'backup_file' => $backupFile !== '' ? basename($backupFile) : '',
            'applied_at' => current_time('mysql'),
            'applied_by' => wp_get_current_user()->user_login ?: 'admin',
            'rolled_back' => false,
        ];

        $history = self::getHistory();
        array_unshift($history, $historyRecord);
        update_option(self::HISTORY_OPTION, array_slice($history, 0, 50), false);

        return [
            'ok' => true,
            'record' => $historyRecord,
        ];
    }

    /**
     * Roll back a previously applied patch using its protected backup ZIP.
     *
     * @return array{ok: bool, error?: string, message?: string}
     */
    public function rollbackPatch(string $recordId): array
    {
        $perm = self::checkEnvironmentPermissions();
        if (! $perm['allowed']) {
            return ['ok' => false, 'error' => $perm['reason']];
        }

        $history = self::getHistory();
        $targetIdx = null;
        foreach ($history as $idx => $item) {
            if (($item['record_id'] ?? '') === $recordId) {
                $targetIdx = $idx;
                break;
            }
        }

        if ($targetIdx === null) {
            return ['ok' => false, 'error' => 'رکورد پچ مورد نظر یافت نشد.'];
        }

        $backupName = basename((string) ($history[$targetIdx]['backup_file'] ?? ''));
        if ($backupName === '') {
            return ['ok' => false, 'error' => 'برای این پچ فایل پشتیبان ثبت نشده است.'];
        }

        $backupPath = self::ensureProtectedStorageDir('backups') . '/' . $backupName;
        if (! is_file($backupPath)) {
            return ['ok' => false, 'error' => 'فایل پشتیبان روی سرور یافت نشد.'];
        }

        $restore = $this->restoreFromBackupArchive($backupPath);
        if (! $restore['ok']) {
            return $restore;
        }

        $history[$targetIdx]['rolled_back'] = true;
        $history[$targetIdx]['rolled_back_at'] = current_time('mysql');
        update_option(self::HISTORY_OPTION, $history, false);

        return [
            'ok' => true,
            'message' => sprintf('پچ «%s» با موفقیت بازگردانی شد و فایل‌های قبلی قالب بازیابی شدند.', $history[$targetIdx]['patch_id']),
        ];
    }

    /**
     * Check remote JSON update manifest URL (if configured in Theme Settings).
     *
     * @return array<string, mixed>
     */
    public function checkRemoteManifest(): array
    {
        $manifestUrl = trim((string) ThemeOptions::get('patch_manifest_url', ''));
        if ($manifestUrl === '') {
            return ['ok' => false, 'error' => 'آدرس سرور بروزرسانی (Manifest URL) در تنظیمات وارد نشده است.'];
        }

        $response = wp_safe_remote_get($manifestUrl, ['timeout' => 10]);
        if (is_wp_error($response)) {
            return ['ok' => false, 'error' => 'خطا در اتصال به سرور بروزرسانی: ' . $response->get_error_message()];
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        if ($status !== 200) {
            return ['ok' => false, 'error' => "پاسخ نامعتبر از سرور بروزرسانی (HTTP {$status})."];
        }

        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        if (! is_array($data) || empty($data['latest_version'])) {
            return ['ok' => false, 'error' => 'ساختار JSON مانیفست بروزرسانی معتبر نیست.'];
        }

        $current = self::currentThemeVersion();
        $latest = sanitize_text_field((string) $data['latest_version']);
        $hasUpdate = version_compare($latest, $current, '>');

        return [
            'ok' => true,
            'current_version' => $current,
            'latest_version' => $latest,
            'has_update' => $hasUpdate,
            'changelog' => sanitize_textarea_field((string) ($data['changelog'] ?? '')),
            'patch_url' => isset($data['patch_url']) ? esc_url_raw((string) $data['patch_url']) : '',
        ];
    }

    /**
     * Download a remote patch archive from an HTTPS URL and run stageAndAnalyzePatch().
     *
     * @return array<string, mixed>
     */
    public function stageRemotePatchUrl(string $patchUrl): array
    {
        $perm = self::checkEnvironmentPermissions();
        if (! $perm['allowed']) {
            return ['ok' => false, 'error' => $perm['reason']];
        }

        if (! str_starts_with(strtolower($patchUrl), 'https://')) {
            return ['ok' => false, 'error' => 'برای امنیت، دانلود خودکار بستهٔ پچ فقط از طریق پروتکل امن HTTPS مجاز است.'];
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        $tmpFile = download_url($patchUrl, 20);
        if (is_wp_error($tmpFile)) {
            return ['ok' => false, 'error' => 'خطا در دریافت فایل پچ: ' . $tmpFile->get_error_message()];
        }

        $filename = basename((string) wp_parse_url($patchUrl, PHP_URL_PATH)) ?: 'remote-patch.zip';
        $result = $this->stageAndAnalyzePatch($tmpFile, $filename);
        @unlink($tmpFile);

        return $result;
    }

    /**
     * Inspect a `.zip` patch package containing `patch.json` + changed files.
     *
     * @return array<string, mixed>
     */
    private function analyzeZipPatch(string $zipPath, string $originalFilename): array
    {
        if (! class_exists(\ZipArchive::class)) {
            return ['ok' => false, 'error' => 'افزونهٔ PHP ZipArchive روی سرور فعال نیست. می‌توانید از فرمت .patch استفاده کنید.'];
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['ok' => false, 'error' => 'فایل فشردهٔ ZIP معتبر نیست یا آسیب دیده است.'];
        }

        $manifestRaw = $zip->getFromName('patch.json');
        $manifest = is_string($manifestRaw) ? json_decode($manifestRaw, true) : null;

        if (! is_array($manifest)) {
            $zip->close();
            return ['ok' => false, 'error' => 'فایل مانیفست patch.json در ریشهٔ بستهٔ ZIP یافت نشد یا ساختار JSON معتبری ندارد.'];
        }

        // Verify target theme & version constraints
        $targetTheme = (string) ($manifest['target_theme'] ?? 'laico-sage');
        if (! in_array($targetTheme, ['laico-sage', get_template()], true)) {
            $zip->close();
            return ['ok' => false, 'error' => "این پچ متعلق به قالب «{$targetTheme}» است و با قالب فعلی همخوانی ندارد."];
        }

        $currentVersion = self::currentThemeVersion();
        if (! empty($manifest['from_version']) && version_compare($currentVersion, (string) $manifest['from_version'], '!=')) {
            $zip->close();
            return ['ok' => false, 'error' => sprintf('این پچ مخصوص نسخهٔ %s است، اما نسخهٔ فعلی قالب %s می‌باشد.', $manifest['from_version'], $currentVersion)];
        }
        if (! empty($manifest['min_version']) && version_compare($currentVersion, (string) $manifest['min_version'], '<')) {
            $zip->close();
            return ['ok' => false, 'error' => sprintf('نصب این پچ نیازمند حداقل نسخهٔ %s قالب است (نسخهٔ فعلی: %s).', $manifest['min_version'], $currentVersion)];
        }

        // Verify cryptographic signature when provided or required
        $requireSig = (bool) ThemeOptions::get('patch_require_signature', false);
        $sigResult = $this->verifyManifestSignature($manifest);
        if ($requireSig && ! $sigResult['verified']) {
            $zip->close();
            return ['ok' => false, 'error' => 'امضای دیجیتال پچ معتبر نیست یا تنظیم نشده است: ' . $sigResult['reason']];
        }

        // Extract files declared in patch.json (or inside `files/` subfolder in zip)
        $declaredFiles = isset($manifest['files']) && is_array($manifest['files']) ? $manifest['files'] : [];
        $deletedFiles = isset($manifest['deleted_files']) && is_array($manifest['deleted_files'])
            ? array_values(array_map('strval', $manifest['deleted_files']))
            : [];

        if (empty($declaredFiles) && empty($deletedFiles)) {
            $zip->close();
            return ['ok' => false, 'error' => 'هیچ فایلی برای بروزرسانی در patch.json تعریف نشده است.'];
        }

        $operations = [];
        foreach ($declaredFiles as $relPath => $spec) {
            $normPath = self::normalizeRelativePath((string) $relPath);
            if ($normPath === null) {
                $zip->close();
                return ['ok' => false, 'error' => "مسیر فایل غیرمجاز در پچ: {$relPath}"];
            }

            $entryContent = $zip->getFromName('files/' . $normPath);
            if ($entryContent === false) {
                $entryContent = $zip->getFromName($normPath);
            }
            if (! is_string($entryContent)) {
                $zip->close();
                return ['ok' => false, 'error' => "فایل اعلام‌شده در مانیفست داخل آرشیو ZIP یافت نشد: {$normPath}"];
            }

            $expectedHash = is_array($spec) ? (string) ($spec['sha256'] ?? '') : (is_string($spec) ? $spec : '');
            if ($expectedHash !== '' && ! hash_equals(strtolower($expectedHash), hash('sha256', $entryContent))) {
                $zip->close();
                return ['ok' => false, 'error' => "عدم تطابق هش SHA-256 برای فایل: {$normPath}"];
            }

            if (is_array($spec) && ! empty($spec['before_sha256'])) {
                $existingAbs = LAICO_THEME_DIR . '/' . $normPath;
                if (! is_file($existingAbs) || ! hash_equals(strtolower((string) $spec['before_sha256']), hash_file('sha256', $existingAbs) ?: '')) {
                    $zip->close();
                    return ['ok' => false, 'error' => "فایل فعلی «{$normPath}» روی سرور تغییر کرده و با هش پیش‌نیاز پچ مطابقت ندارد."];
                }
            }

            $operations[$normPath] = $entryContent;
        }

        $zip->close();

        return [
            'ok' => true,
            'signature_verified' => $sigResult['verified'],
            'meta' => [
                'id' => sanitize_text_field((string) ($manifest['id'] ?? pathinfo($originalFilename, PATHINFO_FILENAME))),
                'from_version' => sanitize_text_field((string) ($manifest['from_version'] ?? $currentVersion)),
                'to_version' => sanitize_text_field((string) ($manifest['to_version'] ?? '')),
                'description' => sanitize_textarea_field((string) ($manifest['description'] ?? 'بسته بروزرسانی ZIP')),
            ],
            'operations' => $operations,
            'deleted_files' => $deletedFiles,
        ];
    }

    /**
     * Pure-PHP Unified Diff (`.patch` / `.diff`) parser & hunk dry-runner.
     * Supports modifying existing files, creating new files, and deleting files without `exec()`.
     *
     * @return array<string, mixed>
     */
    private function analyzeUnifiedDiffPatch(string $patchFilePath, string $originalFilename): array
    {
        if ((bool) ThemeOptions::get('patch_require_signature', false)) {
            return [
                'ok' => false,
                'error' => 'گزینهٔ «الزام امضای دیجیتال پچ» فعال است؛ لطفاً از بستهٔ .zip امضاشده (حاوی patch.json) استفاده کنید.',
            ];
        }

        $rawDiff = file_get_contents($patchFilePath);
        if (! is_string($rawDiff) || trim($rawDiff) === '') {
            return ['ok' => false, 'error' => 'فایل پچ خالی است.'];
        }

        $parsedFiles = self::parseUnifiedDiff($rawDiff);
        if (empty($parsedFiles)) {
            return ['ok' => false, 'error' => 'هیچ بخش تغییر معتبری با فرمت Unified Diff در فایل یافت نشد.'];
        }

        $operations = [];
        $deletedFiles = [];

        foreach ($parsedFiles as $filePatch) {
            $oldPath = $filePatch['old_path'];
            $newPath = $filePatch['new_path'];

            $isNewFile = ($oldPath === '/dev/null');
            $isDeletedFile = ($newPath === '/dev/null');

            $targetRelRaw = $isDeletedFile ? $oldPath : $newPath;
            // Strip optional `laico-sage/` prefix if diff was created from repository root
            if (str_starts_with($targetRelRaw, 'laico-sage/')) {
                $targetRelRaw = substr($targetRelRaw, strlen('laico-sage/'));
            }

            $relPath = self::normalizeRelativePath($targetRelRaw);
            if ($relPath === null) {
                return ['ok' => false, 'error' => "مسیر فایل غیرمجاز در فایل پچ: {$targetRelRaw}"];
            }

            $absTarget = LAICO_THEME_DIR . '/' . $relPath;

            if ($isDeletedFile) {
                if (! is_file($absTarget)) {
                    return ['ok' => false, 'error' => "فایل مورد نظر برای حذف در قالب وجود ندارد: {$relPath}"];
                }
                $deletedFiles[] = $relPath;
                continue;
            }

            if ($isNewFile) {
                $origLines = [];
            } else {
                if (! is_file($absTarget)) {
                    return ['ok' => false, 'error' => "فایل هدف برای اعمال پچ یافت نشد: {$relPath}"];
                }
                $origContent = (string) file_get_contents($absTarget);
                $origContent = str_replace("\r\n", "\n", $origContent);
                $origLines = $origContent === '' ? [] : explode("\n", $origContent);
                // Preserve trailing newline awareness
                if (str_ends_with($origContent, "\n")) {
                    array_pop($origLines);
                }
            }

            $applied = self::applyHunksToLines($origLines, $filePatch['hunks'], $relPath);
            if (! $applied['ok']) {
                return ['ok' => false, 'error' => $applied['error']];
            }

            $newContent = implode("\n", $applied['lines']) . ($applied['trailing_newline'] ? "\n" : '');
            $operations[$relPath] = $newContent;
        }

        return [
            'ok' => true,
            'signature_verified' => false,
            'meta' => [
                'id' => sanitize_key(pathinfo($originalFilename, PATHINFO_FILENAME)) ?: 'diff-patch',
                'from_version' => self::currentThemeVersion(),
                'to_version' => '',
                'description' => 'فایل Unified Diff (' . sanitize_file_name($originalFilename) . ')',
            ],
            'operations' => $operations,
            'deleted_files' => $deletedFiles,
        ];
    }

    /**
     * Parse unified diff text into file descriptors and hunks.
     *
     * @return array<int, array{old_path: string, new_path: string, hunks: array<int, array<string, mixed>>}>
     */
    public static function parseUnifiedDiff(string $diffText): array
    {
        $normalized = str_replace("\r\n", "\n", $diffText);
        $lines = explode("\n", $normalized);
        $count = count($lines);
        $files = [];
        $current = null;
        $currentHunk = null;

        for ($i = 0; $i < $count; $i++) {
            $line = $lines[$i];

            if (str_starts_with($line, '--- ')) {
                if ($current !== null) {
                    if ($currentHunk !== null) {
                        $current['hunks'][] = $currentHunk;
                        $currentHunk = null;
                    }
                    if (! empty($current['hunks'])) {
                        $files[] = $current;
                    }
                }

                $oldRaw = trim(substr($line, 4));
                $oldRaw = preg_split('/\t/', $oldRaw)[0] ?? $oldRaw;
                $oldPath = $oldRaw === '/dev/null' ? '/dev/null' : preg_replace('#^[ab]/#', '', $oldRaw);

                $nextLine = $lines[$i + 1] ?? '';
                if (! str_starts_with($nextLine, '+++ ')) {
                    $current = null;
                    continue;
                }
                $i++;
                $newRaw = trim(substr($nextLine, 4));
                $newRaw = preg_split('/\t/', $newRaw)[0] ?? $newRaw;
                $newPath = $newRaw === '/dev/null' ? '/dev/null' : preg_replace('#^[ab]/#', '', $newRaw);

                $current = [
                    'old_path' => (string) $oldPath,
                    'new_path' => (string) $newPath,
                    'hunks' => [],
                ];
                continue;
            }

            if ($current !== null && preg_match('/^@@\s+-(\d+)(?:,(\d+))?\s+\+(\d+)(?:,(\d+))?\s+@@/', $line, $m)) {
                if ($currentHunk !== null) {
                    $current['hunks'][] = $currentHunk;
                }
                $currentHunk = [
                    'old_start' => (int) $m[1],
                    'old_count' => isset($m[2]) && $m[2] !== '' ? (int) $m[2] : 1,
                    'new_start' => (int) $m[3],
                    'new_count' => isset($m[4]) && $m[4] !== '' ? (int) $m[4] : 1,
                    'lines' => [],
                    'no_eof_newline' => false,
                ];
                continue;
            }

            if ($currentHunk !== null) {
                if ($line === '\ No newline at end of file') {
                    $currentHunk['no_eof_newline'] = true;
                    continue;
                }
                if ($line === '' && $i === $count - 1) {
                    continue;
                }
                $prefix = $line !== '' ? $line[0] : '';
                if (in_array($prefix, [' ', '+', '-'], true)) {
                    $currentHunk['lines'][] = [$prefix, substr($line, 1)];
                } elseif (str_starts_with($line, 'diff --git ')) {
                    $current['hunks'][] = $currentHunk;
                    $currentHunk = null;
                    $files[] = $current;
                    $current = null;
                }
            }
        }

        if ($current !== null) {
            if ($currentHunk !== null) {
                $current['hunks'][] = $currentHunk;
            }
            if (! empty($current['hunks'])) {
                $files[] = $current;
            }
        }

        return $files;
    }

    /**
     * Apply parsed unified diff hunks to an array of source lines with strict context verification.
     *
     * @param array<int, string> $origLines
     * @param array<int, array<string, mixed>> $hunks
     * @return array{ok: bool, lines?: array<int, string>, trailing_newline?: bool, error?: string}
     */
    public static function applyHunksToLines(array $origLines, array $hunks, string $relPath): array
    {
        $result = [];
        $srcPos = 0; // 0-indexed cursor into $origLines
        $trailingNewline = true;

        foreach ($hunks as $hIdx => $hunk) {
            $expectedStart = max(0, ((int) $hunk['old_start']) - 1);
            if ($hunk['old_start'] === 0 && $hunk['old_count'] === 0) {
                $expectedStart = 0;
            }

            if ($expectedStart < $srcPos || $expectedStart > count($origLines)) {
                return [
                    'ok' => false,
                    'error' => sprintf('تداخل خط‌ها در فایل «%s» (بخش #%d پچ با نسخهٔ فعلی فایل همخوانی ندارد).', $relPath, $hIdx + 1),
                ];
            }

            // Copy unchanged lines preceding this hunk
            while ($srcPos < $expectedStart) {
                $result[] = $origLines[$srcPos];
                $srcPos++;
            }

            foreach ($hunk['lines'] as [$op, $text]) {
                if ($op === ' ') {
                    if (! array_key_exists($srcPos, $origLines) || $origLines[$srcPos] !== $text) {
                        return [
                            'ok' => false,
                            'error' => sprintf('عدم تطابق متن مرجع در فایل «%s» پیرامون خط %d.', $relPath, $srcPos + 1),
                        ];
                    }
                    $result[] = $text;
                    $srcPos++;
                } elseif ($op === '-') {
                    if (! array_key_exists($srcPos, $origLines) || $origLines[$srcPos] !== $text) {
                        return [
                            'ok' => false,
                            'error' => sprintf('تداخل در حذف خط %d فایل «%s»: محتوای فایل تغییر کرده است.', $srcPos + 1, $relPath),
                        ];
                    }
                    $srcPos++;
                } elseif ($op === '+') {
                    $result[] = $text;
                }
            }

            if (! empty($hunk['no_eof_newline'])) {
                $trailingNewline = false;
            }
        }

        // Copy remaining tail of original file
        while ($srcPos < count($origLines)) {
            $result[] = $origLines[$srcPos];
            $srcPos++;
        }

        return [
            'ok' => true,
            'lines' => $result,
            'trailing_newline' => $trailingNewline,
        ];
    }

    /**
     * Validate paths, extensions, symlinks, write permissions, and PHP syntax for all planned operations.
     *
     * @param array<string, string> $operations
     * @param array<int, string> $deletedFiles
     */
    private function validateOperationsPlan(array $operations, array $deletedFiles): ?string
    {
        $allPaths = array_unique(array_merge(array_keys($operations), $deletedFiles));

        foreach ($allPaths as $rawPath) {
            $norm = self::normalizeRelativePath($rawPath);
            if ($norm === null || $norm !== $rawPath) {
                return "مسیر فایل غیرمجاز یا ناامن تشخیص داده شد: {$rawPath}";
            }

            if (! self::assertNoSymlinksInChain($norm)) {
                return "مسیر دارای پیوند نمادین (Symlink) غیرمجاز است: {$norm}";
            }

            $absPath = LAICO_THEME_DIR . '/' . $norm;
            if (is_file($absPath) && ! is_writable($absPath)) {
                return "فایل مقصد در سرور دسترسی نوشتن ندارد: {$norm}";
            }
        }

        // In-memory PHP syntax validation (`token_get_all` with `TOKEN_PARSE`) for pure PHP files
        foreach ($operations as $relPath => $content) {
            if (str_ends_with(strtolower($relPath), '.php') && ! str_ends_with(strtolower($relPath), '.blade.php')) {
                $syntaxError = self::validatePhpSyntax($content);
                if ($syntaxError !== null) {
                    return sprintf('خطای نحوی (Syntax Error) در فایل PHP «%s»: %s', $relPath, $syntaxError);
                }
            }
        }

        return null;
    }

    /**
     * Normalize and validate a relative path inside the theme root.
     * Returns null if path traversal, forbidden prefix, or disallowed extension is detected.
     */
    public static function normalizeRelativePath(string $path): ?string
    {
        if ($path === '' || str_contains($path, "\0")) {
            return null;
        }

        $clean = str_replace('\\', '/', trim($path));
        if (str_starts_with($clean, '/') || preg_match('/^[a-zA-Z]:/', $clean)) {
            return null;
        }

        $segments = explode('/', $clean);
        $safeSegments = [];
        foreach ($segments as $seg) {
            if ($seg === '' || $seg === '.' || $seg === '..') {
                return null;
            }
            $safeSegments[] = $seg;
        }

        $normalized = implode('/', $safeSegments);
        $lower = strtolower($normalized);

        foreach (self::FORBIDDEN_PREFIXES as $forbidden) {
            if ($lower === rtrim($forbidden, '/') || str_starts_with($lower, $forbidden)) {
                return null;
            }
        }

        // Check file extension against allowlist
        $ext = strtolower(pathinfo($normalized, PATHINFO_EXTENSION));
        if (! in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return null;
        }

        return $normalized;
    }

    /**
     * Ensure no component of `$relPath` inside `LAICO_THEME_DIR` is a symbolic link,
     * and verify resolved existing parent stays strictly inside `realpath(LAICO_THEME_DIR)`.
     */
    private static function assertNoSymlinksInChain(string $relPath): bool
    {
        $themeReal = realpath(LAICO_THEME_DIR);
        if ($themeReal === false) {
            return false;
        }

        $parts = explode('/', $relPath);
        $cursor = LAICO_THEME_DIR;
        foreach ($parts as $part) {
            $cursor .= '/' . $part;
            if (is_link($cursor)) {
                return false;
            }
            if (file_exists($cursor)) {
                $real = realpath($cursor);
                if ($real === false || ! str_starts_with($real, $themeReal . DIRECTORY_SEPARATOR)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validate PHP syntax in memory without `eval()` or `exec('php -l')`.
     * Uses PHP's internal lexer/parser flag `TOKEN_PARSE`.
     */
    public static function validatePhpSyntax(string $phpSource): ?string
    {
        if (! defined('TOKEN_PARSE')) {
            return null;
        }

        try {
            token_get_all($phpSource, TOKEN_PARSE);
            return null;
        } catch (\ParseError $e) {
            return sprintf('%s (خط %d)', $e->getMessage(), $e->getLine());
        }
    }

    /**
     * Verify `patch.json` signature using Ed25519 (`sodium`) or HMAC-SHA256.
     *
     * @param array<string, mixed> $manifest
     * @return array{verified: bool, reason: string}
     */
    private function verifyManifestSignature(array $manifest): array
    {
        $signature = trim((string) ($manifest['signature'] ?? ''));
        $hmacSig = trim((string) ($manifest['hmac_sha256'] ?? ''));

        $canonicalPayload = self::canonicalManifestPayload($manifest);

        // 1. Ed25519 Public-Key Signature Check
        $pubKeyRaw = trim((string) ThemeOptions::get('patch_public_key', ''));
        if ($pubKeyRaw !== '' && $signature !== '' && function_exists('sodium_crypto_sign_verify_detached')) {
            $pubKey = strlen($pubKeyRaw) === 64 && ctype_xdigit($pubKeyRaw)
                ? hex2bin($pubKeyRaw)
                : base64_decode($pubKeyRaw, true);
            $sigBin = base64_decode($signature, true);

            if (is_string($pubKey) && strlen($pubKey) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES && is_string($sigBin) && strlen($sigBin) === SODIUM_CRYPTO_SIGN_BYTES) {
                $valid = sodium_crypto_sign_verify_detached($sigBin, $canonicalPayload, $pubKey);
                return $valid
                    ? ['verified' => true, 'reason' => 'امضای دیجیتال Ed25519 تأیید شد.']
                    : ['verified' => false, 'reason' => 'امضای دیجیتال Ed25519 نامعتبر است.'];
            }
            return ['verified' => false, 'reason' => 'فرمت کلید عمومی یا امضای Ed25519 صحیح نیست.'];
        }

        // 2. HMAC-SHA256 Shared Secret Check
        $hmacSecret = trim((string) ThemeOptions::get('patch_hmac_secret', ''));
        if ($hmacSecret !== '' && $hmacSig !== '') {
            $expected = hash_hmac('sha256', $canonicalPayload, $hmacSecret);
            return hash_equals($expected, strtolower($hmacSig))
                ? ['verified' => true, 'reason' => 'امضای HMAC-SHA256 تأیید شد.']
                : ['verified' => false, 'reason' => 'امضای HMAC-SHA256 با کلید امنیتی مطابقت ندارد.'];
        }

        return ['verified' => false, 'reason' => 'بسته فاقد امضای دیجیتال یا کلید تایید در تنظیمات است.'];
    }

    /**
     * Build deterministic JSON string of manifest fields (excluding `signature` & `hmac_sha256`) for signing.
     *
     * @param array<string, mixed> $manifest
     */
    public static function canonicalManifestPayload(array $manifest): string
    {
        unset($manifest['signature'], $manifest['hmac_sha256']);
        self::ksortRecursive($manifest);
        return (string) json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param array<mixed> $array
     */
    private static function ksortRecursive(array &$array): void
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                self::ksortRecursive($value);
            }
        }
    }

    /**
     * Create a rollback backup archive before modifying/adding/deleting theme files.
     *
     * @param array<int, string> $operationPaths
     * @param array<int, string> $deletedPaths
     * @return array{ok: bool, backup_file?: string, error?: string}
     */
    private function createRollbackBackup(string $patchId, array $operationPaths, array $deletedPaths): array
    {
        if (! class_exists(\ZipArchive::class)) {
            return ['ok' => false, 'error' => 'افزونه ZipArchive برای ساخت پشتیبان خودکار فعال نیست.'];
        }

        $backupDir = self::ensureProtectedStorageDir('backups');
        $rand = wp_generate_password(10, false, false);
        $backupPath = sprintf('%s/backup-%s-%s-%s.zip', $backupDir, $patchId, gmdate('Ymd-His'), $rand);

        $zip = new \ZipArchive();
        if ($zip->open($backupPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return ['ok' => false, 'error' => 'امکان ایجاد فایل ZIP پشتیبان وجود ندارد.'];
        }

        $newlyAddedFiles = [];
        $restorableFiles = [];

        foreach (array_unique(array_merge($operationPaths, $deletedPaths)) as $relPath) {
            $abs = LAICO_THEME_DIR . '/' . $relPath;
            if (is_file($abs)) {
                $zip->addFile($abs, 'files/' . $relPath);
                $restorableFiles[] = $relPath;
            } else {
                $newlyAddedFiles[] = $relPath;
            }
        }

        // Also always back up style.css so version bumps roll back cleanly
        if (! in_array('style.css', $restorableFiles, true) && is_file(LAICO_THEME_DIR . '/style.css')) {
            $zip->addFile(LAICO_THEME_DIR . '/style.css', 'files/style.css');
            $restorableFiles[] = 'style.css';
        }

        $meta = [
            'patch_id' => $patchId,
            'created_at' => gmdate('c'),
            'theme_version' => self::currentThemeVersion(),
            'restorable_files' => $restorableFiles,
            'added_files_to_remove' => $newlyAddedFiles,
        ];
        $zip->addFromString('backup-meta.json', (string) wp_json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $zip->close();

        return ['ok' => true, 'backup_file' => $backupPath];
    }

    /**
     * Restore theme files from a backup archive created by createRollbackBackup().
     *
     * @return array{ok: bool, error?: string}
     */
    private function restoreFromBackupArchive(string $backupZipPath): array
    {
        if (! class_exists(\ZipArchive::class)) {
            return ['ok' => false, 'error' => 'افزونه ZipArchive روی سرور فعال نیست.'];
        }

        $zip = new \ZipArchive();
        if ($zip->open($backupZipPath) !== true) {
            return ['ok' => false, 'error' => 'امکان باز کردن آرشیو پشتیبان وجود ندارد.'];
        }

        $metaRaw = $zip->getFromName('backup-meta.json');
        $meta = is_string($metaRaw) ? json_decode($metaRaw, true) : null;
        if (! is_array($meta)) {
            $zip->close();
            return ['ok' => false, 'error' => 'فرادادهٔ فایل پشتیبان (backup-meta.json) معتبر نیست.'];
        }

        $touched = [];

        foreach (($meta['restorable_files'] ?? []) as $relPath) {
            $norm = self::normalizeRelativePath((string) $relPath);
            if ($norm === null || ! self::assertNoSymlinksInChain($norm)) {
                continue;
            }
            $content = $zip->getFromName('files/' . $norm);
            if (! is_string($content)) {
                continue;
            }

            $abs = LAICO_THEME_DIR . '/' . $norm;
            wp_mkdir_p(dirname($abs));
            $tmp = dirname($abs) . '/.laico-rb-' . wp_generate_password(10, false, false);
            if (file_put_contents($tmp, $content, LOCK_EX) !== false) {
                @rename($tmp, $abs);
                $touched[] = $abs;
            }
        }

        foreach (($meta['added_files_to_remove'] ?? []) as $addedRel) {
            $norm = self::normalizeRelativePath((string) $addedRel);
            if ($norm !== null && self::assertNoSymlinksInChain($norm)) {
                $abs = LAICO_THEME_DIR . '/' . $norm;
                if (is_file($abs)) {
                    @unlink($abs);
                }
            }
        }

        $zip->close();
        $this->flushRuntimeCaches($touched);

        return ['ok' => true];
    }

    private function bumpStyleCssVersion(string $newVersion): void
    {
        $stylePath = LAICO_THEME_DIR . '/style.css';
        if (! is_file($stylePath) || ! is_writable($stylePath)) {
            return;
        }
        $css = (string) file_get_contents($stylePath);
        $updated = preg_replace('/^(Version:\s*).*$/m', '${1}' . $newVersion, $css, 1);
        if (is_string($updated) && $updated !== $css) {
            file_put_contents($stylePath, $updated, LOCK_EX);
        }
    }

    /**
     * Invalidate PHP OPcache on modified files, clear Acorn's compiled Blade views, and flush WP caches.
     *
     * @param array<int, string> $writtenAbsPaths
     */
    private function flushRuntimeCaches(array $writtenAbsPaths): void
    {
        if (function_exists('opcache_invalidate')) {
            foreach ($writtenAbsPaths as $abs) {
                if (str_ends_with($abs, '.php')) {
                    @opcache_invalidate($abs, true);
                }
            }
        }

        // Clear Acorn compiled Blade templates in wp-content/cache/acorn/framework/views
        $acornViewsDir = WP_CONTENT_DIR . '/cache/acorn/framework/views';
        if (is_dir($acornViewsDir)) {
            foreach (glob($acornViewsDir . '/*.php') ?: [] as $compiledView) {
                if (is_file($compiledView)) {
                    @unlink($compiledView);
                }
            }
        }

        wp_clean_themes_cache();
        wp_cache_flush();
    }

    public static function currentThemeVersion(): string
    {
        $theme = wp_get_theme(get_template());
        $ver = $theme->exists() ? (string) $theme->get('Version') : '';
        return $ver !== '' ? $ver : LAICO_THEME_VERSION;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getHistory(): array
    {
        $history = get_option(self::HISTORY_OPTION, []);
        return is_array($history) ? $history : [];
    }
}
