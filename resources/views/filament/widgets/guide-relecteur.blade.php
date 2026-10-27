@php
    use App\Filament\Resources\HistoireResource;
@endphp

<x-filament-widgets::widget>
    <x-filament::section collapsible collapsed>
        <x-slot name="heading">
            Guide rapide — Relecteur
        </x-slot>

        <div class="flex flex-col gap-y-4">
            <p class="text-sm text-gray-700 dark:text-gray-200">Tu es relecteur. Tu relis les histoires écrites par les rédacteurs, tu les corriges, puis tu les publies ou tu les renvoies en brouillon.</p>

            <x-filament::section compact collapsible collapsed persist-collapsed id="guide-relecteur-relire" icon="heroicon-o-book-open">
                <x-slot name="heading">Relire</x-slot>

                <div class="prose prose-sm dark:prose-invert max-w-none">
                    <ul>
                        <li>Tu vois toutes les histoires, avec leur auteur.</li>
                        <li>Filtre la liste par état pour retrouver celles en <em>Relecture</em>.</li>
                        <li>Le menu de l'histoire a trois pages : <em>Infos</em>, <em>Scènes</em> et <em>Prévisualiser</em>. L'entrée <em>Vérifier</em> du menu liste ce qui manque.</li>
                    </ul>
                </div>
            </x-filament::section>

            <x-filament::section compact collapsible collapsed persist-collapsed id="guide-relecteur-corriger" icon="heroicon-o-film">
                <x-slot name="heading">Corriger</x-slot>

                <div class="prose prose-sm dark:prose-invert max-w-none">
                    <ul>
                        <li>Tu modifies une histoire dans n'importe quel état.</li>
                        <li>Attention : corriger une histoire <em>Publiée</em> change immédiatement le site.</li>
                        <li>Tu ne crées pas d'histoire.</li>
                    </ul>
                </div>
            </x-filament::section>

            <x-filament::section compact collapsible collapsed persist-collapsed id="guide-relecteur-faire-avancer" icon="heroicon-o-arrows-right-left">
                <x-slot name="heading">Faire avancer</x-slot>

                <div class="prose prose-sm dark:prose-invert max-w-none">
                    <ul>
                        <li>Le champ <em>État</em> te laisse choisir librement : <em>Brouillon</em>, <em>Relecture</em>, <em>Validé</em> (approuvé), <em>Publié</em> (visible sur le site).</li>
                        <li>Renvoie une histoire en <em>Brouillon</em> pour que son auteur la reprenne, ou pour la retirer du site. Rien n'est supprimé.</li>
                    </ul>
                </div>
            </x-filament::section>

            <x-filament::link :href="HistoireResource::getUrl()" icon="heroicon-o-arrow-right" icon-position="after" size="sm">
                Ouvrir la page Histoires
            </x-filament::link>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
