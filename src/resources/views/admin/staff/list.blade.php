@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff_list.css') }}">
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
<div class="staff-list">
    <div class="staff-list__inner">
        <h2 class="staff-list__title">スタッフ一覧</h2>

        <div class="staff-list__table-wrap">
            <table class="staff-list__table">
                <thead>
                    <tr>
                        <th>名前</th>
                        <th>メールアドレス</th>
                        <th>月次勤怠</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <a href="{{ route('admin.staff.attendance', $user->id) }}" class="staff-list__detail-link">
                                詳細
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection