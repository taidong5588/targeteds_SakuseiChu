<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\NotifyMailTemplate;

class NotifyMailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        NotifyMailTemplate::insert([
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
                'memo' => '{notify_name}, {tenant_name}, {expiry_date}',
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
                'memo' => '{notify_name}, {tenant_name}, {expiry_date}',
                'is_active' => true,
            ],
            [
                'key' => 'trial_3days',
                'slug' => 'trial-alert',
                'title' => 'トライアル終了3日前',
                'channel' => 'mail',
                'subject' => '【重要】トライアル期間終了の3日前となりました',
                'body' => "{{ notify_name }} 様\n\n{{ tenant_name }}のトライアル終了日は {{ expiry_date }} です。\n本契約への移行をご検討ください。",
                'memo' => 'トライアル終了が近づいた際に送信されます。',
                'is_active' => true,
            ],

            [
                'key'   => 'trial_7days',
                'name'  => 'トライアル終了7日前通知',
                'channel' => 'mail',
                'subject' => '【{{ app_name }}】トライアル終了まで残り7日です',
                'body' => <<<HTML
{{ notify_name }} 様
テナント「{{ tenant_name }}」のトライアルは
{{ expiry_date }} に終了予定です。
ご確認をお願いいたします。
{{ app_name }}
'available_variables' => 'tenant_name, notify_name, expiry_date, app_name',
HTML,
            ]



        ]);
    }
}