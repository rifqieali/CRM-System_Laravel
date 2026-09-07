<div>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('deals.index') }}" wire:navigate class="text-sm text-blue-600 hover:underline">← Kembali ke daftar</a>
            <h1 class="mt-1 text-2xl font-semibold">{{ $deal->name }}</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                {{ $deal->currency }} {{ number_format((float) $deal->value, 0, ',', '.') }}
                @if ($deal->expected_close_date)
                    · Close: {{ $deal->expected_close_date->format('d M Y') }}
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
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
            <x-badge :color="$stageColor">{{ ucfirst($deal->stage) }}</x-badge>

            <livewire:shared.log-activity-modal :morph-type="\App\Models\Deal::class" :morph-id="$deal->id" :key="'log-act-'.$deal->id" />
            <button type="button"
                    wire:click="$dispatch('open-log-activity')"
                    class="inline-flex items-center rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                Log Activity
            </button>

            @can('update', $deal)
                <a href="{{ route('deals.edit', $deal) }}"
                   wire:navigate
                   class="inline-flex items-center rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                    Edit
                </a>
            @endcan

            @can('delete', $deal)
                <button type="button"
                        wire:click="delete"
                        wire:confirm="Hapus deal ini?"
                        class="inline-flex items-center rounded-md border border-red-300 bg-white px-3 py-2 text-sm text-red-700 hover:bg-red-50 dark:border-red-900 dark:bg-zinc-800">
                    Hapus
                </button>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <div class="border-b border-zinc-200 dark:border-zinc-800">
                <nav class="-mb-px flex gap-4" aria-label="Tabs">
                    @foreach (['overview' => 'Overview', 'activities' => 'Aktivitas', 'notes' => 'Notes'] as $key => $label)
                        <button type="button"
                                wire:click="setTab('{{ $key }}')"
                                @class([
                                    'border-b-2 px-3 py-2 text-sm font-medium',
                                    'border-blue-500 text-blue-700' => $activeTab === $key,
                                    'border-transparent text-zinc-500 hover:text-zinc-700' => $activeTab !== $key,
                                ])>
                            {{ $label }}
                        </button>
                    @endforeach
                </nav>
            </div>

            @if ($activeTab === 'overview')
                <x-card title="Detail">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500">Company</dt>
                            <dd>
                                @if ($deal->company)
                                    <a href="{{ route('companies.show', $deal->company) }}" wire:navigate class="text-blue-600 hover:underline">
                                        {{ $deal->company->name }}
                                    </a>
                                @else — @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500">Contact</dt>
                            <dd>
                                @if ($deal->contact)
                                    <a href="{{ route('contacts.show', $deal->contact) }}" wire:navigate class="text-blue-600 hover:underline">
                                        {{ trim($deal->contact->first_name.' '.$deal->contact->last_name) }}
                                    </a>
                                @else — @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500">Value</dt>
                            <dd>{{ $deal->currency }} {{ number_format((float) $deal->value, 0, ',', '.') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500">Stage</dt>
                            <dd>
                                <x-badge :color="$stageColor">{{ ucfirst($deal->stage) }}</x-badge>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500">Probability</dt>
                            <dd>{{ $deal->probability !== null ? $deal->probability.'%' : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500">Expected Close</dt>
                            <dd>{{ $deal->expected_close_date?->format('d M Y') ?? '—' }}</dd>
                        </div>
                        @if ($deal->closed_at)
                            <div class="sm:col-span-2">
                                <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500">Closed at</dt>
                                <dd>{{ $deal->closed_at->format('d M Y H:i') }}</dd>
                            </div>
                        @endif
                    </dl>
                </x-card>
            @elseif ($activeTab === 'activities')
                <x-card>
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-base font-semibold">Aktivitas</h3>
                        <select wire:model.live="activityTypeFilter" class="rounded-md border border-zinc-300 bg-white px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-800">
                            <option value="">Semua tipe</option>
                            @foreach (['call','email','meeting','task'] as $t)
                                <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($this->activities()->isEmpty())
                        <x-empty-state title="Belum ada aktivitas" description="Catat aktivitas pertama via tombol Log Activity." />
                    @else
                        <ul class="space-y-3">
                            @foreach ($this->activities() as $activity)
                                <li class="flex gap-3 border-b border-zinc-100 pb-3 last:border-b-0 dark:border-zinc-800">
                                    <x-badge :color="match($activity->type) { 'call' => 'blue', 'email' => 'amber', 'meeting' => 'emerald', 'task' => 'zinc', default => 'zinc' }">
                                        {{ ucfirst($activity->type) }}
                                    </x-badge>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium">{{ $activity->subject }}</p>
                                        @if ($activity->description)
                                            <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">{{ $activity->description }}</p>
                                        @endif
                                        <p class="mt-1 text-xs text-zinc-500">
                                            {{ $activity->user->name }} · {{ $activity->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>
            @elseif ($activeTab === 'notes')
                <livewire:shared.note-editor :morph-type="\App\Models\Deal::class" :morph-id="$deal->id" :key="'note-'.$deal->id" />
            @endif
        </div>

        <aside class="space-y-6">
            <x-card title="Owner">
                <livewire:shared.owner-badge :user-id="$deal->owner_id" :key="'owner-'.$deal->id" />
            </x-card>

            <x-card title="Tags">
                @can('update', $deal)
                    <livewire:shared.tag-selector :morph-type="\App\Models\Deal::class" :morph-id="$deal->id" :selected="$deal->tags->pluck('id')->all()" :key="'tags-'.$deal->id" />
                @else
                    <div class="flex flex-wrap gap-2">
                        @forelse ($deal->tags as $tag)
                            <x-badge color="zinc">{{ $tag->name }}</x-badge>
                        @empty
                            <p class="text-sm text-zinc-500">Tidak ada tags.</p>
                        @endforelse
                    </div>
                @endcan
            </x-card>

            <x-card title="Timestamps">
                <dl class="space-y-1 text-xs">
                    <div>
                        <dt class="inline font-medium text-zinc-500">Created:</dt>
                        <dd class="inline">{{ $deal->created_at->format('d M Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="inline font-medium text-zinc-500">Updated:</dt>
                        <dd class="inline">{{ $deal->updated_at->format('d M Y H:i') }}</dd>
                    </div>
                </dl>
            </x-card>
        </aside>
    </div>
</div>