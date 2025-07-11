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
        <td>{{ \Carbon\Carbon::parse($request->attendance->date)->format('Y年n月j日') }}</td>
      </tr>
      <tr>
        <th>出勤・退勤</th>
        <td>
          {{ optional($request->attendance->clock_in)->format('H:i') ?? '--:--' }}
          〜
          {{ optional($request->attendance->clock_out)->format('H:i') ?? '--:--' }}
        </td>
      </tr>

      @php $breakCount = $request->attendance->breaks->count(); @endphp
      @for ($i = 0; $i <= $breakCount; $i++)
        <tr>
        <th>休憩{{ $i + 1 }}</th>
        <td>
          @if (isset($request->attendance->breaks[$i]))
          {{ \Carbon\Carbon::parse($request->attendance->breaks[$i]->start)->format('H:i') }}
          〜
          {{ \Carbon\Carbon::parse($request->attendance->breaks[$i]->end)->format('H:i') }}
          @else
          <span class="show__blank-time">--:--</span> 〜 <span class="show__blank-time">--:--</span>
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
    <form id="approve-form">
      @csrf
      <button type="submit" id="approve-button" class="stamp-show__approve-button">承認</button>
    </form>
  </div>
</div>

{{-- 承認処理 --}}
<script>
  document.getElementById('approve-form').addEventListener('submit', function(e) {
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
</script>
@endsection