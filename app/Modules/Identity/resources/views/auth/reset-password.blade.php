@extends('layouts.app')

@section('title', 'گذرواژهٔ جدید')
@section('heading', 'گذرواژهٔ جدید')
@section('lead')
    <p class="lead">کد ارسال‌شده به <strong>{{ $mobile }}</strong> و گذرواژهٔ جدید خود را وارد کنید.</p>
@endsection

@section('content')
    <form method="post" action="{{ route('password.reset.store') }}">
        @csrf
        <label for="code">کد تأیید</label>
        <input id="code" name="code" inputmode="numeric" maxlength="6" required autofocus>

        <label for="password">گذرواژهٔ جدید</label>
        <input id="password" name="password" type="password" required>

        <label for="password_confirmation">تکرار گذرواژهٔ جدید</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required>

        <button type="submit">ثبت گذرواژهٔ جدید</button>
    </form>
@endsection
