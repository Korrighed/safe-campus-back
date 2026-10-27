{{-- Footer du panel admin, affiche sur toutes les pages (connecte ou non) via
     PanelsRenderHook::FOOTER. Structure symetrique au footer du site public
     (SiteFooter.vue, Safe-Campus-front) : copyright + liens legaux. --}}
<div class="fi-panel-footer px-6 py-4 text-center">
    <p class="text-xs text-gray-400">© 2025 Université de Nouvelle-Calédonie</p>
    <nav class="flex justify-center gap-4 mt-1">
        <a href="/mentions-legales" class="text-xs text-gray-400 hover:text-gray-600 underline">
            Mentions légales
        </a>
        <a href="/cgu" class="text-xs text-gray-400 hover:text-gray-600 underline">
            CGU
        </a>
    </nav>
</div>
