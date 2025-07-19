@extends('layouts.admin')

@section('title', '勤怠詳細（管理者）')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/attendance_show.css') }}">
@endsection

@section('content')
<div class="show-wrapper">
  <h2 class="show__title">勤怠詳細</h2>

  <div class="show">
    {{-- 修正フォーム --}}
    <form id="form" method="POST" action="{{ route('admin.attendance.update', $attendance->id) }}">
      @csrf
      @method('POST')

      <table class="show__table">
        <tr>
          <th>名前</th>
          <td>{{ $attendance->user->name }}</td>
        </tr>

        <tr>
          <th>日付</th>
          <td>
            <div class="show__date-row">
              <div class="show__date-year">{{ \Carbon\Carbon::parse($attendance->date)->year }}年</div>
              <div class="show__date-monthday">{{ \Carbon\Carbon::parse($attendance->date)->format('n月j日') }}</div>
            </div>
          </td>
        </tr>

        <tr>
          <th>出勤・退勤</th>
          <td>
            <div class="show__time-row">
              <input type="time" name="clock_in" value="{{ optional($attendance->clock_in)->format('H:i') }}" class="show__time-input no-clock">
              <span class="show__tilde">〜</span>
              <input type="time" name="clock_out" value="{{ optional($attendance->clock_out)->format('H:i') }}" class="show__time-input no-clock">
            </div>
          </td>
        </tr>

        {{-- 休憩表示（既存 + 空欄1行） --}}
        @php $breakCount = $attendance->breaks->count(); @endphp
        @for ($i = 0; $i <= $breakCount; $i++)
          <tr>
          <th>{{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}</th>
          <td>
            <div class="show__time-row">
              <input type="time" name="breaks[{{ $i }}][start]" value="{{ optional($attendance->breaks[$i]->start ?? null)->format('H:i') }}" class="show__time-input no-clock">
              <span class="show__tilde">〜</span>
              <input type="time" name="breaks[{{ $i }}][end]" value="{{ optional($attendance->breaks[$i]->end ?? null)->format('H:i') }}" class="show__time-input no-clock">
            </div>
          </td>
          </tr>
          @endfor

          <tr>
            <th>備考</th>
            <td>
              <div class="show__time-row">
                <input type="text" name="note" value="{{ old('note', $attendance->note ?? '') }}" class="show__note-input">
              </div>
            </td>
          </tr>
      </table>
    </form>
  </div>
</div>

{{-- ✅ 修正ボタン（承認済なら非表示） --}}
@if (!$isApproved)
<div class="show__submit-wrapper">
  @php
  $isFixed = session('status') === 'updated' || $attendance->is_fixed;
  @endphp

  <button
    type="button"
    class="show__submit"
    id="submitBtn"
    @if($isFixed) disabled @endif
    style="{{ $isFixed ? 'background-color: #ccc; color: #666; cursor: not-allowed;' : '' }}">
    {{ $isFixed ? '修正済' : '修正' }}
  </button>
</div>
@endif
@endsection

@section('scripts')
@php
$alreadyUpdated = session('status') === 'updated' || $attendance->is_fixed;
@endphp
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const button = document.getElementById('submitBtn');
    const form = document.getElementById('form');

    const isAlreadyUpdated = @json($alreadyUpdated);

    if (button && form && !isAlreadyUpdated) {
      button.addEventListener('click', function() {
        if (this.disabled) return;

        this.disabled = true;
        this.innerText = '修正済';
        this.style.backgroundColor = '#ccc';
        this.style.color = '#666';
        this.style.cursor = 'not-allowed';

        setTimeout(() => {
          form.submit();
        }, 100);
      });
    }
  });
</script>
@endsection