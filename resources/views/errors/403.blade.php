@extends('layouts.app')
@section('title', 'Akses ditolak')

@section('content')
<div class="mx-auto flex max-w-lg flex-col items-center px-4 py-24 text-center">
    <span class="grid h-14 w-14 place-items-center rounded-2xl bg-amber-100 text-amber-700" aria-hidden="true">
        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
    </span>
    <h1 class="mt-5 text-2xl font-extrabold tracking-tight text-ink-900">Halaman ini tidak untuk akun Anda</h1>
    <p class="mt-2.5 text-ink-600">{{ $exception->getMessage() ?: 'Anda tidak punya izin membuka halaman ini.' }}</p>
    <a href="{{ url('/') }}" class="dt-btn-primary mt-6">Kembali ke beranda</a>
</div>
@endsection
