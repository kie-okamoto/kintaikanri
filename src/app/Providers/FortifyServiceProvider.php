<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Requests\AdminLoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Illuminate\Auth\Events\Verified;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 一般ユーザーの Fortify 処理
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // ログイン画面（一般ユーザー）
        Fortify::loginView(function () {
            return view('auth.login');
        });

        // 会員登録画面（一般ユーザー）
        Fortify::registerView(function () {
            return view('auth.register');
        });

        // メール認証画面（一般ユーザー）
        Fortify::verifyEmailView(function () {
            return view('auth.verify-email');
        });

        // ✅ メール認証イベント後にフラグを保存
        Event::listen(Verified::class, function () {
            session()->put('verified_from_email', true);
        });

        // ✅ 管理者ログイン処理（FormRequestでバリデーション）
        Fortify::authenticateUsing(function (Request $request) {
            // 一般ユーザー用 guard のみ通過させる
            if ($request->is('admin/*')) {
                return null;
            }

            $user = \App\Models\User::where('email', $request->email)->first();

            if (
                $user &&
                \Hash::check($request->password, $user->password) &&
                $user->hasVerifiedEmail()
            ) {
                return $user;
            }

            return null;
        });

        // ✅ レート制限（一般ユーザーと管理者共通）
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower($request->input(Fortify::username())) . '|' . $request->ip()
            );
            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
