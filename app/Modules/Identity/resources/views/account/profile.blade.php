@extends('layouts.app')

@section('title', 'پروفایل')
@section('heading', 'ویرایش پروفایل')
@section('lead')
    <p class="lead">نام و ایمیل را می‌توانید مستقیماً تغییر دهید. تغییر شمارهٔ موبایل نیاز به تأیید پیامکی دارد.</p>
@endsection

@section('content')
    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        <label for="name">نام و نام خانوادگی</label>
        <input id="name" name="name" value="{{ old('name', $user->name) }}" required>

        <label for="email">ایمیل</label>
        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}">

        <label for="national_id">کد ملی</label>
        <input id="national_id" name="national_id" inputmode="numeric" maxlength="10"
               value="{{ old('national_id', $nationalId) }}">
        <p class="muted" style="margin-top:4px;">کد ملی تکراری پذیرفته نمی‌شود؛ همین قاعده برای فروشگاه و تأمین‌کننده هم اعمال می‌شود.</p>

        <button type="submit">ذخیرهٔ اطلاعات</button>
    </form>

    <h2>شمارهٔ موبایل</h2>
    <p class="muted" style="margin-top:0;">
        شمارهٔ فعلی: <strong>{{ $user->mobile ?? 'ثبت نشده' }}</strong>
        @if ($user->mobile_verified_at)
            <span class="tag approved">تأییدشده</span>
        @endif
    </p>

    <form method="post" action="{{ route('profile.mobile.request') }}">
        @csrf
        <label for="mobile">شمارهٔ جدید</label>
        <input id="mobile" name="mobile" inputmode="numeric" placeholder="۰۹۱۲۳۴۵۶۷۸۹" required>
        <button type="submit" class="secondary">ارسال کد به شمارهٔ جدید</button>
    </form>

    @if ($pendingMobile)
        <form method="post" action="{{ route('profile.mobile.confirm') }}" style="margin-top:14px;">
            @csrf
            <label for="mobile_code">کد ارسال‌شده به {{ $pendingMobile }}</label>
            <input id="mobile_code" name="code" inputmode="numeric" maxlength="6" required>
            <button type="submit">تأیید و جایگزینی شماره</button>
        </form>
    @endif
@endsection
