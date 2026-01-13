<?php

namespace App\Filament\Resources\NotifyMailTemplateResource\Pages;

use App\Filament\Resources\NotifyMailTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNotifyMailTemplates extends ListRecords
{
    protected static string $resource = NotifyMailTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
