@extends('layouts.app')

@section('title', 'تأیید شماره موبایل')
@section('heading', 'کد تأیید را وارد کنید')
@section('lead')
    <p class="lead">کد شش‌رقمی ارسال‌شده به <strong>{{ $mobile }}</strong> را وارد کنید و نام خود را بنویسید.</p>
@endsection

@section('content')
    <form method="post" action="{{ route('register.verify.store') }}">
        @csrf
        <label for="code">کد تأیید</label>
        <input id="code" name="code" inputmode="numeric" maxlength="6" placeholder="------" required autofocus>

        <label for="name">نام و نام خانوادگی</label>
        <input id="name" name="name" value="{{ old('name') }}" required>

        <label for="email">ایمیل (اختیاری)</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}">

        <button type="submit">تأیید و ساخت حساب</button>
    </form>

    <form method="post" action="{{ route('register.resend') }}" style="margin-top:10px;">
        @csrf
        <button type="submit" class="secondary">ارسال دوبارهٔ کد</button>
    </form>
@endsection
