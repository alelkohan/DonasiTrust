@extends('layouts.app')
@section('title', 'Transparansi — '.$campaign->title)

@php
    $terkumpul = $campaign->collected_amount;
    $tercairkan = $campaign->disbursed_amount;
    $saldo = $campaign->remainingBalance();
    $totalBar = max(1, $terkumpul);
@endphp

@section('content')
<div class="w-full py-8">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <nav class="text-xs font-bold text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('transparansi') }}" class="hover:text-[#99ff04] transition-colors">Ledger Publik</a>
            <span class="mx-2 text-slate-600" aria-hidden="true">/</span>
            <a href="{{ route('kampanye.show', $campaign) }}" class="hover:text-white transition-colors text-slate-200">{{ Str::limit($campaign->title, 40) }}</a>
        </nav>
        @if (auth()->check() && (auth()->id() === $campaign->user_id || auth()->user()->isPengaju()))
            <a href="{{ route('pengaju.kampanye.index') }}" class="inline-flex items-center gap-1.5 text-xs font-black text-[#99ff04] hover:text-[#84e000] transition-colors">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                Kembali ke Kampanye Saya
            </a>
        @endif
    </div>

    <header class="max-w-3xl mb-8">
        <span class="inline-block rounded bg-cyan-400 px-3 py-1 text-xs font-black uppercase tracking-wider text-black mb-3">
            Real-time Audit Trail
        </span>
        <h1 class="text-3xl font-black tracking-tight text-white sm:text-4xl">Ke mana dana kampanye ini pergi</h1>
        <p class="mt-2 text-base text-slate-300">{{ $campaign->title }}</p>
    </header>

    {{-- Alur dana: satu batang, tiga bagian. --}}
    <section class="rounded-3xl border border-white/10 bg-[#1b182a] p-6 shadow-xl mb-6">
        <h2 class="text-lg font-black text-white">Posisi dana saat ini</h2>

        <div class="mt-5 flex h-4 w-full gap-0.5 overflow-hidden rounded-full bg-[#12101c] p-0.5 border border-white/5"
             role="img" aria-label="Dari {{ rupiah($terkumpul) }} terkumpul, {{ rupiah($tercairkan) }} sudah dicairkan dan {{ rupiah($saldo) }} masih tertahan.">
            <div class="h-full rounded-l-full bg-[#99ff04]" style="width: {{ round($tercairkan / $totalBar * 100, 2) }}%"></div>
            <div class="h-full rounded-r-full bg-amber-400" style="width: {{ round($saldo / $totalBar * 100, 2) }}%"></div>
        </div>

        <dl class="mt-6 grid gap-6 sm:grid-cols-3">
            <div class="rounded-2xl border border-white/5 bg-[#231f36] p-4">
                <dt class="flex items-center gap-2 text-xs font-extrabold tracking-wide text-slate-400 uppercase">
                    <span class="h-2.5 w-2.5 rounded-full bg-slate-500"></span> Terkumpul
                </dt>
                <dd class="mt-2 text-2xl font-black text-white tabular-nums">{{ rupiah($terkumpul) }}</dd>
                <dd class="mt-1 text-xs text-slate-400">dari {{ number_format($donationCount, 0, ',', '.') }} donatur</dd>
            </div>
            <div class="rounded-2xl border border-white/5 bg-[#231f36] p-4">
                <dt class="flex items-center gap-2 text-xs font-extrabold tracking-wide text-slate-400 uppercase">
                    <span class="h-2.5 w-2.5 rounded-full bg-[#99ff04]"></span> Sudah dicairkan
                </dt>
                <dd class="mt-2 text-2xl font-black text-[#99ff04] tabular-nums">{{ rupiah($tercairkan) }}</dd>
                <dd class="mt-1 text-xs text-slate-400">lewat persetujuan admin</dd>
            </div>
            <div class="rounded-2xl border border-white/5 bg-[#231f36] p-4">
                <dt class="flex items-center gap-2 text-xs font-extrabold tracking-wide text-slate-400 uppercase">
                    <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span> Masih tertahan
                </dt>
                <dd class="mt-2 text-2xl font-black text-amber-300 tabular-nums">{{ rupiah($saldo) }}</dd>
                <dd class="mt-1 text-xs text-slate-400">belum bisa disentuh pengaju</dd>
            </div>
        </dl>
    </section>

    {{-- Tahapan Pencairan --}}
    <section class="rounded-3xl border border-white/10 bg-[#1b182a] p-6 shadow-xl mb-6">
        <h2 class="text-lg font-black text-white">Tahapan pencairan</h2>
        <p class="mt-1 text-xs text-slate-400">
            Tahap berikutnya terkunci sampai nota tahap sebelumnya masuk dan diverifikasi admin.
        </p>

        @if ($campaign->isFrontLoaded())
            <div class="mt-4 flex items-start gap-3 rounded-2xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-amber-300">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 9v4.5m0 3.5h.01M10.3 4.2 2.9 17.1a1.9 1.9 0 0 0 1.7 2.9h14.8a1.9 1.9 0 0 0 1.7-2.9L13.7 4.2a1.9 1.9 0 0 0-3.4 0Z"/></svg>
                <p class="text-xs leading-relaxed">
                    <strong>Tahap pertama menyerap {{ $campaign->firstMilestoneShare() }}% dari total dana.</strong>
                    Perlindungan bertahap di kampanye ini lebih sensitif karena alokasi dana awal yang besar.
                </p>
            </div>
        @endif

        <ol class="mt-5 space-y-3">
            @foreach ($campaign->milestones as $milestone)
                @php
                    $done = in_array($milestone->status, ['disbursed', 'reported'], true);
                    $active = in_array($milestone->status, ['available', 'requested', 'approved'], true);
                @endphp
                <li @class([
                    'flex flex-wrap items-center gap-4 rounded-2xl border p-4 transition-all',
                    'border-[#99ff04]/30 bg-[#99ff04]/5' => $done,
                    'border-amber-500/30 bg-amber-500/5' => $active,
                    'border-white/10 bg-[#231f36]' => ! $done && ! $active,
                ])>
                    <span @class([
                        'grid h-9 w-9 shrink-0 place-items-center rounded-xl text-xs font-black',
                        'bg-[#99ff04] text-black' => $done,
                        'bg-amber-400 text-black' => $active,
                        'bg-white/10 text-slate-400' => ! $done && ! $active,
                    ])>{{ $milestone->sequence }}</span>

                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-white text-sm">{{ $milestone->title }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">{{ $milestone->statusLabel() }}</p>
                    </div>

                    <p class="font-black text-white tabular-nums text-sm">{{ rupiah($milestone->amount) }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Riwayat pencairan --}}
    <section class="rounded-3xl border border-white/10 bg-[#1b182a] shadow-xl overflow-hidden mb-6">
        <div class="p-6">
            <h2 class="text-lg font-black text-white">Riwayat pencairan</h2>
            <p class="mt-1 text-xs text-slate-400">Setiap baris pencairan disetujui manual oleh admin platform.</p>
        </div>

        @if ($disbursements->isEmpty())
            <p class="border-t border-white/10 px-6 py-8 text-center text-xs text-slate-400">
                Belum ada dana yang dicairkan dari kampanye ini.
            </p>
        @else
            <div class="overflow-x-auto border-t border-white/10">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-white/10 bg-[#231f36] text-slate-400 font-extrabold uppercase tracking-wider">
                            <th class="py-3 px-4">Nomor</th>
                            <th class="py-3 px-4">Tahap</th>
                            <th class="py-3 px-4">Keperluan</th>
                            <th class="py-3 px-4">Rekening Tujuan</th>
                            <th class="py-3 px-4 text-right">Nominal</th>
                            <th class="py-3 px-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach ($disbursements as $d)
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="py-3.5 px-4 font-mono text-slate-300 whitespace-nowrap">{{ $d->reference }}</td>
                                <td class="py-3.5 px-4 whitespace-nowrap text-white font-bold">Tahap {{ $d->milestone?->sequence ?? '—' }}</td>
                                <td class="py-3.5 px-4 max-w-xs text-slate-300">{{ Str::limit($d->purpose, 90) }}</td>
                                <td class="py-3.5 px-4 whitespace-nowrap text-slate-400">
                                    {{ $d->maskedPayee() ?? 'Tidak tercatat' }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-black text-[#99ff04] tabular-nums">{{ rupiah($d->amount) }}</td>
                                <td class="py-3.5 px-4">
                                    <span class="rounded bg-[#99ff04]/20 px-2 py-0.5 text-[10px] font-black text-[#99ff04] uppercase">
                                        {{ $d->statusLabel() }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- LPJ dengan bukti nota --}}
    <section class="rounded-3xl border border-white/10 bg-[#1b182a] p-6 shadow-xl mb-6">
        <h2 class="text-lg font-black text-white">Laporan pengeluaran &amp; bukti nota</h2>
        <p class="mt-1 text-xs text-slate-400">
            Bukti nota fisik dibuka terbuka untuk publik untuk pengujian akuntabilitas.
        </p>

        @if ($expenses->isEmpty())
            <p class="mt-6 rounded-2xl border border-dashed border-white/15 bg-[#231f36] px-5 py-8 text-center text-xs text-slate-400">
                Belum ada laporan pengeluaran yang diunggah.
            </p>
        @else
            <ul class="mt-5 divide-y divide-white/5">
                @foreach ($expenses as $expense)
                    <li class="flex flex-wrap items-start gap-4 py-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-bold text-white text-sm">{{ $expense->title }}</p>
                                <span class="rounded bg-[#99ff04] px-2 py-0.5 text-[10px] font-black text-black uppercase">
                                    {{ $expense->statusLabel() }}
                                </span>
                            </div>

                            @if ($expense->description)
                                <p class="mt-1 text-xs leading-relaxed text-slate-300">{{ $expense->description }}</p>
                            @endif

                            <p class="mt-1.5 text-[11px] text-slate-400">
                                {{ $expense->spent_on->translatedFormat('d F Y') }}
                                @if ($expense->item)
                                    &middot; RAB: {{ $expense->item->name }}
                                @endif
                            </p>
                        </div>

                        <div class="text-right">
                            <p class="font-black text-white tabular-nums text-sm">{{ rupiah($expense->amount) }}</p>
                            @if ($expense->receipt_path)
                                <a href="{{ route('berkas.lpj', $expense) }}" target="_blank" rel="noopener"
                                   class="mt-1 inline-block text-xs font-bold text-[#99ff04] hover:underline">Lihat nota &rarr;</a>
                            @else
                                <span class="mt-1 block text-xs text-slate-500">Tanpa lampiran</span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <div class="rounded-3xl border border-white/10 bg-[#1b182a] p-6">
        <h2 class="text-sm font-extrabold text-white">Batasan Verifikasi Otomatis Perangkat Lunak</h2>
        <p class="mt-2 text-xs leading-relaxed text-slate-300">
            Sistem memastikan setiap angka konsisten dengan tabel transaksi dan setiap perubahan tercatat
            dalam rantai hash HMAC-SHA256. Keaslian fisik kertas kuitansi di lapangan dikonfirmasi manual oleh admin.
        </p>
    </div>
</div>
@endsection
