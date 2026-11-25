@extends('layouts.app')

@section('title', 'پروفایل فروشگاه/تأمین‌کننده')
@section('heading', 'درخواست‌های فروشگاه و تأمین‌کننده')
@section('lead')
    <p class="lead">پروفایل پس از ثبت، در انتظار تأیید ادمین است. تا زمان تأیید، پنل فروشگاه یا تأمین‌کننده باز نمی‌شود.</p>
@endsection

@section('content')
    <div class="row" style="margin-bottom:18px;">
        <a href="{{ route('merchant.profiles.create', ['type' => 'store']) }}"
           style="display:block;padding:14px;border:1px solid #e5e7eb;border-radius:10px;text-decoration:none;color:#111827;">
            <strong style="font-size:.9rem;">پروفایل فروشگاه</strong>
            <div class="muted" style="margin-top:6px;">برای همکاران فروشندهٔ کالا (پیوست ۳ بند ۱).</div>
        </a>
        <a href="{{ route('merchant.profiles.create', ['type' => 'supplier']) }}"
           style="display:block;padding:14px;border:1px solid #e5e7eb;border-radius:10px;text-decoration:none;color:#111827;">
            <strong style="font-size:.9rem;">پروفایل تأمین‌کننده</strong>
            <div class="muted" style="margin-top:6px;">برای تأمین‌کنندگان کالا و خدمات (پیوست ۳ بند ۴).</div>
        </a>
    </div>

    @if ($profiles->isEmpty())
        <p class="muted">هنوز درخواستی ثبت نکرده‌اید.</p>
    @else
        <table>
            <thead>
            <tr><th>نوع</th><th>نام کسب‌وکار</th><th>وضعیت</th><th>یادداشت بررسی</th><th>تاریخ</th></tr>
            </thead>
            <tbody>
            @foreach ($profiles as $profile)
                <tr>
                    <td>{{ $profile->type === 'supplier' ? 'تأمین‌کننده' : 'فروشگاه' }}</td>
                    <td>{{ $profile->business_name }}</td>
                    <td>
                        <span class="tag {{ $profile->status }}">
                            {{ ['pending' => 'در انتظار تأیید', 'approved' => 'تأییدشده', 'rejected' => 'ردشده'][$profile->status] ?? $profile->status }}
                        </span>
                    </td>
                    <td>{{ $profile->review_note ?? '—' }}</td>
                    <td>{{ $profile->created_at?->format('Y-m-d') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
@endsection
