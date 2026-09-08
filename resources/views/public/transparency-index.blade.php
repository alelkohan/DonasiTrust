@extends('layouts.app')
@section('title', 'Ledger Publik · DonasiTrust')
@section('description', 'Total terkumpul, tercairkan, dan sisa saldo seluruh kampanye DonasiTrust — dihitung langsung dari tabel transaksi.')

@php
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
<div class="w-full py-8">

    <header class="max-w-3xl mb-8">
        <span class="inline-block rounded bg-[#99ff04] px-3 py-1 text-xs font-black uppercase tracking-wider text-black mb-3">
            HMAC Public Ledger
        </span>
        <h1 class="text-3xl font-black tracking-tight text-white sm:text-4xl">Ledger Publik DonasiTrust</h1>
        <p class="mt-2 text-sm text-slate-300 leading-relaxed">
            Data di halaman ini tersinkronisasi langsung dari basis data transaksi, pencairan, dan kuitansi nota — tanpa intervensi penyuntingan manual.
        </p>
    </header>

    {{-- KPI Stat Cards --}}
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <div class="rounded-3xl border border-white/10 bg-[#1b182a] p-6 shadow-xl">
            <span class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">Total Terkumpul</span>
            <p class="mt-2 text-2xl font-black text-[#99ff04] tabular-nums">{{ rupiah($totals['terkumpul']) }}</p>
            <p class="mt-1 text-[11px] text-slate-400">{{ number_format($totals['transaksi'], 0, ',', '.') }} transaksi lunas</p>
        </div>

        <div class="rounded-3xl border border-white/10 bg-[#1b182a] p-6 shadow-xl">
            <span class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">Total Tercairkan</span>
            <p class="mt-2 text-2xl font-black text-cyan-400 tabular-nums">{{ rupiah($totals['tercairkan']) }}</p>
            <p class="mt-1 text-[11px] text-slate-400">Pencairan disetujui admin</p>
        </div>

        <div class="rounded-3xl border border-white/10 bg-[#1b182a] p-6 shadow-xl">
            <span class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">Sisa Saldo</span>
            <p class="mt-2 text-2xl font-black text-amber-300 tabular-nums">{{ rupiah($totals['saldo']) }}</p>
            <p class="mt-1 text-[11px] text-slate-400">Masih tertahan di platform</p>
        </div>

        <div class="rounded-3xl border border-white/10 bg-[#1b182a] p-6 shadow-xl">
            <span class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">Dilaporkan Dengan Nota</span>
            <p class="mt-2 text-2xl font-black text-purple-400 tabular-nums">{{ rupiah($totals['dilaporkan']) }}</p>
            <p class="mt-1 text-[11px] text-slate-400">LPJ fisik terverifikasi</p>
        </div>
    </div>

    {{-- Grafik Donasi Harian --}}
    <section class="rounded-3xl border border-white/10 bg-[#1b182a] p-6 shadow-xl mb-8" x-data="{ aktif: null, tabel: false, data: {{ Illuminate\Support\Js::from($points) }} }">
        <div class="flex flex-wrap items-baseline justify-between gap-3 mb-4">
            <div>
                <h2 class="text-lg font-black text-white">Donasi Masuk Per Hari</h2>
                <p class="mt-0.5 text-xs text-slate-400">30 hari terakhir &middot; Puncak {{ rupiah($maxValue) }}</p>
            </div>
            <button type="button" x-on:click="tabel = ! tabel" class="text-xs font-black text-[#99ff04] hover:underline">
                <span x-show="!tabel">Lihat Sebagai Tabel</span>
                <span x-show="tabel" x-cloak>Sembunyikan Tabel</span>
            </button>
        </div>

        <div class="relative mt-5">
            <svg viewBox="0 0 {{ $w }} {{ $h }}" class="h-56 w-full" role="img" aria-label="Grafik donasi harian">
                <defs>
                    <linearGradient id="grad-donasi" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#99ff04" stop-opacity="0.3"/>
                        <stop offset="100%" stop-color="#99ff04" stop-opacity="0"/>
                    </linearGradient>
                </defs>

                @foreach ([0, 0.5, 1] as $frac)
                    <line x1="0" x2="{{ $w }}" y1="{{ $padT + $plotH * $frac }}" y2="{{ $padT + $plotH * $frac }}" stroke="rgba(255,255,255,0.08)" stroke-width="1"/>
                @endforeach

                <path d="{{ $area }}" fill="url(#grad-donasi)"/>
                <path d="{{ $line }}" fill="none" stroke="#99ff04" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>

                @if ($maxValue > 1)
                    <circle cx="{{ $x($peakIndex) }}" cy="{{ $y($maxValue) }}" r="5" fill="#99ff04" stroke="#12101c" stroke-width="2"/>
                @endif

                @foreach ($points as $i => $p)
                    <rect x="{{ max(0, $x($i) - $w / $count / 2) }}" y="0"
                          width="{{ $w / $count }}" height="{{ $h }}" fill="transparent"
                          x-on:mouseenter="aktif = {{ $i }}" x-on:mouseleave="aktif = null"></rect>
                @endforeach

                <line x-show="aktif !== null" x-cloak
                      :x1="aktif * {{ $count > 1 ? round($w / ($count - 1), 2) : 0 }}"
                      :x2="aktif * {{ $count > 1 ? round($w / ($count - 1), 2) : 0 }}"
                      y1="{{ $padT }}" y2="{{ $padT + $plotH }}"
                      stroke="rgba(255,255,255,0.3)" stroke-width="1" stroke-dasharray="3 3"/>
            </svg>

            <div x-show="aktif !== null" x-cloak
                 class="pointer-events-none absolute top-0 z-10 -translate-x-1/2 rounded-xl bg-[#231f36] border border-white/10 px-3 py-2 text-xs whitespace-nowrap text-white shadow-2xl"
                 :style="`left: ${(aktif / {{ max(1, $count - 1) }}) * 100}%`">
                <span class="block font-bold text-slate-300" x-text="aktif !== null ? data[aktif].label : ''"></span>
                <span class="text-[#99ff04] font-black" x-text="aktif !== null ? data[aktif].formatted : ''"></span>
            </div>
        </div>

        <div class="mt-3 flex justify-between text-xs text-slate-500 font-bold">
            <span>{{ \Carbon\Carbon::parse($points[0]['date'])->translatedFormat('d M Y') }}</span>
            <span>{{ \Carbon\Carbon::parse($points[$count - 1]['date'])->translatedFormat('d M Y') }}</span>
        </div>

        <div id="tabel-donasi-harian" x-show="tabel" x-cloak class="mt-5 max-h-72 overflow-y-auto rounded-2xl border border-white/10">
            <table class="w-full text-left text-xs">
                <thead class="sticky top-0 bg-[#231f36] text-slate-400 font-extrabold uppercase">
                    <tr><th class="py-3 px-4">Tanggal</th><th class="py-3 px-4 text-right">Nominal</th></tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach ($points as $p)
                        <tr class="hover:bg-white/5">
                            <td class="py-3 px-4 text-slate-300">{{ \Carbon\Carbon::parse($p['date'])->translatedFormat('d F Y') }}</td>
                            <td class="py-3 px-4 text-right font-black text-[#99ff04] tabular-nums">{{ rupiah($p['amount']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Status Rantai Audit --}}
    <section @class([
        'rounded-3xl border p-6 mb-8 shadow-xl',
        'border-[#99ff04]/30 bg-[#99ff04]/5' => $chainStatus['valid'],
        'border-rose-500/30 bg-rose-500/5' => ! $chainStatus['valid'],
    ])>
        <div class="flex items-start gap-4">
            <span @class([
                'grid h-10 w-10 shrink-0 place-items-center rounded-2xl text-black font-black',
                'bg-[#99ff04]' => $chainStatus['valid'],
                'bg-rose-500 text-white' => ! $chainStatus['valid'],
            ]) aria-hidden="true">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 12 4.5 4.5L19 7.5"/></svg>
            </span>
            <div>
                <h2 @class(['font-black text-base', 'text-[#99ff04]' => $chainStatus['valid'], 'text-rose-400' => ! $chainStatus['valid']])>
                    {{ $chainStatus['valid'] ? 'Rantai Jejak Audit HMAC Utuh' : 'Rantai Jejak Audit Terputus' }}
                </h2>
                <p class="mt-1 text-xs leading-relaxed text-slate-300">
                    {{ number_format($chainStatus['checked'], 0, ',', '.') }} entri transaksi diverifikasi ulang saat halaman dimuat.
                    @if ($chainStatus['valid'])
                        Seluruh entri tersambung secara valid dengan hash kriptografi entri sebelumnya.
                    @else
                        Ketidakcocokan ditemukan pada entri #{{ $chainStatus['broken_at'] }}. {{ $chainStatus['reason'] }}
                    @endif
                </p>
            </div>
        </div>
    </section>

    {{-- Rincian per kampanye --}}
    <section>
        <h2 class="text-xl font-black text-white mb-4">Rincian Per Kampanye</h2>

        @if ($campaigns->isEmpty())
            <div class="rounded-3xl border border-white/10 bg-[#1b182a] p-8 text-center text-xs text-slate-400">
                Belum ada kampanye tayang.
            </div>
        @else
            <div class="rounded-3xl border border-white/10 bg-[#1b182a] shadow-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-white/10 bg-[#231f36] text-slate-400 font-extrabold uppercase">
                                <th class="py-3 px-4">Kampanye</th>
                                <th class="py-3 px-4 text-right">Terkumpul</th>
                                <th class="py-3 px-4 text-right">Tercairkan</th>
                                <th class="py-3 px-4 text-right">Sisa Saldo</th>
                                <th class="py-3 px-4 text-right">Donatur</th>
                                <th class="py-3 px-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach ($campaigns as $campaign)
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="py-3.5 px-4">
                                        <p class="font-bold text-white">{{ $campaign->title }}</p>
                                        <p class="mt-0.5 text-[11px] text-slate-400">{{ $campaign->categoryLabel() }}</p>
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-black text-[#99ff04] tabular-nums">{{ rupiah($campaign->collected_amount) }}</td>
                                    <td class="py-3.5 px-4 text-right text-cyan-400 tabular-nums font-bold">{{ rupiah($campaign->disbursed_amount) }}</td>
                                    <td class="py-3.5 px-4 text-right tabular-nums font-bold text-amber-300">
                                        {{ rupiah($campaign->remainingBalance()) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right text-white font-bold tabular-nums">{{ $campaign->donatur_count }}</td>
                                    <td class="py-3.5 px-4 text-right">
                                        <a href="{{ route('kampanye.transparansi', $campaign) }}" class="text-xs font-black text-[#99ff04] hover:underline">
                                            Rincian &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </section>
</div>
@endsection
