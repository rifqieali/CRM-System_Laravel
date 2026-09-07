<div>
    <div class="flex flex-wrap gap-2">
        @forelse ($tags as $tag)
            <button type="button"
                    wire:click="toggle({{ $tag->id }})"
                    @class([
                        'inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs',
                        'border-blue-500 bg-blue-50 text-blue-700' => in_array($tag->id, $selected, true),
                        'border-zinc-300 bg-white hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800' => ! in_array($tag->id, $selected, true),
                    ])>
                {{ $tag->name }}
                @if (in_array($tag->id, $selected, true))
                    <span aria-hidden="true">×</span>
                @endif
            </button>
        @empty
            <p class="text-sm text-zinc-500">Belum ada tag.</p>
        @endforelse
    </div>

    <div class="mt-3">
        <details class="text-xs">
            <summary class="cursor-pointer text-blue-600">+ Buat tag baru</summary>
            <form wire:submit="createAndAttach" class="mt-2 flex gap-2">
                <input type="text"
                       wire:model="newTagName"
                       placeholder="Nama tag"
                       class="block w-full rounded-md border border-zinc-300 bg-white px-2 py-1 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                <button type="submit"
                        class="inline-flex items-center rounded-md bg-zinc-100 px-3 py-1 text-sm font-medium hover:bg-zinc-200 dark:bg-zinc-800">
                    Buat
                </button>
            </form>
            @error('newTagName')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </details>
    </div>
</div>