<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\StampController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\Admin\StampController as AdminStampController;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;

Route::get('/', fn() => redirect('/login'));

/*
|--------------------------------------------------------------------------
| 一般ユーザー：会員登録・ログイン（未認証のみ）
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| 一般ユーザー：メール認証ルート
|--------------------------------------------------------------------------
*/
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/attendance');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', '確認用メールを再送信しました。');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

/*
|--------------------------------------------------------------------------
| 一般ユーザー：勤怠管理（認証・メール認証必須）
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/start', [AttendanceController::class, 'start'])->name('attendance.start');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'end'])->name('attendance.clockOut');
    Route::post('/attendance/break-start', [AttendanceController::class, 'breakIn'])->name('attendance.breakStart');
    Route::post('/attendance/break-end', [AttendanceController::class, 'breakOut'])->name('attendance.breakEnd');
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');
    Route::get('/attendance/{id}', [AttendanceController::class, 'show'])->name('attendance.show')->where('id', '.*');
    Route::post('/attendance/{id}/update-request', [AttendanceController::class, 'updateRequest'])->name('attendance.updateRequest');
    Route::get('/stamp_correction_request/list', [StampController::class, 'index'])->name('stamp.list');
});

/*
|--------------------------------------------------------------------------
| 管理者：ログイン（未ログイン時のみ）
|--------------------------------------------------------------------------
*/
Route::middleware('guest:admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| 管理者：ログアウト＆各機能（管理者認証必須）
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware('auth:admin')->group(function () {
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

    // 勤怠一覧・詳細
    Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])->name('attendance.list');
    Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])
        ->where('id', '[0-9]+|new')
        ->name('attendance.show');

    // 更新（修正時：PUT使用）
    Route::put('/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('attendance.update');

    // 新規登録（未登録日：POST使用）
    Route::post('/attendance/new', [AdminAttendanceController::class, 'storeNew'])->name('attendance.storeNew');

    // スタッフ管理
    Route::get('/staff/list', [AdminStaffController::class, 'index'])->name('staff.list');
    Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'staffList'])->name('attendance.staff');
    Route::get('/attendance/staff/{id}/detail', [AdminAttendanceController::class, 'staffDetail'])->name('attendance.staff_detail');
    Route::get('/attendance/staff/{id}/export/{month?}', [AdminAttendanceController::class, 'exportCsv'])->name('attendance.export_csv');

    // 申請処理
    Route::get('/stamp_correction_request/list', [AdminStampController::class, 'index'])->name('stamp.list');
    Route::get('/stamp_correction_request/{id}', [AdminStampController::class, 'show'])->name('stamp_correction_request.show');
    Route::post('/stamp_correction_request/approve/{attendance_correction_request}', [AdminStampController::class, 'approve'])->name('stamp.approve');
});
