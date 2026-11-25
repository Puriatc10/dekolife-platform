<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | SMS driver
    |--------------------------------------------------------------------------
    |
    | The contract requires SMS for registration, order notifications and marketing
    | messages but never names a provider (AMB-NTF-001). `log` is the safe default so a
    | missing credential degrades to a logged message instead of a failed registration;
    | `fake` is used by tests. A real provider is added as another driver here.
    |
    */

    'driver' => env('SMS_DRIVER', 'log'),

    'log_channel' => env('SMS_LOG_CHANNEL', 'single'),

    'otp' => [
        // Six digits, five minutes, three attempts: the values in REQ-IAM-001's
        // acceptance criteria. Rate limiting is applied by the route middleware.
        'length' => 6,
        'ttl_seconds' => 300,
        'max_attempts' => 3,
    ],

];
