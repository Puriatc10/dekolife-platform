<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $role->labelFa() }} — {{ config('app.brand.fa') }}</title>
    <style>
        body { font-family: Vazirmatn, Tahoma, sans-serif; background:#f7f7f8; margin:0; color:#111827; }
        header { background:#fff; border-bottom:1px solid #e5e7eb; padding:14px 20px; display:flex; justify-content:space-between; align-items:center; }
        main { max-width: 780px; margin: 28px auto; padding: 0 16px; }
        .card { background:#fff; border-radius:12px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,.06); }
        .pill { display:inline-block; background:#eef2ff; color:#3730a3; border-radius:999px; padding:3px 10px; font-size:.78rem; }
        .muted { color:#6b7280; font-size:.85rem; line-height:1.9; }
    </style>
</head>
<body>
<header>
    <strong>{{ config('app.brand.fa') }}</strong>
    <span class="muted">سطح فعال: <span class="pill">{{ $role->labelFa() }}</span></span>
</header>
<main>
    <div class="card">
        <h1 style="font-size:1.05rem;margin:0 0 10px">پنل {{ $role->labelFa() }}</h1>
        <p class="muted">
            این صفحه فقط اثبات دسترسی است: نقش فعال شما «{{ $role->labelFa() }}» است و مسیر
            <code>/{{ $panel }}</code> به همین سطح تعلق دارد. دادهٔ این پنل در فازهای بعدی اضافه می‌شود.
        </p>
        @if ($user && count($user->roleCodes()) > 1)
            <form method="post" action="{{ route('panel.role.switch') }}">
                @csrf
                <label class="muted" for="role">تغییر سطح فعال</label>
                <select id="role" name="role" style="padding:8px;border:1px solid #d1d5db;border-radius:8px">
                    @foreach ($user->roleCodes() as $code)
                        <option value="{{ $code }}" @selected($code === $role->value)>{{ $code }}</option>
                    @endforeach
                </select>
                <button type="submit" style="padding:8px 14px;border:0;border-radius:8px;background:#2563eb;color:#fff">اعمال</button>
            </form>
        @endif
    </div>
</main>
</body>
</html>
