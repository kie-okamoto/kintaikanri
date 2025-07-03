@extends('layouts.admin')

@section('title', '勤怠詳細（管理者）')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/attendance_show.css') }}">
@endsection

@section('content')
<h2 class="show__title">勤怠詳細</h2>

<div class="show-wrapper">
  <div class="show">
    <table class="show__table">
      <tr>
        <th>名前</th>
        <td>{{ $attendance->user->name }}</td>
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
          {{ optional($attendance->clock_in)->format('H:i') }} 〜 {{ optional($attendance->clock_out)->format('H:i') }}
        </td>
      </tr>

      {{-- ✅ 休憩時間の表示（申請済 + 空欄1行） --}}
      @php
      $breakCount = $attendance->breaks->count();
      @endphp

      @for ($i = 0; $i <= $breakCount; $i++)
        <tr>
        <th>休憩{{ $i + 1 }}</th>
        <td>
          @if (isset($attendance->breaks[$i]))
          {{ \Carbon\Carbon::parse($attendance->breaks[$i]->start)->format('H:i') }}
          〜
          {{ \Carbon\Carbon::parse($attendance->breaks[$i]->end)->format('H:i') }}
          @else
          <span class="show__blank-time">--:--</span> 〜 <span class="show__blank-time">--:--</span>
          @endif
        </td>
        </tr>
        @endfor

        <tr>
          <th>備考</th>
          <td>{{ $attendance->note }}</td>
        </tr>
    </table>

    <div class="show__actions-outside">
      <a href="{{ route('admin.attendance.list') }}" class="show__submit">修正</a>
    </div>
  </div>
</div>
@endsection