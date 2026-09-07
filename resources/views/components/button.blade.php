@props([
    'type' => 'primary',
    'size' => 'md',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 font-medium rounded-md transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';
    $types = [
        'primary' => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
        'secondary' => 'bg-zinc-100 text-zinc-900 hover:bg-zinc-200 focus:ring-zinc-400 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
        'ghost' => 'text-zinc-700 hover:bg-zinc-100 focus:ring-zinc-400 dark:text-zinc-300 dark:hover:bg-zinc-800',
    ];
    $sizes = [
        'sm' => 'px-2.5 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-2.5 text-base',
    ];
    $cls = $base.' '.$types[$type].' '.$sizes[$size];
@endphp

@if (isset($href))
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $cls]) }}>{{ $slot }}</a>
@elseif (isset($typeAttr) && $typeAttr === 'submit')
    <button type="submit" {{ $attributes->merge(['class' => $cls]) }}>{{ $slot }}</button>
@elseif (isset($typeAttr) && $typeAttr === 'button')
    <button type="button" {{ $attributes->merge(['class' => $cls]) }}>{{ $slot }}</button>
@else
    <button {{ $attributes->merge(['class' => $cls, 'type' => 'submit']) }}>{{ $slot }}</button>
@endif