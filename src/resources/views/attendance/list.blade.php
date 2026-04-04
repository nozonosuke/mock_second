@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/list.css') }}">
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
<div class="attendance-list-page">
    <div class="attendance-list-page__inner">
        <h1 class="attendance-list-page__title">勤怠一覧</h1>

        <div class="attendance-month-nav">
            <a href="{{ route('attendance.list', ['month' => $prevMonth]) }}" class="attendance-month-nav__link">
                ← 前月
            </a>

            <div class="attendance-month-nav__current">
                <span class="attendance-month-nav__icon">🗓</span>
                <span>{{ $displayMonth }}</span>
            </div>

            <a href="{{ route('attendance.list', ['month' => $nextMonth]) }}" class="attendance-month-nav__link attendance-month-nav__link--next">
                翌月 →
            </a>
        </div>

        <div class="attendance-list-table-wrap">
            <table class="attendance-list-table">
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
                    @foreach ($rows as $row)
                        <tr>
                            <td>{{ $row['date_label'] }}</td>
                            <td>{{ $row['clock_in'] }}</td>
                            <td>{{ $row['clock_out'] }}</td>
                            <td>{{ $row['break_total'] }}</td>
                            <td>{{ $row['work_total'] }}</td>
                            <td>
                                @if($row['id'])
                                    <a href="{{ route('attendance.detail', ['id' => $row['id']]) }}" class="attendance-list-table__detail-link">
                                        詳細
                                    </a>
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