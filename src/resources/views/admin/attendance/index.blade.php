@extends('layouts.admin')

@section('title', '勤怠一覧画面（管理者）')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
<link rel="stylesheet" href="{{ asset('css/common.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/attendance_list.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endsection

@section('content')
@php
use Carbon\Carbon;
Carbon::setLocale('ja');
@endphp

{{-- タイトル --}}
<h2 class="admin-attendance__title">
  {{ Carbon::parse($date)->isoFormat('YYYY年MM月DD日') }}の勤怠
</h2>

<div class="admin-attendance__container">

  {{-- 日付ナビゲーション --}}
  <div class="admin-attendance__date">
    <form method="GET" action="{{ route('admin.attendance.list') }}">
      <input type="hidden" name="date" value="{{ Carbon::parse($date)->subDay()->format('Y-m-d') }}">
      <button type="submit" class="nav-button">← 前日</button>
    </form>

    <div class="current-date">
      <i class="fa fa-calendar"></i>
      {{ Carbon::parse($date)->format('Y/m/d') }}
    </div>

    <form method="GET" action="{{ route('admin.attendance.list') }}">
      <input type="hidden" name="date" value="{{ Carbon::parse($date)->addDay()->format('Y-m-d') }}">
      <button type="submit" class="nav-button">翌日 →</button>
    </form>
  </div>

  {{-- 勤怠テーブル --}}
  <div class="admin-attendance__table-wrapper">
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
          <td>{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '-' }}</td>
          <td>{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '-' }}</td>
          <td>{{ $attendance->calculated_break_duration ?? '-' }}</td>
          <td>{{ $attendance->total_duration ? substr($attendance->total_duration, 0, 5) : '-' }}</td>
          <td><a href="{{ route('admin.attendance.show', $attendance->id) }}" class="admin-attendance__link">詳細</a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

</div>
@endsection