<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>پنل مدیریت — {{ config('app.brand.fa') }}</title>
    <style>
        body { font-family: Vazirmatn, Tahoma, sans-serif; background:#f7f7f8; margin:0; color:#111827; }
        header { background:#111827; color:#fff; padding:14px 22px; display:flex; justify-content:space-between; align-items:center; }
        main { max-width: 900px; margin: 24px auto; padding: 0 16px; }
        .card { background:#fff; border-radius:12px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,.08); margin-bottom:18px; }
        table { width:100%; border-collapse:collapse; font-size:.85rem; }
        th, td { text-align:right; padding:8px 6px; border-bottom:1px solid #eee; }
        th { color:#6b7280; font-weight:500; }
        .muted { color:#6b7280; font-size:.8rem; }
    </style>
</head>
<body>
<header>
    <strong>پنل مدیریت {{ config('app.brand.fa') }}</strong>
    <span class="muted">{{ $user?->email }} — سطح فعال: {{ $activeRole?->label_fa }}</span>
</header>
<main>
    <section class="card">
        <h2 style="font-size:1rem;margin:0 0 10px">وضعیت پایه</h2>
        <p class="muted">
            فاز ۱ (هویت، نقش‌ها و مجوزها) فعال است. این داشبورد فقط اثبات می‌کند که زنجیرهٔ
            ورود → نقش فعال → تأیید دومرحله‌ای → ثبت رویداد کار می‌کند؛ بقیهٔ ماژول‌ها در فازهای بعدی می‌آیند.
        </p>
    </section>

    <section class="card">
        <h2 style="font-size:1rem;margin:0 0 10px">آخرین رویدادهای حساس</h2>
        <table>
            <thead>
            <tr><th>#</th><th>رویداد</th><th>کاربر</th><th>سطح</th><th>زمان</th></tr>
            </thead>
            <tbody>
            @forelse ($events as $event)
                <tr>
                    <td>{{ $event->id }}</td>
                    <td>{{ $event->event }}</td>
                    <td>{{ $event->actor_id ?? '—' }}</td>
                    <td>{{ $event->actor_role ?? '—' }}</td>
                    <td>{{ $event->created_at }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">رویدادی ثبت نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
</main>
</body>
</html>
