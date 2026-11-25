<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Providers;

use App\Modules\Notifications\Contracts\SmsDriver;
use App\Modules\Notifications\Infrastructure\FakeSmsDriver;
use App\Modules\Notifications\Infrastructure\LogSmsDriver;
use App\Shared\Modules\ModuleServiceProvider;

final class NotificationsServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsDriver::class, function (): SmsDriver {
            return match ((string) config('sms.driver', 'log')) {
                'fake' => new FakeSmsDriver,
                default => new LogSmsDriver,
            };
        });
    }

    public function boot(): void
    {
        $this->loadModuleMigrations();
    }
}
