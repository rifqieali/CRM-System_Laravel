@props([
    'name',
    'title' => null,
])

@php
    $titleId = $name.'-title';
@endphp

<div
    x-data="{ open: false }"
    x-on:keydown.escape.window="open = false"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') open = true"
    x-on:close-modal.window="if ($event.detail === '{{ $name }}') open = false"
>
    <div x-show="open" x-cloak class="fixed inset-0 z-40 bg-zinc-900/50" x-on:click="open = false"></div>

    <div
        x-show="open"
        x-cloak
        x-transition
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $titleId }}"
    >
        <div class="w-full max-w-lg rounded-lg border border-zinc-200 bg-white shadow-xl dark:border-zinc-800 dark:bg-zinc-900" x-on:click.outside="open = false">
            @if ($title)
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
                    <h3 id="{{ $titleId }}" class="text-base font-semibold">{{ $title }}</h3>
                </div>
            @endif

            <div class="px-6 py-4">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="flex justify-end gap-2 border-t border-zinc-200 px-6 py-3 dark:border-zinc-800">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>