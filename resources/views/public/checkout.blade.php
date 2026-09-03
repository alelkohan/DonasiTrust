@extends('layouts.app')
@section('title', 'Selesaikan pembayaran')

@php
    // Kedaluwarsa diambil dari data yang DISIMPAN saat sesi pembayaran dibuat.
    // Sebelumnya dihitung ulang dengan now()->addHours(2) di setiap render,
    // sehingga jamnya bergeser tiap kali halaman disegarkan — angka yang
    // berubah sendiri persis lawan dari yang dijual aplikasi ini.
    $kedaluwarsa = $donation->expiresAt();
    $sudahLewat = $donation->isExpired();
    $qrPayload = $donation->gateway_payload['qr_payload'] ?? $donation->reference;
@endphp

@section('content')
<div class="mx-auto max-w-2xl px-4 py-10 sm:px-6 lg:px-8">

    <nav class="mb-5 text-sm text-ink-500">
        <a href="{{ route('kampanye.show', $donation->campaign) }}" class="hover:text-brand-700">
            &larr; Kembali ke kampanye
        </a>
    </nav>

    {{-- Konteks: donatur harus tahu ini untuk apa, tanpa perlu klik --}}
    <div class="dt-card flex items-center gap-4 p-4">
        <span class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-ink-100">
            @if ($donation->campaign->cover_path)
                <img src="{{ asset('storage/'.$donation->campaign->cover_path) }}" alt=""
                     class="h-full w-full object-cover">
            @else
                <svg class="h-6 w-6 text-ink-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="16" rx="2"/>
                    <circle cx="8.5" cy="9.5" r="1.5"/>
                    <path d="m4 17 4.5-4.5 3 3L15 11l5 5"/>
                </svg>
            @endif
        </span>
        <div class="min-w-0">
            <p class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Donasi untuk</p>
            <p class="truncate font-bold text-ink-900">{{ $donation->campaign->title }}</p>
        </div>
    </div>

    <div class="dt-card mt-4 overflow-hidden"
         x-data="hitungMundur({ sampai: '{{ $kedaluwarsa->toIso8601String() }}', habis: {{ $sudahLewat ? 'true' : 'false' }} })">

        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink-100 bg-ink-50/60 px-6 py-4">
            <div>
                <p class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Nomor transaksi</p>
                <p class="mt-0.5 font-mono text-base font-bold text-ink-900">{{ $donation->reference }}</p>
            </div>

            <div class="text-right">
                <p class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Batas waktu</p>
                <p class="mt-0.5 font-mono text-base font-bold tabular-nums"
                   :class="habis ? 'text-rose-600' : 'text-ink-900'"
                   x-text="habis ? 'Kedaluwarsa' : sisa"></p>
            </div>
        </div>

        <div class="p-6 sm:p-8">
            <h1 class="text-xl font-extrabold tracking-tight text-ink-900">Selesaikan pembayaran</h1>

            <div class="mt-6 rounded-2xl border border-ink-200 bg-ink-50/70 p-6 text-center">
                <p class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Total tagihan</p>
                <p class="mt-1.5 text-4xl font-extrabold tracking-tight text-ink-900 tabular-nums">{{ rupiah($donation->amount) }}</p>

                <div class="relative mx-auto mt-6 w-fit">
                    <div class="rounded-2xl border border-ink-200 bg-white p-3"
                         :class="habis && 'opacity-30'">
                        {{-- Digambar di peramban dari data-qr. Isinya kode transaksi
                             simulasi, bukan QRIS sungguhan — makanya diberi label. --}}
                        <canvas data-qr="{{ $qrPayload }}" width="176" height="176"
                                class="block h-44 w-44" role="img"
                                aria-label="Kode QR simulasi untuk transaksi {{ $donation->reference }}"></canvas>
                    </div>

                    <span class="absolute -top-2 -right-2 rounded-full bg-amber-500 px-2.5 py-1 text-[10px] font-bold tracking-wide text-white uppercase">
                        Simulasi
                    </span>

                    <div x-show="habis" x-cloak
                         class="absolute inset-0 grid place-items-center rounded-2xl">
                        <span class="rounded-lg bg-rose-600 px-3 py-1.5 text-sm font-bold text-white">Kedaluwarsa</span>
                    </div>
                </div>

                <p class="mt-4 text-xs text-ink-500">
                    Berlaku sampai {{ $kedaluwarsa->translatedFormat('H:i, d F Y') }} WIB
                </p>
            </div>

            @if (config('donasi.gateway') === 'mock')
                <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-sm font-semibold text-amber-900">Mode simulasi aktif</p>
                    <p class="mt-1 text-sm leading-relaxed text-amber-900/80">
                        Gateway pembayaran belum dihubungkan. QR di atas bukan QRIS sungguhan dan
                        tidak bisa dibayar lewat aplikasi bank. Tombol di bawah meniru webhook
                        &ldquo;pembayaran berhasil&rdquo; dari penyedia, persis seperti alur aslinya.
                    </p>

                    <form method="POST" action="{{ route('donasi.simulasi', $donation) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="dt-btn-primary w-full py-3 text-base"
                                :disabled="habis">
                            Simulasikan pembayaran berhasil
                        </button>
                    </form>

                    <p x-show="habis" x-cloak class="mt-2 text-center text-xs text-rose-700">
                        Sesi pembayaran ini sudah lewat batas waktu. Buat donasi baru dari halaman kampanye.
                    </p>
                </div>
            @endif

            <dl class="mt-6 space-y-2.5 border-t border-ink-100 pt-5 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-500">Status</dt>
                    <dd class="font-semibold text-amber-700">{{ $donation->statusLabel() }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-500">Metode</dt>
                    <dd class="font-semibold text-ink-800 uppercase">{{ $donation->payment_channel }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-500">Dibuat</dt>
                    <dd class="font-semibold text-ink-800">{{ $donation->created_at->translatedFormat('d F Y, H:i') }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <p class="mt-5 text-center text-sm text-ink-500">
        Menutup halaman ini tidak membatalkan transaksi. Simpan nomor
        <span class="font-mono font-semibold text-ink-700">{{ $donation->reference }}</span>
        untuk kembali ke sini.
    </p>
</div>

@push('head')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('hitungMundur', ({ sampai, habis }) => ({
            habis,
            sisa: '',
            timer: null,

            init() {
                this.hitung();
                this.timer = setInterval(() => this.hitung(), 1000);
            },

            destroy() {
                clearInterval(this.timer);
            },

            hitung() {
                const selisih = new Date(sampai).getTime() - Date.now();

                if (selisih <= 0) {
                    this.habis = true;
                    this.sisa = '00:00:00';
                    clearInterval(this.timer);
                    return;
                }

                const total = Math.floor(selisih / 1000);
                const jam = String(Math.floor(total / 3600)).padStart(2, '0');
                const menit = String(Math.floor((total % 3600) / 60)).padStart(2, '0');
                const detik = String(total % 60).padStart(2, '0');

                this.sisa = `${jam}:${menit}:${detik}`;
            },
        }));
    });
</script>
@endpush
@endsection
