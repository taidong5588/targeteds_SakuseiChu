<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotifyMailTemplate;

class NotifyMailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'trial_7days',
                'slug' => 'trial-7days',
                'title' => 'トライアル終了7日前通知',
                'channel' => 'mail',
                'subject' => '【重要】トライアル終了7日前のお知らせ',
                'body' => <<<HTML
{notify_name} 様<br><br>
ご利用中の <strong>{tenant_name}</strong> のトライアルは
<strong>{expiry_date}</strong> に終了予定です。<br><br>
ご不明点がございましたら、お気軽にお問い合わせください。
HTML,
                'allowed_variables' => [
                    'notify_name',
                    'tenant_name',
                    'expiry_date',
                ],
                'memo' => 'トライアル終了7日前に送信',
                'is_active' => true,
            ],
            [
                'key' => 'contract_before',
                'slug' => 'contract-before',
                'title' => '本契約終了前通知',
                'channel' => 'mail',
                'subject' => '【重要】契約終了日のご案内',
                'body' => <<<HTML
{notify_name} 様<br><br>
<strong>{tenant_name}</strong> の契約は
<strong>{expiry_date}</strong> に終了予定です。
HTML,
                'allowed_variables' => [
                    'notify_name',
                    'tenant_name',
                    'expiry_date',
                ],
                'memo' => '契約終了前の案内通知',
                'is_active' => true,
            ],
            [
                'key' => 'trial_3days',
                'slug' => 'trial-3days',
                'title' => 'トライアル終了3日前通知',
                'channel' => 'mail',
                'subject' => '【重要】トライアル期間終了の3日前となりました',
                'body' => <<<TEXT
{notify_name} 様

{tenant_name} のトライアル終了日は {expiry_date} です。
本契約への移行をご検討ください。
TEXT,
                'allowed_variables' => [
                    'notify_name',
                    'tenant_name',
                    'expiry_date',
                ],
                'memo' => 'トライアル終了3日前に送信',
                'is_active' => true,
            ],
            [
                'key' => 'trial_7days_app',
                'slug' => 'trial-7days-app',
                'title' => 'トライアル終了7日前通知（アプリ名入り）',
                'channel' => 'mail',
                'subject' => '【{app_name}】トライアル終了まで残り7日です',
                'body' => <<<TEXT
{notify_name} 様

テナント「{tenant_name}」のトライアルは
{expiry_date} に終了予定です。

ご確認をお願いいたします。
{app_name}
TEXT,
                'allowed_variables' => [
                    'notify_name',
                    'tenant_name',
                    'expiry_date',
                    'app_name',
                ],
                'memo' => 'アプリ名を含めたトライアル7日前通知',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            NotifyMailTemplate::updateOrCreate(
                ['key' => $template['key']],
                $template
            );
        }
    }
}
