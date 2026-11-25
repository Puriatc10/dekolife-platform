<?php

declare(strict_types=1);

namespace App\Modules\Merchants\Http\Controllers\Admin;

use App\Models\User;
use App\Modules\Merchants\Application\MerchantProfileService;
use App\Modules\Merchants\Domain\Models\MerchantProfile;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin review of store/supplier profiles (پیوست ۶ بند ۱۰: approval is the admin's).
 *
 * Approval is the only place a store/supplier role is granted, and the decision, the actor
 * and the note are all written to the audit trail.
 */
final class MerchantReviewController
{
    public function __construct(private readonly MerchantProfileService $profiles) {}

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', MerchantProfile::STATUS_PENDING);

        return view('merchants::admin.review', [
            'status' => $status,
            'profiles' => MerchantProfile::query()
                ->with('user')
                ->when(
                    in_array($status, [MerchantProfile::STATUS_PENDING, MerchantProfile::STATUS_APPROVED, MerchantProfile::STATUS_REJECTED], true),
                    fn ($q) => $q->where('status', $status),
                )
                ->orderByDesc('id')
                ->limit(50)
                ->get(),
            'counts' => MerchantProfile::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }

    public function approve(Request $request, MerchantProfile $profile): RedirectResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        try {
            $this->profiles->approve($profile, $admin, (string) $request->input('note', '') ?: null);
        } catch (DomainException $e) {
            return back()->withErrors(['profile' => $e->getMessage()]);
        }

        return back()->with('status', 'پروفایل تأیید شد و نقش مربوط به کاربر داده شد.');
    }

    public function reject(Request $request, MerchantProfile $profile): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:400'],
        ]);

        /** @var User $admin */
        $admin = $request->user();

        try {
            $this->profiles->reject($profile, $admin, $data['reason']);
        } catch (DomainException $e) {
            return back()->withErrors(['profile' => $e->getMessage()]);
        }

        return back()->with('status', 'پروفایل رد شد و دلیل برای متقاضی ثبت گردید.');
    }
}
