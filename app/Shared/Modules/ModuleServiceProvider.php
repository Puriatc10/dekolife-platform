<?php

declare(strict_types=1);

namespace App\Shared\Modules;

use Illuminate\Support\ServiceProvider;

/**
 * Base provider for every module under app/Modules.
 *
 * A module owns its routes, migrations, views and policies; the provider is the only
 * place where they are registered. Keeping this in one base class means a new module
 * cannot forget to load its migrations (the most common cause of "works locally,
 * breaks on a fresh clone").
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /** @return array<int, string> route files to load, relative to the module root */
    protected function webRoutes(): array
    {
        return ['routes/web.php'];
    }

    /** @return array<int, string> */
    protected function apiRoutes(): array
    {
        return ['routes/api.php'];
    }

    protected function loadWebRoutes(): void
    {
        foreach ($this->webRoutes() as $file) {
            $path = $this->modulePath($file);

            if (is_file($path)) {
                $this->loadRoutesFrom($path);
            }
        }
    }

    protected function loadApiRoutes(): void
    {
        foreach ($this->apiRoutes() as $file) {
            $path = $this->modulePath($file);

            if (is_file($path)) {
                $this->loadRoutesFrom($path);
            }
        }
    }

    protected function loadModuleMigrations(): void
    {
        $path = $this->modulePath('database/migrations');

        if (is_dir($path)) {
            $this->loadMigrationsFrom($path);
        }
    }

    protected function loadModuleViews(): void
    {
        $path = $this->modulePath('resources/views');

        if (is_dir($path)) {
            $this->loadViewsFrom($path, $this->moduleKey());
        }
    }

    /** Directory name of the concrete module, e.g. "Identity". */
    protected function moduleName(): string
    {
        $class = static::class;
        $segments = explode('\\', $class);

        // App\Modules\Identity\Providers\IdentityServiceProvider
        return $segments[2] ?? 'Unknown';
    }

    protected function moduleKey(): string
    {
        return strtolower($this->moduleName());
    }

    protected function modulePath(string $relative = ''): string
    {
        $base = app_path('Modules/'.$this->moduleName());

        return $relative === '' ? $base : $base.'/'.$relative;
    }
}
