<div>
    <x-slot:header>
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Activities</h1>
        </div>
    </x-slot:header>

    <div class="space-y-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3 lg:grid-cols-6">
                <div class="lg:col-span-2">
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Cari</label>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Subjek / deskripsi..."
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                    />
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Tipe</label>
                    <select
                        wire:model.live="type"
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                    >
                        <option value="">Semua</option>
                        @foreach ($types as $t)
                            <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
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
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Jatuh Tempo</label>
                    <select
                        wire:model.live="dueFilter"
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                    >
                        <option value="">Semua</option>
                        <option value="overdue">Terlambat</option>
                        <option value="today">Hari ini</option>
                        <option value="week">7 hari ke depan</option>
                        <option value="completed">Selesai</option>
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
                        <th class="px-4 py-3">Tipe</th>
                        <th class="px-4 py-3">Subjek</th>
                        <th class="px-4 py-3">Entitas</th>
                        <th class="px-4 py-3">Jatuh Tempo</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Pelaku</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($activities as $activity)
                        <tr wire:key="activity-{{ $activity->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3">
                                <x-badge :color="match ($activity->type) {
                                    'call' => 'blue',
                                    'email' => 'amber',
                                    'meeting' => 'purple',
                                    'task' => 'zinc',
                                    default => 'zinc',
                                }">
                                    {{ ucfirst($activity->type) }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $activity->subject }}</div>
                                @if ($activity->description)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ \Illuminate\Support\Str::limit($activity->description, 80) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                @php
                                    $parent = $activity->activityable;
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
                                @if ($activity->due_at)
                                    {{ $activity->due_at->format('d M Y') }}
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($activity->completed_at)
                                    <x-badge color="emerald">Selesai</x-badge>
                                @elseif ($activity->due_at && $activity->due_at->isPast())
                                    <x-badge color="red">Terlambat</x-badge>
                                @else
                                    <x-badge color="zinc">Aktif</x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                {{ $activity->user?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if (! $activity->completed_at && $activity->due_at)
                                        @can('update', $activity)
                                            <button
                                                type="button"
                                                wire:click="markComplete({{ $activity->id }})"
                                                wire:confirm="Tandai activity ini selesai?"
                                                class="rounded-md border border-zinc-300 bg-white px-2 py-1 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                                            >
                                                Selesai
                                            </button>
                                        @endcan
                                    @endif
                                    @can('delete', $activity)
                                        <button
                                            type="button"
                                            wire:click="delete({{ $activity->id }})"
                                            wire:confirm="Hapus activity ini? Tindakan tidak bisa dibatalkan."
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
                            <td colspan="7" class="px-4 py-12 text-center">
                                <x-empty-state
                                    title="Belum ada activity"
                                    description="Activity akan muncul di sini setelah Anda mencatatkan aktivitas dari halaman Contact, Company, atau Deal."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $activities->links() }}
        </div>
    </div>
</div>
