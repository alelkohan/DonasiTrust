@extends('layouts.app')
@section('title', 'Selesaikan Pembayaran · DonasiTrust')

@php
    $kedaluwarsa = $donation->expiresAt();
    $sudahLewat = $donation->isExpired();
    $qrPayload = $donation->gateway_payload['qr_payload'] ?? $donation->reference;
    $snapToken = $donation->gateway_payload['snap_token'] ?? null;
    $isMidtrans = config('donasi.gateway') === 'midtrans';
@endphp

@section('content')
<div class="w-full max-w-3xl mx-auto py-8"
     x-data="{
        isPaid: false,
        paidMessage: 'Pembayaran Berhasil! Mengalihkan ke Halaman Transparansi...',
        checkPaymentStatus() {
            fetch('{{ route('donasi.status', $donation->reference) }}')
                .then(res => res.json())
                .then(data => {
                    if (data.is_paid) {
                        this.isPaid = true;
                        setTimeout(() => {
                            window.location.href = data.redirect_url;
                        }, 1200);
                    }
                })
                .catch(err => console.error(err));
        }
     }"
     x-init="setInterval(() => checkPaymentStatus(), 2500)">

    <nav class="mb-5 text-xs font-bold text-slate-400">
        <a href="{{ route('kampanye.show', $donation->campaign) }}" class="hover:text-[#99ff04] transition-colors">
            &larr; Kembali ke Kampanye
        </a>
    </nav>

    <template x-if="isPaid">
        <div class="mb-6 rounded-3xl border border-[#99ff04] bg-[#99ff04]/10 p-6 text-center shadow-2xl animate-bounce">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#99ff04] text-black mb-3">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h2 class="text-xl font-black text-white">Pembayaran Berhasil Ditentukan!</h2>
            <p class="mt-1 text-xs text-[#99ff04]" x-text="paidMessage"></p>
        </div>
    </template>

    <div class="rounded-3xl border border-white/10 bg-[#1b182a] flex items-center gap-4 p-5 shadow-2xl">
        <span class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-[#12101c]">
            <img src="{{ $donation->campaign->coverUrl() }}" alt="{{ $donation->campaign->title }}"
                 onerror="this.onerror=null;this.src='{{ asset('images/no-cover.svg') }}';"
                 class="h-full w-full object-cover">
        </span>
        <div class="min-w-0">
            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Donasi Untuk</p>
            <p class="truncate font-bold text-white text-sm sm:text-base">{{ $donation->campaign->title }}</p>
        </div>
    </div>

    <div class="rounded-3xl border border-white/10 bg-[#1b182a] mt-6 overflow-hidden shadow-2xl"
         x-data="hitungMundur({ sampai: '{{ $kedaluwarsa->toIso8601String() }}', habis: {{ $sudahLewat ? 'true' : 'false' }} })">

        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 bg-[#231f36] px-6 py-4">
            <div>
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Nomor Transaksi</p>
                <p class="mt-0.5 font-mono text-sm font-bold text-white">{{ $donation->reference }}</p>
            </div>

            <div class="text-right">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Batas Waktu</p>
                <p class="mt-0.5 font-mono text-sm font-bold tabular-nums"
                   :class="habis ? 'text-rose-400' : 'text-[#99ff04]'"
                   x-text="habis ? 'Kedaluwarsa' : sisa"></p>
            </div>
        </div>

        <div class="p-6 sm:p-8">
            <h1 class="text-xl font-black tracking-tight text-white">Selesaikan Pembayaran</h1>

            @if ($isMidtrans && $snapToken)
                <div class="mt-6 rounded-3xl border border-white/10 bg-white p-4 shadow-xl">
                    <div id="snap-embed-container" class="min-h-[500px] w-full rounded-2xl overflow-hidden bg-white"></div>
                </div>

                @php
                    $redirectUrl = $donation->gateway_payload['redirect_url'] ?? null;
                @endphp
                @if ($redirectUrl)
                    <div class="mt-4 text-center">
                        <p class="text-xs text-slate-400">
                            Kotak pembayaran tidak muncul?
                            <a href="{{ $redirectUrl }}" target="_blank" rel="noopener noreferrer"
                               class="font-bold text-[#99ff04] hover:underline inline-flex items-center gap-1">
                                Buka Halaman Pembayaran Midtrans &rarr;
                            </a>
                        </p>
                    </div>
                @endif
            @else
                <div class="mt-6 rounded-3xl border border-white/10 bg-[#231f36] p-6 text-center">
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Tagihan</p>
                    <p class="mt-1.5 text-3xl sm:text-4xl font-black tracking-tight text-[#99ff04] tabular-nums">{{ rupiah($donation->amount) }}</p>

                    <div class="relative mx-auto mt-6 w-fit">
                        <div class="rounded-2xl border border-white/20 bg-white p-3 shadow-2xl"
                             :class="habis && 'opacity-30'">
                            @if (Str::startsWith($qrPayload, ['http://', 'https://']))
                                <img src="{{ $qrPayload }}" alt="QRIS Midtrans" class="block h-44 w-44 object-contain" />
                            @else
                                <canvas data-qr="{{ $qrPayload }}" width="176" height="176"
                                        class="block h-44 w-44" role="img"
                                        aria-label="Kode QRIS transaksi {{ $donation->reference }}"></canvas>
                            @endif
                        </div>

                        <span class="absolute -top-2 -right-2 rounded-full px-2.5 py-1 text-[10px] font-black tracking-wide text-black uppercase bg-[#99ff04] shadow-md">
                            {{ $isMidtrans ? 'Midtrans QRIS' : 'Simulasi QR' }}
                        </span>

                        <div x-show="habis" x-cloak
                             class="absolute inset-0 grid place-items-center rounded-2xl">
                            <span class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-bold text-white">Kedaluwarsa</span>
                        </div>
                    </div>

                    <p class="mt-4 text-xs text-slate-400">
                        Berlaku sampai {{ $kedaluwarsa->translatedFormat('H:i, d F Y') }} WIB
                    </p>
                </div>
            @endif

            @if (config('donasi.gateway') === 'mock')
                <div class="mt-6 rounded-2xl border border-[#99ff04]/30 bg-[#99ff04]/5 p-5">
                    <p class="text-xs font-black uppercase text-[#99ff04]">Mode Simulasi Instant Payment</p>
                    <p class="mt-1 text-xs leading-relaxed text-slate-300">
                        Klik tombol di bawah untuk mensimulasikan webhooks sukses dan menguji penerbitan kuitansi HMAC.
                    </p>

                    <form method="POST" action="{{ route('donasi.simulasi', $donation) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="w-full rounded-full bg-[#99ff04] py-3.5 px-6 text-sm font-black text-black hover:bg-[#84e000] transition-transform active:scale-95 shadow-xl"
                                :disabled="habis">
                            Simulasikan Pembayaran Berhasil Now &rarr;
                        </button>
                    </form>
                </div>
            @endif

            <dl class="mt-6 space-y-3 border-t border-white/10 pt-5 text-xs">
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-400">Status Pembayaran</dt>
                    <dd class="font-bold text-amber-300">{{ $donation->statusLabel() }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-400">Metode</dt>
                    <dd class="font-bold text-white uppercase">{{ $donation->payment_channel }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-400">Waktu Dibuat</dt>
                    <dd class="font-bold text-white">{{ $donation->created_at->translatedFormat('d F Y, H:i') }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <p class="mt-5 text-center text-xs text-slate-400">
        Menutup halaman ini tidak membatalkan transaksi. Simpan nomor transaksi
        <span class="font-mono font-bold text-[#99ff04]">{{ $donation->reference }}</span>
        untuk melanjutkan pembayaran sebelum batas waktu berakhir.
    </p>
</div>

@push('head')
@if ($isMidtrans)
    @php
        $serverKey = (string) config('donasi.midtrans.server_key');
        $isProd = (bool) config('donasi.midtrans.is_production', false);
        $snapJsUrl = $isProd ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js';
    @endphp
    <script src="{{ $snapJsUrl }}" data-client-key="{{ config('donasi.midtrans.client_key') }}"></script>
@endif
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

    @if ($isMidtrans && $snapToken)
        function initMidtransSnap() {
            const container = document.getElementById('snap-embed-container');
            if (!container || container.dataset.initialized) return;

            if (window.snap && typeof window.snap.embed === 'function') {
                container.dataset.initialized = 'true';
                window.snap.embed('{{ $snapToken }}', {
                    embedId: 'snap-embed-container',
                    onSuccess: function (result) {
                        fetch('{{ route('donasi.status', $donation->reference) }}')
                            .then(res => res.json())
                            .then(data => {
                                window.location.href = data.redirect_url;
                            })
                            .catch(() => {
                                window.location.href = '{{ route('kuitansi.show', $donation) }}';
                            });
                    },
                    onPending: function (result) {
                        console.log('Midtrans Pending:', result);
                    },
                    onError: function (result) {
                        console.error('Midtrans Error:', result);
                    }
                });
            }
        }

        document.addEventListener('DOMContentLoaded', initMidtransSnap);
        document.addEventListener('livewire:navigated', initMidtransSnap);
        window.addEventListener('load', initMidtransSnap);

        let snapAttempts = 0;
        const checkSnapInterval = setInterval(() => {
            snapAttempts++;
            if (window.snap && typeof window.snap.embed === 'function') {
                clearInterval(checkSnapInterval);
                initMidtransSnap();
            } else if (snapAttempts > 30) {
                clearInterval(checkSnapInterval);
            }
        }, 300);
    @endif
</script>
@endpush
@endsection
