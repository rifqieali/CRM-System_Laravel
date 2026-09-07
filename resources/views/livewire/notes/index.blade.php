<div>
    <x-slot:header>
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Notes</h1>
        </div>
    </x-slot:header>

    <div class="space-y-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Cari</label>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Isi note..."
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                    />
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Entitas</label>
                    <select
                        wire:model.live="entityType"
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                    >
                        <option value="">Semua</option>
                        @foreach ($entityTypes as $class => $label)
                            <option value="{{ $class }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Pelaku</label>
                    <select
                        wire:model.live="ownerId"
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                    >
                        <option value="">Semua</option>
                        @foreach ($this->ownersList as $o)
                            <option value="{{ $o->id }}">{{ $o->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Dari</label>
                    <input
                        type="date"
                        wire:model.live="dateFrom"
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                    />
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Sampai</label>
                    <input
                        type="date"
                        wire:model.live="dateTo"
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                    />
                </div>

                <div class="flex items-end lg:col-span-2">
                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                    >
                        Reset filter
                    </button>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <tr>
                        <th class="px-4 py-3">Isi</th>
                        <th class="px-4 py-3">Entitas</th>
                        <th class="px-4 py-3">Pelaku</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($notes as $note)
                        <tr wire:key="note-{{ $note->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3">
                                <div class="text-zinc-900 dark:text-zinc-100">
                                    {{ \Illuminate\Support\Str::limit($note->body, 140) }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                @php
                                    $parent = $note->noteable;
                                @endphp
                                @if ($parent)
                                    @if ($parent instanceof \App\Models\Contact)
                                        <a href="{{ route('contacts.show', $parent) }}" wire:navigate class="text-blue-600 hover:underline dark:text-blue-400">
                                            {{ $parent->first_name }} {{ $parent->last_name }}
                                        </a>
                                    @elseif ($parent instanceof \App\Models\Company)
                                        <a href="{{ route('companies.show', $parent) }}" wire:navigate class="text-blue-600 hover:underline dark:text-blue-400">
                                            {{ $parent->name }}
                                        </a>
                                    @elseif ($parent instanceof \App\Models\Deal)
                                        <a href="{{ route('deals.show', $parent) }}" wire:navigate class="text-blue-600 hover:underline dark:text-blue-400">
                                            {{ $parent->name }}
                                        </a>
                                    @endif
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                {{ $note->user?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                {{ $note->created_at->format('d M Y H:i') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                @can('delete', $note)
                                    <button
                                        type="button"
                                        wire:click="delete({{ $note->id }})"
                                        wire:confirm="Hapus note ini? Tindakan tidak bisa dibatalkan."
                                        class="rounded-md border border-red-300 bg-white px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-50 dark:border-red-700 dark:bg-zinc-800 dark:text-red-400 dark:hover:bg-red-900/20"
                                    >
                                        Hapus
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center">
                                <x-empty-state
                                    title="Belum ada note"
                                    description="Note akan muncul di sini setelah Anda menambahkannya dari halaman Contact, Company, atau Deal."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $notes->links() }}
        </div>
    </div>
</div>
