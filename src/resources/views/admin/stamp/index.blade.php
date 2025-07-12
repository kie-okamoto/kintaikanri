@extends('layouts.admin')

@section('title', '申請一覧')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
<link rel="stylesheet" href="{{ asset('css/common.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/stamp_list.css') }}">
@endsection

@section('content')
<div class="stamp-index">
  {{-- タイトル --}}
  <h2 class="stamp-index__title">申請一覧</h2>

  {{-- タブ切り替え --}}
  <div class="stamp-index__tabs">
    <a href="{{ route('admin.stamp.list', ['tab' => 'waiting']) }}"
      class="stamp-index__tab {{ $tab === 'waiting' ? 'stamp-index__tab--active' : '' }}">
      承認待ち
    </a>
    <a href="{{ route('admin.stamp.list', ['tab' => 'approved']) }}"
      class="stamp-index__tab {{ $tab === 'approved' ? 'stamp-index__tab--active' : '' }}">
      承認済み
    </a>
  </div>

  {{-- テーブル --}}
  <div class="stamp-index__table-wrapper">
    <table class="stamp-index__table">
      <thead>
        <tr>
          <th>状態</th>
          <th>名前</th>
          <th>対象日時</th>
          <th>申請理由</th>
          <th>申請日時</th>
          <th>詳細</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($requests as $request)
        <tr>
          <td>
            @if ($request->status === 'approved')
            承認済み
            @elseif ($request->status === 'pending' || $request->status === null)
            承認待ち
            @elseif ($request->status === 'rejected')
            却下
            @else
            {{ $request->status }}
            @endif
          </td>
          <td>{{ $request->attendance->user->name ?? '不明' }}</td>
          <td>{{ optional($request->attendance)->date ? \Carbon\Carbon::parse($request->attendance->date)->format('Y/m/d') : '未設定' }}</td>
          <td>{{ $request->reason }}</td>
          <td>{{ \Carbon\Carbon::parse($request->submitted_at)->format('Y/m/d') }}</td>
          <td>
            <a href="{{ route('admin.stamp_correction_request.show', $request->id) }}" class="stamp-index__detail-link">詳細</a>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6">申請はまだありません。</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection