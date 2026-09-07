@extends('layouts.guest')

@section('title', 'Verifikasi Email')

@section('content')
    <h1 class="mb-2 text-center text-xl font-semibold">Verifikasi alamat email Anda</h1>
    <p class="mb-6 text-center text-sm text-zinc-600 dark:text-zinc-400">
        Sebelum melanjutkan, silakan cek email untuk tautan verifikasi.
        Jika belum menerima, kami bisa mengirim ulang.
    </p>

    @if (session('status'))
        <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <x-button type="primary" class="w-full">Kirim Ulang Email</x-button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <x-button type="ghost" class="w-full">Logout</x-button>
    </form>
@endsection