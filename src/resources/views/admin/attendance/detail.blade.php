@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/detail.css') }}">
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
<div class="admin-attendance-detail">
    <div class="admin-attendance-detail__inner">
        <h2 class="admin-attendance-detail__title">勤怠詳細</h2>

        @if(session('success'))
            <div class="admin-attendance-detail__alert admin-attendance-detail__alert--success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="admin-attendance-detail__alert admin-attendance-detail__alert--error">
                {{ session('error') }}
            </div>
        @endif

        @if($isPending)
            <div class="admin-attendance-detail__alert admin-attendance-detail__alert--error">
                承認待ちのため修正はできません。
            </div>
        @endif

        <form action="{{ route('admin.attendance.update', $attendance->id) }}" method="POST">
            @csrf

            <div class="detail-card">
                <div class="detail-row">
                    <div class="detail-row__label">名前</div>
                    <div class="detail-row__value">
                        <span class="detail-text">{{ $attendance->user->name }}</span>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-row__label">日付</div>
                    <div class="detail-row__value detail-row__value--date">
                        @php
                            $date = \Carbon\Carbon::parse($attendance->work_date);
                        @endphp
                        <span class="detail-text">{{ $date->format('Y年') }}</span>
                        <span class="detail-text">{{ $date->format('n月j日') }}</span>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-row__label">出勤・退勤</div>
                    <div class="detail-row__value detail-row__value--time">
                        <input
                            type="time"
                            name="clock_in"
                            class="detail-time-input"
                            value="{{ old('clock_in', $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '') }}"
                            {{ $isPending ? 'disabled' : '' }}
                        >
                        <span class="detail-separator">〜</span>
                        <input
                            type="time"
                            name="clock_out"
                            class="detail-time-input"
                            value="{{ old('clock_out', $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '') }}"
                            {{ $isPending ? 'disabled' : '' }}
                        >
                    </div>
                </div>

                @error('clock_in')
                    <div class="detail-error">{{ $message }}</div>
                @enderror
                @error('clock_out')
                    <div class="detail-error">{{ $message }}</div>
                @enderror

                @foreach($attendance->breakTimes as $index => $break)
                    <div class="detail-row">
                        <div class="detail-row__label">
                            {{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}
                        </div>
                        <div class="detail-row__value detail-row__value--time">
                            <input
                                type="time"
                                name="breaks[{{ $break->id }}][break_start]"
                                class="detail-time-input"
                                value="{{ old('breaks.' . $break->id . '.break_start', $break->break_start ? \Carbon\Carbon::parse($break->break_start)->format('H:i') : '') }}"
                                {{ $isPending ? 'disabled' : '' }}
                            >
                            <span class="detail-separator">〜</span>
                            <input
                                type="time"
                                name="breaks[{{ $break->id }}][break_end]"
                                class="detail-time-input"
                                value="{{ old('breaks.' . $break->id . '.break_end', $break->break_end ? \Carbon\Carbon::parse($break->break_end)->format('H:i') : '') }}"
                                {{ $isPending ? 'disabled' : '' }}
                            >
                        </div>
                    </div>
                @endforeach

                <div class="detail-row">
                    <div class="detail-row__label">
                        {{ $attendance->breakTimes->count() === 0 ? '休憩' : '休憩' . ($attendance->breakTimes->count() + 1) }}
                    </div>
                    <div class="detail-row__value detail-row__value--time">
                        <input
                            type="time"
                            name="breaks[new][break_start]"
                            class="detail-time-input"
                            value="{{ old('breaks.new.break_start') }}"
                            {{ $isPending ? 'disabled' : '' }}
                        >
                        <span class="detail-separator">〜</span>
                        <input
                            type="time"
                            name="breaks[new][break_end]"
                            class="detail-time-input"
                            value="{{ old('breaks.new.break_end') }}"
                            {{ $isPending ? 'disabled' : '' }}
                        >
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-row__label">備考</div>
                    <div class="detail-row__value">
                        <textarea
                            name="note"
                            class="detail-textarea"
                            {{ $isPending ? 'disabled' : '' }}
                        >{{ old('note', $attendance->note) }}</textarea>
                    </div>
                </div>

                @error('breaks')
                    <div class="detail-error">{{ $message }}</div>
                @enderror

                @error('note')
                    <div class="detail-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="detail-actions">
                <button type="button" class="detail-back-button" onclick="history.back()">戻る</button>

                @unless($isPending)
                    <button type="submit" class="detail-submit-button">修正</button>
                @endunless
            </div>
        </form>
    </div>
</div>
@endsection