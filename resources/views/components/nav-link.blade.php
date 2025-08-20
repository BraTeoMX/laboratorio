{{-- resources/views/components/nav-link.blade.php --}}

@props([
'href' => '#',
'icon' => '',
'active' => false
])

{{-- Quitamos :icon de aquí y lo manejaremos dentro --}}
<flux:navlist.item :href="$href" :current="$active" wire:navigate {{ $attributes->class([
    'text-gray-900 dark:text-white font-bold' => $active, // AHORA: Máximo contraste y peso para el activo
    'text-gray-600 dark:text-gray-400' => !$active, // AHORA: Un gris estándar y muy legible para el inactivo
    ]) }}
    >
    <span class="flex items-center gap-x-3">
        {{-- Aquí renderizamos el ícono con sus propias clases de color --}}
        @if ($icon)
        <x-dynamic-component :component="'icon.' . $icon" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
        @endif

        {{-- El texto del enlace --}}
        <span>{{ $slot }}</span>
    </span>
</flux:navlist.item>