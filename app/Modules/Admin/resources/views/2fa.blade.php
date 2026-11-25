<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تأیید دومرحله‌ای — پنل مدیریت {{ config('app.brand.fa') }}</title>
    <style>
        body { font-family: Vazirmatn, Tahoma, sans-serif; background:#f7f7f8; margin:0; }
        main { max-width: 420px; margin: 8vh auto; background:#fff; border-radius:12px; padding:28px; box-shadow:0 1px 3px rgba(0,0,0,.08); }
        h1 { font-size:1.1rem; margin:0 0 16px; }
        label { display:block; font-size:.85rem; margin-bottom:6px; color:#444; }
        input[type=text] { width:100%; padding:10px; border:1px solid #d0d0d5; border-radius:8px; font-size:1.1rem; letter-spacing:.3em; text-align:center; }
        button { margin-top:14px; width:100%; padding:11px; border:0; border-radius:8px; background:#2563eb; color:#fff; font-size:.95rem; cursor:pointer; }
        .errors { color:#b91c1c; font-size:.85rem; margin:10px 0 0; }
        .hint { font-size:.78rem; color:#6b7280; margin-top:12px; line-height:1.7; word-break:break-all; }
        code { background:#f3f4f6; padding:2px 4px; border-radius:4px; }
    </style>
</head>
<body>
<main>
    <h1>تأیید دومرحله‌ای پنل مدیریت</h1>

    <p class="hint">
        کد ۶ رقمی برنامهٔ احراز هویت را وارد کنید. اگر نخستین ورود شماست، این کلید را در برنامه ثبت کنید:
        <br><code>{{ $secret }}</code>
    </p>

    <form method="post" action="{{ route('admin.2fa.verify') }}">
        @csrf
        <label for="code">کد تأیید</label>
        <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required autofocus>
        <button type="submit">تأیید و ورود</button>
    </form>

    @if ($errors->any())
        <p class="errors">{{ $errors->first() }}</p>
    @endif

    <p class="hint">نشانی ثبت در برنامه: {{ $provisioningUri }}</p>
</main>
</body>
</html>
