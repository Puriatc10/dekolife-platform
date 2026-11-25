<?php

use App\Modules\Admin\Providers\AdminServiceProvider;
use App\Modules\Identity\Providers\IdentityServiceProvider;
use App\Modules\Merchants\Providers\MerchantsServiceProvider;
use App\Modules\Notifications\Providers\NotificationsServiceProvider;
use App\Modules\Users\Providers\UsersServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,

    // Modules register their own routes, migrations, views and bindings; the order is the
    // dependency order of docs/architecture/modular-monolith.md.
    NotificationsServiceProvider::class,
    UsersServiceProvider::class,
    IdentityServiceProvider::class,
    MerchantsServiceProvider::class,
    AdminServiceProvider::class,
];
