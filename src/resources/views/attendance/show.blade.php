@extends('layouts.app')

@section('title', '勤怠詳細画面（一般ユーザー）')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/attendance_show.css') }}">
@endsection

@section('content')
<h2 class="show__title">勤怠詳細</h2>

<div class="show-wrapper">
  <div class="show">
    {{-- ✅ formタグにid="form" を追加 --}}
    <form id="form" method="POST" action="{{ route('attendance.updateRequest', $attendance->id) }}">
      @csrf
      @method('POST')

      <table class="show__table">
        <tr>
          <th>名前</th>
          <td>{{ Auth::user()->name }}</td>
        </tr>

        <tr>
          <th>日付</th>
          <td>
            <span class="show__date-year">{{ \Carbon\Carbon::parse($attendance->date)->year }}年</span>
            <span class="show__date-monthday">{{ \Carbon\Carbon::parse($attendance->date)->format('n月j日') }}</span>
          </td>
        </tr>

        <tr>
          <th>出勤・退勤</th>
          <td>
            <input type="time" name="clock_in" value="{{ optional($attendance->clock_in)->format('H:i') }}" class="show__time-input" {{ $isPending ? 'readonly' : '' }}>
            <span class="show__tilde">〜</span>
            <input type="time" name="clock_out" value="{{ optional($attendance->clock_out)->format('H:i') }}" class="show__time-input" {{ $isPending ? 'readonly' : '' }}>
          </td>
        </tr>

        <tr>
          <th>休憩</th>
          <td>
            <input type="time" name="breaks[0][start]" value="{{ optional($attendance->break_start)->format('H:i') }}" class="show__time-input" {{ $isPending ? 'readonly' : '' }}>
            <span class="show__tilde">〜</span>
            <input type="time" name="breaks[0][end]" value="{{ optional($attendance->break_end)->format('H:i') }}" class="show__time-input" {{ $isPending ? 'readonly' : '' }}>
          </td>
        </tr>

        <tr>
          <th>休憩2</th>
          <td>
            <input type="time" name="breaks[1][start]" value="" class="show__time-input" {{ $isPending ? 'readonly' : '' }}>
            <span class="show__tilde">〜</span>
            <input type="time" name="breaks[1][end]" value="" class="show__time-input" {{ $isPending ? 'readonly' : '' }}>
          </td>
        </tr>

        <tr>
          <th>備考</th>
          <td>
            <input type="text" name="note" value="{{ old('note', $attendance->note ?? '') }}" class="show__note-input" {{ $isPending ? 'readonly' : '' }}>
          </td>
        </tr>
      </table>
    </form>
  </div>

  {{-- 修正ボタン：承認待ち以外のときのみ表示 --}}
  @if (!$isPending)
  <div class="show__actions-outside">
    <button type="submit" form="form" class="show__submit">修正</button>
  </div>
  @else
  {{-- ✅ 修正不可コメントは白枠外・右寄せ --}}
  <div class="show__note-warning-wrapper">
    <p class="show__note-warning">※承認待ちのため修正はできません。</p>
  </div>
  @endif

</div>
@endsection