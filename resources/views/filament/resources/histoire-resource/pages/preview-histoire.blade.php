<x-filament-panels::page>
    @php
        $scene = $this->getSceneActuelle();
        $choixFinal = $this->getChoixFinal();
        $contacts = $choixFinal ? $this->getContacts() : collect();
    @endphp

    @if ($scene)
        <x-filament::section>
            <x-slot name="heading">
                {{ $scene->titre }}
            </x-slot>

            @if ($scene->media?->type === \App\Enums\MediaType::Image && $scene->media->url)
                <img
                    src="{{ $scene->media->url }}"
                    alt="{{ $scene->media->libelle }}"
                    class="mb-4 max-h-96 w-full rounded-lg object-cover"
                />
            @endif

            <p class="text-base leading-relaxed">{{ $scene->dialogue_text }}</p>

            <div class="mt-6 flex flex-col gap-2">
                @forelse ($scene->choix as $choix)
                    <x-filament::button
                        color="gray"
                        wire:click="choisir({{ $choix->id }})"
                        class="justify-start text-left"
                    >
                        {{ $choix->text_choix }}
                    </x-filament::button>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Cette scène n'a aucun choix : le parcours s'arrête ici sans issue définie.
                    </p>
                @endforelse
            </div>
        </x-filament::section>
    @elseif ($choixFinal)
        <x-filament::section>
            <x-slot name="heading">
                @if ($choixFinal->issue?->value === 'favorable')
                    ✅ Fin du parcours — Bonne fin
                @else
                    ⚠️ Fin du parcours — Fin à risque
                @endif
            </x-slot>

            <p class="text-base">{{ $choixFinal->text_choix }}</p>

            @if ($choixFinal->issue?->value === 'defavorable')
                <div class="mt-4">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Contacts proposés :</p>

                    @forelse ($contacts as $contact)
                        <div class="mt-2 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <p class="font-semibold">{{ $contact->nom }}</p>
                            @if ($contact->telephones->isNotEmpty())
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $contact->telephones->pluck('numero')->join(' · ') }}
                                </p>
                            @endif
                        </div>
                    @empty
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Aucun contact résolu — vérifie que l'histoire (ou la scène) porte bien un sous-thème avec des contacts actifs rattachés.
                        </p>
                    @endforelse
                </div>
            @endif

            <x-filament::button color="primary" wire:click="recommencer" class="mt-6">
                Recommencer le parcours
            </x-filament::button>
        </x-filament::section>
    @else
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Cette histoire n'a pas de scène initiale définie. Ouvre l'onglet Scènes et coche « Scène initiale » sur l'une d'entre elles.
            </p>
        </x-filament::section>
    @endif
</x-filament-panels::page>
