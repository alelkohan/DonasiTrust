@extends('layouts.dashboard')
@section('title', 'Dasbor Pengaju')

@section('panel')
<div class="space-y-6">

    {{-- Header Welcome Banner --}}
    <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-r from-[#1b182a] via-[#231f36] to-[#1b182a] p-6 sm:p-8 shadow-xl">
        <div aria-hidden="true" class="absolute -top-12 -right-12 h-64 w-64 rounded-full bg-[#99ff04]/10 blur-3xl pointer-events-none"></div>
        
        <div class="relative z-10 flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <div class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-[#99ff04] text-black font-black text-xl shadow-lg shadow-[#99ff04]/20">
                    {{ Str::upper(Str::substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white">
                        Dasbor Pengaju
                    </h1>
                    <p class="mt-1 text-xs sm:text-sm font-semibold text-[#99ff04]">
                        {{ auth()->user()->organization ?: auth()->user()->name }}
                    </p>
                </div>
            </div>

            @if (auth()->user()->canSubmitCampaign())
                <a href="{{ route('pengaju.kampanye.create') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-[#99ff04] px-5 py-2.5 text-xs font-black text-black hover:bg-[#84e000] transition-all hover:scale-105 active:scale-95 shadow-md shadow-[#99ff04]/20 shrink-0">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Buat Kampanye Baru</span>
                </a>
            @endif
        </div>
    </div>

    {{-- Penunjuk Arah / Onboarding Step Timeline --}}
    @php
        $u = auth()->user();
        $sudahKirimBerkas = in_array($u->verification_status, ['pending', 'verified', 'rejected'], true);

        $langkah = [
            [
                'judul' => '1. Pendaftaran Akun',
                'ket' => 'Akun pengaju berhasil dibuat.',
                'keadaan' => 'selesai',
                'aksi' => null,
            ],
            [
                'judul' => '2. Verifikasi Identitas & Rekening',
                'ket' => match ($u->verification_status) {
                    'verified' => 'Identitas terverifikasi. Rekening pencairan terkunci aman.',
                    'pending' => 'Berkas terkirim, sedang ditinjau tim admin.',
                    'rejected' => 'Ditolak — perbaiki berkas sesuai catatan admin lalu kirim ulang.',
                    default => 'Unggah foto KTP dan daftarkan rekening tujuan pencairan.',
                },
                'keadaan' => match ($u->verification_status) {
                    'verified' => 'selesai',
                    'pending' => 'menunggu',
                    'rejected' => 'gagal',
                    default => 'berjalan',
                },
                'aksi' => $u->isVerified() ? null : ['Buka Verifikasi Identitas', route('verifikasi.identitas')],
            ],
            [
                'judul' => '3. Susun Kampanye & Ajukan Review',
                'ket' => $u->isVerified()
                    ? 'Anda sudah memiliki izin membuat kampanye baru.'
                    : 'Fitur pengajuan kampanye terbuka setelah verifikasi disetujui.',
                'keadaan' => $u->isVerified() ? 'berjalan' : 'terkunci',
                'aksi' => $u->canSubmitCampaign() ? ['Buat Kampanye Sekarang', route('pengaju.kampanye.create')] : null,
            ],
        ];

        $nomorAktif = $u->isVerified() ? 3 : ($sudahKirimBerkas ? 2 : 2);
    @endphp

    @unless ($u->isVerified() && $campaigns->isNotEmpty())
        <div class="rounded-3xl border border-white/10 bg-[#1b182a] p-6 shadow-xl backdrop-blur-md">
            <div class="flex items-center justify-between border-b border-white/10 pb-4 mb-6">
                <div>
                    <h2 class="text-base font-black text-white">Panduan Alur Pengajuan</h2>
                    <p class="text-xs font-medium text-slate-400">Langkah {{ $nomorAktif }} dari 3 untuk memulai kampanye</p>
                </div>
                <span class="rounded-full bg-[#231f36] px-3 py-1 text-xs font-extrabold text-slate-300 border border-white/10">
                    Progres Akun
                </span>
            </div>

            <ol class="space-y-6">
                @foreach ($langkah as $i => $l)
                    <li class="relative flex gap-4">
                        @unless ($loop->last)
                            <span class="absolute top-8 left-[15px] bottom-0 w-0.5 bg-white/10" aria-hidden="true"></span>
                        @endunless

                        <span @class([
                            'relative z-10 grid h-8 w-8 shrink-0 place-items-center rounded-full text-xs font-black shadow-md',
                            'bg-[#99ff04] text-black' => $l['keadaan'] === 'selesai',
                            'bg-amber-400 text-black' => $l['keadaan'] === 'menunggu',
                            'bg-rose-500 text-white' => $l['keadaan'] === 'gagal',
                            'bg-[#231f36] text-[#99ff04] border border-[#99ff04]/30' => $l['keadaan'] === 'berjalan',
                            'bg-[#231f36] text-slate-500 border border-white/10' => $l['keadaan'] === 'terkunci',
                        ])>
                            @if ($l['keadaan'] === 'selesai')
                                ✓
                            @else
                                {{ $i + 1 }}
                            @endif
                        </span>

                        <div class="min-w-0 flex-1 pt-0.5">
                            <p class="text-sm font-extrabold text-white">{{ $l['judul'] }}</p>
                            <p class="mt-1 text-xs font-medium leading-relaxed text-slate-300">{{ $l['ket'] }}</p>

                            @if ($l['aksi'])
                                <a href="{{ $l['aksi'][1] }}" class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-[#99ff04] px-4 py-1.5 text-xs font-black text-black hover:bg-[#84e000] transition-all">
                                    <span>{{ $l['aksi'][0] }}</span>
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    @endunless

    {{-- Stat Cards Grid --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-white/10 bg-[#1b182a] p-5 shadow-lg backdrop-blur-md">
            <span class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Total Kampanye</span>
            <div class="mt-2 text-2xl font-black text-white tabular-nums">{{ $stats['kampanye'] }}</div>
            <div class="mt-1 text-[11px] font-semibold text-[#99ff04]">{{ $stats['aktif'] }} sedang tayang</div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-[#1b182a] p-5 shadow-lg backdrop-blur-md">
            <span class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Total Terkumpul</span>
            <div class="mt-2 text-2xl font-black text-white tabular-nums">{{ rupiah($stats['terkumpul']) }}</div>
            <div class="mt-1 text-[11px] font-medium text-slate-400">Dari donatur publik</div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-[#1b182a] p-5 shadow-lg backdrop-blur-md">
            <span class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Sudah Dicairkan</span>
            <div class="mt-2 text-2xl font-black text-[#99ff04] tabular-nums">{{ rupiah($stats['tercairkan']) }}</div>
            <div class="mt-1 text-[11px] font-medium text-slate-400">Pencairan per milestone</div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-[#1b182a] p-5 shadow-lg backdrop-blur-md">
            <span class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Pencairan Menunggu</span>
            <div class="mt-2 text-2xl font-black text-amber-300 tabular-nums">{{ $pendingDisbursements }}</div>
            <div class="mt-1 text-[11px] font-medium text-slate-400">Dalam review admin</div>
    </div>

    {{-- Akun Terpadu: Ringkasan Donasi Pribadi Pengaju --}}
    <div class="rounded-3xl border border-white/10 bg-gradient-to-r from-[#1b182a] via-[#231f36] to-[#1b182a] p-5 sm:p-6 shadow-xl backdrop-blur-md flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#99ff04]/10 text-[#99ff04] border border-[#99ff04]/20 shadow-md">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </span>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-black text-white">Donasi Pribadi Anda</h2>
                    <span class="rounded-full bg-[#99ff04]/10 border border-[#99ff04]/20 px-2 py-0.5 text-[10px] font-black text-[#99ff04]">
                        Akun Terpadu
                    </span>
                </div>
                <p class="mt-0.5 text-xs text-slate-400">
                    Tercatat resmi: <strong class="text-white font-black">{{ rupiah($personalDonationStats['total'] ?? 0) }}</strong> ({{ $personalDonationStats['count'] ?? 0 }} transaksi donasi)
                </p>
            </div>
        </div>
        <a href="{{ route('donatur.dashboard') }}" class="inline-flex items-center justify-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-xs font-bold text-white hover:bg-[#99ff04] hover:text-black hover:border-[#99ff04] transition-all shrink-0">
            <span>Lihat Riwayat Donasi & Kuitansi &rarr;</span>
        </a>
    </div>

    {{-- Kampanye Saya List --}}
    <div class="rounded-3xl border border-white/10 bg-[#1b182a] shadow-xl overflow-hidden backdrop-blur-md">
        <div class="flex items-center justify-between border-b border-white/10 px-6 py-5">
            <h2 class="text-base font-black text-white">Kampanye Terbaru Saya</h2>
            <a href="{{ route('pengaju.kampanye.index') }}" class="text-xs font-extrabold text-[#99ff04] hover:underline">
                Kelola Semua Kampanye &rarr;
            </a>
        </div>

        @if ($campaigns->isEmpty())
            <div class="px-6 py-12 text-center">
                <p class="text-xs sm:text-sm font-medium text-slate-400">Belum ada kampanye yang dibuat.</p>
                @if (auth()->user()->canSubmitCampaign())
                    <a href="{{ route('pengaju.kampanye.create') }}" class="mt-4 inline-flex items-center gap-2 rounded-full bg-[#99ff04] px-5 py-2 text-xs font-black text-black hover:bg-[#84e000]">
                        <span>Buat Kampanye Pertama</span>
                    </a>
                @endif
            </div>
        @else
            <ul class="divide-y divide-white/10">
                @foreach ($campaigns->take(5) as $campaign)
                    <li class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 hover:bg-white/5 transition-colors">
                        <div class="flex items-center gap-3.5 min-w-0 flex-1">
                            <img src="{{ $campaign->coverUrl() }}" alt="{{ $campaign->title }}" class="h-12 w-16 shrink-0 rounded-xl object-cover border border-white/10 shadow-sm bg-[#231f36]">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-sm font-black text-white line-clamp-1">{{ $campaign->title }}</h3>
                                <span class="rounded-full bg-[#231f36] px-2.5 py-0.5 text-[10px] font-extrabold text-slate-300 border border-white/10">
                                    {{ $campaign->statusLabel() }}
                                </span>
                            </div>
                            <div class="mt-2 max-w-xs">
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-[#231f36]">
                                    <div class="h-full bg-[#99ff04]" style="width: {{ $campaign->progressPercent() }}%"></div>
                                </div>
                            </div>
                            <p class="mt-1 text-[11px] font-bold text-slate-400 tabular-nums">
                                {{ rupiah($campaign->collected_amount) }} <span class="font-normal text-slate-500">dari {{ rupiah($campaign->target_amount) }}</span>
                            </p>
                        </div>
                    </div>

                        <div class="flex gap-2">
                            @if ($campaign->isEditable())
                                <a href="{{ route('pengaju.kampanye.edit', $campaign) }}" class="rounded-xl border border-white/20 bg-[#231f36] px-3 py-1.5 text-xs font-extrabold text-white hover:border-[#99ff04] hover:text-[#99ff04]">
                                    Edit
                                </a>
                            @elseif ($campaign->isPublished())
                                <a href="{{ route('kampanye.transparansi', $campaign) }}" class="rounded-xl border border-white/20 bg-[#231f36] px-3 py-1.5 text-xs font-extrabold text-white hover:border-[#99ff04] hover:text-[#99ff04]">
                                    Ledger Publik
                                </a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

</div>
@endsection
