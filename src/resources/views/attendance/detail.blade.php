@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}">
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
<div class="attendance-detail">
    <div class="attendance-detail__inner">
        <div class="attendance-detail__heading">
            <span class="attendance-detail__heading-line"></span>
            <h1 class="attendance-detail__title">勤怠詳細</h1>
            @if ($isPending)
                <p class="attendance-detail__pending">承認待ちのため修正はできません。</p>
            @endif

            @if(session('message'))
                <p class="attendance-detail__success">{{ session('message') }}</p>
            @endif
        </div>

        <form method="POST" action="{{ route('stamp_correction_request.store') }}">
            @csrf
            <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">

            <table class="attendance-detail__table">
                <tr>
                    <th>名前</th>
                    <td>{{ $attendance->user->name }}</td>
                </tr>

                <tr>
                    <th>日付</th>
                    <td>
                        <div class="attendance-detail__date">
                            <span>{{ \Carbon\Carbon::parse($attendance->work_date)->format('Y年') }}</span>
                            <span>{{ \Carbon\Carbon::parse($attendance->work_date)->format('n月j日') }}</span>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th>出勤・退勤</th>
                    <td>
                        <div class="attendance-detail__time-range">
                            <input
                                class="attendance-detail__time-input"
                                type="time"
                                name="clock_in"
                                value="{{ old('clock_in', $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '') }}"
                                @if($isPending) disabled @endif
                            >
                            <span class="attendance-detail__separator">～</span>
                            <input
                                class="attendance-detail__time-input"
                                type="time"
                                name="clock_out"
                                value="{{ old('clock_out', $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '') }}"
                                @if($isPending) disabled @endif
                            >
                        </div>
                        @error('clock_in')
                            <p class="attendance-detail__error">{{ $message }}</p>
                        @enderror
                        @error('clock_out')
                            <p class="attendance-detail__error">{{ $message }}</p>
                        @enderror
                    </td>
                </tr>

                @foreach($breakTimes as $index => $breakTime)
                    <tr>
                        <th>{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</th>
                        <td>
                            <div class="attendance-detail__time-range">
                                <input
                                    class="attendance-detail__time-input"
                                    type="time"
                                    name="breaks[{{ $index }}][break_start]"
                                    value="{{ old('breaks.' . $index . '.break_start', $breakTime->break_start ? \Carbon\Carbon::parse($breakTime->break_start)->format('H:i') : '') }}"
                                    @if($isPending) disabled @endif
                                >
                                <span class="attendance-detail__separator">～</span>
                                <input
                                    class="attendance-detail__time-input"
                                    type="time"
                                    name="breaks[{{ $index }}][break_end]"
                                    value="{{ old('breaks.' . $index . '.break_end', $breakTime->break_end ? \Carbon\Carbon::parse($breakTime->break_end)->format('H:i') : '') }}"
                                    @if($isPending) disabled @endif
                                >
                            </div>

                            @error("breaks.$index.break_start")
                                <p class="attendance-detail__error">{{ $message }}</p>
                            @enderror
                            @error("breaks.$index.break_end")
                                <p class="attendance-detail__error">{{ $message }}</p>
                            @enderror
                        </td>
                    </tr>
                @endforeach

                <tr>
                    <th>備考</th>
                    <td>
                        <textarea class="attendance-detail__note" name="note" @if($isPending) disabled @endif>{{ old('note', $attendance->note) }}</textarea>
                        @error('note')
                            <p class="attendance-detail__error">{{ $message }}</p>
                        @enderror
                    </td>
                </tr>
            </table>

            @if (!$isPending)
                <div class="attendance-detail__button-wrap">
                    <button type="submit" class="attendance-detail__button">修正</button>
                </div>
            @endif
        </form>
    </div>
</div>
@endsection