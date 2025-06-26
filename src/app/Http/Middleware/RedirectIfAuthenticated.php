<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string|null  ...$guards
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {

                // ✅ メール認証直後のリダイレクト制御
                if (session()->pull('verified_from_email')) {
                    Auth::guard($guard)->logout(); // 強制ログアウト
                    return redirect('/login')->with('status', 'メール認証が完了しました。ログインしてください。');
                }

                // ✅ 通常ログイン時のリダイレクト
                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}
