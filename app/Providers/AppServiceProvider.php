<?php

namespace App\Providers;

use App\Filament\Resources\ContactResource\Pages\ListContacts;
use App\Filament\Resources\ContactResource\RelationManagers\TelephonesRelationManager;
use App\Filament\Resources\HistoireResource\Pages\ListHistoires;
use App\Filament\Resources\MediaResource\Pages\ListMedia;
use App\Filament\Resources\ThemeResource\Pages\ListThemes;
use App\Filament\Resources\ThemeResource\RelationManagers\SousThemesRelationManager;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Livewire\ThemeSousThemesTable;
use App\Models\Choix;
use App\Models\Contact;
use App\Models\Histoire;
use App\Models\Media;
use App\Models\Scene;
use App\Models\Signalement;
use App\Models\SousTheme;
use App\Models\Telephone;
use App\Models\Theme;
use App\Models\User;
use App\Observers\AuditObserver;
use App\Observers\HistoireObserver;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPasswordDefaults();
        $this->registerAudit();
        $this->registerClickToEditHints();
        $this->registerAdminBorderStyles();
    }

    /**
     * Regle unique pour tout mot de passe saisi (inscription, futur reset) —
     * Filament lit Password::default(). Au-dessus de la recommandation CNIL.
     * max(72) : bcrypt ignore au-dela de 72 octets, on refuse plutot que
     * tronquer en silence. uncompromised() interroge api.pwnedpasswords.com
     * (k-anonymat : seuls 5 caracteres du SHA-1 sortent).
     */
    private function registerPasswordDefaults(): void
    {
        Password::defaults(fn () => Password::min(14)
            ->max(72)
            ->mixedCase()
            ->symbols()
            ->uncompromised());
    }

    /**
     * Logs d'audit (canal `audit`) : toute creation, modification ou
     * suppression de ces modeles. Les connexions sont tracees par
     * App\Listeners\AuditAuthentification.
     *
     * L'état d'une histoire a en plus son propre événement (HistoireObserver).
     */
    private function registerAudit(): void
    {
        foreach ([
            Choix::class, Contact::class, Histoire::class, Media::class, Scene::class,
            Signalement::class, SousTheme::class, Telephone::class, Theme::class, User::class,
        ] as $model) {
            $model::observe(AuditObserver::class);
        }

        Histoire::observe(HistoireObserver::class);
    }

    /**
     * Standard applique a tous les tableaux ou une ligne ouvre la modification
     * au clic : le rappeler en toutes lettres dans l'en-tete du tableau.
     */
    private function registerClickToEditHints(): void
    {
        $hints = [
            'thème' => [ListThemes::class],
            'sous-thème' => [SousThemesRelationManager::class, ThemeSousThemesTable::class],
            'contact' => [ListContacts::class],
            'média' => [ListMedia::class],
            'téléphone' => [TelephonesRelationManager::class],
            'histoire' => [ListHistoires::class],
            'utilisateur' => [ListUsers::class],
        ];

        $feminins = ['histoire'];

        foreach ($hints as $label => $scopes) {
            $article = in_array($label, $feminins, true) ? 'une' : 'un';

            FilamentView::registerRenderHook(
                TablesRenderHook::TOOLBAR_START,
                fn () => Blade::render(
                    '<span class="fi-ta-hint text-sm italic text-gray-500 dark:text-gray-400">Pour modifier '.$article.' '.e($label).', cliquez dessus.</span>'
                ),
                scopes: $scopes,
            );
        }
    }

    private function registerAdminBorderStyles(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::STYLES_AFTER,
            fn () => Blade::render(<<<'HTML'
                <style>
                    .ring-gray-950\/5 {
                        --tw-ring-color: rgb(66 96 230 / 0.45) !important;
                    }

                    .ring-gray-950\/10 {
                        --tw-ring-color: rgb(66 96 230 / 0.6) !important;
                    }

                    .fi-sidebar-item.fi-active > .fi-sidebar-item-button {
                        border: 1px solid rgb(66 96 230 / 0.6);
                    }

                    .fi-section-header .fi-icon-btn {
                        color: rgb(66 96 230) !important;
                    }

                    .fi-section-header .fi-icon-btn:hover {
                        color: rgb(59 86 207) !important;
                    }

                    .fi-section-header .fi-icon-btn-icon {
                        width: 1.75rem !important;
                        height: 1.75rem !important;
                    }
                </style>
                HTML
            ),
        );
    }
}
