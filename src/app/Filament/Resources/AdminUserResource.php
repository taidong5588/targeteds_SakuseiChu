<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminUserResource\Pages;
use App\Filament\Resources\AdminUserResource\RelationManagers;
use App\Models\AdminUser;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\App;
use Filament\Notifications\Notification;

class AdminUserResource extends Resource
{
    protected static ?string $model = AdminUser::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Admin Users';
    protected static ?string $pluralModelLabel = 'Admin Users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Profile')
                    ->schema([

                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->required(fn (string $context) => $context === 'create')
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                            ->dehydrated(fn ($state) => filled($state)),

                        Forms\Components\Select::make('role')
                            ->label('Role')
                            ->options([
                                'super_admin' => 'Super Admin',
                                'admin'       => 'Admin',
                                'operator'    => 'Operator',
                                'viewer'      => 'Viewer',
                            ])
                            ->required(),

                        // 🌍 言語切替
                        Forms\Components\Select::make('locale')
                            ->label('Language')
                            ->options([
                                'ja'    => '日本語',
                                'en'    => 'English',
                                'ko'    => '한국어',
                                'zh_CN' => '简体中文',
                            ])
                            // ->default('ja')
                            ->required()
                            ->selectablePlaceholder(false) // 空選択を防止
                            // ->live() // リアルタイム更新を有効化
                            // ->afterStateUpdated(function ($state) {

                            //     // 1. DB保存（ログインユーザーの場合）
                            //     if (auth('admin')->check()) {
                            //         auth('admin')->user()->update(['locale' => $state]);
                            //     }

                            //     // 2. セッション保存（未ログイン時のフォールバック用 & 即時反映用）
                            //     session()->put('admin_locale', $state);
                            //     session()->save();

                            //     // ③ アプリケーションロケール更新
                            //     app()->setLocale($state);

                            //     // 3. 画面リロード
                            //     // リロードすることで、さきほど作ったミドルウェアが新しい設定(DB/Session)を読み込み、
                            //     // 画面全体を新しい言語で再描画します。
                            //     return redirect(request()->header('Referer'));

                            // })
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->colors([
                        'danger'  => 'super_admin',
                        'primary' => 'admin',
                        'warning' => 'operator',
                        'gray'    => 'viewer',
                    ])
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('locale')
                    ->label('Language')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'ja' => '日本語',
                        'en' => 'English',
                        'ko' => '한국어',
                        'zh_CN' => '简体中文',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminUsers::route('/'),
            'create' => Pages\CreateAdminUser::route('/create'),
            'edit' => Pages\EditAdminUser::route('/{record}/edit'),
        ];
    }
}
