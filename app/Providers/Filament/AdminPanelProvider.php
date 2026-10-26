<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\Register;
use App\Filament\Resources\HistoireResource\Pages\EditHistoire;
use App\Filament\Resources\HistoireResource\Pages\PreviewHistoire;
use App\Filament\Resources\HistoireResource\Pages\ScenesHistoire;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->registration(Register::class)
            ->profile(EditProfile::class, isSimple: false)
            ->colors([

                'primary' => Color::hex('#4260e6'),
                'gray' => [200 => '179, 191, 245'] + Color::Zinc,
            ])
            ->renderHook(
                PanelsRenderHook::AUTH_REGISTER_FORM_BEFORE,
                fn () => new HtmlString(
                    '<p class="text-sm text-gray-500 dark:text-gray-400">'
                    .'Votre compte ne sera actif qu\'après validation par un administrateur.'
                    .'</p>'
                    .'<p class="text-sm text-gray-500 dark:text-gray-400">'
                    .'Vos nom, prénom, email et téléphone servent à gérer les accès. Ils sont conservés pendant la durée de votre fonction, '
                    .'et votre compte est supprimé après 3 ans sans connexion. Vous pouvez demander sa suppression à un administrateur. '
                    .'<a href="/cgu#donnees-personnelles" target="_blank" class="underline">Détail et droits</a>.'
                    .'</p>'
                ),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn () => new HtmlString(
                    '<p class="text-xs text-gray-500 dark:text-gray-400 text-center">'
                    .'La connexion dépose un cookie de session (120 min d\'inactivité) et votre adresse IP est journalisée 365 jours. '
                    .'<a href="/cgu#donnees-personnelles" target="_blank" class="underline">Données personnelles</a>.'
                    .'</p>'
                ),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->assets([
                // Charge sur toutes les pages du panel (pas seulement quand MapField est visible) :
                // sinon "L" n'existe pas au premier rendu Livewire ou MapField apparait (ex. contact
                // deja en "position_territoire", ou toggle "tout le territoire" desactive puis reactive).
                Css::make('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'),
                Js::make('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'),
            ])
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn (): ViewContract => view('filament.footer'),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): ViewContract => view('filament.watermark'),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): ViewContract => view('filament.resources.histoire-resource.ouvrir-verification'),
                scopes: [EditHistoire::class, ScenesHistoire::class, PreviewHistoire::class],
            )
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
