{{-- Grille locale : classes absentes du CSS précompilé de Filament. --}}
<style>
    @media (min-width: 768px) {
        .sc-en-tete-onglets {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
        }
    }
</style>

@include('filament.resources.histoire-resource.pages.scenes-histoire-styles')

<header class="fi-header flex flex-col gap-4">
    <div class="sc-en-tete-onglets flex flex-col gap-4">
        <div>
            @if ($breadcrumbs)
                <x-filament::breadcrumbs :breadcrumbs="$breadcrumbs" class="hidden sm:block" />
            @endif
        </div>

        <x-filament-panels::page.sub-navigation.tabs :navigation="$onglets" />

        <div></div>
    </div>

    <x-filament-panels::page.sub-navigation.select :navigation="$onglets" />

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="fi-header-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
            {{ $heading }}
        </h1>

        @if ($actions)
            <x-filament::actions :actions="$actions" class="shrink-0" />
        @endif
    </div>
</header>
