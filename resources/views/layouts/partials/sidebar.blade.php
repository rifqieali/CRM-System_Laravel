@php
    $isAdmin = auth()->user()?->isAdmin() ?? false;
@endphp

<aside class="hidden w-60 shrink-0 border-r border-zinc-200 bg-white lg:block dark:border-zinc-800 dark:bg-zinc-900">
    <div class="flex h-16 items-center border-b border-zinc-200 px-6 dark:border-zinc-800">
        <a href="{{ route('dashboard') }}" class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
            {{ config('app.name') }}
        </a>
    </div>

    <nav class="flex flex-col gap-1 p-4 text-sm">
        <a href="{{ route('dashboard') }}"
           class="rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('dashboard') ? 'bg-zinc-100 font-medium dark:bg-zinc-800' : '' }}">
            Dashboard
        </a>

        <div class="mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-zinc-500">CRM</div>
        <a href="#" class="rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800">Contacts</a>
        <a href="#" class="rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800">Companies</a>
        <a href="#" class="rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800">Deals</a>
        <a href="#" class="rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800">Activities</a>
        <a href="#" class="rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800">Tags</a>

        @if ($isAdmin)
            <div class="mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-zinc-500">Admin</div>
            <a href="#" class="rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800">Users</a>
            <a href="#" class="rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800">Roles</a>
        @endif
    </nav>
</aside>