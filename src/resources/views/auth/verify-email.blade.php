@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/verify-email.css') }}">
@endsection

@section('content')
<div class="verify">
    <div class="verify__inner">
        <p class="verify__text">
            登録していただいたメールアドレスに認証メールを送付しました。<br>
            メール認証を完了してください。
        </p>

        <a href="http://localhost:8025" class="verify__button">認証はこちらから</a>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="verify__resend">認証メールを再送する</button>
        </form>

        @if (session('message'))
            <p class="verify__message">
                {{ session('message') }}
            </p>
        @endif
    </div>
</div>
@endsection