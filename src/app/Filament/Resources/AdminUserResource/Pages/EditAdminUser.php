<?php

namespace App\Filament\Resources\AdminUserResource\Pages;

use App\Filament\Resources\AdminUserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\App;

class EditAdminUser extends EditRecord
{
    protected static string $resource = AdminUserResource::class;

    /**
     * 保存後のリダイレクト先を「なし（このページのまま）」に設定
     */
    protected function getRedirectUrl(): ?string
    {
        return null;
    }

    /**
     * 保存後に言語設定をセッションへ反映させる
     */
    protected function afterSave(): void
    {
        $record = $this->getRecord();

        // 自分のプロフィールを編集した場合のみセッションと言語を更新
        if ($record->id === auth('admin')->id()) {
            $locale = $record->language?->code;

            if ($locale) {
                // セッションを即座に更新
                session()->put('admin_locale', $locale);
                session()->save();

                // アプリケーションの言語を切り替え
                App::setLocale($locale);
                
                // ブラウザをリロードして画面全体を新しい言語で表示
                $this->redirect(AdminUserResource::getUrl('edit', ['record' => $record]));
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}