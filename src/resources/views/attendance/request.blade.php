@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/request.css') }}">
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
<div class="request-list">
    <div class="request-list__inner">
        <div class="request-list__heading">
            <span class="request-list__heading-line"></span>
            <h1 class="request-list__title">申請一覧</h1>
        </div>

        <div class="request-list__tabs">
            <a href="{{ route('stamp_correction_request.list', ['status' => 'pending']) }}"
               class="request-list__tab {{ $status === 'pending' ? 'request-list__tab--active' : '' }}">
                承認待ち
            </a>
            <a href="{{ route('stamp_correction_request.list', ['status' => 'approved']) }}"
               class="request-list__tab {{ $status === 'approved' ? 'request-list__tab--active' : '' }}">
                承認済み
            </a>
        </div>

        <table class="request-list__table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $request)
                    <tr>
                        <td>{{ $request->status === 'pending' ? '承認待ち' : '承認済み' }}</td>
                        <td>{{ optional($request->user)->name }}</td>
                        <td>{{ optional($request->attendance)->work_date 
                                ? \Carbon\Carbon::parse($request->attendance->work_date)->format('Y/m/d') 
                                : '' }}
                        </td>
                        <td>{{ $request->note }}</td>
                        <td>{{ \Carbon\Carbon::parse($request->created_at)->format('Y/m/d') }}</td>
                        <td>
                            <a href="{{ route('attendance.detail', ['id' => $request->attendance_id]) }}">詳細</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">申請はありません。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection