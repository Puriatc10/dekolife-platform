<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Infrastructure;

use App\Modules\Notifications\Contracts\SmsDriver;

/**
 * In-memory driver for tests: records every message so a test can assert on the code that
 * was "sent" without touching a provider (docs/testing: fakes in local and CI only).
 */
final class FakeSmsDriver implements SmsDriver
{
    /** @var array<int, array{mobile: string, message: string}> */
    private array $sent = [];

    public function send(string $mobile, string $message): void
    {
        $this->sent[] = ['mobile' => $mobile, 'message' => $message];
    }

    /** @return array<int, array{mobile: string, message: string}> */
    public function messages(): array
    {
        return $this->sent;
    }

    public function lastMessageTo(string $mobile): ?string
    {
        $matches = array_values(array_filter(
            $this->sent,
            static fn (array $entry): bool => $entry['mobile'] === $mobile,
        ));

        return $matches === [] ? null : end($matches)['message'];
    }

    public function flush(): void
    {
        $this->sent = [];
    }
}
