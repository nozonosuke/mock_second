@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/register.css') }}">
@endsection

@section('content')
<div class="register-form__content">
    <div class="register-form__heading">
        <h1>会員登録</h1>
    </div>

    <form action="{{ url('/register') }}" class="form" method="post" novalidate>
        @csrf

        <div class="form__group">
            <label for="name" class="form__label">名前</label>
            <input id="name" class="form__input" type="text" name="name" value="{{ old('name') }}">
            <div class="form__error">
                @error('name')
                    {{ $message }}
                @enderror
            </div>
        </div>

        <div class="form__group">
            <label for="email" class="form__label">メールアドレス</label>
            <input id="email" class="form__input" type="email" name="email" value="{{ old('email') }}">
            <div class="form__error">
                @error('email')
                    {{ $message }}
                @enderror
            </div>
        </div>

        <div class="form__group">
            <label for="password" class="form__label">パスワード</label>
            <input id="password" class="form__input" type="password" name="password">
            <div class="form__error">
                @error('password')
                    {{ $message }}
                @enderror
            </div>
        </div>

        <div class="form__group">
            <label for="password_confirmation" class="form__label">パスワード確認</label>
            <input id="password_confirmation" class="form__input" type="password" name="password_confirmation">
            <div class="form__error">
                @error('password_confirmation')
                    {{ $message }}
                @enderror
            </div>
        </div>

        <div class="form__button">
            <button class="form__button-register" type="submit">登録する</button>
        </div>

        <div class="form__login">
            <a href="/login">ログインはこちら</a>
        </div>
    </form>
</div>
@endsection