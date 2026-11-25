<?php

declare(strict_types=1);

namespace App\Modules\Merchants\Providers;

use App\Shared\Modules\ModuleServiceProvider;

final class MerchantsServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        $this->loadModuleMigrations();
        $this->loadModuleViews();
        $this->loadWebRoutes();
    }
}
