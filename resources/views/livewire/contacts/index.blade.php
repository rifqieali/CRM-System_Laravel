<div>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Contacts</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $contacts->total() }} total</p>
        </div>

        @can('create', App\Models\Contact::class)
            <a href="{{ route('contacts.create') }}"
               wire:navigate
               class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                + New Contact
            </a>
        @endcan
    </div>

    <x-card class="mb-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-zinc-500">Cari</label>
                <input type="search"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Nama atau email..."
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
                <label class="block text-xs font-medium text-zinc-500">Source</label>
                <select wire:model.live="source"
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <option value="">Semua</option>
                    @foreach (['website','referral','cold-call','event','other'] as $opt)
                        <option value="{{ $opt }}">{{ ucfirst($opt) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
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

        @if ($search || $ownerId || $source || $dateFrom || $dateTo)
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
        @if ($contacts->isEmpty())
            <x-empty-state
                title="Belum ada contacts"
                description="Tambah contact pertama untuk mulai mengelola relasi Anda."
            >
                @can('create', App\Models\Contact::class)
                    <a href="{{ route('contacts.create') }}"
                       wire:navigate
                       class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        + New Contact
                    </a>
                @endcan
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <thead class="text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                        <tr>
                            <th class="px-3 py-2">Nama</th>
                            <th class="px-3 py-2">Email</th>
                            <th class="px-3 py-2">Company</th>
                            <th class="px-3 py-2">Owner</th>
                            <th class="px-3 py-2">Source</th>
                            <th class="px-3 py-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($contacts as $contact)
                            <tr wire:key="{{ $contact->id }}">
                                <td class="px-3 py-2">
                                    <a href="{{ route('contacts.show', $contact) }}"
                                       wire:navigate
                                       class="font-medium text-blue-600 hover:underline">
                                        {{ $contact->first_name }} {{ $contact->last_name }}
                                    </a>
                                </td>
                                <td class="px-3 py-2 text-zinc-600 dark:text-zinc-300">{{ $contact->email }}</td>
                                <td class="px-3 py-2 text-zinc-600 dark:text-zinc-300">
                                    {{ $contact->company?->name ?? '—' }}
                                </td>
                                <td class="px-3 py-2 text-zinc-600 dark:text-zinc-300">
                                    {{ $contact->owner?->name ?? '—' }}
                                </td>
                                <td class="px-3 py-2 text-zinc-600 dark:text-zinc-300">
                                    {{ $contact->source ? ucfirst($contact->source) : '—' }}
                                </td>
                                <td class="px-3 py-2 text-right">
                                    @can('update', $contact)
                                        <a href="{{ route('contacts.edit', $contact) }}"
                                           wire:navigate
                                           class="text-blue-600 hover:underline">Edit</a>
                                    @endcan
                                    @can('delete', $contact)
                                        <button type="button"
                                                wire:click="delete({{ $contact->id }})"
                                                wire:confirm="Hapus contact ini?"
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
                {{ $contacts->links() }}
            </div>
        @endif
    </x-card>
</div>