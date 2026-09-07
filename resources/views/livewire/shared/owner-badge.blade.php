<div class="flex items-center gap-3">
    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-zinc-200 text-xs font-semibold text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200">
        {{ $owner ? strtoupper(substr($owner->name, 0, 1)) : '?' }}
    </div>
    <div>
        <p class="text-sm font-medium">{{ $owner?->name ?? 'Tanpa owner' }}</p>
        @if ($owner)
            <p class="text-xs text-zinc-500">{{ $owner->email }}</p>
        @endif
    </div>
</div>