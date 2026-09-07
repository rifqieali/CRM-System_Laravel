<header class="sticky top-0 z-10 flex h-16 items-center justify-between border-b border-zinc-200 bg-white px-4 sm:px-6 lg:px-8 dark:border-zinc-800 dark:bg-zinc-900">
    <div class="flex flex-1 items-center">
        <input type="search"
               placeholder="Cari contacts, companies, deals..."
               class="w-full max-w-md rounded-md border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-800"
               disabled>
    </div>

    <div x-data="{ open: false }" class="relative ml-4">
        <button type="button"
                x-on:click="open = !open"
                class="flex items-center gap-2 rounded-md px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
            <span class="font-medium">{{ auth()->user()->name }}</span>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div x-show="open"
             x-on:click.outside="open = false"
             x-transition
             x-cloak
             class="absolute right-0 mt-2 w-48 rounded-md border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-800 dark:bg-zinc-900">
            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">Profil</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="block w-full px-4 py-2 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    Logout
                </button>
            </form>
        </div>
    </div>
</header>