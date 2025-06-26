{{-- resources/views/attendance/register.blade.php --}}
@extends('layouts.app')

@section('title', '勤怠登録画面')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="attendance">
  <p class="attendance__status">
    @if ($status === 'off') 勤務外
    @elseif ($status === 'working') 出勤中
    @elseif ($status === 'break') 休憩中
    @elseif ($status === 'done') 退勤済
    @endif
  </p>

  {{-- ここは整形済みの文字列なのでそのまま表示でOK --}}
  <p class="attendance__date">{{ $date }}</p>

  <p class="attendance__time">{{ $time }}</p>

  @if ($status === 'off')
  <form action="{{ route('attendance.start') }}" method="POST">
    @csrf
    <button type="submit" class="attendance__btn">出勤</button>
  </form>

  @elseif ($status === 'working')
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

  @elseif ($status === 'break')
  <form action="{{ route('attendance.breakEnd') }}" method="POST">
    @csrf
    <button type="submit" class="attendance__btn attendance__btn--white">休憩戻</button>
  </form>

  @elseif ($status === 'done')
  <p class="attendance__thanks">お疲れ様でした。</p>
  @endif
</div>
@endsection