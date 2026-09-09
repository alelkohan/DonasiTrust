@extends('layouts.app')
@section('title', 'Kuitansi Digital '.$donation->reference.' · DonasiTrust')

@section('content')
<div class="w-full max-w-2xl mx-auto py-8">

    @if ($donation->isPaid())
        <div class="mb-6 flex items-center gap-3.5 rounded-3xl border border-[#99ff04]/30 bg-[#99ff04]/10 p-5 shadow-2xl">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-[#99ff04] text-black font-black" aria-hidden="true">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="m5 12 4.5 4.5L19 7.5"/></svg>
            </span>
            <div>
                <p class="text-sm font-black text-white">Donasi Diterima & Terdaftar</p>
                <p class="text-xs text-[#99ff04]">Terima kasih atas kebaikan Anda. Kuitansi digital ini dapat diverifikasi publik.</p>
            </div>
        </div>
    @else
        <div class="mb-6 rounded-3xl border border-amber-500/30 bg-amber-500/10 p-5 text-xs text-amber-300 shadow-2xl">
            Transaksi belum lunas. Kuitansi baru dinyatakan sah setelah pembayaran diterima.
            <a href="{{ route('donasi.checkout', $donation) }}" class="font-bold underline ml-1 text-amber-200">Lanjutkan Pembayaran</a>
        </div>
    @endif

    <article class="rounded-3xl border border-white/10 bg-[#1b182a] overflow-hidden shadow-2xl print:border-0 print:shadow-none">
        <header class="flex items-start justify-between gap-4 border-b border-white/10 px-6 py-5 sm:px-8 bg-[#231f36]">
            <div class="flex items-center gap-2.5">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-[#99ff04] text-black font-black" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M12 3 4 6.2v5.1c0 4.6 3.2 8.4 8 9.7 4.8-1.3 8-5.1 8-9.7V6.2Z"/><path d="m9 12 2.2 2.2L15.4 10"/></svg>
                </span>
                <div>
                    <p class="font-black tracking-tight text-white">DonasiTrust</p>
                    <p class="text-[10px] text-slate-400 font-bold uppercase">Kuitansi Digital Resmi</p>
                </div>
            </div>
            <span class="rounded bg-[#99ff04] px-2.5 py-1 text-[10px] font-black uppercase text-black">
                {{ $donation->statusLabel() }}
            </span>
        </header>

        <div class="px-6 py-6 sm:px-8">
            <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-[10px] font-black tracking-wider text-slate-400 uppercase">Nomor Transaksi</dt>
                    <dd class="mt-1 font-mono text-sm font-black text-white">{{ $donation->reference }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-black tracking-wider text-slate-400 uppercase">Tanggal</dt>
                    <dd class="mt-1 text-sm font-bold text-white">
                        {{ ($donation->paid_at ?? $donation->created_at)->translatedFormat('d F Y, H:i') }} WIB
                    </dd>
                </div>
                <div>
                    <dt class="text-[10px] font-black tracking-wider text-slate-400 uppercase">Donatur</dt>
                    <dd class="mt-1 text-sm font-bold text-white">{{ $donation->displayName() }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-black tracking-wider text-slate-400 uppercase">Kampanye</dt>
                    <dd class="mt-1 text-sm font-bold text-white">
                        <a href="{{ route('kampanye.show', $donation->campaign) }}" class="text-[#99ff04] hover:underline">{{ $donation->campaign->title }}</a>
                    </dd>
                </div>
            </dl>

            <div class="mt-6 rounded-2xl bg-[#12101c] border border-white/10 px-6 py-5">
                <p class="text-[10px] font-black tracking-wider text-slate-400 uppercase">Nominal Donasi</p>
                <p class="mt-1 text-3xl font-black tracking-tight text-[#99ff04] tabular-nums">{{ rupiah($donation->amount) }}</p>
            </div>

            <div class="mt-6 rounded-2xl border border-white/10 bg-[#231f36] p-5">
                <p class="text-[10px] font-black tracking-wider text-slate-400 uppercase">Kode Verifikasi Keaslian Kuitansi</p>
                <p class="mt-1.5 font-mono text-lg font-black tracking-wider break-all text-white">{{ $shortCode }}</p>
                <p class="mt-2 text-xs leading-relaxed text-slate-300">
                    Kode acak terenkripsi ini menjamin kuitansi Anda 100% asli, aman, dan dapat diverifikasi langsung di platform.
                </p>
                <a href="{{ route('verifikasi.form', ['reference' => $donation->reference, 'code' => $shortCode]) }}"
                   class="mt-3 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-black text-[#99ff04] hover:bg-white/20 transition-all">
                    <svg class="h-4 w-4 text-[#99ff04]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m5 12 4.5 4.5L19 7.5"/></svg>
                    Verifikasi Keaslian Kuitansi Ini (1-Klik) &rarr;
                </a>
            </div>

            @if ($donation->message)
                <div class="mt-6 border-t border-white/10 pt-5">
                    <p class="text-[10px] font-black tracking-wider text-slate-400 uppercase">Pesan Anda</p>
                    <p class="mt-1.5 text-xs text-slate-200 italic">&ldquo;{{ $donation->message }}&rdquo;</p>
                </div>
            @endif
        </div>
    </article>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row print:hidden">
        <button type="button" onclick="window.print()" class="rounded-full border border-white/15 bg-[#231f36] py-3.5 px-6 text-xs font-bold text-white hover:bg-white/15 transition-all flex-1 text-center">
            Cetak / Simpan PDF
        </button>
        <a href="{{ route('kampanye.transparansi', $donation->campaign) }}" class="rounded-full bg-[#99ff04] py-3.5 px-6 text-xs font-black text-black hover:bg-[#84e000] transition-transform hover:scale-105 flex-1 text-center shadow-xl">
            Lacak Penggunaan Dana &rarr;
        </a>
    </div>
</div>
@endsection
