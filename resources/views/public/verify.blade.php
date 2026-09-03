@extends('layouts.app')
@section('title', 'Verifikasi kuitansi')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">

    <header class="text-center">
        <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-brand-100 text-brand-700" aria-hidden="true">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 6.2v5.1c0 4.6 3.2 8.4 8 9.7 4.8-1.3 8-5.1 8-9.7V6.2Z"/><path d="m9 12 2.2 2.2L15.4 10"/></svg>
        </span>
        <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-ink-900">Verifikasi kuitansi</h1>
        <p class="mx-auto mt-3 max-w-md text-ink-600">
            Punya kuitansi DonasiTrust dan ingin memastikan ia asli? Masukkan nomor transaksi
            dan kode verifikasinya. Tidak perlu punya akun.
        </p>
    </header>

    @isset($result)
        @if ($result['valid'])
            <div class="dt-card mt-8 overflow-hidden border-brand-300">
                <div class="flex items-center gap-3 bg-brand-600 px-6 py-4 text-white">
                    <svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4.5 4.5L19 7.5"/></svg>
                    <p class="font-bold">Kuitansi asli dan terdaftar</p>
                </div>
                <dl class="grid gap-x-6 gap-y-4 px-6 py-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Nomor transaksi</dt>
                        <dd class="mt-1 font-mono text-sm font-bold text-ink-900">{{ $result['donation']->reference }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Nominal</dt>
                        <dd class="mt-1 text-sm font-bold text-ink-900 tabular-nums">{{ rupiah($result['donation']->amount) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Status pembayaran</dt>
                        <dd class="mt-1 text-sm font-semibold {{ $result['donation']->isPaid() ? 'text-brand-700' : 'text-amber-700' }}">
                            {{ $result['donation']->statusLabel() }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Kampanye</dt>
                        <dd class="mt-1 text-sm font-semibold text-ink-900">
                            <a href="{{ route('kampanye.show', $result['donation']->campaign) }}" class="dt-link">
                                {{ $result['donation']->campaign->title }}
                            </a>
                        </dd>
                    </div>
                </dl>
                <div class="border-t border-ink-100 px-6 py-4">
                    <a href="{{ route('kampanye.transparansi', $result['donation']->campaign) }}" class="dt-link text-sm">
                        Lihat ke mana dana kampanye ini dipakai &rarr;
                    </a>
                </div>
            </div>
        @else
            <div class="dt-card mt-8 border-rose-300 p-6">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-6 w-6 shrink-0 text-rose-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6m0-6-6 6"/></svg>
                    <div>
                        <p class="font-bold text-rose-800">Kuitansi tidak cocok</p>
                        <p class="mt-1.5 text-sm leading-relaxed text-ink-600">
                            Nomor transaksi <span class="font-mono font-semibold">{{ $result['reference'] }}</span>
                            tidak ditemukan, atau kode verifikasinya tidak sesuai. Periksa kembali penulisannya —
                            kode terdiri dari 16 karakter tanpa spasi.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    @endisset

    <form method="POST" action="{{ route('verifikasi.check') }}" class="dt-card mt-8 space-y-4 p-6 sm:p-7">
        @csrf

        <div>
            <label for="reference" class="dt-label">Nomor transaksi</label>
            <input id="reference" name="reference" type="text" required class="dt-input font-mono"
                   placeholder="DT-2026-000042" value="{{ old('reference') }}">
        </div>

        <div>
            <label for="code" class="dt-label">Kode verifikasi</label>
            <input id="code" name="code" type="text" required class="dt-input font-mono tracking-wider"
                   placeholder="A1B2C3D4E5F6G7H8" value="{{ old('code') }}">
            <p class="dt-hint">Tertera di bagian bawah kuitansi digital Anda.</p>
        </div>

        <button type="submit" class="dt-btn-primary w-full py-3 text-base">Periksa keaslian</button>
    </form>

    <div class="mt-8 rounded-2xl border border-ink-200 bg-white p-5">
        <h2 class="text-sm font-bold text-ink-900">Apa arti &ldquo;asli&rdquo; di sini?</h2>
        <p class="mt-2 text-sm leading-relaxed text-ink-600">
            Kode verifikasi dihitung dengan HMAC-SHA256 memakai kunci rahasia yang hanya ada di server
            DonasiTrust. Cocoknya kode membuktikan data kuitansi (nomor, nominal, kampanye) belum diubah
            sejak diterbitkan. Yang <em>tidak</em> dibuktikan: bahwa uangnya sudah dipakai dengan benar —
            untuk itu, lihat halaman transparansi kampanye dan bukti notanya.
        </p>
    </div>
</div>
@endsection
