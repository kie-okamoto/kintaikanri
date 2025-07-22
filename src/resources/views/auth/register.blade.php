@extends('layouts.app')

@section('title', '会員登録')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')
<div class="container">
  <h1>会員登録</h1>

  <form method="POST" action="{{ route('register') }}">
    @csrf

    <label for="name">名前</label>
    <input type="text" name="name" id="name" value="{{ old('name') }}">
    @error('name')
    <div class="error">{{ $message }}</div>
    @enderror

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

    <label for="password_confirmation">パスワード確認</label>
    <input type="password" name="password_confirmation" id="password_confirmation">

    <button type="submit">登録する</button>
  </form>

  <div class="center-link">
    <a href="{{ route('login') }}">ログインはこちら</a>
  </div>
</div>
@endsection