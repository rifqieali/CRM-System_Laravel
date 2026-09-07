@props([
    'title' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900']) }}>
    @if ($title || isset($header))
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
            {{ $header ?? '<h3 class="text-base font-semibold">'.$title.'</h3>' }}
        </div>
    @endif

    <div class="px-6 py-4">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t border-zinc-200 px-6 py-3 dark:border-zinc-800">
            {{ $footer }}
        </div>
    @endisset
</div>