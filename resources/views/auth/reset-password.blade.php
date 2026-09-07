@extends('layouts.guest')

@section('title', 'Reset Password')

@section('content')
    <h1 class="mb-6 text-center text-xl font-semibold">Atur ulang password</h1>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-input label="Email" name="email" type="email" required autofocus />

        <x-input label="Password Baru" name="password" type="password" required />

        <x-input label="Konfirmasi Password" name="password_confirmation" type="password" required />

        <x-button type="primary" class="w-full">Reset Password</x-button>
    </form>
@endsection