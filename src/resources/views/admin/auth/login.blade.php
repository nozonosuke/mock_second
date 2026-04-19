@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/login.css') }}">
@endsection

@section('content')
<div class="login-form__content">
    <div class="login-form__heading">
        <h2>Admin Login</h2>
    </div>

    <form method="POST" action="{{ url('/login') }}" class="form">
        @csrf

        <input type="hidden" name="login_type" value="admin">

        <div class="form__group">
            <label for="email">メールアドレス</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}">
            @error('email')
                <p class="form__error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form__group">
            <label for="password">パスワード</label>
            <input id="password" type="password" name="password">
            @error('password')
                <p class="form__error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="form__button">ログイン</button>
    </form>
</div>
@endsection