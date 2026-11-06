<?php

declare(strict_types=1);

namespace App\Support;

final class ViteAssets
{
    private const BUILD_DIR = 'public/bundle';

    /** @var array<string, mixed>|null */
    private static ?array $manifest = null;

    /**
     * Read and cache Vite manifest.json from public/bundle/manifest.json.
     *
     * @return array<string, mixed>
     */
    public static function manifest(): array
    {
        if (self::$manifest !== null) {
            return self::$manifest;
        }

        $manifestPath = LAICO_THEME_DIR . '/' . self::BUILD_DIR . '/manifest.json';
        if (! is_file($manifestPath)) {
            self::$manifest = [];
            return self::$manifest;
        }

        $raw = file_get_contents($manifestPath);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        self::$manifest = is_array($decoded) ? $decoded : [];

        return self::$manifest;
    }

    /**
     * Resolve URI for a Vite entry key (e.g. 'resources/css/app.css').
     */
    public static function assetUri(string $entry): ?string
    {
        $manifest = self::manifest();
        if (isset($manifest[$entry]['file']) && is_string($manifest[$entry]['file'])) {
            return trailingslashit(LAICO_THEME_URI) . self::BUILD_DIR . '/' . ltrim($manifest[$entry]['file'], '/');
        }

        $direct = LAICO_THEME_DIR . '/' . ltrim($entry, '/');
        if (is_file($direct)) {
            return trailingslashit(LAICO_THEME_URI) . ltrim($entry, '/');
        }

        return null;
    }

    /**
     * Resolve CSS entrypoint URI from Vite manifest.
     */
    public static function entryCss(string $entry): ?string
    {
        return self::assetUri($entry);
    }

    /**
     * Resolve JS entrypoint URI from Vite manifest.
     */
    public static function entryJs(string $entry): ?string
    {
        return self::assetUri($entry);
    }

    /**
     * Resolve public URL for an asset inside `resources/` (using hashed Vite output if available,
     * falling back to `resources/{relativePath}`). Automatically upgrades legacy `.jpg`/`.jpeg`/`.png`
     * theme image paths to `.webp` when present.
     */
    public static function assetUrl(string $relativePath): string
    {
        $clean = ltrim($relativePath, '/');
        $manifestKey = str_starts_with($clean, 'resources/') ? $clean : 'resources/' . $clean;

        if (preg_match('/\.(?:jpe?g|png)$/i', $manifestKey) === 1) {
            $webpKey = (string) preg_replace('/\.(?:jpe?g|png)$/i', '.webp', $manifestKey);
            if (is_file(LAICO_THEME_DIR . '/' . $webpKey)) {
                $manifestKey = $webpKey;
            }
        }

        $fromManifest = self::assetUri($manifestKey);
        if ($fromManifest !== null) {
            return $fromManifest;
        }

        return trailingslashit(LAICO_THEME_URI) . $manifestKey;
    }

    /**
     * Resolve file version (mtime or theme version) for cache busting.
     */
    public static function assetVersion(string $entry): string
    {
        $manifest = self::manifest();
        if (isset($manifest[$entry]['file']) && is_string($manifest[$entry]['file'])) {
            $path = LAICO_THEME_DIR . '/' . self::BUILD_DIR . '/' . ltrim($manifest[$entry]['file'], '/');
            if (is_file($path)) {
                return (string) filemtime($path);
            }
        }

        return LAICO_THEME_VERSION;
    }
}
