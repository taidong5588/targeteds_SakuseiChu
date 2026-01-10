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
use App\Filament\Support\FormAlert;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    public static function getNavigationLabel(): string
    {
        return __('Tenants');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('System Management');
    }

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

                // --- 2. Subscription & Billing ---
                Forms\Components\Section::make(__('Subscription & Billing'))
                    ->icon('heroicon-m-credit-card')
                    ->description(__('Manage plans, contract periods, and discounts.'))
                    ->relationship('tenantPlan') 
                    ->schema([
                        // Left Column: Selection and Plan Specs
                        Forms\Components\Group::make([
                            Forms\Components\Select::make('plan_id')
                                ->label(__('Subscription Plan'))
                                ->relationship('plan', 'name')
                                ->required()
                                ->live()
                                ->preload()
                                ->columnSpanFull(),

                            // Read-only Plan Specifications for Admin Clarity
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
                                Forms\Components\DatePicker::make('contract_start_at')
                                    ->label(__('Subscription Start Date'))
                                    ->required()
                                    // ->default(now())
                                    ->native(false),
                                    
                                Forms\Components\DatePicker::make('contract_end_at')
                                    ->label(__('Subscription End Date'))
                                    ->native(false),
                            ]),
                        ])->columnSpan(2),

                        // Right Column: Price Adjustment & Live Preview
                        Forms\Components\Section::make(__('Price Adjustment'))
                            ->schema([
                                Forms\Components\Select::make('discount_type')
                                    ->label(__('Discount Type'))
                                    ->options([
                                        'none' => __('None'),
                                        'rate' => __('Percentage (%)'),
                                        'fixed' => __('Fixed Amount'),
                                    ])
                                    ->default('none')
                                    ->selectablePlaceholder(false)
                                    ->required()
                                    ->live(),

                                Forms\Components\TextInput::make('discount_value')
                                    ->label(__('Discount Value'))
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->hidden(fn (Forms\Get $get) => $get('discount_type') === 'none')
                                    ->live(),

                                Forms\Components\TextInput::make('contract_price_override')
                                    ->label(__('Override Base Price'))
                                    ->numeric()
                                    ->helperText(__('Override standard plan price for this specific tenant'))
                                    ->live(),

                                Forms\Components\Placeholder::make('billing_preview')
                                    ->label(__('Monthly Fee Preview (Tax Included)'))
                                    ->extraAttributes([
                                        'class' => 'p-4 bg-primary-50 border border-primary-200 rounded-xl text-primary-700 font-bold'
                                    ])
                                    ->content(function (Forms\Get $get) {
                                        $planId = $get('plan_id');
                                        if (!$planId) return __('Please select a plan.');

                                        $tempPlan = new TenantPlan([
                                            'plan_id' => $planId,
                                            'discount_type' => $get('discount_type') ?? 'none',
                                            'discount_value' => $get('discount_value') ?? 0,
                                            'contract_price_override' => $get('contract_price_override'),
                                        ]);
                                        
                                        // Load the plan relationship manually for the calculator
                                        $tempPlan->setRelation('plan', Plan::find($planId));

                                        if (!$tempPlan->plan) return __('Plan data not found.');

                                        $res = BillingCalculator::calculate($tempPlan, 0);
                                        return __('Estimated Total') . ": ¥" . number_format($res['total']);
                                    }),
                            ])->columnSpan(1),
                    ])->columns(3),

                // --- 3. Usage Status & Expiry (Trial Management) ---
                Forms\Components\Section::make(__('Usage Status & Expiry'))
                    ->icon('heroicon-m-clock')
                    ->collapsible()
                    ->collapsed()
                    ->schema([

                        /* =====================================================
                        | Trial Date Validation Alert (FormAlert使用)
                        ===================================================== */
                        Forms\Components\Placeholder::make('trial_date_error')
                            ->label('')
                            ->columnSpanFull()
                            ->content(function (Forms\Get $get) {
                                $start = $get('trial_start_at');
                                $end   = $get('trial_ends_at');

                                // 💡 不整合がある時だけ、じわじわ光る警告を表示
                                if (filled($start) && filled($end) && $start > $end) {
                                    return \App\Filament\Support\FormAlert::danger(
                                        __('ALERT: The End Date must be after or equal to Start Date!')
                                    );
                                }

                                // 💡 正常時はシンプルなセパレーターを表示
                                return new \Illuminate\Support\HtmlString('
                                    <div class="text-sm font-medium text-gray-500 border-b border-gray-200 pb-2 mb-2 italic">
                                        ' . __('Trial Period Configuration') . '
                                    </div>
                                ');
                            }),

                        /* =====================================================
                        | Trial Start Date
                        ===================================================== */
                        Forms\Components\DateTimePicker::make('trial_start_at')
                            ->label(__('Trial Start Date'))
                            ->live()
                            // ->native(false)
                            ->extraAttributes(fn (Forms\Get $get) => [
                                'class' => (filled($get('trial_start_at')) && filled($get('trial_ends_at')) && $get('trial_start_at') > $get('trial_ends_at'))
                                    ? 'ring-2 ring-danger-500 rounded-lg shadow-sm'
                                    : '',
                            ]),

                        /* =====================================================
                        | Trial End Date
                        ===================================================== */
                        Forms\Components\DateTimePicker::make('trial_ends_at')
                            ->label(__('Trial End Date'))
                            ->live()
                            // ->native(false)
                            ->afterOrEqual('trial_start_at')
                            ->validationMessages([
                                'after_or_equal' => __('The End Date must be after or equal to Start Date.'),
                            ])
                            ->extraAttributes(fn (Forms\Get $get) => [
                                'class' => (filled($get('trial_start_at')) && filled($get('trial_ends_at')) && $get('trial_start_at') > $get('trial_ends_at'))
                                    ? 'ring-2 ring-danger-500 rounded-lg font-bold shadow-sm'
                                    : '',
                            ]),

                        /* =====================================================
                        | Trial Mode Notice (正常時のみ表示される案内)
                        ===================================================== */
                        Forms\Components\Placeholder::make('trial_mode_notice')
                            ->label('')
                            ->columnSpanFull()
                            ->hidden(fn (Forms\Get $get) =>
                                ! filled($get('trial_start_at')) || // 開始日が無い
                                ($get('trial_start_at') > $get('trial_ends_at')) // またはエラー中
                            )
                            ->content(fn () => new \Illuminate\Support\HtmlString('
                                <div class="flex items-center gap-2 p-3 text-xs text-info-800 bg-info-50 border-l-4 border-info-400">
                                    <svg class="w-4 h-4 shrink-0 text-info-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>
                                        ' . __('Trial mode active. Subscription plan selection is currently managed by system constraints.') . '
                                    </span>
                                </div>
                            ')),
                    ])
                    ->columns(2),


                // --- 4. System Settings ---
                Forms\Components\Section::make(__('System Settings'))
                    ->icon('heroicon-m-cog-6-tooth')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Select::make('language_id')
                            ->label(__('Default Language'))
                            ->relationship('language', 'name')
                            ->required()
                            ->preload(),

                        Forms\Components\TextInput::make('audit_log_retention_days')
                            ->label(__('Log Retention Days'))
                            ->numeric()
                            ->suffix(__('Days'))
                            ->default(90)
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Company Name'))
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tenantPlan.plan.name')
                    ->label(__('Plan'))
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Status'))
                    ->boolean()
                    ->sortable(),

                /* ===== Trial Status ===== */
                Tables\Columns\TextColumn::make('trial_status')
                    ->label(__('Trial'))
                    ->badge()
                    ->state(function ($record) {
                        if (! $record->trial_ends_at) return __('No Trial');

                        $days = now()->startOfDay()
                            ->diffInDays($record->trial_ends_at->startOfDay(), false);

                        if ($days < 0) return __('Expired');
                        if ($days === 0) return __('Ends Today');

                        return __('あと :d 日', ['d' => $days]);
                    })
                    ->color(function ($record) {
                        if (! $record->trial_ends_at) return 'gray';

                        $days = now()->startOfDay()
                            ->diffInDays($record->trial_ends_at->startOfDay(), false);

                        if ($days < 0) return 'gray';
                        if ($days <= 3) return 'danger';
                        if ($days <= 7) return 'warning';

                        return 'success';
                    }),

                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label(__('Trial End'))
                    ->dateTime('Y/m/d')
                    ->color(fn ($record) => $record->trial_ends_at?->isPast() ? 'danger' : 'gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('tenantPlan.contract_end_at')
                    ->label(__('Contract End'))
                    ->date('Y/m/d'),
            ])

            /* ===============================
            | 🔥 行スタイル制御
            =============================== */
            ->recordClasses(function ($record) {

                $today = now()->startOfDay();

                $trialEnd      = $record->trial_ends_at?->startOfDay();
                $contractStart = $record->tenantPlan?->contract_start_at;
                $contractEnd   = $record->tenantPlan?->contract_end_at?->startOfDay();

                /**
                 * ⚪ グレー条件（質問内容そのまま）
                 * (trial_ends_at < 0 and contract_start_at)
                 * OR (contract_end_at < 0)
                 * OR (trial_ends_at is null and contract_start_at)
                 * OR (contract_end_at is null)
                 */
                if (
                    ($trialEnd && $trialEnd->lt($today) && $contractStart) ||
                    ($contractEnd && $contractEnd->lt($today)) ||
                    (is_null($trialEnd) && $contractStart) ||
                    is_null($contractEnd)
                ) {
                    return ['bg-gray-100', 'opacity-50'];
                }

                // 🔥 Trial 残り3日以内 → 赤く点滅
                if ($trialEnd) {
                    $days = $today->diffInDays($trialEnd, false);

                    if ($days >= 0 && $days <= 3) {
                        return [
                            'bg-danger-500/20',
                            'animate-pulse',
                            'border-l-4',
                            'border-danger-600',
                        ];
                    }
                }

                return [];
            })
            
            ->filters([ 
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Active Status')), 
                Tables\Filters\SelectFilter::make('plan_id') 
                    ->label(__('Subscription Plan')) 
                    ->relationship('tenantPlan.plan', 'name'), 


                /* =====================================================
                | 契約状態フィルタ（ステータス）
                ===================================================== */
                SelectFilter::make('contract_status')
                    ->label(__('Contract Status'))
                    ->options([
                        'active'        => __('🟢 Active'),
                        'attention'     => __('🔥 Trial Ending Soon'),
                        'trial_expired' => __('⛔ Trial Expired'),
                        'inactive'      => __('⚪ Inactive'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $today = now()->startOfDay();

                        return match ($data['value'] ?? null) {
                            'active' =>
                                $query->whereHas('tenantPlan', fn ($q) =>
                                    $q->whereDate('contract_end_at', '>=', $today)
                                ),

                            'attention' =>
                                $query->whereBetween('trial_ends_at', [
                                    $today,
                                    $today->copy()->addDays(3),
                                ]),

                            'trial_expired' =>
                                $query->whereDate('trial_ends_at', '<', $today),

                            'inactive' =>
                                $query->where(function ($q) {
                                    $q->whereNull('trial_ends_at')
                                    ->orWhereHas('tenantPlan', fn ($qq) =>
                                        $qq->whereNull('contract_end_at')
                                    );
                                }),

                            default => $query,
                        };
                    }),

                /* =====================================================
                | Trial 残り日数フィルタ
                ===================================================== */
                SelectFilter::make('trial_remaining')
                    ->label(__('Trial Remaining'))
                    ->options([
                        '3' => __('Within 3 days'),
                        '7' => __('Within 7 days'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! $data['value']) {
                            return $query;
                        }

                        return $query->whereBetween('trial_ends_at', [
                            now()->startOfDay(),
                            now()->addDays((int) $data['value'])->endOfDay(),
                        ]);
                    }),

                /* =====================================================
                | Subscription Plan
                ===================================================== */
                SelectFilter::make('plan_id')
                    ->label(__('Subscription Plan'))
                    ->relationship('tenantPlan.plan', 'name')
                    ->searchable()
                    ->preload(),

                /* =====================================================
                | Active / Inactive
                ===================================================== */
                TernaryFilter::make('is_active')
                    ->label(__('Active Status')),

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
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}