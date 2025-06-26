@extends('layouts.app')

@section('title', '勤怠一覧画面（一般ユーザー）')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="list">
  <h2 class="list__title">勤怠一覧</h2>

  {{-- カレンダー風の月移動UI --}}
  <div class="list__calendar-nav">
    <form method="GET" action="{{ route('attendance.list') }}" class="calendar-nav__form">
      @php
      use Carbon\Carbon;
      Carbon::setLocale('ja'); // 日本語表示のため追加
      $currentMonth = Carbon::createFromFormat('Y-m', $month);
      $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
      $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');
      @endphp

      <a href="{{ route('attendance.list', ['month' => $prevMonth]) }}" class="calendar-nav__button">← 前月</a>

      <span class="calendar-nav__current">
        <i class="fa fa-calendar"></i> {{ $currentMonth->format('Y/m') }}
      </span>

      <a href="{{ route('attendance.list', ['month' => $nextMonth]) }}" class="calendar-nav__button">翌月 →</a>
    </form>
  </div>

  @if ($attendances->isEmpty())
  <p>該当する勤怠記録はありません。</p>
  @else
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
      <tr>
        {{-- 日付（曜日付き日本語表示） --}}
        <td>{{ \Carbon\Carbon::parse($attendance->date)->isoFormat('MM/DD (ddd)') }}</td>

        {{-- 出勤時間 --}}
        <td>{{ optional($attendance->clock_in)->format('H:i') ?? '-' }}</td>

        {{-- 退勤時間 --}}
        <td>{{ optional($attendance->clock_out)->format('H:i') ?? '-' }}</td>

        {{-- 休憩時間（秒なし） --}}
        <td>{{ $attendance->break_duration ? substr($attendance->break_duration, 0, 5) : '-' }}</td>

        {{-- 合計勤務時間（秒なし） --}}
        <td>{{ $attendance->total_duration ? substr($attendance->total_duration, 0, 5) : '-' }}</td>

        {{-- 詳細リンク --}}
        <td><a href="{{ route('attendance.show', $attendance->id) }}" class="attendance__link">詳細</a></td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif
</div>
@endsection