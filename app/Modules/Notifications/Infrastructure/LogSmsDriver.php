<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Infrastructure;

use App\Modules\Notifications\Contracts\SmsDriver;
use Illuminate\Support\Facades\Log;

/**
 * Development driver: writes the message to the log instead of sending it. It is also the
 * fallback when no provider credential is configured, so a missing SMS account degrades
 * to a logged message rather than a 500 during registration.
 */
final class LogSmsDriver implements SmsDriver
{
    public function send(string $mobile, string $message): void
    {
        Log::channel(config('sms.log_channel', 'single'))
            ->info('[sms:log] outgoing message', [
                'mobile' => $this->mask($mobile),
                'message' => $message,
            ]);
    }

    private function mask(string $mobile): string
    {
        $length = strlen($mobile);

        return $length <= 4
            ? str_repeat('*', $length)
            : substr($mobile, 0, 4).str_repeat('*', $length - 7).substr($mobile, -3);
    }
}
