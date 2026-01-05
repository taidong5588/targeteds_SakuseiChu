<?php

namespace App\Filament\Resources\AdminAuditLogResource\Pages;

use App\Filament\Resources\AdminAuditLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdminAuditLog extends EditRecord
{
    protected static string $resource = AdminAuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
