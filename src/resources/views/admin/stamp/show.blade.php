@extends('layouts.admin')

@section('title', '修正申請承認画面（管理者）')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
<link rel="stylesheet" href="{{ asset('css/common.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/stamp_show.css') }}">
@endsection

@section('content')
<div class="stamp-show">
  <h2 class="stamp-show__title">勤怠詳細</h2>

  {{-- 白ブロック＋テーブル --}}
  <div class="stamp-show__card">
    <table class="stamp-show__table">
      <tr>
        <th>名前</th>
        <td>{{ $request->attendance->user->name ?? '不明' }}</td>
      </tr>
      <tr>
        <th>日付</th>
        <td>
          <span class="date__year">
            {{ \Carbon\Carbon::parse($request->attendance->date)->format('Y年') }}
          </span>
          <span class="show__tilde"></span>
          <span class="date__month-day">
            {{ \Carbon\Carbon::parse($request->attendance->date)->format('n月j日') }}
          </span>
        </td>
      </tr>

      <tr>
        <th>出勤・退勤</th>
        <td>
          <span class="show__time-text">
            {{ optional($request->attendance->clock_in)->format('H:i') ?? '--:--' }}
          </span>
          <span class="show__tilde">〜</span>
          <span class="show__time-text">
            {{ optional($request->attendance->clock_out)->format('H:i') ?? '--:--' }}
          </span>
        </td>
      </tr>

      @php
      $breaks = $request->attendance->breaks ?? collect();
      $maxBreaks = max($breaks->count(), 2); // 最低2行表示
      @endphp

      @for ($i = 0; $i < $maxBreaks; $i++)
        <tr>
        <th>{{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}</th>
        <td>
          @if (isset($breaks[$i]))
          <span class="show__time-text">
            {{ \Carbon\Carbon::parse($breaks[$i]->start)->format('H:i') }}
          </span>
          <span class="show__tilde">〜</span>
          <span class="show__time-text">
            {{ \Carbon\Carbon::parse($breaks[$i]->end)->format('H:i') }}
          </span>
          @else
          {{-- 空欄：時間も〜もなし --}}
          <span class="show__time-text">&nbsp;</span>
          @endif
        </td>
        </tr>
        @endfor

        <tr>
          <th>備考</th>
          <td>{{ $request->reason }}</td>
        </tr>
    </table>
  </div>

  {{-- ✅ ボタンをテーブル外の右下に表示 --}}
  <div class="stamp-show__approve-area">
    @if ($request->status === 'approved')
    <button class="stamp-show__approve-button approved" disabled>承認済み</button>
    @else
    <form id="approve-form">
      @csrf
      <button type="submit" id="approve-button" class="stamp-show__approve-button">承認</button>
    </form>
    @endif
  </div>
</div>

{{-- 承認処理 --}}
<script>
  const form = document.getElementById('approve-form');
  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();

      fetch("{{ route('admin.stamp.approve', $request->id) }}", {
          method: "POST",
          headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json",
            "Content-Type": "application/json"
          },
          body: JSON.stringify({})
        })
        .then(response => {
          if (response.ok) {
            const btn = document.getElementById('approve-button');
            btn.textContent = '承認済み';
            btn.disabled = true;
            btn.classList.add('approved');
          } else {
            alert('承認に失敗しました。');
          }
        })
        .catch(() => {
          alert('通信エラーが発生しました。');
        });
    });
  }
</script>
@endsection