<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\ThemeKernel;
use Illuminate\Support\Facades\Vite;
use Roots\Acorn\Sage\SageServiceProvider;

/**
 * Primary Roots Acorn / Sage Service Provider for Laico Design.
 */
final class ThemeServiceProvider extends SageServiceProvider
{
    public function register(): void
    {
        parent::register();
    }

    public function boot(): void
    {
        parent::boot();

        if (class_exists(Vite::class) && method_exists(Vite::class, 'useBuildDirectory')) {
            Vite::useBuildDirectory('bundle');
        }

        ThemeKernel::boot();
    }
}
