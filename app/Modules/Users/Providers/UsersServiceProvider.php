<?php

declare(strict_types=1);

namespace App\Modules\Users\Providers;

use App\Shared\Modules\ModuleServiceProvider;

final class UsersServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        $this->loadModuleMigrations();
    }
}
