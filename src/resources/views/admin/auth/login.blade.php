@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/login.css') }}">
@endsection

@section('content')
<div class="login-form__content">
    <div class="login-form__heading">
        <h2>管理者ログイン</h2>
    </div>

    <form method="POST" action="{{ route('admin.login.store') }}" class="form">
        @csrf

        <input type="hidden" name="login_type" value="admin">

        <div class="form__group">
            <label for="email" class="form__label">メールアドレス</label>
            <input id="email" class="form__input" type="email" name="email" value="{{ old('email') }}">
            @error('email')
                <p class="form__error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form__group">
            <label for="password" class="form__label">パスワード</label>
            <input id="password" class="form__input" type="password" name="password">
            @error('password')
                <p class="form__error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="form__button">管理者ログインする</button>
    </form>
</div>
@endsection