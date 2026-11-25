<?php

declare(strict_types=1);

namespace App\Modules\Identity\Providers;

use App\Shared\Modules\ModuleServiceProvider;

final class IdentityServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        $this->loadModuleMigrations();
        $this->loadModuleViews();
        $this->loadWebRoutes();
    }
}
