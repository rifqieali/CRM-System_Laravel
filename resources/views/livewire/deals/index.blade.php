<div>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Deals</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $deals->total() }} total</p>
        </div>

        @can('create', App\Models\Deal::class)
            <a href="{{ route('deals.create') }}"
               wire:navigate
               class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                + New Deal
            </a>
        @endcan
    </div>

    <x-card class="mb-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="block text-xs font-medium text-zinc-500">Cari</label>
                <input type="search"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Nama deal..."
                       class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
            </div>

            <div>
                <label class="block text-xs font-medium text-zinc-500">Owner</label>
                <select wire:model.live="ownerId"
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <option value="">Semua</option>
                    @foreach ($this->owners as $owner)
                        <option value="{{ $owner['id'] }}">{{ $owner['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-zinc-500">Stage</label>
                <select wire:model.live="stage"
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <option value="">Semua</option>
                    @foreach (\App\Models\Deal::STAGES as $s)
                        <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-zinc-500">Company</label>
                <select wire:model.live="companyId"
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <option value="">Semua</option>
                    @foreach (\App\Models\Company::orderBy('name')->get(['id','name']) as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2 sm:col-span-2 lg:col-span-2">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-zinc-500">Close dari</label>
                    <input type="date"
                           wire:model.live="dateFrom"
                           class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-medium text-zinc-500">Sampai</label>
                    <input type="date"
                           wire:model.live="dateTo"
                           class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                </div>
            </div>
        </div>

        @if ($search || $ownerId || $stage || $companyId || $dateFrom || $dateTo)
            <div class="mt-3 text-right">
                <button type="button"
                        wire:click="resetFilters"
                        class="text-xs text-blue-600 hover:underline">
                    Reset filter
                </button>
            </div>
        @endif
    </x-card>

    <x-card>
        @if ($deals->isEmpty())
            <x-empty-state
                title="Belum ada deals"
                description="Tambah deal pertama untuk mulai tracking pipeline."
            >
                @can('create', App\Models\Deal::class)
                    <a href="{{ route('deals.create') }}"
                       wire:navigate
                       class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        + New Deal
                    </a>
                @endcan
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <thead class="text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                        <tr>
                            <th class="px-3 py-2">Nama</th>
                            <th class="px-3 py-2">Company</th>
                            <th class="px-3 py-2">Contact</th>
                            <th class="px-3 py-2">Value</th>
                            <th class="px-3 py-2">Stage</th>
                            <th class="px-3 py-2">Close Date</th>
                            <th class="px-3 py-2">Owner</th>
                            <th class="px-3 py-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($deals as $deal)
                            @php
                                $stageColor = match ($deal->stage) {
                                    'prospecting' => 'zinc',
                                    'qualification' => 'blue',
                                    'proposal' => 'amber',
                                    'negotiation' => 'purple',
                                    'won' => 'emerald',
                                    'lost' => 'red',
                                    default => 'zinc',
                                };
                            @endphp
                            <tr wire:key="{{ $deal->id }}">
                                <td class="px-3 py-2">
                                    <a href="{{ route('deals.show', $deal) }}"
                                       wire:navigate
                                       class="font-medium text-blue-600 hover:underline">
                                        {{ $deal->name }}
                                    </a>
                                </td>
                                <td class="px-3 py-2 text-zinc-600 dark:text-zinc-300">
                                    {{ $deal->company?->name ?? '—' }}
                                </td>
                                <td class="px-3 py-2 text-zinc-600 dark:text-zinc-300">
                                    {{ $deal->contact ? trim($deal->contact->first_name.' '.$deal->contact->last_name) : '—' }}
                                </td>
                                <td class="px-3 py-2 text-zinc-600 dark:text-zinc-300">
                                    {{ $deal->currency }} {{ number_format((float) $deal->value, 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-2">
                                    <x-badge :color="$stageColor">{{ ucfirst($deal->stage) }}</x-badge>
                                </td>
                                <td class="px-3 py-2 text-zinc-600 dark:text-zinc-300">
                                    {{ $deal->expected_close_date?->format('d M Y') ?? '—' }}
                                </td>
                                <td class="px-3 py-2 text-zinc-600 dark:text-zinc-300">
                                    {{ $deal->owner?->name ?? '—' }}
                                </td>
                                <td class="px-3 py-2 text-right">
                                    @can('update', $deal)
                                        <a href="{{ route('deals.edit', $deal) }}"
                                           wire:navigate
                                           class="text-blue-600 hover:underline">Edit</a>
                                    @endcan
                                    @can('delete', $deal)
                                        <button type="button"
                                                wire:click="delete({{ $deal->id }})"
                                                wire:confirm="Hapus deal ini?"
                                                class="ml-3 text-red-600 hover:underline">
                                            Hapus
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $deals->links() }}
            </div>
        @endif
    </x-card>
</div>