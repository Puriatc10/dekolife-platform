<?php

declare(strict_types=1);

namespace App\Modules\Admin\Providers;

use App\Shared\Modules\ModuleServiceProvider;

final class AdminServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        $this->loadModuleMigrations();
        $this->loadModuleViews();
        $this->loadWebRoutes();
    }
}
