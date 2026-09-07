<div
    x-data="{ open: @entangle('open') }"
    x-on:open-log-activity.window="open = true"
    x-on:keydown.escape.window="open = false"
>
    <div x-show="open" x-cloak class="fixed inset-0 z-40 bg-zinc-900/50" x-on:click="open = false"></div>

    <div
        x-show="open"
        x-cloak
        x-transition
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
    >
        <div class="w-full max-w-md rounded-lg border border-zinc-200 bg-white shadow-xl dark:border-zinc-800 dark:bg-zinc-900" x-on:click.outside="open = false">
            <form wire:submit="save">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
                    <h3 class="text-base font-semibold">Log Activity</h3>
                </div>

                <div class="space-y-4 px-6 py-4">
                    <div>
                        <label class="block text-sm font-medium">Tipe <span class="text-red-500">*</span></label>
                        <select wire:model="type" class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                            @foreach (['call','email','meeting','task'] as $t)
                                <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                            @endforeach
                        </select>
                        @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Subject <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="subject" class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                        @error('subject') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Deskripsi</label>
                        <textarea wire:model="description" rows="3" class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium">Due</label>
                            <input type="datetime-local" wire:model="due_at" class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Selesai</label>
                            <input type="datetime-local" wire:model="completed_at" class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-zinc-200 px-6 py-3 dark:border-zinc-800">
                    <button type="button" wire:click="closeModal" class="inline-flex items-center rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                        Batal
                    </button>
                    <button type="submit" class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>