@extends('layouts.admin')

@section('title', '勤怠一覧画面（管理者）')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/admin/attendance_list.css') }}">
@endsection

@section('content')
<div class="admin-attendance">
  <h2 class="admin-attendance__title">{{ \Carbon\Carbon::parse($date)->format('Y年n月j日') }}の勤怠</h2>

  <div class="admin-attendance__date-nav">
    <form method="GET" action="{{ route('admin.attendance.list') }}">
      <input type="hidden" name="date" value="{{ \Carbon\Carbon::parse($date)->subDay()->format('Y-m-d') }}">
      <button type="submit" class="nav-button">← 前日</button>
    </form>

    <div class="current-date">{{ \Carbon\Carbon::parse($date)->format('Y/m/d') }}</div>

    <form method="GET" action="{{ route('admin.attendance.list') }}">
      <input type="hidden" name="date" value="{{ \Carbon\Carbon::parse($date)->addDay()->format('Y-m-d') }}">
      <button type="submit" class="nav-button">翌日 →</button>
    </form>
  </div>

  <table class="admin-attendance__table">
    <thead>
      <tr>
        <th>名前</th>
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
        <td>{{ $attendance->user->name }}</td>
        <td>{{ $attendance->clock_in ?? '-' }}</td>
        <td>{{ $attendance->clock_out ?? '-' }}</td>
        <td>{{ $attendance->break_time ?? '-' }}</td>
        <td>{{ $attendance->working_hours ?? '-' }}</td>
        <td><a href="{{ route('admin.attendance.show', $attendance->id) }}">詳細</a></td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection