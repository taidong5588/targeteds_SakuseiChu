<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminUserResource\Pages;
use App\Models\AdminUser;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Role;

class AdminUserResource extends Resource
{
    protected static ?string $model = AdminUser::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    // ナビゲーションラベルの多言語化
    public static function getNavigationLabel(): string
    {
        return __('Admin Users');
    }

    // ナビゲーショングループの多言語化
    public static function getNavigationGroup(): ?string
    {
        return __('System Management');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Profile'))
                    ->schema([
                        // 🏢 テナントマスタ紐付け
                        Forms\Components\Select::make('tenant_id')
                            ->label(__('Company Name'))
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->preload()
                            // 💡 ロジック修正：
                            // 1. Super Admin ではないこと
                            // 2. かつ、Viewer（閲覧者）でもないこと 
                            // この2つの場合に「必須」にする
                            ->required(fn (Forms\Get $get) => 
                                $get('role_id') && 
                                !in_array(Role::find($get('role_id'))?->code, ['super_admin', 'viewer'])
                            )
                            // 💡 Super Admin の場合は非表示
                            ->hidden(fn (Forms\Get $get) => 
                                $get('role_id') && Role::find($get('role_id'))?->code === 'super_admin'
                            )
                            // 💡 閲覧者の場合は、項目は見せるが「編集不可」にする（またはお好みでhiddenでもOK）
                            ->disabled(fn (Forms\Get $get) => 
                                $get('role_id') && Role::find($get('role_id'))?->code === 'viewer'
                            ),

                        Forms\Components\TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label(__('Email'))
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('password')
                            ->label(__('Password'))
                            ->password()
                            ->required(fn (string $context) => $context === 'create')
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                            ->dehydrated(fn ($state) => filled($state)),

                        // 🔑 ロールマスタ紐付け
                        Forms\Components\Select::make('role_id')
                            ->label(__('Role'))
                            ->relationship('role', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => __($record->name)) // DB内の値を翻訳
                            ->required()
                            ->preload(),

                        // 🌍 言語マスタ紐付け（ロケール切替ロジック維持）
                        Forms\Components\Select::make('language_id')
                            ->label(__('Language'))
                            ->relationship('language', 'name')
                            ->required()
                            ->preload()
                            ->live() // 即時反映を有効化
                            ->afterStateUpdated(function ($state, $record) {
                                // 自分のプロフィールを編集している場合のみセッション更新
                                if ($record && $record->id === auth('admin')->id()) {
                                    $lang = \App\Models\Language::find($state);
                                    if ($lang) {
                                        session()->put('admin_locale', $lang->code);
                                        session()->save();
                                    }
                                }
                            }),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label(__('Company Name'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('role.name')
                    ->label(__('Role'))
                    ->formatStateUsing(fn ($state) => __($state))
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Super Admin' => 'danger',
                        'Tenant Admin' => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('language.name')
                    ->label(__('Language'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Registration Date'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label(__('Company Name'))
                    ->relationship('tenant', 'name'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminUsers::route('/'),
            'create' => Pages\CreateAdminUser::route('/create'),
            'edit' => Pages\EditAdminUser::route('/{record}/edit'),
        ];
    }
}