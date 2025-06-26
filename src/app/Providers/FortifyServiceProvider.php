<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
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
        // 各種 Fortify アクション
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // ログイン画面の Blade 指定
        Fortify::loginView(function () {
            return view('auth.login');
        });

        // 登録画面の Blade 指定
        Fortify::registerView(function () {
            return view('auth.register');
        });

        // メール認証画面の Blade 指定
        Fortify::verifyEmailView(function () {
            return view('auth.verify-email');
        });

        // ✅ メール認証イベント後にフラグを保存
        Event::listen(Verified::class, function () {
            session()->put('verified_from_email', true);
        });

        // レート制限（ログイン）
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower($request->input(Fortify::username())) . '|' . $request->ip()
            );
            return Limit::perMinute(5)->by($throttleKey);
        });

        // レート制限（二段階認証）
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
