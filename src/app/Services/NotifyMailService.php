<?php

namespace App\Services;

use App\Models\NotifyMailTemplate;
use App\Models\Tenant;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class NotifyMailService
{

    /**
     * =====================================================
     * 本番用：テンプレートを使って通知メールを送信
     * =====================================================
     */
    /**
     * 🚀 本番送信メソッド
     * @param string $templateKey テンプレートの key (trial_3days など)
     * @param Tenant $tenant 送信先のテナント
     * @param array $extra 追加の変数 (任意)
     * @return bool
     */
    public static function send(
        string $templateKey,
        Tenant $tenant,
        array $extra = []
    ): bool {
        // 1. テンプレートの取得
        $template = NotifyMailTemplate::where('key', $templateKey)
            ->where('is_active', true)
            ->first();

        // テンプレートがない、もしくはテナントにメールアドレスがない場合は終了
        if (! $template || ! $tenant->notify_email) {
            return false;
        }

        // 2. 変数の構築（以前実装した buildVariables を使用）
        $vars = self::buildVariables($template, $tenant, $extra);

        // 3. メールの送信（DynamicNotifyMail Mailableを使用）
        try {
            \Illuminate\Support\Facades\Mail::to($tenant->notify_email)->send(
                new \App\Mail\DynamicNotifyMail(
                    self::renderReplace($template->subject ?? '', $vars),
                    self::renderReplace($template->body ?? '', $vars)
                )
            );
            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Mail Send Error: " . $e->getMessage());
            return false;
        }
    }

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
     * 管理画面プレビュー用 HTML 生成
     *
     * @param NotifyMailTemplate $template
     * @param Tenant $tenant
     * @return HtmlString
     */
    // public static function renderPreview(
    //     NotifyMailTemplate $template,
    //     Tenant $tenant
    // ): HtmlString {
    //     // 1. テンプレートに使用可能な変数を構築
    //     $vars = self::buildVariables($template, $tenant);

    //     // 2. 件名と本文を置換（先ほど作成した内部メソッドを使用）
    //     $renderedSubject = self::renderReplace($template->subject ?? '', $vars);
    //     $renderedBody    = self::renderReplace($template->body ?? '', $vars);

    //     // 3. Filamentのモーダルに表示するためのHTMLを組み立て
    //     return new HtmlString('
    //         <div class="space-y-6 p-4 border rounded-lg bg-white shadow-sm">
    //             <div>
    //                 <h4 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-1">Subject</h4>
    //                 <div class="p-3 bg-gray-50 border border-gray-200 rounded text-gray-800 font-medium">
    //                     ' . e($renderedSubject) . '
    //                 </div>
    //             </div>

    //             <hr class="border-gray-100">

    //             <div>
    //                 <h4 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-1">Email Body</h4>
    //                 <div class="p-4 border border-gray-200 rounded text-gray-700 leading-relaxed bg-white overflow-auto max-h-[400px]">
    //                     ' . nl2br(e($renderedBody)) . '
    //                 </div>
    //             </div>

    //             <div class="pt-2">
    //                 <p class="text-xs text-gray-400 italic">
    //                     ※ Previewing with data from tenant: <strong>' . e($tenant->name) . '</strong>
    //                 </p>
    //             </div>
    //         </div>
    //     ');
    // }
    
    public static function renderPreview(
        NotifyMailTemplate $template,
        Tenant $tenant
    ): HtmlString {
        $vars = self::buildVariables($template, $tenant);

        // 1. Blade記法のように見えるテキストを実際のデータに置換
        $renderedSubject = self::renderReplace($template->subject ?? '', $vars);
        $renderedBody    = self::renderReplace($template->body ?? '', $vars);

        return new HtmlString('
            <div class="bg-gray-100/80 -m-6 p-10 min-h-[600px]">
                <div class="max-w-2xl mx-auto shadow-2xl rounded-xl overflow-hidden bg-white border border-gray-200">
                    
                    <div class="bg-gray-50 border-b border-gray-100 px-6 py-3 flex items-center justify-between">
                        <div class="flex gap-1.5">
                            <div class="w-3 h-3 rounded-full bg-red-300"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-300"></div>
                            <div class="w-3 h-3 rounded-full bg-green-300"></div>
                        </div>
                        <span class="text-[11px] font-medium text-gray-400 font-mono tracking-tighter uppercase">Mail Preview Mode</span>
                    </div>

                    <div class="px-8 py-6 border-b border-gray-50 bg-white">
                        <div class="space-y-2">
                            <div class="flex items-start text-sm">
                                <span class="w-16 text-gray-400 font-medium">From:</span>
                                <span class="text-gray-900 font-semibold">' . e(config('mail.from.name')) . ' &lt;' . e(config('mail.from.address')) . '&gt;</span>
                            </div>
                            <div class="flex items-start text-sm">
                                <span class="w-16 text-gray-400 font-medium">To:</span>
                                <span class="text-gray-900">' . e($tenant->notify_name ?? 'Client') . ' &lt;' . e($tenant->notify_email) . '&gt;</span>
                            </div>
                            <div class="flex items-start text-sm pt-2">
                                <span class="w-16 text-gray-400 font-medium">Subject:</span>
                                <span class="text-gray-900 font-bold text-base">' . e($renderedSubject) . '</span>
                            </div>
                        </div>
                    </div>

                    <div class="px-10 py-12 bg-white min-h-[300px]">
                        <div class="prose max-w-none text-gray-700 leading-relaxed text-[15px] font-sans">
                            ' . nl2br(e($renderedBody)) . '
                        </div>
                    </div>

                    <div class="px-10 py-6 bg-gray-50 border-t border-gray-100 text-center">
                        <p class="text-[11px] text-gray-400 tracking-widest uppercase">
                            Sent via ' . e(config('app.name')) . ' Notification Engine
                        </p>
                    </div>
                </div>

                <p class="mt-6 text-center text-xs text-gray-400 font-medium">
                    ※ 実際の送信時には、会社名や日付が自動的に適用されます
                </p>
            </div>
        ');
    }

}