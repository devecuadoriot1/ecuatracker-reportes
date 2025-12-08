<x-filament-panels::page>
    <form wire:submit="submit" class="space-y-6">
        {{ $this->form }}

        <div>
            <x-filament::button
                type="submit"
                wire:target="submit"
                wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="submit">
                    Generar reporte
                </span>

                <span wire:loading wire:target="submit">
                    Generando...
                </span>
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>