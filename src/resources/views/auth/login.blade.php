@extends('layouts.app')

@section('title', 'ログイン')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')
<div class="container">
  <h1>ログイン</h1>

  <form method="POST" action="{{ route('login') }}">
    @csrf

    <label for="email">メールアドレス</label>
    <input type="text" name="email" id="email" value="{{ old('email') }}">
    @error('email')
    <div class="error">{{ $message }}</div>
    @enderror

    <label for="password">パスワード</label>
    <input type="password" name="password" id="password">
    @error('password')
    <div class="error">{{ $message }}</div>
    @enderror

    <button type="submit">ログインする</button>
  </form>

  <div class="center-link">
    <a href="{{ route('register') }}">会員登録はこちら</a>
  </div>
</div>
@endsection