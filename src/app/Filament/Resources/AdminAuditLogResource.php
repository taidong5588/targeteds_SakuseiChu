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
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Support\Facades\Auth;

/**
 * 🛡️ Admin 操作監査ログ Resource
 *
 * ✔ 外販 / ISMS / SOC2 / 内部監査 対応
 * ✔ 改ざん不可（Read Only）
 * ✔ CSV / Excel Export 対応（全件・選択）
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
            ->defaultSort('occurred_at', 'desc')

            /**
             * 📥 全件エクスポート
             */
            ->headerActions([
                ExportAction::make()
                    ->label('Export All')
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename('all_audit_logs_' . date('Ymd_His')),
                    ])
                    ->after(fn () => static::logExportAction('all')),
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
                 */
                Tables\Columns\TextColumn::make('adminUser.name')
                    ->label('Admin')
                    ->searchable()
                    ->wrap(),

                /**
                 * 🏢 テナント
                 */
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->placeholder('System')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('tenant_id', $direction)),

                /**
                 * 🧭 操作種別
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
                        'export_logs'    => 'success',
                        default          => 'gray',
                    })
                    ->sortable(),

                /**
                 * 🎯 操作対象モデル
                 */
                Tables\Columns\TextColumn::make('target_type')
                    ->label('Target')
                    ->formatStateUsing(
                        fn ($state) => str_replace('App\\Models\\', '', (string)$state)
                    )
                    ->limit(20)
                    ->tooltip(fn ($state): string => (string)$state),

                /**
                 * 🆔 対象ID
                 */
                Tables\Columns\TextColumn::make('target_id')
                    ->label('ID')
                    ->width('80px'),

                /**
                 * 🌐 IPアドレス
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
                        'role_changed'  => 'Role Changed',
                        'login'         => 'Login',
                        'logout'        => 'Logout',
                        'export_logs'   => 'Export Logs',
                    ]),
            ])

            /**
             * 👁️ 個別アクション
             */
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])

            /**
             * 🗳️ チェックボックス選択アクション（一括エクスポート）
             */
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Export Selected')
                        ->exports([
                            ExcelExport::make()
                                ->fromTable()
                                ->withFilename('selected_audit_logs_' . date('Ymd_His')),
                        ])
                        ->after(fn () => static::logExportAction('selected')),
                ]),
            ]);
    }

    /**
     * 📄 詳細画面（Infolist）
     */
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
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

                Infolists\Components\Grid::make(2)
                    ->schema([
                        Infolists\Components\Section::make('Before (Original)')
                            ->icon('heroicon-m-arrow-left-circle')
                            ->iconColor('danger')
                            ->schema([
                                Infolists\Components\KeyValueEntry::make('before')->label(''),
                            ])
                            ->columnSpan(1),

                        Infolists\Components\Section::make('After (Changed)')
                            ->icon('heroicon-m-arrow-right-circle')
                            ->iconColor('success')
                            ->schema([
                                Infolists\Components\KeyValueEntry::make('after')->label(''),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    /**
     * 🚀 監査ログ記録用メソッド
     */
    protected static function logExportAction(string $scope): void
    {
        AdminAuditLog::create([
            'admin_user_id' => Auth::guard('admin')->id(),
            'tenant_id'     => Auth::guard('admin')->user()->tenant_id ?? null,
            'action'        => 'export_logs',
            'target_type'   => self::class,
            'after'         => ['scope' => $scope, 'purpose' => 'System Audit Export'],
            'ip'            => request()->ip(),
            'user_agent'    => request()->userAgent(),
            'occurred_at'   => now(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminAuditLogs::route('/'),
        ];
    }
}