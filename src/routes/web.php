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

/*
|--------------------------------------------------------------------------
| 初期アクセス時：ログイン画面へリダイレクト
|--------------------------------------------------------------------------
*/

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
    return redirect('/attendance'); // 認証完了後のリダイレクト先
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
    // 勤怠打刻画面
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/register', [AttendanceController::class, 'register'])->name('attendance.register');

    // 打刻アクション
    Route::post('/attendance/start', [AttendanceController::class, 'start'])->name('attendance.start');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'end'])->name('attendance.clockOut');
    Route::post('/attendance/break-start', [AttendanceController::class, 'breakIn'])->name('attendance.breakStart');
    Route::post('/attendance/break-end', [AttendanceController::class, 'breakOut'])->name('attendance.breakEnd');

    // 勤怠一覧・詳細
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');
    Route::get('/attendance/{id}', [AttendanceController::class, 'show'])->name('attendance.show');

    // 修正申請の更新
    Route::post('/attendance/{id}/update-request', [AttendanceController::class, 'updateRequest'])->name('attendance.updateRequest');

    // 修正申請一覧（本人）
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
| 管理者：ログアウト＆機能（admin認証必須）
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware('auth:admin')->group(function () {
    // ログアウト
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

    // 勤怠一覧・詳細
    Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])->name('attendance.list');
    Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('attendance.show');

    // スタッフ一覧・スタッフ別勤怠
    Route::get('/staff/list', [AdminStaffController::class, 'index'])->name('staff.list');
    Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'staffList'])->name('attendance.staff');
    Route::get('/attendance/staff/{id}/detail', [AdminAttendanceController::class, 'staffDetail'])->name('attendance.staff_detail');

    Route::get('/attendance/staff/{id}/export', [AdminAttendanceController::class, 'exportCsv'])->name('attendance.export_csv');


    // 修正申請一覧・承認
    Route::get('/stamp_correction_request/list', [AdminStampController::class, 'index'])->name('stamp.list');
    Route::get('/stamp_correction_request/approve/{attendance_correct_request}', [AdminStampController::class, 'approve'])->name('stamp.approve');
});
