@php
    use App\Filament\Resources\HistoireResource;
@endphp

<x-filament-widgets::widget>
    <x-filament::section collapsible collapsed>
        <x-slot name="heading">
            Guide rapide — Rédacteur
        </x-slot>

        <div class="flex flex-col gap-y-4">
            <p class="text-sm text-gray-700 dark:text-gray-200">Tu es rédacteur. Tu écris les histoires interactives (mode histoire) affichées sur le site.</p>

            <x-filament::section compact collapsible persist-collapsed id="guide-redacteur-histoires" icon="heroicon-o-book-open">
                <x-slot name="heading">Histoires</x-slot>

                <div class="prose prose-sm dark:prose-invert max-w-none">
                    <ul>
                        <li>Une histoire est un parcours interactif à choix multiples : le joueur avance de scène en scène et arrive à une <em>Bonne fin</em> ou à une <em>Fin à risque</em>.</li>
                        <li>Le bouton <em>Nouvelle histoire</em> ouvre un assistant en deux étapes : le titre et les sous-thèmes, puis la mise en situation de la première scène, qui devient automatiquement le point de départ.</li>
                        <li>Une fois l'histoire ouverte, un menu la découpe en trois pages : <em>1. Infos de l'histoire</em> (titre, état, sous-thèmes), <em>2. Scènes</em> et <em>3. Prévisualiser</em>.</li>
                        <li>Les <em>Sous-thèmes</em> font apparaître le bouton « Suivre l'histoire » sur les pages ressources correspondantes.</li>
                        <li>Le champ <em>État</em> : passe l'histoire de <em>Brouillon</em> à <em>Relecture</em> quand elle est prête. Elle est alors verrouillée : un relecteur la relit, la corrige, la publie ou te la renvoie en <em>Brouillon</em>. Seule une histoire <em>Publiée</em> apparaît sur le site.</li>
                        <li>Tu ne vois que tes propres histoires. Pour modifier une histoire déjà soumise, demande à un relecteur de la renvoyer en <em>Brouillon</em>.</li>
                    </ul>

                    <x-filament::link :href="HistoireResource::getUrl()" icon="heroicon-o-arrow-right" icon-position="after" size="sm">
                        Ouvrir la page Histoires
                    </x-filament::link>
                </div>
            </x-filament::section>

            <x-filament::section compact collapsible collapsed persist-collapsed id="guide-redacteur-scenes" icon="heroicon-o-film">
                <x-slot name="heading">Scènes</x-slot>
                <x-slot name="description">Page <em>2. Scènes</em> d'une histoire</x-slot>

                <div class="prose prose-sm dark:prose-invert max-w-none">
                    <ul>
                        <li>Méthode conseillée : écris d'abord tes scènes, puis leurs choix. Un choix ne peut mener qu'à une scène qui existe déjà. L'encadré <em>Comment écrire ton histoire</em>, en haut de la page, le rappelle.</li>
                        <li>Les scènes sont listées dans l'ordre : <em>Scène 1</em>, <em>Scène 2</em>… Sous chacune, un extrait de sa mise en situation et le cadre <em>Choix du joueur</em>.</li>
                        <li>Clique sur <em>Modifier</em> : la scène s'ouvre sur place pour écrire son <em>Nom</em> (un repère pour toi, jamais montré au joueur), sa <em>Mise en situation</em> (le texte que lit le joueur) et ses choix. Termine par <em>Enregistrer la scène</em>, ou <em>Annuler</em>. Une seule scène s'ouvre à la fois.</li>
                        <li><em>Nouvelle scène</em>, en haut de la page, ajoute une scène en bas de la liste.</li>
                        <li>Les badges signalent la scène de <em>Départ</em>, les <em>Fins</em>, et en orange ce qui reste à faire : <em>Mise en situation à écrire</em>, <em>Aucun choix</em>, <em>Choix à compléter</em>, <em>Jamais atteinte</em>.</li>
                        <li>Le menu <em>⋮</em> d'une scène permet de la <em>Définir comme départ</em>, de la <em>Monter</em> ou la <em>Descendre</em> dans la liste, de la <em>Retirer de l'histoire</em> (elle reste réutilisable) ou de la <em>Supprimer</em> définitivement.</li>
                        <li>Le dépliant <em>Média et thème</em> ajoute une image à la scène, ou fait changer l'histoire de thème à partir de cette scène. Laisse-le vide dans la plupart des cas.</li>
                    </ul>
                </div>
            </x-filament::section>

            <x-filament::section compact collapsible collapsed persist-collapsed id="guide-redacteur-choix" icon="heroicon-o-arrows-right-left">
                <x-slot name="heading">Choix</x-slot>
                <x-slot name="description">Cadre <em>Choix du joueur</em> d'une scène, un choix par ligne</x-slot>

                <div class="prose prose-sm dark:prose-invert max-w-none">
                    <ul>
                        <li>Écris ce que le joueur peut répondre, puis ce qui se passe ensuite : <em>Bonne fin</em>, <em>Continuer</em> vers une autre scène, ou <em>Fin à risque</em>.</li>
                        <li>Un choix ne crée pas de scène : les scènes se créent uniquement avec <em>Nouvelle scène</em>, en haut de la page. Si la scène suivante n'existe pas encore, laisse <em>Continuer vers</em> vide et enregistre : le choix est marqué <em>Suite à choisir</em>. Crée la scène, puis reviens choisir la destination : un bloc d'alerte, en haut de la page, liste les choix en attente, avec un lien <em>Relier ce choix</em>.</li>
                        <li>Sur une <em>Fin à risque</em>, les contacts d'aide s'affichent automatiquement au joueur, à partir des sous-thèmes de l'histoire.</li>
                    </ul>
                </div>
            </x-filament::section>

            <x-filament::section compact collapsible collapsed persist-collapsed id="guide-redacteur-verifier" icon="heroicon-o-clipboard-document-check">
                <x-slot name="heading">Vérifier et prévisualiser</x-slot>

                <div class="prose prose-sm dark:prose-invert max-w-none">
                    <ul>
                        <li>Dès qu'une histoire est ouverte, l'entrée <em>Vérifier</em> apparaît dans le menu de gauche, sous <em>Histoires</em>. Elle ouvre une fenêtre qui liste ce qui manque encore (départ, scènes jamais atteintes, mises en situation à écrire, choix sans suite, aucune fin…) avec un lien <em>Corriger</em> pour chaque point. Son badge indique combien de points restent à revoir. Rien n'est bloquant.</li>
                        <li>La page <em>3. Prévisualiser</em> rejoue le parcours clic par clic comme un joueur, avant publication.</li>
                    </ul>
                </div>
            </x-filament::section>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
