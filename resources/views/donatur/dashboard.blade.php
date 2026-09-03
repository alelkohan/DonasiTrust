@extends('layouts.dashboard')
@section('title', 'Dasbor donatur')


@section('panel')
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-ink-900">Halo, {{ auth()->user()->name }}</h1>
            <p class="mt-1 text-sm text-ink-600">Semua kuitansi Anda tersimpan di sini dan bisa dicek ulang kapan saja.</p>
        </div>
        <a href="{{ route('kampanye.index') }}" class="dt-btn-primary">Donasi lagi</a>
    </header>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <x-stat label="Total donasi Anda" :value="rupiah($totalDonated)" tone="success" />
        <x-stat label="Jumlah transaksi" :value="number_format($donations->total(), 0, ',', '.')" />
        <x-stat label="Lunas di halaman ini"
                :value="number_format($donations->getCollection()->where('status', 'paid')->count(), 0, ',', '.')" />
    </div>

    <section class="dt-card mt-6 overflow-hidden">
        <div class="p-5 sm:p-6">
            <h2 class="text-lg font-bold text-ink-900">Riwayat donasi</h2>
        </div>

        @if ($donations->isEmpty())
            <div class="border-t border-ink-100 px-5 py-12 text-center sm:px-6">
                <p class="text-sm text-ink-500">Anda belum pernah berdonasi lewat akun ini.</p>
                <a href="{{ route('kampanye.index') }}" class="dt-btn-primary mt-4">Lihat kampanye</a>
            </div>
        @else
            <div class="overflow-x-auto border-t border-ink-100">
                <table class="dt-table min-w-[640px]">
                    <thead>
                        <tr>
                            <th scope="col">Nomor</th>
                            <th scope="col">Kampanye</th>
                            <th scope="col">Tanggal</th>
                            <th scope="col" class="text-right">Nominal</th>
                            <th scope="col">Status</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($donations as $donation)
                            <tr>
                                <td class="font-mono text-xs whitespace-nowrap text-ink-600">{{ $donation->reference }}</td>
                                <td>
                                    <a href="{{ route('kampanye.show', $donation->campaign) }}" class="dt-link">
                                        {{ Str::limit($donation->campaign->title, 40) }}
                                    </a>
                                </td>
                                <td class="whitespace-nowrap text-ink-600">{{ $donation->created_at->translatedFormat('d M Y') }}</td>
                                <td class="text-right font-semibold tabular-nums">{{ rupiah($donation->amount) }}</td>
                                <td><x-badge :tone="$donation->isPaid() ? 'success' : 'warning'">{{ $donation->statusLabel() }}</x-badge></td>
                                <td class="text-right whitespace-nowrap">
                                    @if ($donation->isPaid())
                                        <a href="{{ route('kuitansi.show', $donation) }}" class="dt-link text-sm">Kuitansi</a>
                                    @else
                                        <a href="{{ route('donasi.checkout', $donation) }}" class="dt-link text-sm">Bayar</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-ink-100 px-5 py-4 sm:px-6">{{ $donations->links() }}</div>
        @endif
    </section>
@endsection
