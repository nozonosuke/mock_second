@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/login.css') }}">
@endsection

@section('content')
<div class="login-form__content">
    <div class="login-form__heading">
        <h1>管理者ログイン</h1>
    </div>

    <form action="{{ route('admin.login.store') }}" class="form" method="post">
        @csrf

        <div class="form__group">
            <div class="form__group-title">
                <label for="email" class="form__label">メールアドレス</label>
            </div>
            <div class="form__group-content">
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="form__input"
                >

                <div class="form__error">
                    @error('email')
                        {{ $message }}
                    @enderror
                </div>
            </div>
        </div>

        <div class="form__group">
            <div class="form__group-title">
                <label for="password" class="form__label">パスワード</label>
            </div>
            <div class="form__group-content">
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="form__input"
                >

                <div class="form__error">
                    @error('password')
                        {{ $message }}
                    @enderror
                </div>
            </div>
        </div>

        <div class="form__error">
            @if (session('auth_error'))
                {{ session('auth_error') }}
            @endif
        </div>

        <div class="form__button">
            <button class="form__button-login" type="submit">ログインする</button>
        </div>

        <div class="form__register">
            <a href="{{ route('register') }}">会員登録はこちら</a>
        </div>
    </form>
</div>
@endsection