<?php

namespace App\Services;

use App\Models\NotifyMailTemplate;
use App\Models\Tenant;
use App\Services\NotifyMailService;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use App\Mail\DynamicNotifyMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotifyMailService
{

    /**
     * テンプレートKeyごとの許可変数定義
     */
    public static function getAllowedVariables(?string $key): array
    {
        return match ($key) {
            'trial_7days', 'trial_expired' => [
                'tenant_name' => 'テナント名',
                'notify_name' => '通知先担当者名',
                'expiry_date' => '期限日',
                'app_name'    => 'システム名',
            ],
            'contract_expired' => [
                'tenant_name' => 'テナント名',
                'expiry_date' => '契約終了日',
            ],
            default => [
                'tenant_name' => 'テナント名',
                'app_name'    => 'システム名',
            ],
        };
    }

    /**
     * バリデーション実行
     */
    public static function validate(array $data): void
    {
        $text = ($data['subject'] ?? '') . ' ' . ($data['body'] ?? '');
        preg_match_all('/{{\s*(\w+)\s*}}/', $text, $matches);
        
        $used = array_unique($matches[1] ?? []);
        $allowed = array_keys(self::getAllowedVariables($data['key'] ?? null));
        
        $undefined = array_diff($used, $allowed);

        if (!empty($undefined)) {
            throw ValidationException::withMessages([
                'body' => '許可されていない変数が含まれています: ' . implode(', ', array_map(fn($v) => "{{ $v }}", $undefined)),
            ]);
        }
    }


    /**
     * 変数置換の実行
     */
    protected static function renderReplace(string $text, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $text = str_replace("{{ $key }}", (string) $value, $text);
        }
        return $text;
    }

    /**
     * テンプレート内で使用されている変数を抽出
     */
    protected static function extractVariables(string $text): array
    {
        preg_match_all('/{{\s*(\w+)\s*}}/', $text, $m);
        return array_unique($m[1] ?? []);
    }

    /**
     * プレビューや送信に使用する変数の実データを作成
     * ここが今回エラーになっている箇所です
     */
    protected static function buildVariables(
        NotifyMailTemplate $template,
        Tenant $tenant,
        array $extra = []
    ): array {
        $base = [
            'tenant_name' => $tenant->name,
            'notify_name' => $tenant->notify_name ?? 'Customer',
            'expiry_date' => self::expiryDate($template->key ?? '', $tenant),
            'app_name'    => config('app.name'),
        ];

        // テンプレートで許可されている変数のみに絞り込む
        return array_intersect_key(
            array_merge($base, $extra),
            array_flip($template->allowed_variables ?? [])
        );
    }

    /**
     * 有効期限の計算
     */
    protected static function expiryDate(string $key, Tenant $tenant): string
    {
        $date = str_contains($key, 'trial')
            ? $tenant->trial_ends_at
            : ($tenant->tenantPlan?->contract_end_at ?? null);

        return $date instanceof \DateTimeInterface ? $date->format('Y/m/d') : '-';
    }

    /**
     * 🚀 本番・テスト共通送信メソッド
     * @param string $templateKey テンプレートの key
     * @param Tenant $tenant 対象テナント
     * @param string|null $overrideEmail テスト用のアドレスがあれば上書き
     */
    public static function send(
        string $templateKey,
        Tenant $tenant,
        ?string $overrideEmail = null,
        array $extra = []
    ): bool {
        // 1. テンプレート取得
        $template = NotifyMailTemplate::where('key', $templateKey)
            ->where('is_active', true)
            ->first();

        // 送信先を決定 (上書きアドレスがあれば優先、なければテナントのメアド)
        $targetEmail = $overrideEmail ?? $tenant->notify_email;

        if (! $template || ! $targetEmail) {
            Log::warning("Mail Skip: Template/Email not found. Key: {$templateKey}");
            return false;
        }

        // 2. 変数構築と置換
        $vars = self::buildVariables($template, $tenant, $extra);
        $subject = self::renderReplace($template->subject ?? '', $vars);
        $body = self::renderReplace($template->body ?? '', $vars);

        // 3. 送信
        try {
            Mail::to($targetEmail)->send(new DynamicNotifyMail($subject, $body));
            return true;
        } catch (\Exception $e) {
            Log::error("Mail Send Error [{$templateKey}]: " . $e->getMessage());
            throw $e; // Filament側でキャッチするためにスロー
        }
    }

    /**
     * プレビュー用 HTML 生成
     */
    public static function renderPreview(NotifyMailTemplate $template, Tenant $tenant): HtmlString
    {
        $vars = self::buildVariables($template, $tenant);
        $subject = self::renderReplace($template->subject ?? '', $vars);
        $body = self::renderReplace($template->body ?? '', $vars);

        // HtmlString を正しくインスタンス化
        return new HtmlString('
            <div class="bg-gray-100/80 -m-6 p-10 min-h-[500px]">
                <div class="max-w-2xl mx-auto shadow-2xl rounded-xl overflow-hidden bg-white border border-gray-200">
                    <div class="bg-gray-50 border-b border-gray-100 px-6 py-3 font-mono text-[10px] text-gray-400">
                        MAIL PREVIEW MODE
                    </div>
                    <div class="px-8 py-6 border-b border-gray-50 bg-white space-y-2 text-sm">
                        <div class="flex"><span class="w-16 text-gray-400 font-medium">From:</span><span class="text-gray-900 font-semibold">' . e(config('mail.from.address')) . '</span></div>
                        <div class="flex"><span class="w-16 text-gray-400 font-medium">Subject:</span><span class="text-gray-900 font-bold text-base">' . e($subject) . '</span></div>
                    </div>
                    <div class="px-10 py-12 bg-white prose max-w-none text-gray-700 leading-relaxed">
                        ' . nl2br(e($body)) . '
                    </div>
                </div>
            </div>
        ');
    }


}