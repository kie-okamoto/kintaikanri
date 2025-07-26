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
    <form id="form" method="POST" action="{{ route('attendance.updateRequest', $attendance->id ?? 'new') }}">
      @csrf
      @method('POST')
      <input type="hidden" name="date" value="{{ $targetDate }}">


      <table class="show__table">
        {{-- 名前 --}}
        <tr>
          <th>名前</th>
          <td>{{ Auth::user()->name }}</td>
        </tr>

        {{-- 日付 --}}
        <tr>
          <th>日付</th>
          <td>
            @php
            $parsedDate = \Carbon\Carbon::parse($targetDate);
            @endphp
            <div class="show__date-row">
              <div class="show__date-year">{{ $parsedDate->year }}年</div>
              <div class="show__date-monthday">{{ $parsedDate->format('n月j日') }}</div>
            </div>
          </td>
        </tr>

        {{-- 出勤・退勤 --}}
        <tr>
          <th>出勤・退勤</th>
          <td>
            <div class="show__time-row">
              <input type="time" name="clock_in" value="{{ old('clock_in', optional($attendance->clock_in)->format('H:i')) }}"
                class="show__time-input no-clock" @if($isPending) disabled @endif>
              @if($isPending)
              <input type="hidden" name="clock_in" value="{{ old('clock_in', optional($attendance->clock_in)->format('H:i')) }}">
              @endif

              <span class="show__tilde">〜</span>

              <input type="time" name="clock_out" value="{{ old('clock_out', optional($attendance->clock_out)->format('H:i')) }}"
                class="show__time-input no-clock" @if($isPending) disabled @endif>
              @if($isPending)
              <input type="hidden" name="clock_out" value="{{ old('clock_out', optional($attendance->clock_out)->format('H:i')) }}">
              @endif
            </div>

            @if ($errors->has('clock_in'))
            <div class="show__error">{{ $errors->first('clock_in') }}</div>
            @endif
          </td>
        </tr>

        {{-- 休憩 --}}
        @for ($i = 0; $i < $breakCount + ($isPending ? 0 : 1); $i++)
          @php
          $startValue=old("breaks.{$i}.start", isset($attendance->breaks[$i]) && $attendance->breaks[$i]->start ? $attendance->breaks[$i]->start->format('H:i') : '');
          $endValue = old("breaks.{$i}.end", isset($attendance->breaks[$i]) && $attendance->breaks[$i]->end ? $attendance->breaks[$i]->end->format('H:i') : '');
          @endphp
          <tr>
            <th>{{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}</th>
            <td>
              <div class="show__time-row">
                <input type="time" name="breaks[{{ $i }}][start]" value="{{ $startValue }}"
                  class="show__time-input no-clock" @if($isPending) disabled @endif>
                @if($isPending)
                <input type="hidden" name="breaks[{{ $i }}][start]" value="{{ $startValue }}">
                @endif

                <span class="show__tilde">〜</span>

                <input type="time" name="breaks[{{ $i }}][end]" value="{{ $endValue }}"
                  class="show__time-input no-clock" @if($isPending) disabled @endif>
                @if($isPending)
                <input type="hidden" name="breaks[{{ $i }}][end]" value="{{ $endValue }}">
                @endif
              </div>

              @foreach (['start', 'end'] as $key)
              @if ($errors->has("breaks.{$i}.{$key}"))
              <div class="show__error">{{ $errors->first("breaks.{$i}.{$key}") }}</div>
              @endif
              @endforeach
            </td>
          </tr>
          @endfor

          {{-- 備考 --}}
          <tr>
            <th>備考</th>
            <td>
              <div class="show__note-wrapper">
                <input type="text" name="note" value="{{ old('note', $attendance->note ?? '') }}"
                  class="show__note-input" @if($isPending) disabled @endif>
                @if($isPending)
                <input type="hidden" name="note" value="{{ old('note', $attendance->note ?? '') }}">
                @endif
              </div>

              @foreach ($errors->get('note') as $message)
              <div class="show__error">{{ $message }}</div>
              @endforeach
            </td>
          </tr>
      </table>
    </form>
  </div>
</div>

@if ($isPending)
<div class="show__note-warning-wrapper">
  <p class="show__note-warning">※承認待ちのため修正はできません。</p>
</div>
@else
<div class="show__button-outside">
  <button type="submit" form="form" class="show__submit">修正</button>
</div>
@endif
@endsection