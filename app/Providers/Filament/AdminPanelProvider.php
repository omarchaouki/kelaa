<?php

namespace App\Providers\Filament;

use App\Filament\AvatarProviders\InitialsAvatarProvider;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName(fn () => config('app.name').' · Admin')
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2.25rem')
            ->favicon('/icons/icon-192.png')
            // Same look as the public site: one blue, the cool greys (and their dark-mode
            // counterparts), the system font so nothing is fetched from a font CDN.
            ->colors([
                'primary' => [
                    50 => '#e7f3ff',
                    100 => '#dbe7f2',
                    200 => '#bcd6f8',
                    300 => '#8eb9f5',
                    400 => '#4599ff',
                    500 => '#2d88ff',
                    600 => '#1877f2',
                    700 => '#166fe5',
                    800 => '#1461c9',
                    900 => '#0b4ca1',
                    950 => '#08336b',
                ],
                'danger' => Color::hex('#e41e3f'),
                'success' => Color::hex('#31a24c'),
                'warning' => Color::hex('#f7b928'),
                'gray' => [
                    50 => '#f7f8fa',
                    100 => '#f0f2f5',
                    200 => '#e4e6eb',
                    300 => '#ced0d4',
                    400 => '#bcc0c4',
                    500 => '#8a8d91',
                    600 => '#65676b',
                    700 => '#3e4042',
                    800 => '#3a3b3c',
                    900 => '#242526',
                    950 => '#18191a',
                ],
            ])
            ->font('Segoe UI', provider: LocalFontProvider::class)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->defaultAvatarProvider(InitialsAvatarProvider::class)
            // Filament's inline scripts have no CSP nonce: see the view for what it replaces.
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => view('filament.csp-bootstrap'))
            ->navigationGroups([
                __('Modération'),
                __('Catalogue'),
                __('Contenu'),
                __('Monétisation'),
                __('Utilisateurs'),
            ])
            ->databaseNotifications()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,

            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
