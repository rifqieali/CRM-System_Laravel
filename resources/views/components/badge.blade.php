@props([
    'color' => 'zinc',
])

@php
    $colors = [
        'zinc' => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-100',
        'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100',
        'emerald' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100',
        'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-100',
        'red' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100',
        'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-100',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '.$colors[$color]]) }}>
    {{ $slot }}
</span>