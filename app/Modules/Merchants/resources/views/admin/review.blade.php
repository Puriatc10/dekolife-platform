@extends('layouts.app')

@section('title', 'بررسی پروفایل‌های فروشگاه و تأمین‌کننده')
@section('heading', 'بررسی پروفایل فروشگاه و تأمین‌کننده')
@section('width', 'wide')
@section('lead')
    <p class="lead">
        تأیید پروفایل، نقش فروشگاه یا تأمین‌کننده را به کاربر می‌دهد؛ رد آن، دلیل را برای متقاضی ثبت می‌کند.
        هر دو تصمیم در لاگ حسابرسی ثبت می‌شوند.
    </p>
@endsection

@section('content')
    <div class="row" style="margin-bottom:14px;">
        @foreach (['pending' => 'در انتظار', 'approved' => 'تأییدشده', 'rejected' => 'ردشده'] as $key => $label)
            <a href="{{ route('admin.merchants.index', ['status' => $key]) }}"
               style="text-decoration:none;color:#111827;border:1px solid {{ $status === $key ? '#2563eb' : '#e5e7eb' }};border-radius:10px;padding:10px 12px;">
                {{ $label }} <strong style="margin-inline-start:6px;">{{ (int) ($counts[$key] ?? 0) }}</strong>
            </a>
        @endforeach
    </div>

    @if ($profiles->isEmpty())
        <p class="muted">در این وضعیت درخواستی وجود ندارد.</p>
    @else
        <table>
            <thead>
            <tr>
                <th>#</th><th>نوع</th><th>کسب‌وکار</th><th>متقاضی</th><th>موبایل</th>
                <th>استان/شهر</th><th>وضعیت</th><th>تصمیم</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($profiles as $profile)
                <tr>
                    <td>{{ $profile->id }}</td>
                    <td>{{ $profile->type === 'supplier' ? 'تأمین‌کننده' : 'فروشگاه' }}</td>
                    <td>{{ $profile->business_name }}<div class="muted" style="margin:2px 0 0;">{{ $profile->owner_name }}</div></td>
                    <td>{{ $profile->user?->name }}<div class="muted" style="margin:2px 0 0;">{{ $profile->user?->email }}</div></td>
                    <td>{{ $profile->user?->mobile }}</td>
                    <td>{{ trim(($profile->province ?? '').' / '.($profile->city ?? ''), ' /') ?: '—' }}</td>
                    <td>
                        <span class="tag {{ $profile->status }}">
                            {{ ['pending' => 'در انتظار', 'approved' => 'تأییدشده', 'rejected' => 'ردشده'][$profile->status] ?? $profile->status }}
                        </span>
                    </td>
                    <td style="min-width:220px;">
                        @if ($profile->isPending())
                            <form method="post" action="{{ route('admin.merchants.approve', $profile) }}" class="inline">
                                @csrf
                                <button type="submit" style="margin:0;padding:6px 10px;font-size:.78rem;">تأیید</button>
                            </form>
                            <form method="post" action="{{ route('admin.merchants.reject', $profile) }}" style="margin-top:6px;">
                                @csrf
                                <input name="reason" placeholder="دلیل رد" required style="padding:6px;font-size:.78rem;">
                                <button type="submit" class="danger" style="margin:6px 0 0;padding:6px 10px;font-size:.78rem;">رد</button>
                            </form>
                        @else
                            <span class="muted">{{ $profile->review_note ?: '—' }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
@endsection
