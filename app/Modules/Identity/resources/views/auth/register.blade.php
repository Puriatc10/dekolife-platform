@extends('layouts.app')

@section('title', 'ثبت‌نام')
@section('heading', 'ثبت‌نام مشتری')
@section('lead')
    <p class="lead">شمارهٔ موبایل خود را وارد کنید؛ یک کد شش‌رقمی برایتان پیامک می‌شود.</p>
@endsection

@section('content')
    <form method="post" action="{{ route('register.request') }}">
        @csrf
        <label for="mobile">شمارهٔ موبایل</label>
        <input id="mobile" name="mobile" inputmode="numeric" value="{{ old('mobile') }}" placeholder="۰۹۱۲۳۴۵۶۷۸۹" required autofocus>

        <button type="submit">دریافت کد تأیید</button>
    </form>

    <p class="muted">
        حساب دارید؟ <a href="{{ route('login') }}">ورود</a> — گذرواژه را فراموش کرده‌اید؟
        <a href="{{ route('password.request') }}">بازیابی با پیامک</a>
    </p>
@endsection
