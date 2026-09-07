@extends('layouts.guest')

@section('title', 'Lupa Password')

@section('content')
    <h1 class="mb-2 text-center text-xl font-semibold">Lupa password?</h1>
    <p class="mb-6 text-center text-sm text-zinc-600 dark:text-zinc-400">
        Masukkan email Anda dan kami akan kirim tautan untuk mengatur ulang password.
    </p>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <x-input label="Email" name="email" type="email" required autofocus />

        <x-button type="primary" class="w-full">Kirim Tautan Reset</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-zinc-600 dark:text-zinc-400">
        <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:underline">Kembali ke login</a>
    </p>
@endsection