@extends('layouts.app')
@section('title', 'Kuitansi '.$donation->reference)

@section('content')
<div class="mx-auto max-w-2xl px-4 py-10 sm:px-6 lg:px-8">

    @if ($donation->isPaid())
        <div class="mb-6 flex items-center gap-3 rounded-2xl border border-brand-200 bg-brand-50 px-5 py-4">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-brand-600 text-white" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4.5 4.5L19 7.5"/></svg>
            </span>
            <div>
                <p class="text-sm font-bold text-brand-900">Donasi Anda diterima</p>
                <p class="text-sm text-brand-900/75">Terima kasih. Kuitansi digital di bawah bisa diverifikasi siapa pun.</p>
            </div>
        </div>
    @else
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
            Transaksi ini belum lunas. Kuitansi baru sah setelah pembayaran diterima.
            <a href="{{ route('donasi.checkout', $donation) }}" class="font-semibold underline">Lanjutkan pembayaran</a>
        </div>
    @endif

    <article class="dt-card overflow-hidden print:border-0 print:shadow-none">
        <header class="flex items-start justify-between gap-4 border-b border-ink-100 px-6 py-5 sm:px-8">
            <div class="flex items-center gap-2.5">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-600 text-white" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 6.2v5.1c0 4.6 3.2 8.4 8 9.7 4.8-1.3 8-5.1 8-9.7V6.2Z"/><path d="m9 12 2.2 2.2L15.4 10"/></svg>
                </span>
                <div>
                    <p class="font-extrabold tracking-tight text-ink-900">DonasiTrust</p>
                    <p class="text-xs text-ink-500">Kuitansi digital</p>
                </div>
            </div>
            <x-badge :tone="$donation->isPaid() ? 'success' : 'warning'">{{ $donation->statusLabel() }}</x-badge>
        </header>

        <div class="px-6 py-6 sm:px-8">
            <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Nomor transaksi</dt>
                    <dd class="mt-1 font-mono text-sm font-bold text-ink-900">{{ $donation->reference }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Tanggal</dt>
                    <dd class="mt-1 text-sm font-semibold text-ink-900">
                        {{ ($donation->paid_at ?? $donation->created_at)->translatedFormat('d F Y, H:i') }} WIB
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Donatur</dt>
                    <dd class="mt-1 text-sm font-semibold text-ink-900">{{ $donation->displayName() }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Kampanye</dt>
                    <dd class="mt-1 text-sm font-semibold text-ink-900">
                        <a href="{{ route('kampanye.show', $donation->campaign) }}" class="dt-link">{{ $donation->campaign->title }}</a>
                    </dd>
                </div>
            </dl>

            <div class="mt-6 rounded-2xl bg-ink-900 px-6 py-5">
                <p class="text-xs font-semibold tracking-wide text-ink-400 uppercase">Nominal donasi</p>
                <p class="mt-1 text-3xl font-extrabold tracking-tight text-white tabular-nums">{{ rupiah($donation->amount) }}</p>
            </div>

            <div class="mt-6 rounded-2xl border border-ink-200 bg-ink-50/70 p-5">
                <p class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Kode verifikasi</p>
                <p class="mt-1.5 font-mono text-lg font-bold tracking-wider break-all text-ink-900">{{ $shortCode }}</p>
                <p class="mt-2.5 text-xs leading-relaxed text-ink-500">
                    Kode ini adalah HMAC-SHA256 atas nomor transaksi, nominal, dan kampanye — dihitung
                    dengan kunci rahasia server. Ia membuktikan kuitansi diterbitkan oleh DonasiTrust.
                    Ini <strong>bukan</strong> tanda tangan digital: verifikasi tetap dilakukan oleh server kami,
                    bukan oleh pihak ketiga secara mandiri.
                </p>
                <a href="{{ route('verifikasi.form', ['reference' => $donation->reference, 'code' => $shortCode]) }}" class="dt-btn-secondary mt-3 inline-flex items-center gap-2 text-xs py-2 px-3">
                    <svg class="h-4 w-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m5 12 4.5 4.5L19 7.5"/></svg>
                    Verifikasi Keaslian Kuitansi ini (1-Klik Opsi) &rarr;
                </a>
            </div>

            @if ($donation->message)
                <div class="mt-6 border-t border-ink-100 pt-5">
                    <p class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Pesan Anda</p>
                    <p class="mt-1.5 text-sm text-ink-700 italic">&ldquo;{{ $donation->message }}&rdquo;</p>
                </div>
            @endif
        </div>
    </article>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row print:hidden">
        <button type="button" onclick="window.print()" class="dt-btn-secondary flex-1">Cetak / simpan PDF</button>
        <a href="{{ route('kampanye.transparansi', $donation->campaign) }}" class="dt-btn-primary flex-1">
            Lacak penggunaan dana
        </a>
    </div>
</div>
@endsection
