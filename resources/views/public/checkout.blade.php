@extends('layouts.app')
@section('title', 'Selesaikan pembayaran')

@php
    $kedaluwarsa = $donation->expiresAt();
    $sudahLewat = $donation->isExpired();
    $qrPayload = $donation->gateway_payload['qr_payload'] ?? $donation->reference;
    $snapToken = $donation->gateway_payload['snap_token'] ?? null;
    $isMidtrans = config('donasi.gateway') === 'midtrans';
@endphp

@section('content')
<div class="mx-auto max-w-2xl px-4 py-10 sm:px-6 lg:px-8"
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

    <nav class="mb-5 text-sm text-ink-500">
        <a href="{{ route('kampanye.show', $donation->campaign) }}" class="hover:text-brand-700">
            &larr; Kembali ke kampanye
        </a>
    </nav>

    <template x-if="isPaid">
        <div class="mb-6 rounded-2xl border border-emerald-300 bg-emerald-50 p-6 text-center shadow-lg animate-bounce">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-600 text-white mb-3">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h2 class="text-xl font-bold text-emerald-900">Pembayaran Berhasil Ditentukan!</h2>
            <p class="mt-1 text-sm text-emerald-700" x-text="paidMessage"></p>
        </div>
    </template>

    <div class="dt-card flex items-center gap-4 p-4">
        <span class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-ink-100">
            <img src="{{ $donation->campaign->coverUrl() }}" alt="{{ $donation->campaign->title }}"
                 class="h-full w-full object-cover">
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

            @if ($isMidtrans && $snapToken)
                <div class="mt-6 rounded-2xl border border-ink-200 bg-white p-4 shadow-sm">
                    {{-- Container Embed Snap Midtrans (Menampilkan QRIS & Pembayaran Langsung di Layar) --}}
                    <div id="snap-embed-container" class="min-h-[500px] w-full rounded-xl overflow-hidden bg-white"></div>
                </div>
            @else
                <div class="mt-6 rounded-2xl border border-ink-200 bg-ink-50/70 p-6 text-center">
                    <p class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Total tagihan</p>
                    <p class="mt-1.5 text-4xl font-extrabold tracking-tight text-ink-900 tabular-nums">{{ rupiah($donation->amount) }}</p>

                    <div class="relative mx-auto mt-6 w-fit">
                        <div class="rounded-2xl border border-ink-200 bg-white p-3"
                             :class="habis && 'opacity-30'">
                            @if (Str::startsWith($qrPayload, ['http://', 'https://']))
                                <img src="{{ $qrPayload }}" alt="QRIS Midtrans" class="block h-44 w-44 object-contain" />
                            @else
                                <canvas data-qr="{{ $qrPayload }}" width="176" height="176"
                                        class="block h-44 w-44" role="img"
                                        aria-label="Kode QRIS transaksi {{ $donation->reference }}"></canvas>
                            @endif
                        </div>

                        <span class="absolute -top-2 -right-2 rounded-full px-2.5 py-1 text-[10px] font-bold tracking-wide text-white uppercase {{ $isMidtrans ? 'bg-indigo-600' : 'bg-amber-500' }}">
                            {{ $isMidtrans ? 'Midtrans QRIS' : 'Simulasi' }}
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
            @endif

            @if (config('donasi.gateway') === 'mock')
                <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-sm font-semibold text-amber-900">Mode simulasi aktif</p>
                    <p class="mt-1 text-sm leading-relaxed text-amber-900/80">
                        Gateway simulasi aktif. Tombol di bawah meniru webhook pembayaran berhasil secara instan dan menguji alur sistem.
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
        document.addEventListener('DOMContentLoaded', function () {
            if (window.snap && typeof window.snap.embed === 'function') {
                window.snap.embed('{{ $snapToken }}', {
                    embedId: 'snap-embed-container',
                    onSuccess: function (result) {
                        fetch('{{ route('donasi.status', $donation->reference) }}')
                            .then(res => res.json())
                            .then(data => {
                                window.location.href = data.redirect_url;
                            });
                    },
                    onPending: function (result) {
                        console.log('Pending:', result);
                    },
                    onError: function (result) {
                        console.log('Error:', result);
                    }
                });
            }

            const payButton = document.getElementById('pay-button');
            if (payButton) {
                payButton.addEventListener('click', function () {
                    window.snap.pay('{{ $snapToken }}', {
                        onSuccess: function (result) {
                            fetch('{{ route('donasi.status', $donation->reference) }}')
                                .then(res => res.json())
                                .then(data => {
                                    window.location.href = data.redirect_url;
                                });
                        },
                        onPending: function (result) {
                            console.log('Pending:', result);
                        },
                        onError: function (result) {
                            alert('Pembayaran gagal atau dibatalkan.');
                        }
                    });
                });
            }
        });
    @endif
</script>
@endpush
@endsection
