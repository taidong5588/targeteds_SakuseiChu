<?php

namespace App\Filament\Resources\AdminAuditLogResource\Pages;

use App\Filament\Resources\AdminAuditLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * 📋 監査ログ一覧ページ
 */
class ListAdminAuditLogs extends ListRecords
{
    protected static string $resource = AdminAuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
