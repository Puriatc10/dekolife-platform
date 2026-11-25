<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Admin\Application\AuditLogger;
use App\Modules\Admin\Http\Middleware\EnsureAdminTwoFactor;
use App\Shared\Security\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

/**
 * TOTP enrolment and challenge for the admin panel.
 *
 * A user with no stored secret gets one generated on first visit and must prove possession
 * of it before the session is marked verified — enrolment and challenge are the same
 * screen, which is what keeps a compromised password from being enough on its own.
 */
final class TwoFactorController
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function show(Request $request): View
    {
        $user = $request->user();

        $secret = $user->two_factor_secret === null
            ? null
            : Crypt::decryptString($user->two_factor_secret);

        if ($secret === null) {
            $secret = Totp::generateSecret();
            $user->forceFill(['two_factor_secret' => Crypt::encryptString($secret)])->save();

            $this->audit->log('admin.2fa.enrolled', [], $user);
        }

        $totp = new Totp($secret);

        return view('admin::2fa', [
            'provisioningUri' => $totp->provisioningUri((string) $user->email, (string) config('app.brand.en')),
            'secret' => $secret,
            'verified' => (bool) $user->two_factor_confirmed_at,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:12']]);

        $user = $request->user();
        $secret = $user->two_factor_secret === null
            ? null
            : Crypt::decryptString($user->two_factor_secret);

        if ($secret === null) {
            return redirect()->route('admin.2fa.show')->withErrors(['code' => 'ابتدا ثبت‌نام دومرحله‌ای را کامل کنید.']);
        }

        if (! (new Totp($secret))->verify((string) $request->input('code'))) {
            $this->audit->log('admin.2fa.failed');

            return redirect()->route('admin.2fa.show')->withErrors(['code' => 'کد وارد‌شده معتبر نیست.']);
        }

        $request->session()->put(EnsureAdminTwoFactor::SESSION_KEY, true);
        $user->forceFill(['two_factor_confirmed_at' => $user->two_factor_confirmed_at ?? now()])->save();

        $this->audit->log('admin.2fa.verified', [], $user);

        return redirect()->route('admin.dashboard');
    }
}
