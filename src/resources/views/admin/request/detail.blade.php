@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/request_detail.css') }}">
@endsection

@section('header')
<nav class="header-nav">
    <ul class="header-nav__list">
        <li class="header-nav__item">
            <a href="{{ route('admin.attendance.list') }}" class="header-nav__link">勤怠一覧</a>
        </li>
        <li class="header-nav__item">
            <a href="{{ route('admin.staff.list') }}" class="header-nav__link">スタッフ一覧</a>
        </li>
        <li class="header-nav__item">
            <a href="{{ route('admin.request.list') }}" class="header-nav__link">申請一覧</a>
        </li>
        <li class="header-nav__item">
            <form method="POST" action="{{ route('admin.logout') }}" class="header-nav__logout-form">
                @csrf
                <button type="submit" class="header-nav__logout-button">ログアウト</button>
            </form>
        </li>
    </ul>
</nav>
@endsection

@section('content')
<div class="request-detail">
    <div class="request-detail__inner">
        <h2 class="request-detail__title">勤怠詳細</h2>

        @if(session('success'))
            <div class="request-detail__alert">
                {{ session('success') }}
            </div>
        @endif

        @php
            $date = \Carbon\Carbon::parse($correctionRequest->attendance->work_date);
        @endphp

        <div class="detail-card">
            <div class="detail-row">
                <div class="detail-row__label">名前</div>
                <div class="detail-row__value">{{ $correctionRequest->user->name }}</div>
            </div>

            <div class="detail-row">
                <div class="detail-row__label">日付</div>
                <div class="detail-row__value detail-row__value--date">
                    <span>{{ $date->format('Y年') }}</span>
                    <span>{{ $date->format('n月j日') }}</span>
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-row__label">出勤・退勤</div>
                <div class="detail-row__value detail-row__value--time">
                    <span>{{ \Carbon\Carbon::parse($correctionRequest->requested_clock_in)->format('H:i') }}</span>
                    <span>〜</span>
                    <span>{{ \Carbon\Carbon::parse($correctionRequest->requested_clock_out)->format('H:i') }}</span>
                </div>
            </div>

            @php
                $displayBreaks = collect($requestedBreaks);
                $breakCount = max(2, $displayBreaks->count());
            @endphp

            @for($i = 0; $i < $breakCount; $i++)
                @php
                    $break = $displayBreaks[$i] ?? null;
                    $label = $i === 0 ? '休憩' : '休憩' . ($i + 1);
                @endphp
                <div class="detail-row">
                    <div class="detail-row__label">{{ $label }}</div>
                    <div class="detail-row__value detail-row__value--time">
                        <span>{{ !empty($break['break_start']) ? \Carbon\Carbon::parse($break['break_start'])->format('H:i') : '' }}</span>
                        <span>{{ (!empty($break['break_start']) || !empty($break['break_end'])) ? '〜' : '' }}</span>
                        <span>{{ !empty($break['break_end']) ? \Carbon\Carbon::parse($break['break_end'])->format('H:i') : '' }}</span>
                    </div>
                </div>
            @endfor

            <div class="detail-row">
                <div class="detail-row__label">備考</div>
                <div class="detail-row__value">{{ $correctionRequest->note }}</div>
            </div>
        </div>

        <div class="request-detail__actions">
            @if($correctionRequest->status === 'pending')
                <form action="{{ route('admin.request.approve', $correctionRequest->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="request-detail__approve-button">承認</button>
                </form>
            @else
                <button type="button" class="request-detail__approved-button" disabled>承認済み</button>
            @endif
        </div>
    </div>
</div>
@endsection