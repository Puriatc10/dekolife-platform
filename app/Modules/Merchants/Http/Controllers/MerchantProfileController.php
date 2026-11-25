<?php

declare(strict_types=1);

namespace App\Modules\Merchants\Http\Controllers;

use App\Models\User;
use App\Modules\Merchants\Application\MerchantProfileService;
use App\Modules\Merchants\Domain\Models\MerchantProfile;
use App\Modules\Users\Domain\Exceptions\DuplicateNationalIdException;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The applicant side of store/supplier onboarding (پیوست ۳ ۳-۱: a store or supplier submits
 * its business profile; approval is the admin's, not the applicant's).
 */
final class MerchantProfileController
{
    public function __construct(private readonly MerchantProfileService $profiles) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('merchants::index', [
            'profiles' => MerchantProfile::query()
                ->where('user_id', $user->getKey())
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $type = $request->query('type') === 'supplier' ? 'supplier' : 'store';

        return view('merchants::form', [
            'type' => $type,
            'profile' => MerchantProfile::query()
                ->where('user_id', $request->user()->getKey())
                ->where('type', $type)
                ->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $type = $request->input('type') === 'supplier' ? 'supplier' : 'store';

        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:160'],
            'owner_name' => ['nullable', 'string', 'max:120'],
            'national_id' => ['required', 'string'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'province' => ['nullable', 'string', 'max:64'],
            'city' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:400'],
            'phone' => ['nullable', 'string', 'max:16'],
        ]);

        /** @var User $user */
        $user = $request->user();

        try {
            $this->profiles->submit($user, $type, $data);
        } catch (DuplicateNationalIdException $e) {
            throw ValidationException::withMessages(['national_id' => $e->getMessage()]);
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['business_name' => $e->getMessage()]);
        }

        return redirect()->route('merchant.profiles.index')
            ->with('status', 'پروفایل ثبت شد و در انتظار تأیید ادمین است.');
    }
}
