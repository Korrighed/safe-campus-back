<x-filament-panels::page>
    <div class="mt-6 flex flex-col gap-y-6">
        @foreach ($themes as $theme)
            <section class="flex flex-col gap-y-3">
                <hr class="border-t border-gray-200 dark:border-white/10" />

                <div class="flex items-center justify-between gap-x-4">
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                        {{ $theme->libelle }}
                    </h2>

                    <x-filament::button
                        tag="a"
                        href="{{ \App\Filament\Resources\SousThemeResource::getUrl('create', ['theme_id' => $theme->getKey()]) }}"
                        icon="heroicon-m-plus"
                        size="sm"
                    >
                        Nouveau sous-thème
                    </x-filament::button>
                </div>

                @livewire(\App\Livewire\ThemeSousThemesTable::class, ['theme' => $theme], key('sous-themes-table-' . $theme->getKey()))
            </section>
        @endforeach
    </div>
</x-filament-panels::page>
