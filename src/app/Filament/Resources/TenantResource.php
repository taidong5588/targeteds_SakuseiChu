<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Models\Tenant;
use App\Models\TenantPlan;
use App\Models\Plan;
use App\Services\BillingCalculator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction as PxlrbtExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Enums\ExportFormat;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    public static function getNavigationLabel(): string { return __('Tenants'); }
    public static function getNavigationGroup(): ?string { return __('System Management'); }

    protected static ?string $pollingInterval = '30s';
    protected static bool $isLazy = false;

    public bool $visible = false;
    protected int | string | array $columnSpan = 'full';

    #[On('toggleTenantStats')]
    public function toggle(): void
    {
        $this->visible = ! $this->visible;
    }

    public function shouldRender(): bool
    {
        return $this->visible;
    }

    /**
     * ✅ 編集画面：ご提示の構成を完全維持
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- 1. Basic Information ---
                Forms\Components\Section::make(__('Basic Information'))
                    ->icon('heroicon-m-identification')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Company Name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('notify_name')
                            ->label(__('Notification Name'))
                            ->placeholder('例）〇〇株式会社 管理窓口'),

                        Forms\Components\TextInput::make('notify_email')
                            ->label(__('Notification Email'))
                            ->email()
                            ->placeholder('notify@example.com'),

                        Forms\Components\TextInput::make('code')
                            ->label(__('Tenant Code'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText(__('Used for URLs and internal identification')),
                        Forms\Components\TextInput::make('domain')
                            ->label(__('Custom Domain'))
                            ->placeholder('tenant.example.com'),
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('Active Status'))
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger'),
                    ])->columns(2),

                // --- 2. Usage trial Status & Expiry ---
                Forms\Components\Section::make(__('Usage trial Status & Expiry'))
                    ->icon('heroicon-m-clock')->collapsible()->collapsed()
                    ->schema([
                        Forms\Components\Placeholder::make('trial_date_error')
                            ->label('')->columnSpanFull()
                            ->content(function (Forms\Get $get) {
                                $start = $get('trial_start_at');
                                $end   = $get('trial_ends_at');
                                if (filled($start) && filled($end) && $start > $end) {
                                    return \App\Filament\Support\FormAlert::danger(__('ALERT: The End Date must be after or equal to Start Date!'));
                                }
                                return new \Illuminate\Support\HtmlString('<div class="text-sm font-medium text-gray-500 border-b border-gray-200 pb-2 mb-2 italic">'.__('Trial Period Configuration').'</div>');
                            }),
                        Forms\Components\DateTimePicker::make('trial_start_at')->label(__('Trial Start Date'))->live(),
                        Forms\Components\DateTimePicker::make('trial_ends_at')->label(__('Trial End Date'))->live()->afterOrEqual('trial_start_at'),
                        Forms\Components\Placeholder::make('trial_mode_notice')
                            ->label('')->columnSpanFull()
                            ->hidden(fn (Forms\Get $get) => ! filled($get('trial_start_at')) || ($get('trial_start_at') > $get('trial_ends_at')))
                            ->content(fn () => new \Illuminate\Support\HtmlString('<div class="flex items-center gap-2 p-3 text-xs text-info-800 bg-info-50 border-l-4 border-info-400"><span>'.__('Trial mode active. Subscription plan selection is currently managed by system constraints.').'</span></div>')),
                    ])->columns(2),

                // --- 3. Subscription & Billing ---
                Forms\Components\Section::make(__('Subscription & Billing'))
                    ->icon('heroicon-m-credit-card')
                    ->description(__('Manage plans, contract periods, and discounts.'))
                    ->relationship('tenantPlan') 
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\Select::make('plan_id')
                                ->label(__('Subscription Plan'))
                                ->relationship('plan', 'name')
                                ->required()
                                ->live()
                                ->preload()
                                ->columnSpanFull(),
                            Forms\Components\Fieldset::make(__('Selected Plan Specifications'))
                                ->hidden(fn (Forms\Get $get) => !$get('plan_id'))
                                ->schema([
                                    Forms\Components\Grid::make(3)->schema([
                                        Forms\Components\Placeholder::make('p_base_price')
                                            ->label(__('Base Price'))
                                            ->content(fn (Forms\Get $get) => "¥" . number_format(Plan::find($get('plan_id'))?->base_price ?? 0)),
                                        Forms\Components\Placeholder::make('p_annual_fee')
                                            ->label(__('Annual Fee'))
                                            ->content(fn (Forms\Get $get) => "¥" . number_format(Plan::find($get('plan_id'))?->annual_fee ?? 0)),
                                        Forms\Components\Placeholder::make('p_type')
                                            ->label(__('Pricing Type'))
                                            ->content(fn (Forms\Get $get) => strtoupper(Plan::find($get('plan_id'))?->pricing_type ?? '-')),
                                        Forms\Components\Placeholder::make('p_included_mails')
                                            ->label(__('Included Mails'))
                                            ->content(fn (Forms\Get $get) => number_format(Plan::find($get('plan_id'))?->included_mails ?? 0) . ' ' . __('Units')),
                                        Forms\Components\Placeholder::make('p_unit_price')
                                            ->label(__('Standard Unit Price'))
                                            ->content(fn (Forms\Get $get) => "¥" . number_format(Plan::find($get('plan_id'))?->unit_price ?? 0)),
                                        Forms\Components\Placeholder::make('p_overage_unit_price')
                                            ->label(__('Overage Unit Price'))
                                            ->content(fn (Forms\Get $get) => "¥" . number_format(Plan::find($get('plan_id'))?->overage_unit_price ?? 0) . ' / ' . __('Unit')),
                                    ]),
                                ])->columnSpanFull(),

                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\Placeholder::make('contract_date_error')
                                    ->label('')->columnSpanFull()
                                    ->content(function (Forms\Get $get) {
                                        $start = $get('contract_start_at');
                                        $end   = $get('contract_end_at');
                                        if (filled($start) && filled($end) && $start > $end) {
                                            return \App\Filament\Support\FormAlert::danger(__('ALERT: The End Date must be after or equal to Start Date!'));
                                        }
                                        return new \Illuminate\Support\HtmlString('<div class="text-sm font-medium text-gray-500 border-b border-gray-200 pb-2 mb-2 italic">'.__('Subscription Period Configuration').'</div>');
                                    }),
                                Forms\Components\DatePicker::make('contract_start_at')
                                    ->label(__('Subscription Start Date'))
                                    ->required()->native(false),
                                Forms\Components\DatePicker::make('contract_end_at')
                                    ->label(__('Subscription End Date'))
                                    ->live()
                                    ->afterOrEqual('contract_start_at')
                                    ->native(false),
                             ]),
                        ])->columnSpan(2),

                        Forms\Components\Section::make(__('Price Adjustment'))
                            ->schema([
                                Forms\Components\Select::make('discount_type')
                                    ->label(__('Discount Type'))
                                    ->options(['none' => __('None'), 'rate' => __('Percentage (%)'), 'fixed' => __('Fixed Amount')])
                                    ->default('none')->selectablePlaceholder(false)->required()->live(),
                                Forms\Components\TextInput::make('discount_value')
                                    ->label(__('Discount Value'))
                                    ->numeric()->default(0)->required()
                                    ->hidden(fn (Forms\Get $get) => $get('discount_type') === 'none')->live(),
                                Forms\Components\TextInput::make('contract_price_override')
                                    ->label(__('Override Base Price'))
                                    ->numeric()->helperText(__('Override standard plan price for this specific tenant'))->live(),
                                Forms\Components\Placeholder::make('billing_preview')
                                    ->label(__('Monthly Fee Preview (Tax Included)'))
                                    ->extraAttributes(['class' => 'p-4 bg-primary-50 border border-primary-200 rounded-xl text-primary-700 font-bold'])
                                    ->content(function (Forms\Get $get) {
                                        $planId = $get('plan_id');
                                        if (!$planId) return __('Please select a plan.');
                                        $tempPlan = new TenantPlan(['plan_id' => $planId, 'discount_type' => $get('discount_type') ?? 'none', 'discount_value' => $get('discount_value') ?? 0, 'contract_price_override' => $get('contract_price_override')]);
                                        $tempPlan->setRelation('plan', Plan::find($planId));
                                        if (!$tempPlan->plan) return __('Plan data not found.');
                                        $res = BillingCalculator::calculate($tempPlan, 0);
                                        return __('Estimated Total') . ": ¥" . number_format($res['total']);
                                    }),
                            ])->columnSpan(1),
                    ])->columns(3),

                // --- 4. System Settings ---
                Forms\Components\Section::make(__('System Settings'))
                    ->icon('heroicon-m-cog-6-tooth')->collapsible()->collapsed()
                    ->schema([
                        Forms\Components\Select::make('language_id')->label(__('Default Language'))->relationship('language', 'name')->required()->preload(),
                        Forms\Components\TextInput::make('audit_log_retention_days')->label(__('Log Retention Days'))->numeric()->suffix(__('Days'))->default(90)->required(),
                    ])->columns(2),
            ]);
    }

    /**
     * ✅ テーブル一覧：ご指定のレイアウトを採用
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Company Name'))
                    ->weight('bold')
                    ->searchable()->sortable(),
                Tables\Columns\TextColumn::make('tenantPlan.plan.name')
                    ->label(__('Plan'))
                    ->badge()->color('info'),
                Tables\Columns\TextColumn::make('state')
                    ->label(__('Status'))
                    ->state(fn (Tenant $record) => $record->contractState())
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'trial_critical' => __('Trial ≤ 3 days'),
                        'trial_warning'  => __('Trial ≤ 7 days'),
                        'expired'        => __('Expired'),
                        'inactive'       => __('Inactive'),
                        'upcoming'       => __('開始前'),
                        default          => __('Active'),
                    })
                    ->color(fn (string $state) => match ($state) {
                        'trial_critical' => 'danger',
                        'trial_warning'  => 'warning',
                        'upcoming'       => 'info',
                        'expired', 'inactive' => 'gray',
                        default          => 'success',
                    }),
                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label(__('Trial End'))
                    ->date('Y/m/d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('contract_start_at')
                    ->label(__('Contract Start'))
                    ->date('Y/m/d')
                    ->sortable(),   
                Tables\Columns\TextColumn::make('contract_end_at')
                    ->label(__('Contract End'))
                    ->date('Y/m/d')
                    ->sortable(),                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('notify_email')
                    ->label(__('Notify Email'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    // 🚀 マスキング表示 (例: te***@example.com)
                    ->formatStateUsing(fn (string $state) => \Illuminate\Support\Str::mask($state, '*', 2, 5)),                 
                    //->getStateUsing(fn ($record) => $record->manager_email ? substr($record->manager_email,0,1).'***@***.com' : '-')
            ])
            // 🚀 一番左にチェックボックスを表示させる設定
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    PxlrbtExportAction::make(),
                ]),
            ])        
            ->filters([
                Tables\Filters\SelectFilter::make('contract_state')
                    ->label(__('Contract State'))
                    ->options([
                        'trial_critical' => __('Trial ≤ 3 days'),
                        'trial_warning'  => __('Trial ≤ 7 days'),
                        'expired'        => __('Expired'),
                        'inactive'       => __('Inactive'),
                        'upcoming'       => __('開始前'),
                        'active'         => __('Active'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) return $query;
                        $ids = Tenant::with('tenantPlan')->get()
                            ->filter(fn (Tenant $t) => $t->contractState() === $data['value'])
                            ->pluck('id');
                        return $query->whereIn('id', $ids);
                    }),
                // 🚀 「Trial Only」フィルタの追加
                Tables\Filters\Filter::make('is_trial')
                    ->label('トライアル中のみ')
                    ->query(fn (Builder $query) => $query->whereNotNull('trial_ends_at')->where('is_active', true))
                    ->indicator('Trialing'),                    
                ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}