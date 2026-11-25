@extends('layouts.app')

@section('title', 'بازیابی گذرواژه')
@section('heading', 'بازیابی گذرواژه با پیامک')
@section('lead')
    <p class="lead">شمارهٔ موبایل حساب خود را وارد کنید تا کد بازیابی برایتان پیامک شود.</p>
@endsection

@section('content')
    <form method="post" action="{{ route('password.request.store') }}">
        @csrf
        <label for="mobile">شمارهٔ موبایل</label>
        <input id="mobile" name="mobile" inputmode="numeric" value="{{ old('mobile') }}" required autofocus>

        <button type="submit">ارسال کد بازیابی</button>
    </form>

    <p class="muted">برای حساب‌های داخلی (ادمین، فروشگاه، تأمین‌کننده) گذرواژه را مدیر سامانه بازنشانی می‌کند.</p>
@endsection
