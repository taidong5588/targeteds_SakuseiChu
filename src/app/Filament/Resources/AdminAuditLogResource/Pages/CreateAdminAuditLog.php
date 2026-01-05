<?php

namespace App\Filament\Resources\AdminAuditLogResource\Pages;

use App\Filament\Resources\AdminAuditLogResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAdminAuditLog extends CreateRecord
{
    protected static string $resource = AdminAuditLogResource::class;
}
