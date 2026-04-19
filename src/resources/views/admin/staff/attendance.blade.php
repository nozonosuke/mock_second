@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff_attendance.css') }}">
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
<div class="staff-attendance">
    <div class="staff-attendance__inner">
        <h2 class="staff-attendance__title">{{ $user->name }}さんの勤怠</h2>

        <div class="staff-attendance__nav">
            <a href="{{ route('admin.staff.attendance', ['user' => $user->id, 'month' => $prevMonth]) }}" class="staff-attendance__nav-link">
                ← 前月
            </a>

            <div class="staff-attendance__month">
                {{ $currentMonth->format('Y/m') }}
            </div>

            <a href="{{ route('admin.staff.attendance', ['user' => $user->id, 'month' => $nextMonth]) }}" class="staff-attendance__nav-link">
                翌月 →
            </a>
        </div>

        <div class="staff-attendance__table-wrap">
            <table class="staff-attendance__table">
                <thead>
                    <tr>
                        <th>日付</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($attendanceList as $attendance)
                    <tr>
                        <td>{{ $attendance['date'] }}</td>
                        <td>{{ $attendance['clock_in'] }}</td>
                        <td>{{ $attendance['clock_out'] }}</td>
                        <td>{{ $attendance['break_time'] }}</td>
                        <td>{{ $attendance['total_time'] }}</td>
                        <td>
                            @if ($attendance['attendance_id'])
                                <a href="{{ route('admin.attendance.show', $attendance['attendance_id']) }}" class="staff-attendance__detail-link">
                                    詳細
                                </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="staff-attendance__actions">
            <a href="{{ route('admin.staff.attendance.csv', ['user' => $user->id, 'month' => $currentMonth->format('Y-m')]) }}"
            class="staff-attendance__csv-button">
                CSV出力
            </a>
        </div>
    </div>
</div>
@endsection