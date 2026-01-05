<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminAuditLogResource\Pages;
use App\Models\AdminAuditLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;

/**
 * 🛡️ Admin 操作監査ログ Resource
 *
 * 【設計方針】
 * - 監査ログは「完全閲覧専用（改変不可）」
 * - 最新操作を即座に確認できる UI
 * - before / after を人が読める形で提示
 * - 外販・ISMS・SOC 監査に耐える設計
 */
class AdminAuditLogResource extends Resource
{
    /**
     * 対象モデル
     */
    protected static ?string $model = AdminAuditLog::class;

    /**
     * ナビゲーション設定
     */
    protected static ?string $navigationIcon  = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Admin Audit Logs';
    protected static ?string $navigationGroup = 'System';
    protected static ?int    $navigationSort  = 99;

    /**
     * 🔒 権限制御
     *
     * 監査ログは「証跡」そのもののため、
     * UI レベルでも Create / Edit / Delete を完全に禁止する。
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    /**
     * 📋 一覧テーブル定義
     *
     * - 最新ログを最優先で表示
     * - 幅を明示的に制御し、横スクロールを抑制
     * - 危険操作は色で即判別可能
     */
    public static function table(Table $table): Table
    {
        return $table
            // 監査ログは「最新事象の確認」が最重要
            ->defaultSort('occurred_at', 'desc')

            ->columns([
                /**
                 * 発生日時
                 * - 幅固定で時系列確認を容易にする
                 */
                Tables\Columns\TextColumn::make('occurred_at')
                    ->label('Time')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),

                /**
                 * 操作管理者
                 * - 管理者名は検索対象
                 */
                Tables\Columns\TextColumn::make('adminUser.name')
                    ->label('Admin')
                    ->searchable()
                    ->wrap(),

                /**
                 * テナントID
                 * - NULL の場合は System 操作と判断
                 */
                Tables\Columns\TextColumn::make('tenant_id')
                    ->label('Tenant')
                    // ->placeholder('System')
                    ->formatStateUsing(fn ($state) => $state ?? 'System (NULL)')
                    ->toggleable(),

                /**
                 * 操作種別
                 * - 色 = リスクレベル
                 */
                Tables\Columns\TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'role_changed'   => 'danger',   // 権限変更（最重要）
                        'deleted'        => 'warning',  // 削除
                        'created'        => 'success',  // 作成
                        'updated'        => 'info',     // 更新
                        'login', 'logout'=> 'gray',     // 認証イベント
                        default          => 'gray',
                    })
                    ->sortable(),

                /**
                 * 操作対象モデル
                 * - 名前空間を除去して可読性向上
                 */
                Tables\Columns\TextColumn::make('target_type')
                    ->label('Target')
                    ->formatStateUsing(
                        fn ($state) => str_replace('App\\Models\\', '', $state)
                    )
                    ->limit(20)
                    ->tooltip(fn ($state): string => $state),

                /**
                 * 操作対象ID
                 */
                Tables\Columns\TextColumn::make('target_id')
                    ->label('ID')
                    ->limit(15)
                    ->tooltip(fn ($state): string => $state),

                /**
                 * IPアドレス
                 * - 通常は非表示（必要時のみ表示）
                 */
                Tables\Columns\TextColumn::make('ip')
                    ->label('IP Address')
                    ->toggleable(isToggledHiddenByDefault: true),

                /**
                 * User-Agent
                 * - 詳細調査用
                 */
                Tables\Columns\TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            /**
             * フィルタ
             */
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->options([
                        'created'       => 'Created',
                        'updated'       => 'Updated',
                        'deleted'       => 'Deleted',
                        'role_changed'  => 'Role Changed',
                        'login'         => 'Login',
                        'logout'        => 'Logout',
                    ]),
            ])

            /**
             * アクション
             * - 監査ログでは「詳細確認」が必須
             */
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])

            /**
             * 一括操作は不要（誤操作防止）
             */
            ->bulkActions([]);
    }

    /**
     * 📄 詳細表示（Infolist）
     *
     * - 誰が / いつ / 何を / どう変えたか を明確に
     * - before / after を KeyValue 形式で表示
     */
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                /**
                 * トレーサビリティ情報
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
                    ])
                    ->columns(4),

                /**
                 * データ変更内容
                 * - 監査・説明責任用
                 */
                Infolists\Components\Section::make('Data Changes')
                    ->description('Before / After comparison')
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('before')
                            ->label('Before (Original)'),

                        Infolists\Components\KeyValueEntry::make('after')
                            ->label('After (Changed)'),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * ページ定義
     * - View は Modal 表示のため専用ページ不要
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminAuditLogs::route('/'),
        ];
    }
}
