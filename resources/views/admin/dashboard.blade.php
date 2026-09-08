@extends('layouts.dashboard')
@section('title', 'Dasbor admin')

@php($menu = \App\Support\AdminMenu::items('ringkasan'))

@section('panel')
    <header>
        <h1 class="text-2xl font-black tracking-tight text-white">Dasbor admin</h1>
        <p class="mt-1 text-sm font-medium text-slate-400">Antrean yang menunggu keputusan Anda.</p>
    </header>

    {{-- Antrean: kartu yang bernilai 0 tidak boleh mengundang klik ke halaman kosong --}}
    <h2 class="mt-6 text-xs font-black tracking-wider text-slate-400 uppercase">Menunggu keputusan</h2>

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

    <h2 class="mt-8 text-xs font-black tracking-wider text-slate-400 uppercase">Ringkasan platform</h2>

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
        'mt-6 rounded-2xl border p-5 transition-all',
        'border-emerald-500/30 bg-emerald-500/10' => $chainStatus['valid'],
        'border-rose-500/30 bg-rose-500/10' => ! $chainStatus['valid'],
    ])>
        <div class="flex flex-wrap items-center gap-4">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full {{ $chainStatus['valid'] ? 'bg-[#99ff04] shadow-[0_0_8px_#99ff04]' : 'bg-rose-500' }}"></span>
                    <h2 @class(['font-bold', 'text-white' => $chainStatus['valid'], 'text-rose-300' => ! $chainStatus['valid']])>
                        {{ $chainStatus['valid'] ? 'Rantai jejak audit utuh' : 'Rantai jejak audit terputus' }}
                    </h2>
                </div>
                <p class="mt-1 text-sm {{ $chainStatus['valid'] ? 'text-slate-300' : 'text-rose-200' }}">
                    {{ number_format($chainStatus['checked'], 0, ',', '.') }} entri diperiksa.
                    @unless ($chainStatus['valid'])
                        Ketidakcocokan pertama di entri #{{ $chainStatus['broken_at'] }} — {{ $chainStatus['reason'] }}
                    @endunless
                </p>
            </div>
            <a href="{{ route('admin.audit.index') }}" class="dt-btn-secondary text-xs">Buka jejak audit</a>
        </div>
    </section>

    <section class="dt-card mt-6 overflow-hidden">
        <div class="flex items-center justify-between border-b border-white/10 p-5 sm:p-6">
            <h2 class="text-base font-black text-white">Antrean review kampanye</h2>
            <a href="{{ route('admin.kampanye.index') }}" class="text-xs font-extrabold text-[#99ff04] hover:underline">Lihat semua &rarr;</a>
        </div>

        @if ($recentCampaigns->isEmpty())
            <p class="px-5 py-10 text-center text-sm text-slate-400 sm:px-6">
                Antrean kosong. Tidak ada kampanye yang menunggu keputusan.
            </p>
        @else
            <ul class="divide-y divide-white/10">
                @foreach ($recentCampaigns as $campaign)
                    <li class="flex flex-wrap items-center gap-4 px-5 py-4 sm:px-6 hover:bg-white/[0.02] transition-colors">
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-white">{{ $campaign->title }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">
                                {{ $campaign->user->name }} &middot; target <span class="text-white font-medium">{{ rupiah($campaign->target_amount) }}</span>
                                &middot; diajukan {{ $campaign->submitted_at?->diffForHumans() }}
                            </p>
                        </div>
                        <a href="{{ route('admin.kampanye.show', $campaign) }}" class="dt-btn-primary text-xs py-1.5 px-4">Tinjau</a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
