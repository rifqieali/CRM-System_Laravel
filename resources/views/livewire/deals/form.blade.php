<div>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">
            {{ $dealId ? 'Edit Deal' : 'New Deal' }}
        </h1>
        <a href="{{ route('deals.index') }}" wire:navigate class="mt-1 inline-block text-sm text-blue-600 hover:underline">
            ← Kembali ke daftar
        </a>
    </div>

    <form wire:submit="save(false)" class="space-y-6">
        <x-card title="Informasi Dasar">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-input label="Nama" name="name" wire:model="name" required />
                <x-input label="Value" name="value" type="number" step="0.01" min="0" wire:model="value" required />
                <x-input label="Currency" name="currency" wire:model="currency" maxlength="3" required />
                <x-select label="Stage" name="stage" wire:model="stage" required>
                    @foreach (\App\Models\Deal::STAGES as $s)
                        <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                    @endforeach
                </x-select>
                <x-input label="Probability (%)" name="probability" type="number" min="0" max="100" wire:model="probability" />
                <x-input label="Expected Close Date" name="expected_close_date" type="date" wire:model="expected_close_date" />
            </div>
        </x-card>

        <x-card title="Relasi">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-select label="Contact" name="contact_id" wire:model.live="contact_id" required>
                    <option value="">— Pilih contact —</option>
                    @foreach ($this->contactsList as $c)
                        <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                    @endforeach
                </x-select>
                <x-select label="Company" name="company_id" wire:model="company_id" required>
                    <option value="">— Pilih company —</option>
                    @foreach ($this->companiesList as $c)
                        <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                    @endforeach
                </x-select>
                <x-select label="Owner" name="owner_id" wire:model="owner_id" required>
                    @foreach ($this->ownersList as $owner)
                        <option value="{{ $owner['id'] }}">{{ $owner['name'] }}</option>
                    @endforeach
                </x-select>
            </div>
        </x-card>

        <x-card title="Tags">
            <div class="flex flex-wrap gap-2">
                @foreach ($this->availableTags as $tag)
                    <label class="inline-flex cursor-pointer items-center gap-1 rounded-full border border-zinc-300 bg-white px-3 py-1 text-xs hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                        <input type="checkbox"
                               wire:click="toggleTag({{ $tag['id'] }})"
                               @checked(in_array($tag['id'], $tag_ids ?? []))
                               class="rounded border-zinc-300">
                        <span>{{ $tag['name'] }}</span>
                    </label>
                @endforeach
            </div>

            <div class="mt-3 flex gap-2">
                <input type="text"
                       wire:model="newTagName"
                       placeholder="Tag baru..."
                       class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                <button type="button"
                        wire:click="createTag"
                        class="inline-flex items-center rounded-md bg-zinc-100 px-3 py-2 text-sm font-medium hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700">
                    Buat
                </button>
            </div>
        </x-card>

        <div class="flex justify-end gap-2">
            <a href="{{ route('deals.index') }}" wire:navigate class="inline-flex items-center rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                Batal
            </a>
            <button type="button"
                    wire:click="save(true)"
                    class="inline-flex items-center rounded-md border border-blue-600 bg-white px-4 py-2 text-sm font-medium text-blue-600 hover:bg-blue-50">
                Save & Add Another
            </button>
            <button type="submit"
                    class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                Save
            </button>
        </div>
    </form>
</div>