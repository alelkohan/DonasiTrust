@extends('layouts.app')
@section('title', 'Halaman tidak ditemukan')

@section('content')
<div class="mx-auto flex max-w-lg flex-col items-center px-4 py-24 text-center">
    <span class="grid h-14 w-14 place-items-center rounded-2xl bg-ink-100 text-ink-500" aria-hidden="true">
        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
    </span>
    <h1 class="mt-5 text-2xl font-extrabold tracking-tight text-ink-900">Halaman tidak ditemukan</h1>
    <p class="mt-2.5 text-ink-600">
        Alamatnya mungkin salah ketik, atau kampanye yang Anda cari belum tayang.
    </p>
    <div class="mt-6 flex flex-wrap justify-center gap-3">
        <a href="{{ url('/') }}" class="dt-btn-primary">Beranda</a>
        <a href="{{ route('kampanye.index') }}" class="dt-btn-secondary">Lihat kampanye</a>
    </div>
</div>
@endsection
