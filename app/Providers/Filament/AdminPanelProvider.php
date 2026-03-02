<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Resources\Campaigns\CampaignResource;
use App\Filament\Resources\Invitations\InvitationResource;
use App\Filament\Resources\Locations\LocationResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\PageTypes\PageTypeResource;
use App\Filament\Resources\Schools\SchoolResource;
use App\Filament\Resources\Settings\SettingResource;
use App\Filament\Resources\Templates\TemplateResource;
use App\Filament\Resources\TemplateTypes\TemplateTypeResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\Admin\DataCollectionTrendChart;
use App\Filament\Widgets\Admin\OrdersByStatusChart;
use App\Filament\Widgets\Admin\OrdersKpiOverview;
use App\Filament\Widgets\Admin\TopSchoolsChart;
use App\Filament\Widgets\Admin\TopSchoolsTable;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Notifications\Livewire\Notifications;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Yebor974\Filament\RenewPassword\RenewPasswordPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile(EditProfile::class)
            ->plugin(
                RenewPasswordPlugin::make()
                    ->forceRenewPassword(true)
                    ->timestampColumn('last_password_renew_at')
            )

            ->colors([
                'primary' => Color::Amber,
            ])
            ->spa(hasPrefetching: true)

            ->databaseNotifications()
            // Se non ci sono websocket/pusher attivi, il polling fa comparire la notifica da solo.
            ->databaseNotificationsPolling('5s')

            // Tema Filament (standard) per personalizzazioni UI del pannello.
            ->viteTheme('resources/css/filament/admin/theme.css')

            // Toast in basso a destra
            ->bootUsing(function (): void {
                Notifications::alignment(Alignment::End);
                Notifications::verticalAlignment(VerticalAlignment::End);
            })

            ->resources([
                UserResource::class,
                InvitationResource::class,
                SchoolResource::class,
                LocationResource::class,
                CampaignResource::class,
                OrderResource::class,
                SettingResource::class,
                TemplateTypeResource::class,
                TemplateResource::class,
                PageTypeResource::class,
                PageResource::class,
            ])

            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                /** @var User|null $user */
                $user = Auth::user();
                $role = (string) ($user->role ?? '');

                $isExternal = (bool) str_starts_with($role, 'external');
                $isInternal = (bool) str_starts_with($role, 'internal');

                $groups = [];

                $groups[] = NavigationGroup::make()
                    ->collapsible(false)
                    ->items([
                        NavigationItem::make('dashboard')
                            ->label(__('filament-panels::pages/dashboard.title'))
                            ->icon('heroicon-o-home')
                            ->url(fn(): string => Dashboard::getUrl())
                            ->isActiveWhen(fn(): bool => request()->routeIs('filament.admin.pages.dashboard')),
                    ]);

                if (!$isExternal) {
                    $groups[] = NavigationGroup::make('Utenti')
                        ->icon('heroicon-o-users')
                        ->collapsible()
                        ->items([
                            NavigationItem::make('users')
                                ->label('Attivi')
                                ->url(fn(): string => UserResource::getUrl('index'))
                                ->isActiveWhen(fn(): bool => request()->routeIs('filament.admin.resources.users.*')),

                            NavigationItem::make('invitations')
                                ->label('Inviti')
                                ->url(fn(): string => InvitationResource::getUrl('index'))
                                ->isActiveWhen(fn(): bool => request()->routeIs('filament.admin.resources.invitations.*')),
                        ]);
                }

                $groups[] = NavigationGroup::make()
                    ->collapsible(false)
                    ->items([
                        NavigationItem::make('schools')
                            ->label('Scuole')
                            ->icon('heroicon-o-academic-cap')
                            ->url(fn(): string => SchoolResource::getUrl('index'))
                            ->isActiveWhen(fn(): bool => request()->routeIs('filament.admin.resources.schools.*')),
                    ]);

                $groups[] = NavigationGroup::make()
                    ->collapsible(false)
                    ->items([
                        NavigationItem::make('campaigns')
                            ->label('Campagne')
                            ->icon('heroicon-o-calendar-days')
                            ->url(fn(): string => CampaignResource::getUrl('index'))
                            ->isActiveWhen(fn(): bool => request()->routeIs('filament.admin.resources.campaigns.*')),
                    ]);

                $groups[] = NavigationGroup::make()
                    ->collapsible(false)
                    ->items([
                        NavigationItem::make('orders')
                            ->label('Ordini')
                            ->icon('heroicon-o-rectangle-stack')
                            ->url(fn(): string => OrderResource::getUrl('index'))
                            ->isActiveWhen(fn(): bool => request()->routeIs('filament.admin.resources.orders.*')),
                    ]);

                $groups[] = NavigationGroup::make('Configurazione')
                    ->icon('heroicon-o-rectangle-stack')
                    ->collapsible()
                    ->items([
                        NavigationItem::make('tipologie_pagina')
                            ->label('Tipologie Pagina')
                            ->url(fn(): string => PageTypeResource::getUrl('index'))
                            ->isActiveWhen(fn(): bool => request()->routeIs('filament.admin.resources.page-types.*')),

                        NavigationItem::make('modelli_diario')
                            ->label('Modelli Diario')
                            ->url(fn(): string => TemplateTypeResource::getUrl('index'))
                            ->isActiveWhen(fn(): bool => request()->routeIs('filament.admin.resources.template-types.*')),
                    ]);

                $groups[] = NavigationGroup::make('Compilazione')
                    ->icon('heroicon-o-document-text')
                    ->collapsible()
                    ->items([
                    NavigationItem::make('templates')
                        ->label('Modelli Istanziati')
                        ->url(fn(): string => TemplateResource::getUrl('index'))
                        ->isActiveWhen(fn(): bool => request()->routeIs('filament.admin.resources.templates.*')),

                    NavigationItem::make('pages')
                        ->label('Pagine Istanziate')
                        ->url(fn(): string => PageResource::getUrl('index'))
                        ->isActiveWhen(fn(): bool => request()->routeIs('filament.admin.resources.pages.*')),
                    ]);

                return $builder->groups($groups);
            })

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
                OrdersKpiOverview::class,
                OrdersByStatusChart::class,
                DataCollectionTrendChart::class,
                TopSchoolsChart::class,
                TopSchoolsTable::class,
            ])

            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
