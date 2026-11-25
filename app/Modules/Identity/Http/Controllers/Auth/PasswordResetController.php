<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers\Auth;

use App\Models\User;
use App\Modules\Admin\Application\AuditLogger;
use App\Modules\Identity\Application\OtpService;
use App\Modules\Identity\Domain\Exceptions\OtpException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Password recovery by mobile + OTP (REQ-IAM-003).
 *
 * The first step answers the same way whether or not the number exists: telling an anonymous
 * caller «این شماره ثبت نشده» turns the form into a customer-enumeration oracle. The code is
 * only issued for a real, active account.
 */
final class PasswordResetController
{
    private const SESSION_MOBILE = 'password.reset.mobile';

    private const SESSION_VERIFIED = 'password.reset.verified';

    public function __construct(
        private readonly OtpService $otp,
        private readonly AuditLogger $audit,
    ) {}

    public function showRequest(): View
    {
        return view('identity::auth.forgot-password');
    }

    public function requestCode(Request $request): RedirectResponse
    {
        $mobile = $this->otp->normaliseMobile((string) $request->input('mobile', ''));

        if (! OtpService::isValidMobile($mobile)) {
            throw ValidationException::withMessages(['mobile' => 'شماره موبایل وارد‌شده معتبر نیست.']);
        }

        $user = User::query()->where('mobile', $mobile)->where('is_active', true)->first();

        $code = null;

        if ($user !== null) {
            try {
                $code = $this->otp->issue($mobile, 'reset_password');
            } catch (OtpException $e) {
                throw ValidationException::withMessages(['mobile' => $e->messageFa()]);
            }

            $this->audit->log('identity.password.reset_requested', [], $user);
        }

        $request->session()->put(self::SESSION_MOBILE, $mobile);
        $request->session()->forget(self::SESSION_VERIFIED);

        return redirect()->route('password.reset.verify')
            ->with('status', 'اگر این شماره در سامانه ثبت شده باشد، کد بازیابی برای آن پیامک شد.')
            ->with('debug_code', $code);
    }

    public function showVerify(Request $request): View|RedirectResponse
    {
        $mobile = $request->session()->get(self::SESSION_MOBILE);

        if (! is_string($mobile)) {
            return redirect()->route('password.request');
        }

        return view('identity::auth.reset-password', ['mobile' => $mobile]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $mobile = $request->session()->get(self::SESSION_MOBILE);

        if (! is_string($mobile)) {
            return redirect()->route('password.request');
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->where('mobile', $mobile)->where('is_active', true)->first();

        if ($user === null) {
            throw ValidationException::withMessages(['code' => 'کد وارد‌شده نادرست است.']);
        }

        try {
            $this->otp->verify($mobile, 'reset_password', $data['code']);
        } catch (OtpException $e) {
            throw ValidationException::withMessages(['code' => $e->messageFa()]);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
            'remember_token' => null,
        ])->save();

        // Any session opened with the old password is dropped, otherwise a stolen session
        // survives the recovery that was supposed to end it.
        if (config('session.driver') === 'database') {
            $request->session()->getHandler()->destroy($user->getKey());
        }

        $request->session()->forget([self::SESSION_MOBILE, self::SESSION_VERIFIED]);

        $this->audit->log('identity.password.reset', [], $user);

        return redirect()->route('login')->with('status', 'گذرواژهٔ شما تغییر کرد. اکنون وارد شوید.');
    }
}
