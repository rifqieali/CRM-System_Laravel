@extends('layouts.app')

@section('title', 'Profil')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Profil Saya</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
            Perbarui informasi akun dan password Anda.
        </p>
    </div>

    <div class="space-y-6">
        <x-card title="Informasi Akun">
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <x-input label="Nama" name="name" type="text" :value="$user->name" required />

                <x-input label="Email" name="email" type="email" :value="$user->email" required />

                @if ($user->isDirty('email') || ! $user->hasVerifiedEmail())
                    <p class="text-sm text-amber-700 dark:text-amber-300">
                        Perubahan email akan mengirim ulang tautan verifikasi.
                    </p>
                @endif

                <div class="flex justify-end">
                    <x-button type="primary">Simpan</x-button>
                </div>
            </form>
        </x-card>

        <x-card title="Ubah Password">
            <form method="POST" action="{{ route('profile.password.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <x-input label="Password Saat Ini" name="current_password" type="password" required />

                <x-input label="Password Baru" name="password" type="password" required />

                <x-input label="Konfirmasi Password Baru" name="password_confirmation" type="password" required />

                <div class="flex justify-end">
                    <x-button type="primary">Update Password</x-button>
                </div>
            </form>
        </x-card>

        <x-card title="Hapus Akun">
            <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-400">
                Setelah akun dihapus, semua data akan dihapus secara permanen.
            </p>

            <form method="POST" action="{{ route('profile.destroy') }}" x-data="{ open: false }">
                @csrf
                @method('DELETE')

                <div x-show="!open">
                    <x-button type="danger" x-on:click.prevent="open = true">Hapus Akun</x-button>
                </div>

                <div x-show="open" x-cloak class="space-y-3">
                    <x-input label="Konfirmasi Password" name="password" type="password" required />

                    <div class="flex gap-2">
                        <x-button type="danger">Konfirmasi Hapus</x-button>
                        <x-button type="secondary" x-on:click.prevent="open = false">Batal</x-button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>
@endsection