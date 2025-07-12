@extends('layouts.app')

@section('title', '勤怠詳細画面（一般ユーザー）')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/attendance_show.css') }}">
@endsection

@section('content')
<h2 class="show__title">勤怠詳細</h2>

<div class="show-wrapper">
  <div class="show">
    {{-- 修正申請フォーム --}}
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
            <input type="time" name="clock_in"
              value="{{ optional($attendance->clock_in)->format('H:i') }}"
              class="show__time-input" {{ $isPending ? 'readonly' : '' }}>
            <span class="show__tilde">〜</span>
            <input type="time" name="clock_out"
              value="{{ optional($attendance->clock_out)->format('H:i') }}"
              class="show__time-input" {{ $isPending ? 'readonly' : '' }}>
          </td>
        </tr>

        {{-- 休憩時間 --}}
        @foreach ($attendance->breaks as $i => $break)
        <tr>
          <th>{{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}</th>
          <td>
            <input type="time" name="breaks[{{ $i }}][start]"
              value="{{ optional($break->start)->format('H:i') }}"
              class="show__time-input" {{ $isPending ? 'readonly' : '' }}>
            <span class="show__tilde">〜</span>
            <input type="time" name="breaks[{{ $i }}][end]"
              value="{{ optional($break->end)->format('H:i') }}"
              class="show__time-input" {{ $isPending ? 'readonly' : '' }}>
          </td>
        </tr>
        @endforeach

        {{-- 追加の空欄（承認待ちでは表示しない） --}}
        @unless($isPending)
        <tr>
          <th>休憩{{ $attendance->breaks->count() + 1 }}</th>
          <td>
            <input type="time" name="breaks[{{ $attendance->breaks->count() }}][start]" class="show__time-input">
            <span class="show__tilde">〜</span>
            <input type="time" name="breaks[{{ $attendance->breaks->count() }}][end]" class="show__time-input">
          </td>
        </tr>
        @endunless

        <tr>
          <th>備考</th>
          <td>
            <input type="text" name="note"
              value="{{ old('note', $attendance->note ?? '') }}"
              class="show__note-input" {{ $isPending ? 'readonly' : '' }}>
          </td>
        </tr>
      </table>
    </form>
  </div>

  {{-- 修正ボタンまたは注意表示 --}}
  @if (!$isPending)
  <div class="show__actions-outside">
    <button type="submit" form="form" class="show__submit">修正</button>
  </div>
  @else
  <div class="show__note-warning-wrapper">
    <p class="show__note-warning">※承認待ちのため修正はできません。</p>
  </div>
  @endif
</div>
@endsection

@section('scripts')
{{-- JavaScript 不要 --}}
@endsection