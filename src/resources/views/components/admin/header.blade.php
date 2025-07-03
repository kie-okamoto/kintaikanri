<header class="admin-header">
  <div class="admin-header__inner">
    <div class="admin-header__left">
      <a href="{{ route('admin.attendance.list') }}" class="admin-header__logo">
        <img src="{{ asset('images/logo.svg') }}" alt="管理者ロゴ">
      </a>
    </div>

    <nav class="admin-header__nav">
      <a href="{{ route('admin.attendance.list') }}">勤怠一覧</a>
      <a href="{{ route('admin.staff.list') }}">スタッフ一覧</a>
      <a href="{{ route('admin.stamp.list') }}">申請一覧</a>

      {{-- ログアウト --}}
      <a href="{{ route('admin.logout') }}"
        onclick="event.preventDefault(); document.getElementById('admin-logout-form').submit();">
        ログアウト
      </a>
      <form id="admin-logout-form" action="{{ route('admin.logout') }}" method="POST" style="display: none;">
        @csrf
      </form>
    </nav>
  </div>
</header>