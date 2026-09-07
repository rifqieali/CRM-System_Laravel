@extends('layouts.guest')

@section('title', 'Daftar')

@section('content')
    <h1 class="mb-6 text-center text-xl font-semibold">Buat akun baru</h1>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <x-input label="Nama" name="name" type="text" required autofocus />

        <x-input label="Email" name="email" type="email" required />

        <x-input label="Password" name="password" type="password" required />

        <x-input label="Konfirmasi Password" name="password_confirmation" type="password" required />

        <x-button type="primary" class="w-full">Daftar</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-zinc-600 dark:text-zinc-400">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:underline">Masuk</a>
    </p>
@endsection