@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/list.css') }}">
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
            <form method="POST" action="{{ route('admin.logout') }}" class="header-nav__logout-form">
                @csrf
                <button type="submit" class="header-nav__logout-button">ログアウト</button>
            </form>
        </li>
    </ul>
</nav>
@endsection

@section('content')
<div class="attendance-list">
    <div class="attendance-list__inner">
        <h2 class="attendance-list__title">{{ $currentDate->format('Y年n月j日') }}の勤怠</h2>

        <div class="attendance-list__nav">
            <a href="{{ route('admin.attendance.list', ['date' => $prevDate]) }}" class="attendance-list__nav-link">
                ← 前日
            </a>

            <div class="attendance-list__date">
                {{ $currentDate->format('Y/m/d') }}
            </div>

            <a href="{{ route('admin.attendance.list', ['date' => $nextDate]) }}" class="attendance-list__nav-link">
                翌日 →
            </a>
        </div>

        <div class="attendance-list__table-wrap">
            <table class="attendance-list__table">
                <thead>
                    <tr>
                        <th>名前</th>
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
                        <td>{{ $attendance['user_name'] }}</td>
                        <td>{{ $attendance['clock_in'] }}</td>
                        <td>{{ $attendance['clock_out'] }}</td>
                        <td>{{ $attendance['break_time'] }}</td>
                        <td>{{ $attendance['total_time'] }}</td>
                        <td>
                            @if ($attendance['attendance_id'])
                                <a href="{{ route('admin.attendance.show', $attendance['attendance_id']) }}" class="attendance-list__detail-link">
                                    詳細
                                </a>
                            @else
                                
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection