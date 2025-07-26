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
    @php
    $action = $attendance->id
    ? route('admin.attendance.update', $attendance->id)
    : route('admin.attendance.storeNew');
    @endphp

    <form id="form" method="POST" action="{{ $action }}">
      @csrf
      @if ($attendance->id)
      @method('PUT')
      @endif
      <input type="hidden" name="user_id" value="{{ $user->id }}">
      <input type="hidden" name="date" value="{{ $attendance->date }}">




      <table class="show__table">
        <tr>
          <th>名前</th>
          <td>{{ $user->name }}</td>
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
              <input type="time" name="clock_in" value="{{ old('clock_in', optional($attendance->clock_in)->format('H:i')) }}" class="show__time-input">
              <span class="show__tilde">〜</span>
              <input type="time" name="clock_out" value="{{ old('clock_out', optional($attendance->clock_out)->format('H:i')) }}" class="show__time-input">
            </div>
            @if ($errors->has('clock_in'))
            @foreach ($errors->get('clock_in') as $message)
            <div class="error">{{ $message }}</div>
            @break
            @endforeach
            @endif
          </td>
        </tr>

        {{-- 休憩時間 --}}
        @php $breakCount = $attendance->breaks->count(); @endphp
        @for ($i = 0; $i <= $breakCount; $i++)
          <tr>
          <th>{{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}</th>
          <td>
            <div class="show__time-row">
              <input type="time" name="breaks[{{ $i }}][start]" value="{{ old("breaks.$i.start", optional($attendance->breaks[$i]->start ?? null)->format('H:i')) }}" class="show__time-input">
              <span class="show__tilde">〜</span>
              <input type="time" name="breaks[{{ $i }}][end]" value="{{ old("breaks.$i.end", optional($attendance->breaks[$i]->end ?? null)->format('H:i')) }}" class="show__time-input">
            </div>
            @error("breaks.$i.start")
            <div class="error">{{ $message }}</div>
            @enderror
          </td>
          </tr>
          @endfor

          <tr>
            <th>備考</th>
            <td>
              <div class="show__time-row">
                <input type="text" name="note" value="{{ old('note', $attendance->note ?? '') }}" class="show__note-input">
              </div>
              @error('note')
              <div class="error">{{ $message }}</div>
              @enderror
            </td>
          </tr>
      </table>
    </form>
  </div>
</div>

{{-- ✅ 修正ボタン --}}
<div class="show__submit-wrapper">
  @php
  $isDisabled = $isApproved || $attendance->is_fixed;
  @endphp
  <button
    type="button"
    class="show__submit"
    id="submitBtn"
    @if($isDisabled) disabled @endif
    style="{{ $isDisabled ? 'background-color: #ccc; color: #666; cursor: not-allowed;' : '' }}">
    {{ $isDisabled ? '修正済' : '修正' }}
  </button>
</div>
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

        // ✅ submit直後にボタンを完全に無効化（ダブルクリック防止）
        this.setAttribute('disabled', 'disabled');
        setTimeout(() => {
          form.submit();
        }, 100);
      });
    }
  });
</script>

@endsection