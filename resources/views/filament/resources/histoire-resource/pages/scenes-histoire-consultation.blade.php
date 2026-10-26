@php
    $scene = $section['scene'];
@endphp

@if (filled($scene->dialogue_text))
    <p class="text-sm text-gray-700 dark:text-gray-200">« {{ \Illuminate\Support\Str::limit($scene->dialogue_text, 220) }} »</p>
@else
    <p class="sc-couleur text-sm italic" style="--sc-clair: var(--warning-700); --sc-sombre: var(--warning-400)">Mise en situation à écrire.</p>
@endif

<div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200">Choix du joueur</div>

    <ul>
        @forelse ($section['lignes'] as $ligne)
            <li class="flex flex-wrap gap-x-4 gap-y-1 border-t border-gray-200 px-4 py-2 text-sm dark:border-white/10">
                <span class="min-w-0 flex-1 text-gray-950 dark:text-white">{{ $ligne['texte'] }}</span>
                <span class="sc-couleur min-w-0 flex-1 font-medium" style="--sc-clair: var(--{{ $ligne['couleur'] }}-700); --sc-sombre: var(--{{ $ligne['couleur'] }}-400)">→ {{ $ligne['suite'] }}</span>
            </li>
        @empty
            <li class="border-t border-gray-200 px-4 py-2 text-sm text-gray-600 dark:border-white/10 dark:text-gray-400">Aucun choix pour l'instant.</li>
        @endforelse
    </ul>
</div>
