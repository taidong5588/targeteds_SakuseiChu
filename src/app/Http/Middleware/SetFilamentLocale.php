<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\App;

class SetFilamentLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $valid = config('app.supported_locales');
        
        // デフォルトを決定するロジック（ここが重要）
        // 優先度 3: 設定ファイルのデフォルト
        $locale = config('app.locale'); 

        // 優先度 2: ブラウザの言語設定を自動検知（ログイン画面用）
        // ブラウザが 'ja' を求めていて、かつ 'ja' が許可リストにある場合、それを採用
        $browserLocale = $request->getPreferredLanguage($valid);
        if ($browserLocale) {
            $locale = $browserLocale;
        }

        // 優先度 1: セッション（手動切り替え後）
        if (session()->has('admin_locale')) {
            $sessionLocale = session('admin_locale');
            if (in_array($sessionLocale, $valid, true)) {
                $locale = $sessionLocale;
            }
        }

        // 優先度 0 (最高): ログインユーザーのDB設定
        if (auth('admin')->check()) {
            $user = auth('admin')->user();
            if ($user->locale && in_array($user->locale, $valid, true)) {
                $locale = $user->locale;
            }
        }

        // 決定した言語を適用
        App::setLocale($locale);

        return $next($request);
    }
}