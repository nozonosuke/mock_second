@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/request_list.css') }}">
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
<div class="request-list">
    <div class="request-list__inner">
        <h2 class="request-list__title">申請一覧</h2>

        <div class="request-list__tabs">
            <a href="{{ route('admin.request.list', ['status' => 'pending']) }}"
               class="request-list__tab {{ $status === 'pending' ? 'request-list__tab--active' : '' }}">
                承認待ち
            </a>
            <a href="{{ route('admin.request.list', ['status' => 'approved']) }}"
               class="request-list__tab {{ $status === 'approved' ? 'request-list__tab--active' : '' }}">
                承認済み
            </a>
        </div>

        <div class="request-list__table-wrap">
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
                    @foreach ($requests as $requestItem)
                    <tr>
                        <td>{{ $requestItem->status === 'pending' ? '承認待ち' : '承認済み' }}</td>
                        <td>{{ $requestItem->user->name }}</td>
                        <td>{{ \Carbon\Carbon::parse($requestItem->attendance->work_date)->format('Y/m/d') }}</td>
                        <td>{{ $requestItem->note }}</td>
                        <td>{{ \Carbon\Carbon::parse($requestItem->created_at)->format('Y/m/d') }}</td>
                        <td>
                            <a href="{{ route('admin.request.show', $requestItem->id) }}" class="request-list__detail-link">
                                詳細
                            </a>
                        </td>
                    </tr>
                    @endforeach

                    @if ($requests->isEmpty())
                    <tr>
                        <td colspan="6"></td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection