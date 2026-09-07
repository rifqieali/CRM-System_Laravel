<div>
    @if ($activities->isEmpty())
        <p class="text-sm text-zinc-500">Belum ada aktivitas.</p>
    @else
        <ul class="space-y-3">
            @foreach ($activities as $activity)
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
</div>