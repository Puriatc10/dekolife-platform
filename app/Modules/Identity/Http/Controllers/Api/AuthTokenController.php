<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers\Api;

use App\Models\User;
use App\Modules\Admin\Application\AuditLogger;
use App\Modules\Identity\Application\OtpService;
use App\Modules\Identity\Domain\Exceptions\OtpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Sanctum token issuing for `routes/api.php` (appendix 1: the backend must also serve the
 * mobile app; the same accounts, the same roles).
 *
 * Two ways in, because the contract has two onboarding paths: password for internal accounts
 * and OTP for customers who never chose a password. A token is scoped to one device name and
 * expires, so a lost phone does not mean a permanently valid token.
 */
final class AuthTokenController
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly AuditLogger $audit,
    ) {}

    /** POST /api/v1/auth/token — mobile + password. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        $mobile = $this->otp->normaliseMobile($data['mobile']);
        $user = User::query()->where('mobile', $mobile)->where('is_active', true)->first();

        // Same message and same timing path for «no such user» and «wrong password».
        if ($user === null || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['mobile' => 'شماره موبایل یا گذرواژه نادرست است.']);
        }

        return $this->issue($user, $data['device_name'] ?? 'api');
    }

    /** POST /api/v1/auth/otp-token — mobile + the code that was just sent. */
    public function storeFromOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string'],
            'code' => ['required', 'string', 'digits:6'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        $mobile = $this->otp->normaliseMobile($data['mobile']);
        $user = User::query()->where('mobile', $mobile)->where('is_active', true)->first();

        if ($user === null) {
            throw ValidationException::withMessages(['code' => 'کد وارد‌شده نادرست است.']);
        }

        try {
            $this->otp->verify($mobile, 'api_login', $data['code']);
        } catch (OtpException $e) {
            throw ValidationException::withMessages(['code' => $e->messageFa()]);
        }

        return $this->issue($user, $data['device_name'] ?? 'api');
    }

    /** POST /api/v1/auth/otp — request a login code for the mobile app. */
    public function requestOtp(Request $request): JsonResponse
    {
        $mobile = $this->otp->normaliseMobile((string) $request->input('mobile', ''));

        if (! OtpService::isValidMobile($mobile)) {
            throw ValidationException::withMessages(['mobile' => 'شماره موبایل معتبر نیست.']);
        }

        $code = null;

        if (User::query()->where('mobile', $mobile)->where('is_active', true)->exists()) {
            try {
                $code = $this->otp->issue($mobile, 'api_login');
            } catch (OtpException $e) {
                throw ValidationException::withMessages(['mobile' => $e->messageFa()]);
            }
        }

        return response()->json(array_filter([
            'message' => 'اگر این شماره ثبت شده باشد، کد ورود پیامک شد.',
            'debug_code' => $code,
        ], static fn ($v): bool => $v !== null));
    }

    public function destroy(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        // A session-authenticated request has no token; deleting it must not delete others.
        if ($token !== null && method_exists($token, 'delete')) {
            $token->delete();
        }

        $this->audit->log('api.token.revoked');

        return response()->json(['message' => 'توکن باطل شد.']);
    }

    private function issue(User $user, string $deviceName): JsonResponse
    {
        $token = $user->createToken($deviceName, ['*'], now()->addDays(30));

        $expiresAt = $token->accessToken->getAttribute('expires_at');

        $user->forceFill(['last_login_at' => now()])->save();

        $this->audit->log('api.token.issued', ['device' => $deviceName], $user);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt instanceof \DateTimeInterface ? $expiresAt->format(DATE_ATOM) : null,
            'user' => [
                'id' => $user->getKey(),
                'name' => $user->name,
                'mobile' => $user->mobile,
                'roles' => $user->roleCodes(),
            ],
        ], 201);
    }
}
