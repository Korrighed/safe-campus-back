{{-- Lien « Vérifier » du menu principal -> modale de la page (voir OuvreVerification). --}}
<script>
    document.addEventListener('click', (event) => {
        const lien = event.target.closest('a[href$="#verifier"]');

        if (! lien) {
            return;
        }

        event.preventDefault();
        Livewire.dispatch('ouvrir-verification');
    });
</script>
