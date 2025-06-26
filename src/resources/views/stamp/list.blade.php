@extends('layouts.app')

@section('title', '申請一覧画面（一般ユーザー）')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="list">
  <h2 class="list__title">申請一覧</h2>

  <div class="list__tabs">
    <button class="list__tab list__tab--active">承認待ち</button>
    <button class="list__tab">承認済み</button>
  </div>

  <table class="list__table">
    <thead>
      <tr>
        <th>状態</th>
        <th>名前</th>
        <th>対象日</th>
        <th>申請理由</th>
        <th>申請日時</th>
        <th>詳細</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($attendances as $attendance)
      <tr>
        <td>
          @if ($attendance->approval_status === 'pending')
          承認待ち
          @elseif ($attendance->approval_status === 'approved')
          承認済み
          @else
          {{ $attendance->approval_status }}
          @endif
        </td>
        <td>{{ $attendance->user->name }}</td>
        <td>{{ \Carbon\Carbon::parse($attendance->date)->format('Y/m/d') }}</td>
        <td>{{ $attendance->note }}</td>
        <td>{{ $attendance->updated_at->format('Y/m/d') }}</td>
        <td><a href="{{ route('attendance.show', $attendance->id) }}" class="list__link">詳細</a></td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection