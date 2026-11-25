<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ورود — {{ config('app.brand.fa') }}</title>
    <style>
        body { font-family: Vazirmatn, Tahoma, sans-serif; background:#f7f7f8; margin:0; color:#111827; }
        main { max-width: 380px; margin: 10vh auto; background:#fff; border-radius:12px; padding:26px; box-shadow:0 1px 3px rgba(0,0,0,.08); }
        h1 { font-size:1.05rem; margin:0 0 18px; }
        label { display:block; font-size:.82rem; color:#4b5563; margin:12px 0 5px; }
        input { width:100%; padding:10px; border:1px solid #d1d5db; border-radius:8px; font-size:.95rem; }
        button { margin-top:18px; width:100%; padding:11px; border:0; border-radius:8px; background:#2563eb; color:#fff; font-size:.95rem; cursor:pointer; }
        .errors { color:#b91c1c; font-size:.82rem; margin-top:10px; }
        .muted { color:#6b7280; font-size:.76rem; margin-top:14px; line-height:1.8; }
    </style>
</head>
<body>
<main>
    <h1>ورود به {{ config('app.brand.fa') }}</h1>

    <form method="post" action="{{ route('login.store') }}">
        @csrf
        <label for="email">ایمیل</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>

        <label for="password">گذرواژه</label>
        <input id="password" name="password" type="password" required>

        <button type="submit">ورود</button>
    </form>

    @if ($errors->any())
        <p class="errors">{{ $errors->first() }}</p>
    @endif

    <p class="muted">ورود با پیامک برای مشتریان در همین فاز اضافه می‌شود؛ حساب‌های نمونه با گذرواژهٔ آزمایشی کار می‌کنند.</p>
</main>
</body>
</html>
