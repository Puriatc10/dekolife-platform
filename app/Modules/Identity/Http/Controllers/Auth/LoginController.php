<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers\Auth;

use App\Modules\Admin\Application\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Web login (REQ-IAM-001 for internal accounts; mobile-OTP registration lands in the next
 * slice of this phase).
 *
 * Every attempt is rate-limited per email+IP, a successful login rotates the session id,
 * and both outcomes reach the audit trail — a login that is not audited cannot be part of
 * an investigation (پیوست ۱ بند ۱۰).
 */
final class LoginController
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function show(): View
    {
        return view('identity::auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'تلاش‌های ناموفق بیش از حد مجاز است. لطفاً چند دقیقه بعد تلاش کنید.',
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            $this->audit->log('identity.login.failed', ['email' => $credentials['email']]);

            throw ValidationException::withMessages([
                'email' => 'ایمیل یا گذرواژه نادرست است.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $this->audit->log('identity.login', [], $user);

        if ($user->needsPasswordChange()) {
            return redirect()->route('password.change');
        }

        return redirect()->route('panel.redirect');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->audit->log('identity.logout');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
