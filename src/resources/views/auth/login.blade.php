{{-- resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <title>ログイン</title>
  <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/common.css') }}">
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>

<body class="auth-page">
  @include('components.header')

  <div class="container">
    <h1>ログイン</h1>

    <form method="POST" action="{{ route('login') }}">
      @csrf

      <label for="email">メールアドレス</label>
      <input type="email" name="email" id="email" value="{{ old('email') }}" required>

      <label for="password">パスワード</label>
      <input type="password" name="password" id="password" required>

      <button type="submit">ログインする</button>
    </form>

    <div class="center-link">
      <a href="{{ route('register') }}">会員登録はこちら</a>
    </div>
  </div>

</body>

</html>