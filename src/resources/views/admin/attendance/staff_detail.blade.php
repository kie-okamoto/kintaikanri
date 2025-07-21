@extends('layouts.admin')

@section('title', 'スタッフ別勤怠一覧画面（管理者）')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
<link rel="stylesheet" href="{{ asset('css/common.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/staff_detail.css') }}">
@endsection

@section('content')
{{-- タイトル --}}
<h2 class="staff-detail__title--outside">{{ $user->name }}さんの勤怠</h2>

{{-- 月ナビゲーション（中央カレンダーアイコン + 前月/翌月） --}}
<div class="staff-detail__date-nav">
  {{-- 前月ボタン --}}
  <form method="GET" action="{{ route('admin.attendance.staff_detail', ['id' => $user->id]) }}">
    <input type="hidden" name="month" value="{{ \Carbon\Carbon::parse($currentMonth)->subMonth()->format('Y-m') }}">
    <button type="submit" class="nav-button">← 前月</button>
  </form>

  {{-- 現在月 + カレンダーアイコン --}}
  <div class="current-date">
    <i class="fa fa-calendar staff-detail__calendar-icon"></i>
    {{ \Carbon\Carbon::parse($currentMonth)->format('Y/m') }}
  </div>

  {{-- 翌月ボタン --}}
  <form method="GET" action="{{ route('admin.attendance.staff_detail', ['id' => $user->id]) }}">
    <input type="hidden" name="month" value="{{ \Carbon\Carbon::parse($currentMonth)->addMonth()->format('Y-m') }}">
    <button type="submit" class="nav-button">翌月 →</button>
  </form>
</div>


{{-- 勤怠テーブル --}}
<div class="staff-detail__wrapper">
  <div class="staff-detail__table-wrapper">
    <table class="staff-detail__table">
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
        @foreach($attendances as $attendance)
        <tr>
          <td>
            {{
              $attendance->date instanceof \Carbon\Carbon
                ? $attendance->date->isoFormat('MM/DD（dd）')
                : \Carbon\Carbon::parse($attendance->date)->isoFormat('MM/DD（dd）')
            }}
          </td>
          <td>{{ $attendance->formatted_clock_in }}</td>
          <td>{{ $attendance->formatted_clock_out }}</td>
          <td>{{ $attendance->break_duration ? substr($attendance->break_duration, 0, 5) : '' }}</td>
          <td>{{ $attendance->total_duration ? substr($attendance->total_duration, 0, 5) : '' }}</td>
          <td>
            @if ($attendance->clock_in && $attendance->clock_out)
            <a href="{{ route('admin.attendance.show', ['id' => $attendance->id]) }}" class="staff-detail__link">詳細</a>
            @else
            <span class="staff-detail__link staff-detail__link--disabled">詳細</span>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

{{-- CSV出力ボタン --}}
<div class="staff-detail__csv--outside">
  <form method="GET" action="{{ route('admin.attendance.export_csv', ['id' => $user->id, 'month' => $currentMonth->format('Y-m')]) }}">
    <button type="submit" class="staff-detail__csv-button">CSV出力</button>
  </form>
</div>
@endsection