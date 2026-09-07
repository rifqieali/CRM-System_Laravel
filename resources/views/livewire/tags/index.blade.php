<div>
    <x-slot:header>
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Tags</h1>
            @can('create', \App\Models\Tag::class)
                <button
                    type="button"
                    wire:click="openCreate"
                    class="rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    + Tag baru
                </button>
            @endcan
        </div>
    </x-slot:header>

    <div class="space-y-4">
        @if (session('status'))
            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        @if ($showForm)
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ $editingId ? 'Edit tag' : 'Tag baru' }}
                </h2>
                <form wire:submit="save" class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Nama</label>
                        <input
                            type="text"
                            wire:model="name"
                            class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                        />
                        @error('name')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Warna (hex, opsional)</label>
                        <input
                            type="text"
                            wire:model="color"
                            placeholder="#RRGGBB"
                            class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                        />
                        @error('color')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-3 flex items-center gap-2">
                        <button
                            type="submit"
                            class="rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700"
                        >
                            {{ $editingId ? 'Simpan perubahan' : 'Buat tag' }}
                        </button>
                        <button
                            type="button"
                            wire:click="cancelForm"
                            class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                        >
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Cari</label>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Nama / slug..."
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                    />
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <tr>
                        <th class="px-4 py-3">
                            <button type="button" wire:click="sortBy('name')" class="inline-flex items-center gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                                Nama
                                @if ($sort === 'name')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3">
                            <button type="button" wire:click="sortBy('slug')" class="inline-flex items-center gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                                Slug
                                @if ($sort === 'slug')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3">Warna</th>
                        <th class="px-4 py-3">Penggunaan</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($tags as $tag)
                        <tr wire:key="tag-{{ $tag->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $tag->name }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $tag->slug }}</td>
                            <td class="px-4 py-3">
                                @if ($tag->color)
                                    <span class="inline-flex items-center gap-2">
                                        <span class="inline-block h-4 w-4 rounded border border-zinc-300 dark:border-zinc-700" style="background: {{ $tag->color }}"></span>
                                        <span class="text-xs text-zinc-600 dark:text-zinc-400">{{ $tag->color }}</span>
                                    </span>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                {{ $this->usageCounts[$tag->id] ?? 0 }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @can('update', $tag)
                                        <button
                                            type="button"
                                            wire:click="openEdit({{ $tag->id }})"
                                            class="rounded-md border border-zinc-300 bg-white px-2 py-1 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                                        >
                                            Edit
                                        </button>
                                    @endcan
                                    @can('delete', $tag)
                                        <button
                                            type="button"
                                            wire:click="delete({{ $tag->id }})"
                                            wire:confirm="Hapus tag \"{{ $tag->name }}\"? Tindakan tidak bisa dibatalkan."
                                            class="rounded-md border border-red-300 bg-white px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-50 dark:border-red-700 dark:bg-zinc-800 dark:text-red-400 dark:hover:bg-red-900/20"
                                        >
                                            Hapus
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center">
                                <x-empty-state
                                    title="Belum ada tag"
                                    description="Tag akan muncul di sini setelah Anda membuatnya. Tag bisa dipasang ke Contact, Company, atau Deal."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $tags->links() }}
        </div>
    </div>
</div>
