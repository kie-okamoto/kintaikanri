{{-- resources/views/auth/verify-email.blade.php --}}
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <title>メール認証誘導</title>
  <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/common.css') }}">
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>

<body class="auth-page">
  @include('components.header')

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

</body>

</html>