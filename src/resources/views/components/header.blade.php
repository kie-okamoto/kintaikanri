{{-- resources/views/components/header.blade.php --}}
<header class="header">
  <div class="header__inner">
    <div class="header__left">
      <a href="/">
        <img src="{{ asset('images/logo.svg') }}" alt="COACHTECHロゴ" class="header__logo">
      </a>
    </div>

    @php
    // ログインしていても、以下のルートではヘッダーナビゲーションを非表示にする
    $hideOnRoutes = ['login', 'register', 'verification.notice'];
    @endphp

    @if (!in_array(Route::currentRouteName(), $hideOnRoutes))
    @auth
    <nav class="header__nav">
      @if (isset($status) && $status === 'done')
      <a href="{{ route('attendance.list') }}">今月の出勤一覧</a>
      <a href="{{ route('stamp.list') }}">申請一覧</a>
      @else
      <a href="{{ route('attendance.index') }}">勤怠</a>
      <a href="{{ route('attendance.list') }}">勤怠一覧</a>
      <a href="{{ route('stamp.list') }}">申請</a>
      @endif
      <form method="POST" action="{{ route('logout') }}" class="header__logout-form">
        @csrf
        <button type="submit" class="header__logout-button">ログアウト</button>
      </form>
    </nav>
    @endauth
    @endif
  </div>
</header>