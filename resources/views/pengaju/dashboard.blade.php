@extends('layouts.dashboard')
@section('title', 'Dasbor pengaju')


@section('panel')
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-ink-900">Dasbor pengaju</h1>
            <p class="mt-1 text-sm text-ink-600">{{ auth()->user()->organization ?: auth()->user()->name }}</p>
        </div>
        @if (auth()->user()->canSubmitCampaign())
            <a href="{{ route('pengaju.kampanye.create') }}" class="dt-btn-primary">Buat kampanye baru</a>
        @endif
    </header>

    {{-- Penunjuk arah untuk pengaju baru. Tanpa ini, orang yang baru mendaftar
         menemukan semuanya terkunci dan tidak tahu dia ada di langkah mana. --}}
    @php
        $u = auth()->user();
        $sudahKirimBerkas = in_array($u->verification_status, ['pending', 'verified', 'rejected'], true);

        $langkah = [
            [
                'judul' => 'Buat akun',
                'ket' => 'Selesai.',
                'keadaan' => 'selesai',
                'aksi' => null,
            ],
            [
                'judul' => 'Verifikasi identitas & rekening',
                'ket' => match ($u->verification_status) {
                    'verified' => 'Disetujui admin.',
                    'pending' => 'Berkas terkirim, sedang ditinjau admin. Biasanya 1x24 jam.',
                    'rejected' => 'Ditolak — perbaiki sesuai catatan admin, lalu kirim ulang.',
                    default => 'Unggah KTP dan daftarkan rekening tujuan pencairan.',
                },
                'keadaan' => match ($u->verification_status) {
                    'verified' => 'selesai',
                    'pending' => 'menunggu',
                    'rejected' => 'gagal',
                    default => 'berjalan',
                },
                'aksi' => $u->isVerified() ? null : ['Buka verifikasi', route('verifikasi.identitas')],
            ],
            [
                'judul' => 'Susun kampanye & ajukan',
                'ket' => $u->isVerified()
                    ? 'Anda sudah bisa membuat kampanye.'
                    : 'Terbuka setelah identitas Anda terverifikasi.',
                'keadaan' => $u->isVerified() ? 'berjalan' : 'terkunci',
                'aksi' => $u->canSubmitCampaign() ? ['Buat kampanye', route('pengaju.kampanye.create')] : null,
            ],
        ];

        $nomorAktif = $u->isVerified() ? 3 : ($sudahKirimBerkas ? 2 : 2);
    @endphp

    @unless ($u->isVerified() && $campaigns->isNotEmpty())
        <section class="dt-card mt-6 p-5 sm:p-6">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-lg font-bold text-ink-900">Langkah menuju kampanye pertama</h2>
                <p class="text-sm text-ink-500">Langkah {{ $nomorAktif }} dari 3</p>
            </div>

            <ol class="mt-5 space-y-0">
                @foreach ($langkah as $i => $l)
                    <li class="relative flex gap-4 pb-6 last:pb-0">
                        @unless ($loop->last)
                            <span class="absolute top-9 left-[15px] h-full w-px bg-ink-200" aria-hidden="true"></span>
                        @endunless

                        <span @class([
                            'relative z-10 grid h-8 w-8 shrink-0 place-items-center rounded-full text-xs font-bold',
                            'bg-brand-600 text-white' => $l['keadaan'] === 'selesai',
                            'bg-amber-500 text-white' => $l['keadaan'] === 'menunggu',
                            'bg-rose-600 text-white' => $l['keadaan'] === 'gagal',
                            'bg-ink-900 text-white' => $l['keadaan'] === 'berjalan',
                            'bg-ink-200 text-ink-500' => $l['keadaan'] === 'terkunci',
                        ])>
                            @if ($l['keadaan'] === 'selesai')
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4.5 4.5L19 7.5"/></svg>
                            @else
                                {{ $i + 1 }}
                            @endif
                        </span>

                        <div class="min-w-0 flex-1 pt-0.5">
                            <p class="text-sm font-semibold text-ink-900">{{ $l['judul'] }}</p>
                            <p class="mt-0.5 text-sm leading-relaxed text-ink-600">{{ $l['ket'] }}</p>

                            @if ($l['aksi'])
                                <a href="{{ $l['aksi'][1] }}" class="dt-btn-primary mt-3">{{ $l['aksi'][0] }}</a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    @endunless

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Kampanye" :value="$stats['kampanye']" :hint="$stats['aktif'].' sedang tayang'" />
        <x-stat label="Total terkumpul" :value="rupiah($stats['terkumpul'])" tone="success" />
        <x-stat label="Sudah dicairkan" :value="rupiah($stats['tercairkan'])" />
        <x-stat label="Pencairan menunggu" :value="$pendingDisbursements"
                hint="Diproses admin" :tone="$pendingDisbursements > 0 ? 'warning' : 'neutral'" />
    </div>

    <section class="dt-card mt-6 overflow-hidden">
        <div class="flex items-center justify-between p-5 sm:p-6">
            <h2 class="text-lg font-bold text-ink-900">Kampanye saya</h2>
            <a href="{{ route('pengaju.kampanye.index') }}" class="dt-link text-sm">Kelola semua &rarr;</a>
        </div>

        @if ($campaigns->isEmpty())
            <div class="border-t border-ink-100 px-5 py-12 text-center sm:px-6">
                <p class="text-sm text-ink-500">Belum ada kampanye. Mulai dengan menyusun RAB dan tahapan pencairan.</p>
                @if (auth()->user()->canSubmitCampaign())
                    <a href="{{ route('pengaju.kampanye.create') }}" class="dt-btn-primary mt-4">Buat kampanye pertama</a>
                @endif
            </div>
        @else
            <ul class="divide-y divide-ink-100 border-t border-ink-100">
                @foreach ($campaigns->take(5) as $campaign)
                    <li class="flex flex-wrap items-center gap-4 px-5 py-4 sm:px-6">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-ink-900">{{ $campaign->title }}</p>
                                <x-badge :tone="match($campaign->status) {
                                    'approved', 'completed' => 'success',
                                    'pending' => 'warning',
                                    'rejected' => 'danger',
                                    default => 'neutral',
                                }">{{ $campaign->statusLabel() }}</x-badge>
                            </div>
                            <div class="mt-2 max-w-xs">
                                <x-progress :value="$campaign->progressPercent()" />
                            </div>
                            <p class="mt-1.5 text-xs text-ink-500 tabular-nums">
                                {{ rupiah($campaign->collected_amount) }} dari {{ rupiah($campaign->target_amount) }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            @if ($campaign->isEditable())
                                <a href="{{ route('pengaju.kampanye.edit', $campaign) }}" class="dt-btn-secondary">Ubah</a>
                            @elseif ($campaign->isPublished())
                                <a href="{{ route('kampanye.transparansi', $campaign) }}" class="dt-btn-secondary">Ledger</a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
