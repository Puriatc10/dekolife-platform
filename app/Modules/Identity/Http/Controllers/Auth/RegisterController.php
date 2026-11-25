<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers\Auth;

use App\Models\User;
use App\Modules\Admin\Application\AuditLogger;
use App\Modules\Identity\Application\OtpService;
use App\Modules\Identity\Domain\Exceptions\OtpException;
use App\Modules\Identity\Domain\RoleCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Customer registration by mobile + OTP (REQ-IAM-001, پیوست ۲: «ثبت‌نام مشتری با شماره
 * موبایل»).
 *
 * Three steps, each with its own route, so a refresh cannot skip the code: mobile → code →
 * account. The mobile is unique; a second registration on the same number is refused before
 * a code is sent (no SMS cost for a known number) and the code is bound to the mobile, not
 * to the session.
 */
final class RegisterController
{
    private const SESSION_MOBILE = 'register.mobile';

    public function __construct(
        private readonly OtpService $otp,
        private readonly AuditLogger $audit,
    ) {}

    public function show(): View
    {
        return view('identity::auth.register');
    }

    public function requestCode(Request $request): RedirectResponse
    {
        $mobile = $this->otp->normaliseMobile((string) $request->input('mobile', ''));

        if (! OtpService::isValidMobile($mobile)) {
            throw ValidationException::withMessages(['mobile' => 'شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقم باشد.']);
        }

        if (User::query()->where('mobile', $mobile)->exists()) {
            throw ValidationException::withMessages([
                'mobile' => 'این شماره قبلاً ثبت شده است. وارد شوید یا گذرواژه را بازیابی کنید.',
            ]);
        }

        $code = null;

        try {
            $code = $this->otp->issue($mobile, 'register');
        } catch (OtpException $e) {
            throw ValidationException::withMessages(['mobile' => $e->messageFa()]);
        }

        $request->session()->put(self::SESSION_MOBILE, $mobile);

        return redirect()->route('register.verify')
            ->with('status', 'کد تأیید به شمارهٔ شما پیامک شد.')
            ->with('debug_code', $code);
    }

    public function showVerify(Request $request): View|RedirectResponse
    {
        $mobile = $request->session()->get(self::SESSION_MOBILE);

        if (! is_string($mobile)) {
            return redirect()->route('register');
        }

        return view('identity::auth.register-verify', ['mobile' => $mobile]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $mobile = $request->session()->get(self::SESSION_MOBILE);

        if (! is_string($mobile)) {
            return redirect()->route('register');
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'digits:6'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'string', 'email', 'max:190'],
        ]);

        try {
            $this->otp->verify($mobile, 'register', $data['code']);
        } catch (OtpException $e) {
            throw ValidationException::withMessages(['code' => $e->messageFa()]);
        }

        $user = DB::transaction(function () use ($mobile, $data): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'mobile' => $mobile,
                // A password is set, but the customer signs in with OTP; a random secret
                // that nobody knows is safer than a blank password column.
                'password' => Hash::make(Str::random(40)),
                'is_active' => true,
            ]);

            $user->forceFill(['mobile_verified_at' => now()])->save();
            $user->assignRole(RoleCode::Consumer);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget(self::SESSION_MOBILE);
        $request->session()->put('active_role_code', RoleCode::Consumer->value);

        $this->audit->log('identity.register', ['mobile' => $mobile], $user);

        return redirect()->route('panel.consumer');
    }

    /** Resend is the same endpoint with the mobile already in session, rate-limited inside OtpService. */
    public function resend(Request $request): RedirectResponse
    {
        $mobile = $request->session()->get(self::SESSION_MOBILE);

        if (! is_string($mobile)) {
            return redirect()->route('register');
        }

        try {
            $code = $this->otp->issue($mobile, 'register');
        } catch (OtpException $e) {
            throw ValidationException::withMessages(['code' => $e->messageFa()]);
        }

        return redirect()->route('register.verify')
            ->with('status', 'کد تازه ارسال شد.')
            ->with('debug_code', $code);
    }
}
