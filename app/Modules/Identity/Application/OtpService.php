<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\Exceptions\OtpException;
use App\Modules\Notifications\Contracts\SmsDriver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * One-time codes for registration, password reset and mobile change (REQ-IAM-001/003).
 *
 * Until the client's SMS provider is chosen (AMB-NTF-001) the driver is `log` or `fake`, so
 * the code is only visible in the log or to a test — never in the HTTP response, except in
 * `local` when `app.debug` is on, which is the documented way to demo the flow without a
 * provider. That exception is gated by environment, not by a toggle a caller can flip.
 */
final class OtpService
{
    public function __construct(private readonly SmsDriver $sms) {}

    /**
     * Issue a code and deliver it. Returns the plain code only when the caller is allowed
     * to see it (local debugging), otherwise null.
     *
     * @param  array<string, mixed>  $payload
     */
    public function issue(string $mobile, string $purpose, array $payload = []): ?string
    {
        $mobile = $this->normaliseMobile($mobile);
        $this->assertNotThrottled($mobile, $purpose);

        $length = (int) config('sms.otp.length', 6);
        $code = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);

        DB::table('iam_otp_codes')->insert([
            'mobile' => $mobile,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'payload' => $payload === [] ? null : json_encode($payload, JSON_UNESCAPED_UNICODE),
            'attempts' => 0,
            'expires_at' => now()->addSeconds((int) config('sms.otp.ttl_seconds', 300)),
            'requested_ip' => request()->ip(),
            'created_at' => now(),
        ]);

        $this->sms->send($mobile, "کد تأیید {$this->brand()} {$code}");

        return $this->exposesCode() ? $code : null;
    }

    /**
     * Verify a code and return the payload it was issued with. On success the code is
     * consumed; a wrong code burns an attempt (three, then the code is dead).
     *
     * @return array<string, mixed>
     */
    public function verify(string $mobile, string $purpose, string $code): array
    {
        $mobile = $this->normaliseMobile($mobile);

        $row = DB::table('iam_otp_codes')
            ->where('mobile', $mobile)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->orderByDesc('id')
            ->first();

        if ($row === null) {
            throw OtpException::notIssued();
        }

        if (now()->greaterThan($row->expires_at)) {
            throw OtpException::expired();
        }

        if ((int) $row->attempts >= (int) config('sms.otp.max_attempts', 3)) {
            throw OtpException::tooManyAttempts();
        }

        if (! Hash::check($code, $row->code_hash)) {
            DB::table('iam_otp_codes')->where('id', $row->id)->increment('attempts');

            throw OtpException::invalid();
        }

        DB::table('iam_otp_codes')->where('id', $row->id)->update(['consumed_at' => now()]);

        $payload = $row->payload === null ? [] : json_decode((string) $row->payload, true);

        return is_array($payload) ? $payload : [];
    }

    /** Persian/Arabic digits and separators are what a user types; normalise before use. */
    public function normaliseMobile(string $mobile): string
    {
        $mobile = Str::of($mobile)
            ->replace(['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'])
            ->replace(['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'])
            ->replaceMatches('/[^0-9]/', '')
            ->toString();

        if (str_starts_with($mobile, '98')) {
            $mobile = '0'.substr($mobile, 2);
        }

        if (str_starts_with($mobile, '9') && strlen($mobile) === 10) {
            $mobile = '0'.$mobile;
        }

        return $mobile;
    }

    public static function isValidMobile(string $mobile): bool
    {
        return (bool) preg_match('/^09\d{9}$/', $mobile);
    }

    private function assertNotThrottled(string $mobile, string $purpose): void
    {
        $recent = DB::table('iam_otp_codes')
            ->where('mobile', $mobile)
            ->where('purpose', $purpose)
            ->where('created_at', '>=', now()->subSeconds(60))
            ->exists();

        if ($recent) {
            throw OtpException::tooSoon();
        }

        $daily = DB::table('iam_otp_codes')
            ->where('mobile', $mobile)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($daily >= 10) {
            throw OtpException::tooManyRequests();
        }
    }

    /** Only a local, debugging session may see the code in the response. */
    private function exposesCode(): bool
    {
        return app()->environment('local') && (bool) config('app.debug');
    }

    private function brand(): string
    {
        return (string) config('app.brand.fa', 'دکولایف');
    }
}
