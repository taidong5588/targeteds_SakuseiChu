<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule; // 👈 追加

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * 監査ログのアーカイブを毎月実行する
 */
Schedule::command('audit:archive')->monthly();

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
| ここに Artisan コマンドやスケジュールを書く（Laravel 12方式）
|--------------------------------------------------------------------------
*/

Schedule::command('notify:send-tenant-ending-alerts')
    ->dailyAt('09:00')
    ->onOneServer()
    ->withoutOverlapping()
    ->runInBackground();