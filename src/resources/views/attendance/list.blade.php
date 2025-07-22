@extends('layouts.app')

@section('title', '勤怠一覧画面（一般ユーザー）')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="list">
  <h2 class="list__title">勤怠一覧</h2>

  {{-- カレンダー風の月移動UI --}}
  <div class="list__calendar-nav">
    <div class="calendar-nav__form">
      @php
      use Carbon\Carbon;
      Carbon::setLocale('ja');
      $currentMonth = Carbon::createFromFormat('Y-m', $month);
      $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
      $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');
      @endphp

      <a href="{{ route('attendance.list', ['month' => $prevMonth]) }}" class="calendar-nav__button">← 前月</a>

      <span class="calendar-nav__current">
        <i class="fa fa-calendar"></i> {{ $currentMonth->format('Y年m月') }}
      </span>

      <a href="{{ route('attendance.list', ['month' => $nextMonth]) }}" class="calendar-nav__button">翌月 →</a>
    </div>
  </div>

  <table class="list__table">
    <thead>
      <tr>
        <th>日付</th>
        <th>出勤</th>
        <th>退勤</th>
        <th>休憩</th>
        <th>合計</th>
        <th>詳細</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($attendances as $attendance)
      @php
      $today = Carbon::today();
      $attendanceDate = $attendance->date instanceof Carbon
      ? $attendance->date
      : Carbon::parse($attendance->date);
      $isPastOrToday = $attendanceDate->lte($today);
      $linkId = $attendance->id ?? 'new';
      @endphp
      <tr>
        <td>{{ $attendanceDate->isoFormat('MM/DD（dd）') }}</td>
        <td>{{ $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '' }}</td>
        <td>{{ $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '' }}</td>
        <td>{{ $attendance->calculated_break_duration ?? '' }}</td>
        <td>{{ $attendance->total_duration ? substr($attendance->total_duration, 0, 5) : '' }}</td>
        <td>
          @if ($isPastOrToday)
          <a href="{{ route('attendance.show', ['id' => $linkId, 'date' => $attendanceDate->format('Y-m-d')]) }}" class="list__link">詳細</a>

          @else
          <span class="attendance__link--disabled">詳細</span>
          @endif
        </td>

      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection