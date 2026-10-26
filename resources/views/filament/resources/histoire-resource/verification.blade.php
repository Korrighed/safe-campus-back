@php
    use App\Filament\Resources\HistoireResource;
@endphp

<ul class="flex flex-col gap-4">
    @foreach ($controles as $controle)
        <li class="flex gap-3">
            @if ($controle['ok'])
                <x-filament::icon icon="heroicon-o-check-circle" class="sc-couleur h-5 w-5 shrink-0" style="--sc-clair: var(--success-700); --sc-sombre: var(--success-400)" />
            @else
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="sc-couleur h-5 w-5 shrink-0" style="--sc-clair: var(--warning-700); --sc-sombre: var(--warning-400)" />
            @endif

            <div class="flex flex-col gap-1 text-sm">
                <p @class(['font-medium', 'text-gray-500 dark:text-gray-400' => $controle['ok']])>
                    {{ $controle['libelle'] }}
                </p>

                @if ($controle['details'] !== [])
                    <ul class="text-gray-600 dark:text-gray-200">
                        @foreach ($controle['details'] as $detail)
                            <li>– {{ $detail }}</li>
                        @endforeach
                    </ul>
                @endif

                @unless ($controle['ok'])
                    <div>
                        <x-filament::link :href="HistoireResource::urlCorrection($histoire, $controle['cle'])" icon="heroicon-m-arrow-right" icon-position="after" size="sm">
                            Corriger
                        </x-filament::link>
                    </div>
                @endunless
            </div>
        </li>
    @endforeach
</ul>
