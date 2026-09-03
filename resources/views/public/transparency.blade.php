@extends('layouts.app')
@section('title', 'Transparansi — '.$campaign->title)

@php
    $terkumpul = $campaign->collected_amount;
    $tercairkan = $campaign->disbursed_amount;
    $saldo = $campaign->remainingBalance();
    $totalBar = max(1, $terkumpul);
@endphp

@section('content')
<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <nav class="text-sm text-ink-500" aria-label="Breadcrumb">
            <a href="{{ route('transparansi') }}" class="hover:text-brand-700">Ledger publik</a>
            <span class="mx-2" aria-hidden="true">/</span>
            <a href="{{ route('kampanye.show', $campaign) }}" class="hover:text-brand-700">{{ Str::limit($campaign->title, 40) }}</a>
        </nav>
        @if (auth()->check() && (auth()->id() === $campaign->user_id || auth()->user()->isPengaju()))
            <a href="{{ route('pengaju.kampanye.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-brand-900 transition-colors">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                Kembali ke Kampanye Saya
            </a>
        @endif
    </div>

    <header>
        <h1 class="text-3xl font-extrabold tracking-tight text-ink-900">Ke mana dana kampanye ini pergi</h1>
        <p class="mt-2 text-lg text-ink-600">{{ $campaign->title }}</p>
    </header>

    {{-- Alur dana: satu batang, tiga bagian. Menjawab pertanyaan utama donatur. --}}
    <section class="dt-card mt-8 p-5 sm:p-6">
        <h2 class="text-lg font-bold text-ink-900">Posisi dana saat ini</h2>

        <div class="mt-5 flex h-4 w-full gap-0.5 overflow-hidden rounded-full bg-ink-100"
             role="img" aria-label="Dari {{ rupiah($terkumpul) }} terkumpul, {{ rupiah($tercairkan) }} sudah dicairkan dan {{ rupiah($saldo) }} masih tertahan.">
            <div class="h-full rounded-l-full bg-brand-600" style="width: {{ round($tercairkan / $totalBar * 100, 2) }}%"></div>
            <div class="h-full rounded-r-full bg-amber-400" style="width: {{ round($saldo / $totalBar * 100, 2) }}%"></div>
        </div>

        <dl class="mt-5 grid gap-4 sm:grid-cols-3">
            <div>
                <dt class="flex items-center gap-2 text-xs font-semibold tracking-wide text-ink-500 uppercase">
                    <span class="h-2.5 w-2.5 rounded-full bg-ink-300"></span> Terkumpul
                </dt>
                <dd class="mt-1.5 text-xl font-extrabold text-ink-900 tabular-nums">{{ rupiah($terkumpul) }}</dd>
                <dd class="text-xs text-ink-500">dari {{ number_format($donationCount, 0, ',', '.') }} donatur</dd>
            </div>
            <div>
                <dt class="flex items-center gap-2 text-xs font-semibold tracking-wide text-ink-500 uppercase">
                    <span class="h-2.5 w-2.5 rounded-full bg-brand-600"></span> Sudah dicairkan
                </dt>
                <dd class="mt-1.5 text-xl font-extrabold text-brand-700 tabular-nums">{{ rupiah($tercairkan) }}</dd>
                <dd class="text-xs text-ink-500">lewat persetujuan admin</dd>
            </div>
            <div>
                <dt class="flex items-center gap-2 text-xs font-semibold tracking-wide text-ink-500 uppercase">
                    <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span> Masih tertahan
                </dt>
                <dd class="mt-1.5 text-xl font-extrabold text-amber-700 tabular-nums">{{ rupiah($saldo) }}</dd>
                <dd class="text-xs text-ink-500">belum bisa disentuh pengaju</dd>
            </div>
        </dl>
    </section>

    {{-- Tahapan --}}
    <section class="dt-card mt-6 p-5 sm:p-6">
        <h2 class="text-lg font-bold text-ink-900">Tahapan pencairan</h2>
        <p class="mt-1 text-sm text-ink-600">
            Tahap berikutnya terkunci sampai nota tahap sebelumnya masuk dan diverifikasi admin.
        </p>

        @if ($campaign->isFrontLoaded())
            <div class="mt-4 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                <svg class="mt-0.5 h-4.5 w-4.5 shrink-0 text-amber-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4.5m0 3.5h.01M10.3 4.2 2.9 17.1a1.9 1.9 0 0 0 1.7 2.9h14.8a1.9 1.9 0 0 0 1.7-2.9L13.7 4.2a1.9 1.9 0 0 0-3.4 0Z"/></svg>
                <p class="text-sm leading-relaxed text-amber-900">
                    <strong>Tahap pertama menyerap {{ $campaign->firstMilestoneShare() }}% dari total dana.</strong>
                    Kadang memang begitu sifat pekerjaannya, tapi artinya perlindungan bertahap di
                    kampanye ini lebih tipis daripada kampanye yang tahapannya lebih merata.
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
                    'flex flex-wrap items-center gap-4 rounded-xl border p-4',
                    'border-brand-200 bg-brand-50/50' => $done,
                    'border-amber-200 bg-amber-50/50' => $active,
                    'border-ink-200' => ! $done && ! $active,
                ])>
                    <span @class([
                        'grid h-9 w-9 shrink-0 place-items-center rounded-full text-sm font-bold',
                        'bg-brand-600 text-white' => $done,
                        'bg-amber-500 text-white' => $active,
                        'bg-ink-200 text-ink-500' => ! $done && ! $active,
                    ])>{{ $milestone->sequence }}</span>

                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-ink-900">{{ $milestone->title }}</p>
                        <p class="mt-0.5 text-xs text-ink-500">{{ $milestone->statusLabel() }}</p>
                    </div>

                    <p class="font-bold text-ink-900 tabular-nums">{{ rupiah($milestone->amount) }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Riwayat pencairan --}}
    <section class="dt-card mt-6 overflow-hidden">
        <div class="p-5 sm:p-6">
            <h2 class="text-lg font-bold text-ink-900">Riwayat pencairan</h2>
            <p class="mt-1 text-sm text-ink-600">Setiap baris di bawah pernah melewati meja admin.</p>
        </div>

        @if ($disbursements->isEmpty())
            <p class="border-t border-ink-100 px-5 py-8 text-center text-sm text-ink-500 sm:px-6">
                Belum ada dana yang dicairkan dari kampanye ini.
            </p>
        @else
            <div class="overflow-x-auto border-t border-ink-100">
                <table class="dt-table min-w-[760px]">
                    <thead>
                        <tr>
                            <th scope="col">Nomor</th>
                            <th scope="col">Tahap</th>
                            <th scope="col">Keperluan</th>
                            <th scope="col">Rekening tujuan</th>
                            <th scope="col" class="text-right">Nominal</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($disbursements as $d)
                            <tr>
                                <td class="font-mono text-xs whitespace-nowrap text-ink-600">{{ $d->reference }}</td>
                                <td class="whitespace-nowrap">Tahap {{ $d->milestone?->sequence ?? '—' }}</td>
                                <td class="max-w-xs text-ink-700">{{ Str::limit($d->purpose, 90) }}</td>
                                <td class="text-xs whitespace-nowrap text-ink-600">
                                    {{ $d->maskedPayee() ?? 'Tidak tercatat' }}
                                </td>
                                <td class="text-right font-semibold tabular-nums">{{ rupiah($d->amount) }}</td>
                                <td>
                                    <x-badge :tone="$d->status === 'released' ? 'success' : 'info'">{{ $d->statusLabel() }}</x-badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- LPJ dengan bukti nota --}}
    <section class="dt-card mt-6 p-5 sm:p-6">
        <h2 class="text-lg font-bold text-ink-900">Laporan pengeluaran &amp; bukti nota</h2>
        <p class="mt-1 text-sm text-ink-600">
            Bukti nota sengaja dibuka untuk publik — di sinilah klaim transparansi diuji.
        </p>

        @if ($expenses->isEmpty())
            <p class="mt-6 rounded-xl border border-dashed border-ink-300 px-5 py-8 text-center text-sm text-ink-500">
                Belum ada laporan pengeluaran yang diunggah.
            </p>
        @else
            <ul class="mt-5 divide-y divide-ink-100">
                @foreach ($expenses as $expense)
                    <li class="flex flex-wrap items-start gap-4 py-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-ink-900">{{ $expense->title }}</p>
                                <x-badge :tone="match($expense->status) {
                                    'verified' => 'success',
                                    'rejected' => 'danger',
                                    default => 'warning',
                                }">{{ $expense->statusLabel() }}</x-badge>
                            </div>

                            @if ($expense->description)
                                <p class="mt-1 text-sm leading-relaxed text-ink-600">{{ $expense->description }}</p>
                            @endif

                            <p class="mt-1.5 text-xs text-ink-500">
                                {{ $expense->spent_on->translatedFormat('d F Y') }}
                                @if ($expense->item)
                                    &middot; RAB: {{ $expense->item->name }}
                                @endif
                            </p>
                        </div>

                        <div class="text-right">
                            <p class="font-bold text-ink-900 tabular-nums">{{ rupiah($expense->amount) }}</p>
                            @if ($expense->receipt_path)
                                <a href="{{ route('berkas.lpj', $expense) }}" target="_blank" rel="noopener"
                                   class="dt-link mt-1 inline-block text-xs">Lihat nota &rarr;</a>
                            @else
                                <span class="mt-1 block text-xs text-ink-400">Tanpa lampiran</span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Realisasi terhadap RAB --}}
    <section class="dt-card mt-6 overflow-hidden">
        <div class="p-5 sm:p-6">
            <h2 class="text-lg font-bold text-ink-900">Realisasi terhadap RAB</h2>
            <p class="mt-1 text-sm text-ink-600">Rencana versus yang benar-benar dilaporkan terpakai.</p>
        </div>

        <div class="overflow-x-auto border-t border-ink-100">
            <table class="dt-table min-w-[560px]">
                <thead>
                    <tr>
                        <th scope="col">Item RAB</th>
                        <th scope="col" class="text-right">Direncanakan</th>
                        <th scope="col" class="text-right">Dilaporkan</th>
                        <th scope="col" class="text-right">Selisih</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($campaign->items as $item)
                        @php
                            $realized = $item->realizedAmount();
                            $diff = $item->subtotal - $realized;
                        @endphp
                        <tr>
                            <td class="font-medium text-ink-900">{{ $item->name }}</td>
                            <td class="text-right tabular-nums">{{ rupiah($item->subtotal) }}</td>
                            <td class="text-right tabular-nums">{{ rupiah($realized) }}</td>
                            <td class="text-right font-semibold tabular-nums {{ $diff < 0 ? 'text-rose-600' : 'text-ink-500' }}">
                                {{ $diff < 0 ? '+' : '' }}{{ rupiah(abs($diff)) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-8 rounded-2xl border border-ink-200 bg-white p-5">
        <h2 class="text-sm font-bold text-ink-900">Apa yang halaman ini tidak jamin</h2>
        <p class="mt-2 text-sm leading-relaxed text-ink-600">
            Sistem memastikan setiap angka konsisten dengan tabel transaksi dan setiap perubahan tercatat
            dalam rantai hash. Yang tidak bisa dijamin oleh perangkat lunak mana pun: keaslian nota fisik
            yang diunggah. Verifikasi nota tetap dilakukan manusia — admin — dan itu memang batas jujurnya.
        </p>
    </div>
</div>
@endsection
