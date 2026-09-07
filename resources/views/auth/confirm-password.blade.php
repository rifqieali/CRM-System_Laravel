@extends('layouts.guest')

@section('title', 'Konfirmasi Password')

@section('content')
    <h1 class="mb-2 text-center text-xl font-semibold">Konfirmasi password</h1>
    <p class="mb-6 text-center text-sm text-zinc-600 dark:text-zinc-400">
        Ini area sensitif. Mohon konfirmasi password Anda untuk melanjutkan.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <x-input label="Password" name="password" type="password" required autofocus />

        <x-button type="primary" class="w-full">Konfirmasi</x-button>
    </form>
@endsection