@php
    use App\Filament\Resources\ContactResource;
    use App\Filament\Resources\MediaResource;
    use App\Filament\Resources\SousThemeResource;
    use App\Filament\Resources\ThemeResource;
@endphp

<x-filament-widgets::widget>
    <x-filament::section collapsible collapsed>
        <x-slot name="heading">
            Guide rapide — Webmaster
        </x-slot>

        <div class="prose prose-sm dark:prose-invert max-w-none">
            <p>Tu es webmaster. Tu tiens à jour les contacts affichés sur le site. Tu gères aussi les thèmes, les sous-thèmes et les médias.</p>

            <p>
                <strong>Contacts</strong>
                <x-filament::link :href="ContactResource::getUrl()" icon="heroicon-o-arrow-right" icon-position="after" size="xs">
                    Ouvrir la page Contacts
                </x-filament::link>
            </p>
            <ul>
                <li>Chaque contact représente une structure. Cette structure peut être une association, un numéro d'urgence ou un service public.</li>
                <li>Le champ <code>ref</code> identifie le contact de façon unique. Ce champ se fige après la création : tu ne peux plus le modifier ensuite.</li>
                <li>L'onglet <em>Téléphones</em> liste les numéros du contact. Un contact peut avoir plusieurs numéros. Choisis le bon type pour chaque numéro (fixe, mobile, SMS ou urgence).</li>
                <li>L'onglet <em>Sous-thèmes</em> de la fiche contact rattache le contact à une ou plusieurs rubriques (bouton <em>Attacher</em>, puis choisis le sous-thème et son <em>Ordre</em> d'affichage). Ce rattachement détermine où le contact apparaît sur le site. Le même rattachement est aussi possible dans l'autre sens, depuis l'onglet <em>Contacts</em> d'un sous-thème.</li>
                <li>L'interrupteur <em>Actif</em> retire le contact du site sans le supprimer. Utilise cet interrupteur dès que tu as un doute sur une information.</li>
                <li>Les champs Latitude et Longitude placent le contact sur la carte du site. Laisse ces champs vides si tu ne connais pas les coordonnées : le site masque alors simplement le repère.</li>
            </ul>

            <p>
                <strong>Sous-thèmes</strong>
                <x-filament::link :href="SousThemeResource::getUrl()" icon="heroicon-o-arrow-right" icon-position="after" size="xs">
                    Ouvrir la page Sous-thèmes
                </x-filament::link>
            </p>
            <ul>
                <li>Un sous-thème regroupe plusieurs contacts autour d'un sujet précis, par exemple l'alcool ou le harcèlement.</li>
                <li>Cette page te permet de modifier le résumé, l'article complet et l'introduction de la page ressources.</li>
                <li>L'interrupteur <em>Actif</em> retire le sous-thème et sa fiche du site sans le supprimer.</li>
            </ul>

            <p>
                <strong>Thèmes</strong>
                <x-filament::link :href="ThemeResource::getUrl()" icon="heroicon-o-arrow-right" icon-position="after" size="xs">
                    Ouvrir la page Thèmes
                </x-filament::link>
            </p>
            <ul>
                <li>Un thème regroupe plusieurs sous-thèmes, par exemple les addictions ou les violences.</li>
                <li>Cette page te permet de modifier l'ordre d'affichage des thèmes sur l'accueil du site.</li>
                <li>L'interrupteur <em>Actif</em> retire le thème et tous ses sous-thèmes du site sans les supprimer.</li>
            </ul>

            <p>
                <strong>Médias</strong>
                <x-filament::link :href="MediaResource::getUrl()" icon="heroicon-o-arrow-right" icon-position="after" size="xs">
                    Ouvrir la page Médias
                </x-filament::link>
            </p>
            <ul>
                <li>Un média est un fichier utile aux visiteurs : un PDF, une image ou un autre document.</li>
                <li>Cette page te permet d'ajouter un fichier. Rattache-le ensuite à un ou plusieurs sous-thèmes via l'onglet <em>Sous-thèmes</em> de la fiche média (bouton <em>Attacher</em>, puis choisis le sous-thème et son <em>Ordre</em> d'affichage), ou depuis l'onglet <em>Médias</em> d'un sous-thème.</li>
                <li>L'interrupteur <em>Actif</em> retire le média du site sans le supprimer.</li>
            </ul>

            <p class="text-xs opacity-70">Une suppression efface aussi l'information sur le site public, pas seulement en base de données. Préfère toujours désactiver un élément plutôt que le supprimer.</p>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
