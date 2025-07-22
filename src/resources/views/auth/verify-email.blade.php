@extends('layouts.app')

@section('title', 'メール認証')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')
<div class="auth-wrapper">
  <p class="verify-description">
    登録していただいたメールアドレスに認証メールを送付しました。<br>
    メール認証を完了してください。
  </p>

  {{-- Mailhogを開くボタン --}}
  <div class="center-button">
    <a href="http://localhost:8025/#" class="verify-main-button" target="_blank" rel="noopener noreferrer">
      認証はこちらから
    </a>
  </div>

  {{-- 認証メール再送リンク（青文字リンク） --}}
  <div class="center-link">
    <form method="POST" action="{{ route('verification.send') }}">
      @csrf
      <button type="submit" class="resend-link">認証メールを再送する</button>
    </form>
  </div>
</div>
@endsection