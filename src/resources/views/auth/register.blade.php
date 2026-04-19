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
            <div class="form__group-title">
                <label for="name">ユーザー名</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" />
                    <div class="form__error">
                        @error('name')
                        {{ $message }}
                        @enderror
                    </div>
                <label for="email">メールアドレス</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" />
                    <div class="form__error">
                        @error('email')
                        {{ $message }}
                        @enderror
                    </div>
                
                <label for="password">パスワード</label>
                <input id="password" type="password" name="password" />
                    <div class="form__error">
                        @error('password')
                        {{ $message }}
                        @enderror
                    </div>

                <label>確認用パスワード</label>
                <input type="password" name="password_confirmation" />
                    <div class="form__error">
                        @error('password_confirmation')
                        {{ $message }}
                        @enderror
                    </div>
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