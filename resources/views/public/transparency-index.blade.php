@extends('layouts.app')
@section('title', 'Ledger publik')
@section('description', 'Total terkumpul, tercairkan, dan sisa saldo seluruh kampanye DonasiTrust — dihitung langsung dari tabel transaksi.')

@php
    // --- Persiapan data grafik (dihitung di PHP; tidak ada pustaka chart eksternal) ---
    $points   = $chart->all();
    $maxValue = max(1, (int) collect($points)->max('amount'));
    $count    = max(1, count($points));

    $w = 720; $h = 200; $padT = 12; $padB = 26;
    $plotH = $h - $padT - $padB;

    $x = fn ($i) => $count > 1 ? round($i * ($w / ($count - 1)), 2) : 0;
    $y = fn ($v) => round($padT + $plotH - ($v / $maxValue) * $plotH, 2);

    $line = collect($points)->map(fn ($p, $i) => ($i === 0 ? 'M' : 'L').$x($i).' '.$y($p['amount']))->implode(' ');
    $area = $line.' L'.$x($count - 1).' '.($padT + $plotH).' L0 '.($padT + $plotH).' Z';

    $peakIndex = collect($points)->pluck('amount')->search($maxValue);
@endphp

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

    <header class="max-w-3xl">
        <span class="dt-badge bg-brand-100 text-brand-800">Data langsung dari tabel transaksi</span>
        <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-ink-900 sm:text-4xl">Ledger publik DonasiTrust</h1>
        <p class="mt-3 text-ink-600">
            Angka di halaman ini tidak diketik manual. Semuanya diagregasi saat halaman dimuat dari
            tabel donasi, pencairan, dan laporan pengeluaran — sehingga tidak ada versi &ldquo;untuk publik&rdquo;
            yang berbeda dari versi internal.
        </p>
    </header>

    {{-- KPI: angka, bukan grafik, karena tugasnya menyampaikan satu nilai --}}
    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Total terkumpul" :value="rupiah($totals['terkumpul'])"
                :hint="number_format($totals['transaksi'], 0, ',', '.').' transaksi lunas'" tone="success" />
        <x-stat label="Total tercairkan" :value="rupiah($totals['tercairkan'])"
                hint="Sudah disetujui admin & dilepas" />
        <x-stat label="Sisa saldo" :value="rupiah($totals['saldo'])"
                hint="Belum dicairkan, masih di platform" tone="warning" />
        <x-stat label="Dilaporkan dengan nota" :value="rupiah($totals['dilaporkan'])"
                hint="LPJ terverifikasi admin" />
    </div>

    {{-- Grafik --}}
    <section class="dt-card mt-6 p-5 sm:p-6" x-data="{ aktif: null, tabel: false, data: {{ Illuminate\Support\Js::from($points) }} }">
        <div class="flex flex-wrap items-baseline justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-ink-900">Donasi masuk per hari</h2>
                <p class="mt-0.5 text-sm text-ink-500">30 hari terakhir &middot; puncak {{ rupiah($maxValue) }}</p>
            </div>
            <button type="button" x-on:click="tabel = ! tabel" :aria-expanded="tabel.toString()"
                    aria-controls="tabel-donasi-harian" class="dt-link text-sm">
                <span x-show="!tabel">Lihat sebagai tabel</span>
                <span x-show="tabel" x-cloak>Sembunyikan tabel</span>
            </button>
        </div>

        <div class="relative mt-5">
            <svg viewBox="0 0 {{ $w }} {{ $h }}" class="h-52 w-full" role="img"
                 aria-label="Grafik donasi harian 30 hari terakhir. Nilai tertinggi {{ rupiah($maxValue) }}.">
                <defs>
                    <linearGradient id="grad-donasi" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#069370" stop-opacity="0.22"/>
                        <stop offset="100%" stop-color="#069370" stop-opacity="0"/>
                    </linearGradient>
                </defs>

                {{-- Grid resesif: hanya 3 garis, cukup untuk memberi skala --}}
                @foreach ([0, 0.5, 1] as $frac)
                    <line x1="0" x2="{{ $w }}"
                          y1="{{ $padT + $plotH * $frac }}" y2="{{ $padT + $plotH * $frac }}"
                          stroke="#eceef2" stroke-width="1"/>
                @endforeach

                <path d="{{ $area }}" fill="url(#grad-donasi)"/>
                <path d="{{ $line }}" fill="none" stroke="#069370" stroke-width="2"
                      stroke-linejoin="round" stroke-linecap="round"/>

                {{-- Penanda puncak saja — bukan label di setiap titik --}}
                @if ($maxValue > 1)
                    <circle cx="{{ $x($peakIndex) }}" cy="{{ $y($maxValue) }}" r="4.5"
                            fill="#069370" stroke="#ffffff" stroke-width="2"/>
                @endif

                {{-- Kolom hover: target sentuh jauh lebih besar dari mark-nya --}}
                @foreach ($points as $i => $p)
                    <rect x="{{ max(0, $x($i) - $w / $count / 2) }}" y="0"
                          width="{{ $w / $count }}" height="{{ $h }}" fill="transparent"
                          x-on:mouseenter="aktif = {{ $i }}" x-on:mouseleave="aktif = null"></rect>
                @endforeach

                <line x-show="aktif !== null" x-cloak
                      :x1="aktif * {{ $count > 1 ? round($w / ($count - 1), 2) : 0 }}"
                      :x2="aktif * {{ $count > 1 ? round($w / ($count - 1), 2) : 0 }}"
                      y1="{{ $padT }}" y2="{{ $padT + $plotH }}"
                      stroke="#b0b8c8" stroke-width="1" stroke-dasharray="3 3"/>

                <circle x-show="aktif !== null" x-cloak r="4"
                        fill="#ffffff" stroke="#069370" stroke-width="2"
                        :cx="aktif * {{ $count > 1 ? round($w / ($count - 1), 2) : 0 }}"
                        :cy="aktif !== null ? {{ $padT + $plotH }} - (data[aktif].amount / {{ $maxValue }}) * {{ $plotH }} : 0"/>
            </svg>

            {{-- Tooltip HTML, diposisikan proporsional terhadap lebar plot --}}
            <div x-show="aktif !== null" x-cloak
                 class="pointer-events-none absolute top-0 z-10 -translate-x-1/2 rounded-lg bg-ink-900 px-3 py-2 text-xs whitespace-nowrap text-white shadow-lg"
                 :style="`left: ${(aktif / {{ max(1, $count - 1) }}) * 100}%`">
                <span class="block font-semibold" x-text="aktif !== null ? data[aktif].label : ''"></span>
                <span class="text-ink-300" x-text="aktif !== null ? data[aktif].formatted : ''"></span>
            </div>
        </div>

        <div class="mt-2 flex justify-between text-xs text-ink-400">
            <span>{{ \Carbon\Carbon::parse($points[0]['date'])->translatedFormat('d M') }}</span>
            <span>{{ \Carbon\Carbon::parse($points[$count - 1]['date'])->translatedFormat('d M') }}</span>
        </div>

        {{-- Alternatif tabel: syarat aksesibilitas, bukan pelengkap --}}
        <div id="tabel-donasi-harian" x-show="tabel" x-cloak class="mt-5 max-h-72 overflow-y-auto rounded-xl border border-ink-200">
            <table class="dt-table">
                <caption class="sr-only">Donasi masuk per hari, 30 hari terakhir</caption>
                <thead class="sticky top-0 bg-white"><tr><th scope="col">Tanggal</th><th scope="col" class="text-right">Nominal</th></tr></thead>
                <tbody>
                    @foreach ($points as $p)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($p['date'])->translatedFormat('d F Y') }}</td>
                            <td class="text-right tabular-nums">{{ rupiah($p['amount']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Status rantai audit --}}
    <section @class([
        'mt-6 rounded-2xl border p-5 sm:p-6',
        'border-brand-200 bg-brand-50' => $chainStatus['valid'],
        'border-rose-300 bg-rose-50' => ! $chainStatus['valid'],
    ])>
        <div class="flex items-start gap-3.5">
            <span @class([
                'grid h-10 w-10 shrink-0 place-items-center rounded-xl text-white',
                'bg-brand-600' => $chainStatus['valid'],
                'bg-rose-600' => ! $chainStatus['valid'],
            ]) aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    @if ($chainStatus['valid'])
                        <path d="m5 12 4.5 4.5L19 7.5"/>
                    @else
                        <path d="M12 8v5m0 3.5h.01"/><circle cx="12" cy="12" r="9"/>
                    @endif
                </svg>
            </span>
            <div>
                <h2 @class(['font-bold', 'text-brand-900' => $chainStatus['valid'], 'text-rose-900' => ! $chainStatus['valid']])>
                    {{ $chainStatus['valid'] ? 'Rantai jejak audit utuh' : 'Rantai jejak audit terputus' }}
                </h2>
                <p class="mt-1 text-sm leading-relaxed {{ $chainStatus['valid'] ? 'text-brand-900/80' : 'text-rose-900/80' }}">
                    {{ number_format($chainStatus['checked'], 0, ',', '.') }} entri diperiksa ulang saat halaman ini dimuat.
                    @if ($chainStatus['valid'])
                        Setiap entri masih cocok dengan hash entri sebelumnya — belum ada catatan lama yang diubah.
                    @else
                        Ketidakcocokan pertama ada di entri #{{ $chainStatus['broken_at'] }}. {{ $chainStatus['reason'] }}
                    @endif
                </p>
            </div>
        </div>
    </section>

    {{-- Rincian per kampanye --}}
    <section class="mt-10">
        <h2 class="text-2xl font-extrabold tracking-tight text-ink-900">Rincian per kampanye</h2>

        @if ($campaigns->isEmpty())
            <x-empty-state class="mt-5" title="Belum ada kampanye tayang" />
        @else
            <div class="dt-card mt-5 overflow-x-auto">
                <table class="dt-table min-w-[720px]">
                    <thead>
                        <tr>
                            <th scope="col">Kampanye</th>
                            <th scope="col" class="text-right">Terkumpul</th>
                            <th scope="col" class="text-right">Tercairkan</th>
                            <th scope="col" class="text-right">Sisa saldo</th>
                            <th scope="col" class="text-right">Donatur</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($campaigns as $campaign)
                            <tr>
                                <td>
                                    <p class="font-semibold text-ink-900">{{ $campaign->title }}</p>
                                    <p class="mt-0.5 text-xs text-ink-500">{{ $campaign->categoryLabel() }}</p>
                                </td>
                                <td class="text-right font-semibold tabular-nums">{{ rupiah($campaign->collected_amount) }}</td>
                                <td class="text-right tabular-nums">{{ rupiah($campaign->disbursed_amount) }}</td>
                                <td class="text-right tabular-nums {{ $campaign->remainingBalance() > 0 ? 'text-amber-700' : 'text-ink-500' }}">
                                    {{ rupiah($campaign->remainingBalance()) }}
                                </td>
                                <td class="text-right tabular-nums">{{ $campaign->donatur_count }}</td>
                                <td class="text-right">
                                    <a href="{{ route('kampanye.transparansi', $campaign) }}" class="dt-link text-sm whitespace-nowrap">Rincian &rarr;</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
