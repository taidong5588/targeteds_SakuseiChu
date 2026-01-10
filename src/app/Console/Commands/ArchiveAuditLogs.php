<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AdminAuditLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB; // 👈 追加

class ArchiveAuditLogs extends Command
{
    /**
     * コマンド名（php artisan audit:archive で実行できるようになります）
     */
    protected $signature = 'audit:archive';

    /**
     * コマンドの説明
     */
    protected $description = '90日以上前の監査ログをアーカイブ（CSV化）して削除します';

    /**
     * 実行ロジック
     */
    public function handle()
    {
        $cutoffDate = now()->subDays(90); // 90日前
        $logs = AdminAuditLog::where('occurred_at', '<', $cutoffDate)->get();

        if ($logs->isEmpty()) {
            $this->info('アーカイブ対象のログはありません。');
            return;
        }

        // CSVデータの作成準備
        $fileName = "archives/audit_log_backup_" . now()->format('Ymd_His') . ".csv";
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['ID', 'Admin_ID', 'Action', 'Target', 'Time', 'IP']);

        foreach ($logs as $log) {
            fputcsv($handle, [
                $log->id, 
                $log->admin_user_id, 
                $log->action, 
                $log->target_type, 
                $log->occurred_at, 
                $log->ip
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        // トランザクションで「保存」と「削除」を一気に行う
        DB::transaction(function () use ($fileName, $content, $cutoffDate) {
            // 1. CSVファイルを保存
            // ✅ 今はローカルディスク（/var/www/src/storage/app/private/archives/）　に保存
            // AWS構築後はここを 's3' に変えるだけ！
            \Storage::disk('local')->put($fileName, $content);
            // \Storage::disk('s3')->put($fileName, $content);

            // 2. DBから対象期間を削除
            AdminAuditLog::where('occurred_at', '<', $cutoffDate)->delete();
        });

        $this->info(count($logs) . " 件のログを storage/app/private/archives/{$fileName} に保存し、DBから削除しました。💯");
    }
}