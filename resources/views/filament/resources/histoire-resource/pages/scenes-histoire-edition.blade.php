<form wire:submit="enregistrer" class="flex flex-col gap-y-4">
    {{ $this->form }}

    <div class="flex flex-wrap justify-end gap-2">
        <x-filament::button color="gray" wire:click="annuler">
            Annuler
        </x-filament::button>

        <x-filament::button type="submit">
            Enregistrer la scène
        </x-filament::button>
    </div>
</form>
