<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <title>ログイン画面（管理者）</title>
  <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/common.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin/login.css') }}">
</head>

<body class="auth-page">
  @include('components.header')

  <div class="container">
    <h1>管理者ログイン</h1>

    <form method="POST" action="{{ route('admin.login') }}">
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

      <button type="submit">管理者ログインする</button>
    </form>
  </div>
</body>

</html>