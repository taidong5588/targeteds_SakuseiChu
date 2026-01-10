<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminAuditLogResource\Pages;
use App\Models\AdminAuditLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Support\Facades\Auth;

/**
 * 🛡️ Admin 操作監査ログ Resource
 *
 * ✔ 外販 / ISMS / SOC2 / 内部監査 対応
 * ✔ 改ざん不可（Read Only）
 * ✔ CSV / Excel Export 対応
 * ✔ 人が読める UI（wrap / limit / tooltip）
 */
class AdminAuditLogResource extends Resource
{
    protected static ?string $model = AdminAuditLog::class;

    protected static ?string $navigationIcon  = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Admin Audit Logs';
    protected static ?string $navigationGroup = 'System';
    protected static ?int    $navigationSort  = 99;

    /**
     * 🔒 監査ログは「参照のみ」
     * （作成・編集・削除は禁止）
     */
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }

    /**
     * 📋 一覧テーブル
     */
    public static function table(Table $table): Table
    {
        return $table
            // 最新ログを上に表示（監査で最重要）
            ->defaultSort('occurred_at', 'desc')

            /**
             * 📥 CSV / Excel Export
             * 外販・監査提出で必須
             */
            ->headerActions([
                ExportAction::make()
                    ->label('Export Logs')
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename('audit_logs_' . date('Ymd_His')),
                    ])
                    // 🚀 エクスポート完了後に監査ログを生成
                    ->after(function () {
                        AdminAuditLog::create([
                            'admin_user_id' => Auth::guard('admin')->id(),
                            'tenant_id' => Auth::guard('admin')->user()->tenant_id ?? null,
                            'action' => 'export_logs', // 専用のアクション名
                            'target_type' => AdminAuditLog::class,
                            'target_id' => null,
                            'before' => null,
                            'after' => ['purpose' => 'System Audit Export'],
                            'ip' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                            'occurred_at' => now(),
                        ]); 
                    }),
            ])

            ->columns([
                /**
                 * ⏰ 発生日時
                 */
                Tables\Columns\TextColumn::make('occurred_at')
                    ->label('Time')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),

                /**
                 * 👤 操作管理者
                 * wrap で列幅を人が調整可能に
                 */
                Tables\Columns\TextColumn::make('adminUser.name')
                    ->label('Admin')
                    ->searchable()
                    ->wrap(),

                /**
                 * 🏢 テナント
                 *
                 * DB: NULL = システム操作
                 * UI: NULL は不親切なため "System" 表示
                 */
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->placeholder('System')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('tenant_id', $direction)),

                /**
                 * 🧭 操作種別
                 * 色分けで即判別可能
                 */
                Tables\Columns\TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'role_changed'   => 'danger',
                        'deleted'        => 'warning',
                        'created'        => 'success',
                        'updated'        => 'info',
                        'login', 'logout'=> 'gray',
                        default          => 'gray',
                    })
                    ->sortable(),

                /**
                 * 🎯 操作対象モデル
                 * 長い FQCN は limit + tooltip
                 */
                Tables\Columns\TextColumn::make('target_type')
                    ->label('Target')
                    ->formatStateUsing(
                        fn ($state) => str_replace('App\\Models\\', '', $state)
                    )
                    ->limit(20)
                    ->tooltip(fn ($state): string => $state),

                /**
                 * 🆔 対象ID
                 * width 固定で表を安定させる
                 */
                Tables\Columns\TextColumn::make('target_id')
                    ->label('ID')
                    ->width('80px'),

                /**
                 * 🌐 IPアドレス
                 * 通常は非表示（必要時のみ）
                 */
                Tables\Columns\TextColumn::make('ip')
                    ->label('IP Address')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            /**
             * 🔍 フィルター
             */
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->options([
                        'created'       => 'Created',
                        'updated'       => 'Updated',
                        'deleted'       => 'Deleted',
                        'role_changed' => 'Role Changed',
                        'login'         => 'Login',
                        'logout'        => 'Logout',
                    ]),
            ])

            /**
             * 👁 詳細表示のみ許可
             */
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    /**
     * 📄 詳細画面（Infolist）
     * 証跡・追跡性を重視
     */
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                /**
                 * 🔎 トレーサビリティ情報
                 */
                Infolists\Components\Section::make('Traceability')
                    ->schema([
                        Infolists\Components\TextEntry::make('occurred_at')
                            ->label('Timestamp')
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('adminUser.name')
                            ->label('Operator'),

                        Infolists\Components\TextEntry::make('action')
                            ->badge(),

                        Infolists\Components\TextEntry::make('ip')
                            ->label('Source IP'),

                        Infolists\Components\TextEntry::make('user_agent')
                            ->label('User Agent')
                            ->columnSpanFull(),
                    ])
                    ->columns(4),

                /**
                 * 🔄 変更差分（Before / After）
                 * JSON を Key-Value で可視化
                 */
                Infolists\Components\Grid::make(2)
                    ->schema([
                        Infolists\Components\Section::make('Before (Original)')
                            ->icon('heroicon-m-arrow-left-circle')
                            ->iconColor('danger')
                            ->schema([
                                Infolists\Components\KeyValueEntry::make('before')
                                    ->label(''),
                            ])
                            ->columnSpan(1),

                        Infolists\Components\Section::make('After (Changed)')
                            ->icon('heroicon-m-arrow-right-circle')
                            ->iconColor('success')
                            ->schema([
                                Infolists\Components\KeyValueEntry::make('after')
                                    ->label(''),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    /**
     * 📌 Pages
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminAuditLogs::route('/'),
        ];
    }
}
