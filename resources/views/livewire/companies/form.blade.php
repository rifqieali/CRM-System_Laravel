<div>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">
            {{ $companyId ? 'Edit Company' : 'New Company' }}
        </h1>
        <a href="{{ route('companies.index') }}" wire:navigate class="mt-1 inline-block text-sm text-blue-600 hover:underline">
            ← Kembali ke daftar
        </a>
    </div>

    <form wire:submit="save(false)" class="space-y-6">
        <x-card title="Informasi Dasar">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-input label="Nama" name="name" wire:model="name" required />
                <x-select label="Industry" name="industry" wire:model="industry" placeholder="(Tidak diketahui)">
                    @foreach (['tech','finance','healthcare','retail','manufacturing','other'] as $opt)
                        <option value="{{ $opt }}">{{ ucfirst($opt) }}</option>
                    @endforeach
                </x-select>
                <x-select label="Size" name="size" wire:model="size" placeholder="(Tidak diketahui)">
                    @foreach (['1-10','11-50','51-200','201-500','500+'] as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                    @endforeach
                </x-select>
                <x-input label="Website" name="website" type="url" wire:model="website" />
                <x-input label="Phone" name="phone" wire:model="phone" />
                <x-input label="Address" name="address" wire:model="address" />
                <x-input label="City" name="city" wire:model="city" />
                <x-input label="Country" name="country" wire:model="country" />
            </div>
        </x-card>

        <x-card title="Relasi">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-select label="Owner" name="owner_id" wire:model="owner_id" required>
                    @foreach ($this->ownersList as $owner)
                        <option value="{{ $owner['id'] }}">{{ $owner['name'] }}</option>
                    @endforeach
                </x-select>
            </div>
        </x-card>

        <x-card title="Catatan & Tags">
            <x-textarea label="Notes" name="notes" wire:model="notes" :rows="4" />

            <div class="mt-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Tags</label>
                <div class="mt-2 flex flex-wrap gap-2">
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
            </div>
        </x-card>

        <div class="flex justify-end gap-2">
            <a href="{{ route('companies.index') }}" wire:navigate class="inline-flex items-center rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
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