@extends('layouts.app')

@section('title', '申請一覧画面（一般ユーザー）')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="list">
  <h2 class="list__title">申請一覧</h2>

  <div class="list__tabs">
    <a href="{{ route('stamp.list', ['tab' => 'waiting']) }}" class="list__tab {{ $tab === 'waiting' ? 'list__tab--active' : '' }}">承認待ち</a>
    <a href="{{ route('stamp.list', ['tab' => 'approved']) }}" class="list__tab {{ $tab === 'approved' ? 'list__tab--active' : '' }}">承認済み</a>
  </div>

  <div class="list__tab-content">
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
        @forelse ($requests as $request)
        <tr>
          <td>{{ $tab === 'approved' ? '承認済み' : '承認待ち' }}</td>
          <td>{{ $request->attendance->user->name ?? '不明' }}</td>
          <td>{{ \Carbon\Carbon::parse($request->attendance->date)->format('Y/m/d') }}</td>
          <td>{{ $request->reason }}</td>
          <td>{{ \Carbon\Carbon::parse($request->submitted_at)->format('Y/m/d H:i') }}</td>
          <td><a href="{{ route('attendance.show', $request->attendance->id) }}" class="list__link">詳細</a></td>
        </tr>
        @empty
        <tr>
          <td colspan="6">
            {{ $tab === 'approved' ? '承認済みの申請はありません。' : '承認待ちの申請はありません。' }}
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection