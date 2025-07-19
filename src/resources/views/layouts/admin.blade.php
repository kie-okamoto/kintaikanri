<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', '管理者画面')</title>

  {{-- リセットCSS --}}
  <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">

  {{-- 共通CSS（必要に応じて） --}}
  <link rel="stylesheet" href="{{ asset('css/admin/header.css') }}">

  {{-- ページ固有のCSS --}}
  @yield('styles')
</head>

<body>
  {{-- 管理者共通ヘッダー --}}
  @include('components.admin.header')

  {{-- メインコンテンツ --}}
  <main class="admin-main">
    @yield('content')
  </main>

  {{-- ページ固有のJavaScript --}}
  @yield('scripts')
</body>

</html>