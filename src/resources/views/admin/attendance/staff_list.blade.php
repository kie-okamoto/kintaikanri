@extends('layouts.admin')

@section('title', 'スタッフ一覧画面（管理者）')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
<link rel="stylesheet" href="{{ asset('css/common.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/staff_list.css') }}">
@endsection

@section('content')
<h2 class="staff-list__title">スタッフ一覧</h2>

<div class="staff-list__wrapper">
  <table class="staff-list__table">
    <thead>
      <tr>
        <th>名前</th>
        <th>メールアドレス</th>
        <th>月次勤怠</th>
      </tr>
    </thead>
    <tbody>
      @foreach($users as $user)
      <tr>
        <td>{{ $user->name }}</td>
        <td>{{ $user->email }}</td>
        <td>
          <a href="{{ route('admin.attendance.staff_detail', ['id' => $user->id]) }}" class="staff-list__link">詳細</a>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection