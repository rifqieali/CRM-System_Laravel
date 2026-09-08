<div class="relative w-full max-w-md"
     x-data="{ open: @entangle('open').defer }"
     x-on:click.outside="open = false"
     x-on:keydown.escape.window="open = false">
    <div class="relative">
        <input type="search"
               wire:model.live.debounce.300ms="query"
               placeholder="Cari contacts, companies, deals..."
               class="w-full rounded-md border border-zinc-300 bg-zinc-50 px-3 py-2 pr-9 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-800"
               autocomplete="off">
        @if ($query !== '')
            <button type="button"
                    wire:click="clear"
                    class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
                    aria-label="Bersihkan pencarian">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        @endif
    </div>

    @if ($open)
        <div class="absolute left-0 right-0 z-20 mt-2 max-h-96 overflow-auto rounded-md border border-zinc-200 bg-white shadow-lg dark:border-zinc-800 dark:bg-zinc-900"
             x-transition.opacity>
            @if ($total === 0)
                <p class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                    Tidak ada hasil untuk &ldquo;{{ $query }}&rdquo;.
                </p>
            @else
                @if ($results['contacts']->isNotEmpty())
                    <div class="border-b border-zinc-100 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                        Contacts
                    </div>
                    @foreach ($results['contacts'] as $contact)
                        <a href="{{ route('contacts.show', $contact) }}"
                           wire:click="close"
                           class="block px-4 py-2 text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <span class="font-medium">{{ $contact->first_name }} {{ $contact->last_name }}</span>
                            <span class="ml-2 text-xs text-zinc-500 dark:text-zinc-400">{{ $contact->email }}</span>
                        </a>
                    @endforeach
                @endif

                @if ($results['companies']->isNotEmpty())
                    <div class="border-b border-t border-zinc-100 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                        Companies
                    </div>
                    @foreach ($results['companies'] as $company)
                        <a href="{{ route('companies.show', $company) }}"
                           wire:click="close"
                           class="block px-4 py-2 text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <span class="font-medium">{{ $company->name }}</span>
                            @if ($company->industry)
                                <span class="ml-2 text-xs text-zinc-500 dark:text-zinc-400">{{ $company->industry }}</span>
                            @endif
                        </a>
                    @endforeach
                @endif

                @if ($results['deals']->isNotEmpty())
                    <div class="border-b border-t border-zinc-100 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                        Deals
                    </div>
                    @foreach ($results['deals'] as $deal)
                        <a href="{{ route('deals.show', $deal) }}"
                           wire:click="close"
                           class="block px-4 py-2 text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <span class="font-medium">{{ $deal->name }}</span>
                            <span class="ml-2 text-xs text-zinc-500 dark:text-zinc-400">{{ ucfirst($deal->stage) }}</span>
                        </a>
                    @endforeach
                @endif
            @endif
        </div>
    @endif
</div>
