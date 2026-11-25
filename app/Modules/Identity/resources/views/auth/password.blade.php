<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تغییر گذرواژه — {{ config('app.brand.fa') }}</title>
    <style>
        body { font-family: Vazirmatn, Tahoma, sans-serif; background:#f7f7f8; margin:0; color:#111827; }
        main { max-width: 400px; margin: 10vh auto; background:#fff; border-radius:12px; padding:26px; box-shadow:0 1px 3px rgba(0,0,0,.08); }
        h1 { font-size:1.05rem; margin:0 0 8px; }
        label { display:block; font-size:.82rem; color:#4b5563; margin:12px 0 5px; }
        input { width:100%; padding:10px; border:1px solid #d1d5db; border-radius:8px; }
        button { margin-top:18px; width:100%; padding:11px; border:0; border-radius:8px; background:#2563eb; color:#fff; cursor:pointer; }
        .errors { color:#b91c1c; font-size:.82rem; margin-top:10px; }
        .muted { color:#6b7280; font-size:.78rem; margin-top:12px; }
    </style>
</head>
<body>
<main>
    <h1>تغییر گذرواژهٔ نخستین ورود</h1>
    <p class="muted">برای ادامه، گذرواژهٔ موقت خود را با یک گذرواژهٔ تازه جایگزین کنید.</p>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        <label for="current_password">گذرواژهٔ فعلی</label>
        <input id="current_password" name="current_password" type="password" required autofocus>

        <label for="password">گذرواژهٔ جدید</label>
        <input id="password" name="password" type="password" required>

        <label for="password_confirmation">تکرار گذرواژهٔ جدید</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required>

        <button type="submit">ثبت گذرواژهٔ جدید</button>
    </form>

    @if ($errors->any())
        <p class="errors">{{ $errors->first() }}</p>
    @endif
</main>
</body>
</html>
