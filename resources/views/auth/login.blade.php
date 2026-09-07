@extends('layouts.guest')

@section('title', 'Login')

@section('content')
    <h1 class="mb-6 text-center text-xl font-semibold">Masuk ke akun Anda</h1>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <x-input label="Email" name="email" type="email" required autofocus />

        <x-input label="Password" name="password" type="password" required />

        <div class="flex items-center justify-between text-sm">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="remember" value="1" class="rounded border-zinc-300">
                <span>Ingat saya</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-blue-600 hover:underline">Lupa password?</a>
            @endif
        </div>

        <x-button type="primary" class="w-full">Masuk</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-zinc-600 dark:text-zinc-400">
        Belum punya akun?
        <a href="{{ route('register') }}" class="font-medium text-blue-600 hover:underline">Daftar</a>
    </p>
@endsection