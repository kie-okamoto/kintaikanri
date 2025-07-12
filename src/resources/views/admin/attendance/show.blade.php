@extends('layouts.admin')

@section('title', '勤怠詳細（管理者）')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
<link rel="stylesheet" href="{{ asset('css/attendance_show.css') }}">
@endsection

@section('content')
<div class="show-wrapper">
  <h2 class="show__title">勤怠詳細</h2>

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
        <td>{{ optional($attendance->clock_in)->format('H:i') }} 〜 {{ optional($attendance->clock_out)->format('H:i') }}</td>
      </tr>

      {{-- 休憩表示＋1行 --}}
      @php $breakCount = $attendance->breaks->count(); @endphp
      @for ($i = 0; $i <= $breakCount; $i++)
        <tr>
        <th>休憩{{ $i + 1 }}</th>
        <td>
          @if (isset($attendance->breaks[$i]))
          {{ \Carbon\Carbon::parse($attendance->breaks[$i]->start)->format('H:i') }} 〜
          {{ \Carbon\Carbon::parse($attendance->breaks[$i]->end)->format('H:i') }}
          @else
          --:-- 〜 --:--
          @endif
        </td>
        </tr>
        @endfor

        <tr>
          <th>備考</th>
          <td>{{ $attendance->note }}</td>
        </tr>

        <tr>
          <th>承認ステータス</th>
          <td>{{ $attendance->approval_status === 'approved' ? '承認済' : '承認待ち' }}</td>
        </tr>
    </table>

    <div class="show__actions-bottom">
      @if ($attendance->approval_status !== 'approved')
      <form method="POST" action="{{ route('admin.stamp.approve', $attendance->id) }}">
        @csrf
        <button type="submit" class="show__approve-button">承認</button>
      </form>
      @else
      <button class="show__approve-button" disabled>承認済</button>
      @endif
    </div>
  </div>
</div>
@endsection