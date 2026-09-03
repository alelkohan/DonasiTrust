@extends('layouts.app')
@section('title', 'Semua kampanye')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

    <header class="max-w-2xl">
        <h1 class="text-3xl font-extrabold tracking-tight text-ink-900 sm:text-4xl">Kampanye yang sedang berjalan</h1>
        <p class="mt-3 text-ink-600">
            Setiap kampanye di sini sudah lolos review manual admin, punya RAB terbuka,
            dan tahapan pencairan yang bisa Anda periksa sebelum berdonasi.
        </p>
    </header>

    <form method="GET" action="{{ route('kampanye.index') }}" class="dt-card mt-8 grid gap-3 p-4 sm:grid-cols-[1fr_auto_auto_auto]">
        <div>
            <label for="q" class="sr-only">Cari kampanye</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" class="dt-input"
                   placeholder="Cari judul atau ringkasan kampanye…">
        </div>

        <div>
            <label for="kategori" class="sr-only">Kategori</label>
            <select id="kategori" name="kategori" class="dt-input sm:w-48">
                <option value="">Semua kategori</option>
                @foreach ($categories as $key => $label)
                    <option value="{{ $key }}" @selected(request('kategori') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="urut" class="sr-only">Urutkan</label>
            <select id="urut" name="urut" class="dt-input sm:w-44">
                <option value="populer" @selected(request('urut') !== 'terbaru')>Paling banyak terkumpul</option>
                <option value="terbaru" @selected(request('urut') === 'terbaru')>Terbaru</option>
            </select>
        </div>

        <button type="submit" class="dt-btn-primary">Terapkan</button>
    </form>

    <p class="mt-5 text-sm text-ink-500">
        Menampilkan {{ $campaigns->count() }} dari {{ $campaigns->total() }} kampanye.
    </p>

    @if ($campaigns->isEmpty())
        <x-empty-state class="mt-6" title="Tidak ada kampanye yang cocok"
                       description="Coba kata kunci lain atau hapus filter kategori.">
            <a href="{{ route('kampanye.index') }}" class="dt-btn-secondary">Hapus filter</a>
        </x-empty-state>
    @else
        <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($campaigns as $campaign)
                <x-campaign-card :campaign="$campaign" />
            @endforeach
        </div>

        <div class="mt-10">{{ $campaigns->links() }}</div>
    @endif
</div>
@endsection
