@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('header')
<nav class="header-nav">
    <ul class="header-nav__list">
        <li class="header-nav__item">
            <a href="{{ route('attendance.index') }}" class="header-nav__link">勤怠</a>
        </li>
        <li class="header-nav__item">
            <a href="{{ route('attendance.list') }}" class="header-nav__link">勤怠一覧</a>
        </li>
        <li class="header-nav__item">
            <a href="{{ route('stamp_correction_request.list') }}" class="header-nav__link">申請</a>
        </li>
        <li class="header-nav__item">
            <form method="POST" action="{{ route('logout') }}" class="header-nav__logout-form">
                @csrf
                <button type="submit" class="header-nav__logout-button">ログアウト</button>
            </form>
        </li>
    </ul>
</nav>
@endsection

@section('content')
<div class="attendance-page">
    <div class="attendance-card">
        @if (session('message'))
            <p class="attendance-card__flash-message">{{ session('message') }}</p>
        @endif

        <p class="attendance-card__status">{{ $statusLabel }}</p>
        <p class="attendance-card__date">{{ $currentDate }}</p>
        <p class="attendance-card__time">{{ $currentTime }}</p>

        <div class="attendance-card__actions">
            @if ($status === 'off_duty')
                <form method="POST" action="{{ route('attendance.clockIn') }}">
                    @csrf
                    <button type="submit" class="attendance-button attendance-button--primary">出勤</button>
                </form>
            @elseif ($status === 'working')
                <form method="POST" action="{{ route('attendance.breakStart') }}">
                    @csrf
                    <button type="submit" class="attendance-button attendance-button--secondary">休憩入</button>
                </form>

                <form method="POST" action="{{ route('attendance.clockOut') }}">
                    @csrf
                    <button type="submit" class="attendance-button attendance-button--primary">退勤</button>
                </form>
            @elseif ($status === 'on_break')
                <form method="POST" action="{{ route('attendance.breakEnd') }}">
                    @csrf
                    <button type="submit" class="attendance-button attendance-button--secondary">休憩戻</button>
                </form>
            @elseif ($status === 'done')
                <p class="attendance-card__message">お疲れ様でした。</p>
            @endif
        </div>
    </div>
</div>
@endsection