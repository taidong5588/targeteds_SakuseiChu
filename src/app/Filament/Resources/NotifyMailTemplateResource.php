<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotifyMailTemplateResource\Pages;
use App\Filament\Resources\NotifyMailTemplateResource\RelationManagers;
use App\Models\NotifyMailTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use App\Models\Tenant;
use App\Services\NotifyMailService;
use Illuminate\Support\HtmlString;

class NotifyMailTemplateResource extends Resource
{
    protected static ?string $model = NotifyMailTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationLabel(): string { return __('Mail Templates'); }
    public static function getNavigationGroup(): ?string { return __('System Management'); }
    public static function getModelLabel(): string { return __('Mail Template'); }    

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('Template Configuration'))
                ->description(__('Variables wrapped in {{ }} will be automatically replaced.'))
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label(__('Template Title'))
                        ->placeholder(__('e.g., Trial 7 days notice'))
                        ->required(),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('key')
                            ->label(__('Internal Key'))
                            ->placeholder(__('e.g., trial_7days (Used by system)'))
                            ->required()
                            ->live()
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('slug')
                            ->label(__('Slug'))
                            ->required()
                            ->unique(ignoreRecord: true),
                    ]),

                    Forms\Components\Grid::make(12) // 12カラムのグリッドを作成
                        ->schema([
                            /* =====================================================
                            * チャンネル選択（左側：4カラム）
                            * ===================================================== */
                            Forms\Components\Select::make('channel')
                                ->label(__('Notification Channel'))
                                ->options([
                                    'mail'  => __('Email'),
                                    'slack' => __('Slack'),
                                    'web'   => __('Web Notification'),
                                ])
                                ->default('mail')
                                ->required()
                                ->live()
                                ->columnSpan(fn ($get) => $get('channel') === 'mail' ? 4 : 12), // mailの場合は4カラム、その他は全幅
                                // ->columnSpan(4), // 全体の3分の1の幅

                            /* =====================================================
                            * 件名（右側：8カラム）
                            * ===================================================== */
                            Forms\Components\TextInput::make('subject')
                                ->label(__('Subject (for email)'))
                                ->placeholder(__('Enter email subject...'))
                                ->required(fn ($get) => $get('channel') === 'mail')
                                ->visible(fn ($get) => $get('channel') === 'mail')
                                ->columnSpan(8), // 残りの3分の2の幅
                                
                        ])
                        ->columnSpanFull(),

                    Forms\Components\Grid::make(3)
                        ->schema([
                            /* =========================
                            * 本文（Body）
                            * ========================= */
                            Forms\Components\RichEditor::make('body')
                                ->label(__('Body'))
                                ->required()
                                ->placeholder(__('e.g., {{ tenant_name }}, {{ notify_name }}'))
                                // Alpine.jsでの幅制御（案2）を維持しつつ、リサイズ設定を追加
                                ->extraAttributes([
                                    'x-bind:class' => "{ 'col-span-8': sideOpen, 'col-span-12': !sideOpen }",
                                    'class' => 'transition-all duration-300 ease-in-out template-body-editor',
                                    'style' => '
                                        resize: vertical; 
                                        overflow: auto; 
                                        min-height: 200px; 
                                        max-height: 1000px;
                                    ',
                                ])
                                ->columnSpan(2),

                                // 変数クリックパネル
                                Forms\Components\Section::make('使用可能な変数')
                                    ->description('クリックして挿入')
                                    ->columnSpan(1)
                                    ->schema([
                                        Forms\Components\Placeholder::make('variable_list')
                                            ->content(function ($get) {
                                                $vars = NotifyMailService::getAllowedVariables($get('key'));
                                                
                                                // グリッドレイアウトでボタンを配置
                                                $html = '<div class="grid grid-cols-1 sm:grid-cols-2 gap-2">';
                                                
                                                foreach ($vars as $v => $label) {
                                                    $html .= "
                                                        <button type='button'
                                                            x-on:click=\"\$dispatch('insert-variable', { text: '{{ {$v} }}' })\"
                                                            class='flex items-center justify-between px-3 py-2 border rounded-lg bg-white hover:bg-gray-50 hover:border-primary-500 transition shadow-sm group'>
                                                            
                                                            <span class='font-mono font-bold text-primary-600 text-xs tracking-tighter uppercase'>
                                                                {{ {$v} }}
                                                            </span>
                                                            
                                                            <span class='text-gray-400 text-[10px] font-medium group-hover:text-primary-400 transition'>
                                                                {$label}
                                                            </span>
                                                            
                                                        </button>";
                                                }
                                                
                                                return new \Illuminate\Support\HtmlString($html . '</div>');
                                            }),                                        
                                    ]),                                                                   
                        ]),

                    /* =====================================================
                    * memo（管理者向けメモ）
                    * ===================================================== */                       
                    Forms\Components\Textarea::make('memo')
                        ->label(__('Memo (for administrators)'))
                        ->placeholder(__('Explain when this template is sent...'))
                        ->columnSpanFull(),

                    /* =====================================================
                    * 有効 / 無効
                    * ===================================================== */
                    Forms\Components\Toggle::make('is_active')
                        ->label(__('Active'))
                        ->helperText(__('ON の場合のみ通知に使用されます'))
                        ->default(true)
                        ->onColor('success'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('Template Title'))
                    ->description(fn (NotifyMailTemplate $record) => $record->key)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('channel')
                    ->label(__('Channel'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'mail' => 'info',
                        'slack' => 'success',
                        'web' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => __($state === 'mail' ? 'Email' : Str::headline($state))),

                Tables\Columns\TextColumn::make('subject')
                    ->label(__('Subject'))
                    ->limit(30)
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Last Updated'))
                    ->dateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('channel')
                    ->label(__('Channel'))
                    ->options([
                        'mail' => __('Email'),
                        'slack' => 'Slack',
                        'web' => __('Web Notification'),
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Active Status')),
            ])
            
        ->actions([
                Tables\Actions\EditAction::make(),

                Action::make('preview')
                    ->label(__('Preview'))
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->modalHeading(__('Mail Template Preview'))
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalContent(fn (NotifyMailTemplate $record) =>
                        ($tenant = Tenant::first())
                            ? NotifyMailService::renderPreview($record, $tenant)
                            : __('Tenant not found.')
                    )
                    ->extraModalFooterActions([
                        // ① 自分に送信
                        Action::make('send_me')
                            ->label(__('Send to me'))
                            ->icon('heroicon-m-user')
                            ->color('info')
                            ->requiresConfirmation()
                            ->modalHeading(__('Send test email to yourself?'))
                            ->modalDescription(__('It will be sent to: ') . auth()->user()->email)
                            ->action(fn (NotifyMailTemplate $record) =>
                                // ✅ クラス内の static メソッドを呼び出す
                                self::handleTestSend(auth()->user()->email, $record)
                            ),

                        // ② 宛先指定送信
                        Action::make('send_any')
                            ->label(__('Send to specified address'))
                            ->icon('heroicon-m-paper-airplane')
                            ->color('success')
                            ->form([
                                Forms\Components\TextInput::make('email')
                                    ->label(__('Email Address'))
                                    ->email()
                                    ->required()
                                    ->placeholder(fn () => auth()->user()->email),
                            ])
                            ->action(fn (array $data, NotifyMailTemplate $record) =>
                                // ✅ クラス内の static メソッドを呼び出す
                                self::handleTestSend($data['email'], $record)
                            ),
                    ]),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * ✅ UI側の送信ハンドラ
     */
    public static function handleTestSend(string $email, NotifyMailTemplate $record): void
    {
        try {
            // テナントはプレビュー用に1件取得
            $tenant = \App\Models\Tenant::first();

            if (!$tenant) {
                throw new \Exception('テナントが存在しません。');
            }

            // 🚀 本番と同じロジックを呼び出す
            \App\Services\NotifyMailService::send(
                templateKey: $record->key,
                tenant: $tenant,
                overrideEmail: $email // 👈 ここでテスト用アドレスを渡す
            );

            \Filament\Notifications\Notification::make()
                ->title(__('Mail sent successfully'))
                ->body($email)
                ->success()
                ->send();

        } catch (\Throwable $e) {
            \Filament\Notifications\Notification::make()
                ->title(__('Send failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
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
            'index' => Pages\ListNotifyMailTemplates::route('/'),
            'create' => Pages\CreateNotifyMailTemplate::route('/create'),
            'edit' => Pages\EditNotifyMailTemplate::route('/{record}/edit'),
        ];
    }
}