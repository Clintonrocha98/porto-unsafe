<x-filament-panels::page>
    <x-filament::tabs label="Tipos de Despesa">
        @foreach(\App\Enums\ExpenseType::cases() as $type)
            <x-filament::tabs.item
                :alpine-active="'$wire.activeType === \'' . $type->value . '\''"
                wire:click="$set('activeType', '{{ $type->value }}')"
            >
                {{ $type->getLabel() }}
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>

    {{ $this->content }}
</x-filament-panels::page>
