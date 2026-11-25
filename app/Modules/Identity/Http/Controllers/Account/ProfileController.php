<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers\Account;

use App\Models\User;
use App\Modules\Admin\Application\AuditLogger;
use App\Modules\Identity\Application\OtpService;
use App\Modules\Identity\Domain\Exceptions\OtpException;
use App\Modules\Users\Application\NationalIdRegistry;
use App\Modules\Users\Domain\Exceptions\DuplicateNationalIdException;
use App\Modules\Users\Domain\NationalId;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Profile editing, with an SMS-confirmed mobile change (REQ-IAM-004: «ویرایش اطلاعات با
 * تأیید پیامکی»).
 *
 * A mobile number is the account's second identity: it receives the login codes. Changing it
 * silently would let anyone with one open session take the account over, so the new number
 * must prove possession with a code before it replaces the old one.
 */
final class ProfileController
{
    private const SESSION_PENDING_MOBILE = 'profile.pending_mobile';

    public function __construct(
        private readonly OtpService $otp,
        private readonly NationalIdRegistry $nationalIds,
        private readonly AuditLogger $audit,
    ) {}

    public function edit(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('identity::account.profile', [
            'user' => $user,
            'nationalId' => $this->nationalIds->decryptFor($user),
            'pendingMobile' => $request->session()->get(self::SESSION_PENDING_MOBILE),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'string', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->getKey())],
            'national_id' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($user, $data): void {
            $user->forceFill([
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
            ])->save();

            if (($data['national_id'] ?? null) !== null && $data['national_id'] !== '') {
                $this->claimNationalId($user, (string) $data['national_id']);
            }
        });

        $this->audit->log('identity.profile.updated', [], $user);

        return redirect()->route('profile.edit')->with('status', 'اطلاعات پروفایل ذخیره شد.');
    }

    /** Step 1 of a mobile change: send a code to the *new* number. */
    public function requestMobileChange(Request $request): RedirectResponse
    {
        $mobile = $this->otp->normaliseMobile((string) $request->input('mobile', ''));

        if (! OtpService::isValidMobile($mobile)) {
            throw ValidationException::withMessages(['mobile' => 'شماره موبایل جدید معتبر نیست.']);
        }

        /** @var User $user */
        $user = $request->user();

        if ($mobile === $user->mobile) {
            throw ValidationException::withMessages(['mobile' => 'این همان شمارهٔ فعلی شماست.']);
        }

        if (User::query()->where('mobile', $mobile)->whereKeyNot($user->getKey())->exists()) {
            throw ValidationException::withMessages(['mobile' => 'این شماره برای حساب دیگری ثبت شده است.']);
        }

        try {
            $code = $this->otp->issue($mobile, 'mobile_change', ['mobile' => $mobile]);
        } catch (OtpException $e) {
            throw ValidationException::withMessages(['mobile' => $e->messageFa()]);
        }

        $request->session()->put(self::SESSION_PENDING_MOBILE, $mobile);

        return redirect()->route('profile.edit')
            ->with('status', 'کد تأیید به شمارهٔ جدید ارسال شد؛ برای نهایی‌شدن، کد را وارد کنید.')
            ->with('debug_code', $code);
    }

    /** Step 2: the code proves possession, and the change is applied in the same request. */
    public function confirmMobileChange(Request $request): RedirectResponse
    {
        $pending = $request->session()->get(self::SESSION_PENDING_MOBILE);

        if (! is_string($pending)) {
            return redirect()->route('profile.edit');
        }

        $data = $request->validate(['code' => ['required', 'string', 'digits:6']]);

        try {
            $payload = $this->otp->verify($pending, 'mobile_change', $data['code']);
        } catch (OtpException $e) {
            throw ValidationException::withMessages(['code' => $e->messageFa()]);
        }

        // The number that was verified is the number from the payload, not one from the
        // request: a client cannot swap in a different number between the two steps.
        $verified = (string) ($payload['mobile'] ?? $pending);

        /** @var User $user */
        $user = $request->user();

        if (User::query()->where('mobile', $verified)->whereKeyNot($user->getKey())->exists()) {
            throw ValidationException::withMessages(['code' => 'این شماره برای حساب دیگری ثبت شده است.']);
        }

        $previous = $user->mobile;

        $user->forceFill([
            'mobile' => $verified,
            'mobile_verified_at' => now(),
        ])->save();

        $request->session()->forget(self::SESSION_PENDING_MOBILE);

        $this->audit->log('identity.mobile.changed', ['previous' => $previous, 'current' => $verified], $user);

        return redirect()->route('profile.edit')->with('status', 'شمارهٔ موبایل تأیید و جایگزین شد.');
    }

    private function claimNationalId(User $user, string $raw): void
    {
        try {
            $nationalId = NationalId::fromString($raw);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['national_id' => 'کد ملی باید ۱۰ رقم باشد.']);
        }

        if (! $nationalId->isValid()) {
            throw ValidationException::withMessages(['national_id' => 'کد ملی وارد‌شده معتبر نیست.']);
        }

        try {
            $this->nationalIds->claim($nationalId, $user, 'registration');
        } catch (DuplicateNationalIdException $e) {
            throw ValidationException::withMessages(['national_id' => $e->getMessage()]);
        }
    }
}
