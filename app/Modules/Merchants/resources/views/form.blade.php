@extends('layouts.app')

@section('title', $type === 'supplier' ? 'پروفایل تأمین‌کننده' : 'پروفایل فروشگاه')
@section('heading', $type === 'supplier' ? 'ثبت پروفایل تأمین‌کننده' : 'ثبت پروفایل فروشگاه')
@section('lead')
    <p class="lead">
        اطلاعات کسب‌وکار را وارد کنید. کد ملی برای جلوگیری از ثبت تکراری بررسی می‌شود و پس از ثبت،
        درخواست برای تأیید به ادمین می‌رود.
    </p>
@endsection

@section('content')
    @if ($profile)
        <div class="ok">
            برای این نوع پروفایل ثبت کرده‌اید؛ وضعیت فعلی:
            {{ ['pending' => 'در انتظار تأیید', 'approved' => 'تأییدشده', 'rejected' => 'ردشده'][$profile->status] ?? $profile->status }}
            — ثبت دوباره، درخواست را به‌روزرسانی و به صف بررسی برمی‌گرداند.
        </div>
    @endif

    <form method="post" action="{{ route('merchant.profiles.store') }}">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">

        <label for="business_name">نام کسب‌وکار</label>
        <input id="business_name" name="business_name" value="{{ old('business_name', $profile?->business_name) }}" required autofocus>

        <label for="owner_name">نام مالک / مسئول</label>
        <input id="owner_name" name="owner_name" value="{{ old('owner_name', $profile?->owner_name) }}">

        <label for="national_id">کد ملی مالک</label>
        <input id="national_id" name="national_id" inputmode="numeric" maxlength="10" required>

        <div class="row">
            <div>
                <label for="province">استان</label>
                <input id="province" name="province" value="{{ old('province', $profile?->province) }}">
            </div>
            <div>
                <label for="city">شهر</label>
                <input id="city" name="city" value="{{ old('city', $profile?->city) }}">
            </div>
        </div>

        <div class="row">
            <div>
                <label for="postal_code">کد پستی</label>
                <input id="postal_code" name="postal_code" inputmode="numeric" maxlength="10" value="{{ old('postal_code', $profile?->postal_code) }}">
            </div>
            <div>
                <label for="phone">تلفن</label>
                <input id="phone" name="phone" inputmode="tel" value="{{ old('phone', $profile?->phone) }}">
            </div>
        </div>

        <label for="address">نشانی</label>
        <textarea id="address" name="address" rows="3">{{ old('address', $profile?->address) }}</textarea>

        <button type="submit">ثبت برای بررسی</button>
    </form>
@endsection
