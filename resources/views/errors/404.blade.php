@extends('layouts.app')

@section('content')
    <div class="flex min-h-[60vh] flex-col items-center justify-center px-4 text-center">
        <p class="font-mono text-sm font-medium text-zinc-500 dark:text-zinc-400">404</p>
        <h1 class="mt-3 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Halaman tidak ditemukan</h1>
        <p class="mt-2 max-w-md text-sm text-zinc-600 dark:text-zinc-400">
            Halaman yang Anda cari tidak ada atau telah dipindahkan.
        </p>
        <a href="{{ route('dashboard') }}"
           class="mt-6 inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Kembali ke Dashboard
        </a>
    </div>
@endsection
