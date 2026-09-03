@extends('layouts.dashboard')
@section('title', 'Dasbor admin')

@php($menu = \App\Support\AdminMenu::items('ringkasan'))

@section('panel')
    <header>
        <h1 class="text-2xl font-extrabold tracking-tight text-ink-900">Dasbor admin</h1>
        <p class="mt-1 text-sm text-ink-600">Antrean yang menunggu keputusan Anda.</p>
    </header>

    {{-- Antrean: kartu yang bernilai 0 tidak boleh mengundang klik ke halaman kosong --}}
    <h2 class="mt-6 text-xs font-semibold tracking-wide text-ink-500 uppercase">Menunggu keputusan</h2>

    <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Kampanye menunggu review" :value="$stats['kampanye_pending']"
                :href="route('admin.kampanye.index')"
                hint="Klik untuk meninjau" empty-hint="Tidak ada antrean"
                :tone="$stats['kampanye_pending'] > 0 ? 'warning' : 'neutral'" />

        <x-stat label="Identitas menunggu verifikasi" :value="$stats['user_pending']"
                :href="route('admin.pengguna.index')"
                hint="Klik untuk meninjau" empty-hint="Tidak ada antrean"
                :tone="$stats['user_pending'] > 0 ? 'warning' : 'neutral'" />

        <x-stat label="Pencairan menunggu" :value="$stats['pencairan_pending']"
                :href="route('admin.pencairan.index')"
                hint="Klik untuk meninjau" empty-hint="Tidak ada antrean"
                :tone="$stats['pencairan_pending'] > 0 ? 'warning' : 'neutral'" />

        <x-stat label="LPJ menunggu verifikasi" :value="$stats['lpj_pending']"
                :href="route('admin.lpj.index')"
                hint="Klik untuk meninjau" empty-hint="Tidak ada antrean"
                :tone="$stats['lpj_pending'] > 0 ? 'warning' : 'neutral'" />
    </div>

    <h2 class="mt-8 text-xs font-semibold tracking-wide text-ink-500 uppercase">Ringkasan platform</h2>

    <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Total terkumpul" :value="rupiah($stats['total_terkumpul'])" tone="success"
                hint="Dari seluruh donasi lunas" />
        <x-stat label="Transaksi lunas" :value="number_format($stats['total_donatur'], 0, ',', '.')"
                hint="Jumlah donasi berhasil" />
        <x-stat label="Kampanye aktif" :value="$stats['kampanye_aktif']"
                hint="Sedang tayang di publik" />
        <x-stat label="Sudah dicairkan" :value="rupiah($stats['total_tercairkan'] ?? 0)"
                hint="Lewat persetujuan admin" />
    </div>

    {{-- Integritas rantai audit --}}
    <section @class([
        'mt-6 rounded-2xl border p-5',
        'border-brand-200 bg-brand-50' => $chainStatus['valid'],
        'border-rose-300 bg-rose-50' => ! $chainStatus['valid'],
    ])>
        <div class="flex flex-wrap items-center gap-4">
            <div class="min-w-0 flex-1">
                <h2 @class(['font-bold', 'text-brand-900' => $chainStatus['valid'], 'text-rose-900' => ! $chainStatus['valid']])>
                    {{ $chainStatus['valid'] ? 'Rantai jejak audit utuh' : 'Rantai jejak audit terputus' }}
                </h2>
                <p class="mt-1 text-sm {{ $chainStatus['valid'] ? 'text-brand-900/80' : 'text-rose-900/80' }}">
                    {{ number_format($chainStatus['checked'], 0, ',', '.') }} entri diperiksa.
                    @unless ($chainStatus['valid'])
                        Ketidakcocokan pertama di entri #{{ $chainStatus['broken_at'] }} — {{ $chainStatus['reason'] }}
                    @endunless
                </p>
            </div>
            <a href="{{ route('admin.audit.index') }}" class="dt-btn-secondary">Buka jejak audit</a>
        </div>
    </section>

    <section class="dt-card mt-6 overflow-hidden">
        <div class="flex items-center justify-between p-5 sm:p-6">
            <h2 class="text-lg font-bold text-ink-900">Antrean review kampanye</h2>
            <a href="{{ route('admin.kampanye.index') }}" class="dt-link text-sm">Lihat semua &rarr;</a>
        </div>

        @if ($recentCampaigns->isEmpty())
            <p class="border-t border-ink-100 px-5 py-10 text-center text-sm text-ink-500 sm:px-6">
                Antrean kosong. Tidak ada kampanye yang menunggu keputusan.
            </p>
        @else
            <ul class="divide-y divide-ink-100 border-t border-ink-100">
                @foreach ($recentCampaigns as $campaign)
                    <li class="flex flex-wrap items-center gap-4 px-5 py-4 sm:px-6">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-ink-900">{{ $campaign->title }}</p>
                            <p class="mt-0.5 text-xs text-ink-500">
                                {{ $campaign->user->name }} &middot; target {{ rupiah($campaign->target_amount) }}
                                &middot; diajukan {{ $campaign->submitted_at?->diffForHumans() }}
                            </p>
                        </div>
                        <a href="{{ route('admin.kampanye.show', $campaign) }}" class="dt-btn-primary">Tinjau</a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
