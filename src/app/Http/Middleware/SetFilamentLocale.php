<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\App;
use App\Models\Language; // 🚀 追加
use Illuminate\Support\Facades\Cache; // 🚀 パフォーマンス向上のため追加を推奨

class SetFilamentLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. DBから有効な言語コード一覧を取得
        // ※ リクエストのたびにDBにアクセスするのは重いため、キャッシュ化するのが一般的です
        $valid = Cache::rememberForever('active_language_codes', function () {
            return Language::where('is_active', true)->pluck('code')->toArray();
        });
        
        // 2. デフォルト設定
        $locale = config('app.locale', 'ja'); 

        // 3. ブラウザの言語設定を検知（未ログイン時用）
        $browserLocale = $request->getPreferredLanguage($valid);
        if ($browserLocale) {
            $locale = $browserLocale;
        }

        // 4. セッション（手動で切り替えた場合）
        if (session()->has('admin_locale')) {
            $sessionLocale = session('admin_locale');
            if (in_array($sessionLocale, $valid, true)) {
                $locale = $sessionLocale;
            }
        }

        // 5. ログインユーザーのDB設定（リレーション経由）
        if (auth('admin')->check()) {
            $user = auth('admin')->user();
            // languageリレーションを通じてDBに保存されている言語コードを取得
            if ($user->language && in_array($user->language->code, $valid, true)) {
                $locale = $user->language->code;
            }
        }

        App::setLocale($locale);

        return $next($request);
    }
}