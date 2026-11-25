<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Contracts;

/**
 * Provider-agnostic SMS sender (AMB-NTF-001: the provider is not named in the contract,
 * so it is a driver behind this interface, selected by config).
 */
interface SmsDriver
{
    public function send(string $mobile, string $message): void;
}
