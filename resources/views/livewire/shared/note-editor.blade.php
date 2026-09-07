<div>
    <form wire:submit="save" class="mb-6 space-y-2">
        <textarea wire:model="body"
                  rows="3"
                  placeholder="Tulis catatan..."
                  class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 @error('body') border-red-500 @enderror"></textarea>
        @error('body')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
        <div class="text-right">
            <button type="submit"
                    class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">
                Tambah Note
            </button>
        </div>
    </form>

    @if ($notes->isEmpty())
        <p class="text-sm text-zinc-500">Belum ada notes.</p>
    @else
        <ul class="space-y-3">
            @foreach ($notes as $note)
                <li class="rounded-md border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="whitespace-pre-line text-sm">{{ $note->body }}</p>
                    <p class="mt-2 text-xs text-zinc-500">
                        {{ $note->user->name }} · {{ $note->created_at->diffForHumans() }}
                    </p>
                </li>
            @endforeach
        </ul>
    @endif
</div>