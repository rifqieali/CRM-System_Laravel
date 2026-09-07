<div>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Companies</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $companies->total() }} total</p>
        </div>

        @can('create', App\Models\Company::class)
            <a href="{{ route('companies.create') }}"
               wire:navigate
               class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                + New Company
            </a>
        @endcan
    </div>

    <x-card class="mb-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-zinc-500">Cari</label>
                <input type="search"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Nama atau website..."
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
                <label class="block text-xs font-medium text-zinc-500">Industry</label>
                <select wire:model.live="industry"
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <option value="">Semua</option>
                    @foreach (['tech','finance','healthcare','retail','manufacturing','other'] as $opt)
                        <option value="{{ $opt }}">{{ ucfirst($opt) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-zinc-500">Size</label>
                <select wire:model.live="size"
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <option value="">Semua</option>
                    @foreach (['1-10','11-50','51-200','201-500','500+'] as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2 lg:col-span-2 lg:col-start-4">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-zinc-500">Dari</label>
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

        @if ($search || $ownerId || $industry || $size || $dateFrom || $dateTo)
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
        @if ($companies->isEmpty())
            <x-empty-state
                title="Belum ada companies"
                description="Tambah company pertama untuk mulai mengelola akun Anda."
            >
                @can('create', App\Models\Company::class)
                    <a href="{{ route('companies.create') }}"
                       wire:navigate
                       class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        + New Company
                    </a>
                @endcan
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <thead class="text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                        <tr>
                            <th class="px-3 py-2">Nama</th>
                            <th class="px-3 py-2">Industry</th>
                            <th class="px-3 py-2">Size</th>
                            <th class="px-3 py-2">Website</th>
                            <th class="px-3 py-2">Owner</th>
                            <th class="px-3 py-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($companies as $company)
                            <tr wire:key="{{ $company->id }}">
                                <td class="px-3 py-2">
                                    <a href="{{ route('companies.show', $company) }}"
                                       wire:navigate
                                       class="font-medium text-blue-600 hover:underline">
                                        {{ $company->name }}
                                    </a>
                                </td>
                                <td class="px-3 py-2 text-zinc-600 dark:text-zinc-300">
                                    {{ $company->industry ? ucfirst($company->industry) : '—' }}
                                </td>
                                <td class="px-3 py-2 text-zinc-600 dark:text-zinc-300">
                                    {{ $company->size ?? '—' }}
                                </td>
                                <td class="px-3 py-2 text-zinc-600 dark:text-zinc-300">
                                    @if ($company->website)
                                        <a href="{{ $company->website }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">
                                            {{ parse_url($company->website, PHP_URL_HOST) }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-zinc-600 dark:text-zinc-300">
                                    {{ $company->owner?->name ?? '—' }}
                                </td>
                                <td class="px-3 py-2 text-right">
                                    @can('update', $company)
                                        <a href="{{ route('companies.edit', $company) }}"
                                           wire:navigate
                                           class="text-blue-600 hover:underline">Edit</a>
                                    @endcan
                                    @can('delete', $company)
                                        <button type="button"
                                                wire:click="delete({{ $company->id }})"
                                                wire:confirm="Hapus company ini?"
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
                {{ $companies->links() }}
            </div>
        @endif
    </x-card>
</div>