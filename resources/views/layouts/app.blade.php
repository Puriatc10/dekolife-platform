<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.brand.fa')) — {{ config('app.brand.fa') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Vazirmatn, Tahoma, sans-serif; background:#f7f7f8; margin:0; color:#111827; }
        header { background:#fff; border-bottom:1px solid #e5e7eb; padding:12px 18px; display:flex; gap:14px; align-items:center; font-size:.85rem; }
        header strong { font-size:.95rem; }
        header a { color:#2563eb; text-decoration:none; }
        header .spacer { flex:1; }
        main { max-width: 560px; margin: 28px auto; background:#fff; border-radius:12px; padding:26px; box-shadow:0 1px 3px rgba(0,0,0,.08); }
        main.wide { max-width: 900px; }
        h1 { font-size:1.05rem; margin:0 0 6px; }
        h2 { font-size:.95rem; margin:22px 0 8px; }
        p.lead { color:#6b7280; font-size:.8rem; margin:0 0 18px; line-height:1.9; }
        label { display:block; font-size:.82rem; color:#4b5563; margin:12px 0 5px; }
        input, select, textarea { width:100%; padding:10px; border:1px solid #d1d5db; border-radius:8px; font-size:.9rem; font-family:inherit; }
        button { margin-top:16px; padding:10px 16px; border:0; border-radius:8px; background:#2563eb; color:#fff; font-size:.9rem; cursor:pointer; }
        button.secondary { background:#e5e7eb; color:#111827; }
        button.danger { background:#b91c1c; }
        .ok { background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:10px 12px; border-radius:8px; font-size:.82rem; margin-bottom:14px; }
        .errors { background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; padding:10px 12px; border-radius:8px; font-size:.82rem; margin-bottom:14px; }
        .debug { background:#fffbeb; border:1px dashed #fcd34d; color:#92400e; padding:10px 12px; border-radius:8px; font-size:.82rem; margin-bottom:14px; }
        .muted { color:#6b7280; font-size:.76rem; margin-top:14px; line-height:1.9; }
        table { width:100%; border-collapse:collapse; font-size:.8rem; }
        th, td { text-align:right; padding:8px 6px; border-bottom:1px solid #f3f4f6; }
        th { color:#6b7280; font-weight:500; }
        .tag { display:inline-block; padding:2px 8px; border-radius:999px; font-size:.72rem; }
        .tag.pending { background:#fef3c7; color:#92400e; }
        .tag.approved { background:#d1fae5; color:#065f46; }
        .tag.rejected { background:#fee2e2; color:#991b1b; }
        .row { display:flex; gap:10px; flex-wrap:wrap; }
        .row > * { flex:1 1 180px; }
        form.inline { display:inline; }
    </style>
</head>
<body>
<header>
    <strong>{{ config('app.brand.fa') }}</strong>
    @auth
        <a href="{{ route('panel.redirect') }}">پنل</a>
        <a href="{{ route('profile.edit') }}">پروفایل</a>
        <a href="{{ route('merchant.profiles.index') }}">فروشگاه/تأمین‌کننده</a>
        @if (auth()->user()->isAdmin())
            <a href="{{ route('admin.dashboard') }}">مدیریت</a>
        @endif
        <span class="spacer"></span>
        <form method="post" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="secondary" style="margin:0;padding:6px 12px;font-size:.8rem;">خروج</button>
        </form>
    @else
        <span class="spacer"></span>
        <a href="{{ route('login') }}">ورود</a>
        <a href="{{ route('register') }}">ثبت‌نام</a>
    @endauth
</header>

<main class="@yield('width')">
    <h1>@yield('heading')</h1>
    @yield('lead')

    @if (session('status'))
        <div class="ok">{{ session('status') }}</div>
    @endif

    @if (session('debug_code'))
        {{-- Local debugging only: OtpService returns the code only when app.debug is on. --}}
        <div class="debug">کد آزمایشی (فقط محیط توسعه): <strong>{{ session('debug_code') }}</strong></div>
    @endif

    @if ($errors->any())
        <div class="errors">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @yield('content')
</main>
</body>
</html>
