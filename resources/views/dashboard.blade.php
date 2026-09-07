@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Selamat datang, {{ $greetingName }}</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
            Berikut ringkasan aktivitas CRM Anda.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-card>
            <p class="text-sm text-zinc-500">Pipeline Value</p>
            <p class="mt-2 text-2xl font-semibold">Rp {{ number_format($pipelineValue, 0, ',', '.') }}</p>
        </x-card>

        <x-card>
            <p class="text-sm text-zinc-500">Deals Won Bulan Ini</p>
            <p class="mt-2 text-2xl font-semibold">{{ $dealsWonThisMonth }}</p>
        </x-card>

        <x-card>
            <p class="text-sm text-zinc-500">Activities Due Today</p>
            <p class="mt-2 text-2xl font-semibold">{{ $activitiesDueToday }}</p>
        </x-card>

        <x-card>
            <p class="text-sm text-zinc-500">Contacts Baru Minggu Ini</p>
            <p class="mt-2 text-2xl font-semibold">{{ $newContactsThisWeek }}</p>
        </x-card>
    </div>

    <div class="mt-6">
        <x-card title="Aktivitas Terbaru">
            @if ($recentActivities->isEmpty())
                <x-empty-state
                    title="Belum ada aktivitas"
                    description="Aktivitas yang Anda catat akan muncul di sini."
                />
            @else
                <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($recentActivities as $activity)
                        <li class="flex items-start gap-3 py-3">
                            <x-badge :color="match($activity->type) {
                                    'call' => 'blue',
                                    'email' => 'amber',
                                    'meeting' => 'emerald',
                                    'task' => 'zinc',
                                    default => 'zinc',
                                }">
                                {{ ucfirst($activity->type) }}
                            </x-badge>
                            <div class="flex-1">
                                <p class="text-sm font-medium">{{ $activity->subject }}</p>
                                <p class="text-xs text-zinc-500">
                                    {{ $activity->user->name }} · {{ $activity->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
@endsection