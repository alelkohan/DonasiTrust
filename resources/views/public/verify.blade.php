@extends('layouts.app')
@section('title', 'Verifikasi kuitansi · DonasiTrust')

@section('content')
<div class="w-full max-w-3xl mx-auto px-4 py-12 sm:px-6 lg:px-8">

    <header class="text-center">
        <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-[#99ff04] text-black font-black shadow-lg" aria-hidden="true">
            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 6.2v5.1c0 4.6 3.2 8.4 8 9.7 4.8-1.3 8-5.1 8-9.7V6.2Z"/><path d="m9 12 2.2 2.2L15.4 10"/></svg>
        </span>
        <h1 class="mt-4 text-3xl font-black tracking-tight text-white sm:text-4xl">Verifikasi Kuitansi Donasi</h1>
        <p class="mx-auto mt-3 max-w-lg text-sm text-slate-300 leading-relaxed">
            Punya kuitansi DonasiTrust dan ingin memastikan keasliannya? Masukkan nomor transaksi
            dan kode HMAC 16-karakter. Tanpa perlu login.
        </p>
    </header>

    @isset($result)
        @if ($result['valid'])
            <div class="mt-8 overflow-hidden rounded-3xl border border-[#99ff04]/40 bg-[#1b182a] shadow-2xl">
                <div class="flex items-center gap-3 bg-[#99ff04] px-6 py-4 text-black font-black">
                    <svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4.5 4.5L19 7.5"/></svg>
                    <p class="text-base">Kuitansi Asli & Valid (Terdaftar di Ledger Platform)</p>
                </div>
                <dl class="grid gap-x-6 gap-y-4 px-6 py-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-black tracking-wider text-slate-400 uppercase">Nomor Transaksi</dt>
                        <dd class="mt-1 font-mono text-sm font-black text-white">{{ $result['donation']->reference }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-black tracking-wider text-slate-400 uppercase">Nominal Donasi</dt>
                        <dd class="mt-1 text-sm font-black text-[#99ff04] tabular-nums">{{ rupiah($result['donation']->amount) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-black tracking-wider text-slate-400 uppercase">Status Pembayaran</dt>
                        <dd class="mt-1 text-sm font-bold text-emerald-400">
                            {{ $result['donation']->statusLabel() }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-black tracking-wider text-slate-400 uppercase">Kampanye</dt>
                        <dd class="mt-1 text-sm font-bold text-white">
                            <a href="{{ route('kampanye.show', $result['donation']->campaign) }}" class="text-[#99ff04] hover:underline">
                                {{ $result['donation']->campaign->title }}
                            </a>
                        </dd>
                    </div>
                </dl>
                <div class="border-t border-white/10 px-6 py-4 bg-[#231f36]">
                    <a href="{{ route('kampanye.transparansi', $result['donation']->campaign) }}" class="text-xs font-black text-[#99ff04] hover:underline">
                        Lihat audit transparansi ke mana uang disalurkan &rarr;
                    </a>
                </div>
            </div>
        @else
            <div class="mt-8 rounded-3xl border border-rose-500/40 bg-[#1b182a] p-6 shadow-2xl">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-6 w-6 shrink-0 text-rose-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6m0-6-6 6"/></svg>
                    <div>
                        <p class="font-black text-rose-400 text-base">Kuitansi Tidak Cocok / Palsu</p>
                        <p class="mt-1.5 text-xs leading-relaxed text-slate-300">
                            Nomor transaksi <span class="font-mono font-bold text-white">{{ $result['reference'] }}</span>
                            tidak ditemukan atau kode HMAC verifikasinya tidak sesuai.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    @endisset

    <form method="POST" action="{{ route('verifikasi.check') }}" class="mt-8 rounded-3xl border border-white/10 bg-[#1b182a] space-y-5 p-6 sm:p-8 shadow-2xl">
        @csrf

        <div>
            <label for="reference" class="block text-xs font-extrabold text-slate-300 mb-1.5">Nomor Transaksi</label>
            <input id="reference" name="reference" type="text" required
                   class="w-full rounded-2xl border border-white/15 bg-[#231f36] px-4 py-3 font-mono text-sm text-white placeholder-slate-400 focus:border-[#99ff04] focus:outline-none"
                   placeholder="DT-2026-000042" value="{{ old('reference', $prefill_ref ?? '') }}">
        </div>

        <div>
            <label for="code" class="block text-xs font-extrabold text-slate-300 mb-1.5">Kode Verifikasi (HMAC 16-Karakter)</label>
            <input id="code" name="code" type="text" required
                   class="w-full rounded-2xl border border-white/15 bg-[#231f36] px-4 py-3 font-mono tracking-wider text-sm text-white placeholder-slate-400 focus:border-[#99ff04] focus:outline-none"
                   placeholder="A1B2C3D4E5F6G7H8" value="{{ old('code', $prefill_code ?? '') }}">
            <p class="mt-1.5 text-xs text-slate-400">Tertera di bagian bawah kuitansi digital Anda.</p>
        </div>

        <button type="submit" class="w-full rounded-full bg-[#99ff04] py-3.5 px-6 text-sm font-black text-black hover:bg-[#84e000] transition-transform hover:scale-105 active:scale-95 shadow-xl">
            Periksa Keaslian Kuitansi
        </button>
    </form>

    <div class="mt-8 rounded-3xl border border-white/10 bg-[#1b182a] p-6">
        <h2 class="text-sm font-extrabold text-white">Prinsip Keamanan Kriptografi HMAC-SHA256</h2>
        <p class="mt-2 text-xs leading-relaxed text-slate-300">
            Kode verifikasi dihitung menggunakan algoritma HMAC-SHA256 berstandar industri dengan Kunci Rahasia platform.
            Kesesuaian kode membuktikan data kuitansi belum pernah dimanipulasi atau diubah sejak diterbitkan.
        </p>
    </div>
</div>
@endsection
