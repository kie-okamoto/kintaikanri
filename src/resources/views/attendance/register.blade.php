{{-- resources/views/attendance/register.blade.php --}}
@extends('layouts.app')

@section('title', '勤怠登録画面')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="attendance">
  {{-- ステータス表示（テストで使えるようdata-testid追加） --}}
  <p class="attendance__status" data-testid="status-text">{{ $status }}</p>

  {{-- 日付と現在時刻 --}}
  <p class="attendance__date">{{ $date }}</p>
  <p class="attendance__time">{{ $time }}</p>

  {{-- 状態に応じてボタン表示 --}}
  @if ($status === '勤務外')
  <form action="{{ route('attendance.start') }}" method="POST">
    @csrf
    <button type="submit" class="attendance__btn">出勤</button>
  </form>

  @elseif ($status === '出勤中')
  <div class="attendance__btn-group">
    <form action="{{ route('attendance.clockOut') }}" method="POST">
      @csrf
      <button type="submit" class="attendance__btn">退勤</button>
    </form>
    <form action="{{ route('attendance.breakStart') }}" method="POST">
      @csrf
      <button type="submit" class="attendance__btn attendance__btn--white">休憩入</button>
    </form>
  </div>

  @elseif ($status === '休憩中')
  <form action="{{ route('attendance.breakEnd') }}" method="POST">
    @csrf
    <button type="submit" class="attendance__btn attendance__btn--white">休憩戻</button>
  </form>

  @elseif ($status === '退勤済')
  <p class="attendance__thanks">お疲れ様でした。</p>
  @endif
</div>
@endsection