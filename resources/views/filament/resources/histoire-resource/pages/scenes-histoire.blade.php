<x-filament-panels::page>
    @php
        $sections = $this->getSections();
        $editionEnCours = $this->sceneEnEdition !== null;
        $choixEnAttente = $this->getChoixEnAttente();
    @endphp
    @php $peutModifier = $this->peutModifier(); @endphp

    <div class="flex flex-col gap-y-6">
        @if (! $peutModifier)
            <x-filament::section compact>
                <p class="text-sm text-gray-600 dark:text-gray-200">Lecture seule : cette histoire n'est plus en brouillon. Elle redevient modifiable si un relecteur la renvoie en brouillon.</p>
            </x-filament::section>
        @endif

        <x-filament::section
            icon="heroicon-o-light-bulb"
            icon-color="primary"
            collapsible
            :collapsed="count($sections) > 2"
            persist-collapsed
            id="methode-ecriture"
        >
            <x-slot name="heading">Comment écrire ton histoire</x-slot>

            <ol class="flex flex-col gap-y-2 text-sm text-gray-700 dark:text-gray-200">
                <li><strong>1. Les scènes d'abord.</strong> Crée chaque scène avec « Nouvelle scène » : son nom et sa mise en situation.</li>
                <li><strong>2. Puis les choix.</strong> Ouvre une scène avec « Modifier » et ajoute ses choix. Un choix « Continuer » ne peut mener qu'à une scène <em>qui existe déjà</em>.</li>
                <li><strong>Tu préfères avancer scène par scène ?</strong> C'est possible : laisse « Continuer vers » vide, le choix est marqué « Suite à choisir ». Une fois la scène suivante créée, reviens sur ce choix pour le relier.</li>
            </ol>
        </x-filament::section>

        @if ($choixEnAttente !== [] && ! $editionEnCours)
            <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="warning">
                <x-slot name="heading">
                    {{ count($choixEnAttente) > 1 ? count($choixEnAttente).' choix attendent encore leur scène suivante' : '1 choix attend encore sa scène suivante' }}
                </x-slot>

                <x-slot name="description">
                    Si la scène existe maintenant, ouvre la scène du choix et choisis-la dans « Continuer vers ».
                </x-slot>

                <ul class="flex flex-col gap-y-2 text-sm text-gray-700 dark:text-gray-200">
                    @foreach ($choixEnAttente as $attente)
                        <li class="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <span>Scène {{ $attente['numero'] }} — {{ $attente['scene'] }} : « {{ $attente['texte'] }} »</span>
                            @if ($peutModifier)
                                <x-filament::link tag="button" size="sm" wire:click="modifier({{ $attente['sceneId'] }})">
                                    Relier ce choix
                                </x-filament::link>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </x-filament::section>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-4">
            <p class="max-w-2xl text-sm text-gray-600 dark:text-gray-200">
                Une histoire avance de scène en scène. Chaque scène pose une mise en situation, puis propose des choix au joueur.
            </p>

            @if ($peutModifier)
                <div class="flex items-center gap-x-2">
                    <x-filament::button wire:click="nouvelleScene" icon="heroicon-m-plus" :disabled="$editionEnCours">
                        Nouvelle scène
                    </x-filament::button>

                    <x-filament::button
                        color="gray"
                        wire:click="$toggle('reutilisationOuverte')"
                        icon="heroicon-m-chevron-down"
                        icon-position="after"
                        :disabled="$editionEnCours"
                    >
                        Plus
                    </x-filament::button>
                </div>
            @endif
        </div>

        @if ($peutModifier && $this->reutilisationOuverte)
            @php
                $reutilisables = $this->getScenesReutilisables();
            @endphp

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex flex-wrap items-end gap-4">
                    <label class="flex min-w-0 flex-1 flex-col gap-y-2 text-sm font-medium text-gray-950 dark:text-white">
                        Réutiliser une scène d'une autre histoire

                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model="sceneAReutiliser">
                                <option value="">Choisir une scène</option>
                                @foreach ($reutilisables as $id => $libelle)
                                    <option value="{{ $id }}">{{ $libelle }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>

                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                            La scène reste partagée : la modifier ici la modifie aussi dans l'autre histoire.
                        </span>
                    </label>

                    <div class="flex gap-x-2">
                        <x-filament::button color="gray" wire:click="$set('reutilisationOuverte', false)">
                            Annuler
                        </x-filament::button>
                        <x-filament::button wire:click="reutiliser">
                            Ajouter à l'histoire
                        </x-filament::button>
                    </div>
                </div>
            </div>
        @endif

        @forelse ($sections as $section)
            @php
                $scene = $section['scene'];
                $enEdition = $this->sceneEnEdition === $scene->id;
                $bloquee = $editionEnCours && ! $enEdition;
                $tooltipModifier = match (true) {
                    $bloquee => 'Termine d\'abord la scène en cours de modification',
                    ! $section['modifiable'] => 'Cette scène est aussi utilisée par une histoire verrouillée ou d\'un autre auteur',
                    default => null,
                };
            @endphp

            <section class="flex flex-col gap-y-3" wire:key="scene-{{ $scene->id }}">
                <hr class="border-t border-gray-200 dark:border-white/10" />

                <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                        Scène {{ $section['numero'] }} — {{ $scene->titre }}
                    </h2>

                    @foreach ($section['badges'] as $badge)
                        <x-filament::badge :color="$badge['couleur']">{{ $badge['libelle'] }}</x-filament::badge>
                    @endforeach

                    @if ($enEdition)
                        <x-filament::badge color="primary">Modification en cours</x-filament::badge>
                    @endif

                    @if ($peutModifier)
                        @unless ($enEdition)
                            <div class="ms-auto flex items-center gap-x-2">
                                <x-filament::button
                                    color="gray"
                                    size="sm"
                                    wire:click="modifier({{ $scene->id }})"
                                    :disabled="$bloquee || ! $section['modifiable']"
                                    :tooltip="$tooltipModifier"
                                >
                                    Modifier
                                </x-filament::button>

                                <x-filament::dropdown placement="bottom-end">
                                    <x-slot name="trigger">
                                        <x-filament::icon-button
                                            icon="heroicon-m-ellipsis-vertical"
                                            color="gray"
                                            :label="'Autres actions sur la scène '.$section['numero']"
                                            :disabled="$bloquee"
                                        />
                                    </x-slot>

                                    <x-filament::dropdown.list>
                                        @unless ($section['estDepart'])
                                            <x-filament::dropdown.list.item icon="heroicon-m-flag" wire:click="definirDepart({{ $scene->id }})">
                                                Définir comme départ
                                            </x-filament::dropdown.list.item>
                                        @endunless

                                        @unless ($section['estPremiere'])
                                            <x-filament::dropdown.list.item icon="heroicon-m-arrow-up" wire:click="monter({{ $scene->id }})">
                                                Monter
                                            </x-filament::dropdown.list.item>
                                        @endunless

                                        @unless ($section['estDerniere'])
                                            <x-filament::dropdown.list.item icon="heroicon-m-arrow-down" wire:click="descendre({{ $scene->id }})">
                                                Descendre
                                            </x-filament::dropdown.list.item>
                                        @endunless

                                        <x-filament::dropdown.list.item
                                            icon="heroicon-m-minus-circle"
                                            wire:click="mountAction('retirerScene', { scene: {{ $scene->id }} })"
                                        >
                                            Retirer de l'histoire
                                        </x-filament::dropdown.list.item>

                                        <x-filament::dropdown.list.item
                                            icon="heroicon-m-trash"
                                            color="danger"
                                            wire:click="mountAction('supprimerScene', { scene: {{ $scene->id }} })"
                                        >
                                            Supprimer la scène
                                        </x-filament::dropdown.list.item>
                                    </x-filament::dropdown.list>
                                </x-filament::dropdown>
                            </div>
                        @endunless
                    @endif
                </div>

                @if ($enEdition)
                    @include('filament.resources.histoire-resource.pages.scenes-histoire-edition')
                @else
                    @include('filament.resources.histoire-resource.pages.scenes-histoire-consultation', ['section' => $section])
                @endif
            </section>
        @empty
            <p class="text-sm text-gray-600 dark:text-gray-200">
                Aucune scène pour l'instant. Commence par la scène de départ avec le bouton « Nouvelle scène ».
            </p>
        @endforelse

        @if ($this->sceneEnEdition === 0)
            <section class="flex flex-col gap-y-3" wire:key="scene-nouvelle">
                <hr class="border-t border-gray-200 dark:border-white/10" />

                <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                        Scène {{ count($sections) + 1 }} — Nouvelle scène
                    </h2>
                    <x-filament::badge color="primary">Modification en cours</x-filament::badge>
                </div>

                @include('filament.resources.histoire-resource.pages.scenes-histoire-edition')
            </section>
        @endif
    </div>
</x-filament-panels::page>
