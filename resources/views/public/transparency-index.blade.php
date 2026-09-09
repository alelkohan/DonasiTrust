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
<div class="w-full py-8"
     x-data="{
         activeCampaign: null,
         showModal: false,
         campaignsMap: {{ Illuminate\Support\Js::from($campaignsData) }}
     }">

    <header class="max-w-3xl mb-8">
        <span class="inline-block rounded bg-[#99ff04] px-3 py-1 text-xs font-black uppercase tracking-wider text-black mb-3">
            Jejak Transparansi Publik
        </span>
        <h1 class="text-3xl font-black tracking-tight text-white sm:text-4xl">Riwayat Transparansi DonasiTrust</h1>
        <p class="mt-2 text-sm text-slate-300 leading-relaxed">
            Data di halaman ini tersinkronisasi langsung dari catatan resmi transaksi, pencairan dana, dan bukti penggunaan — terbuka dan aman.
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
                    {{ $chainStatus['valid'] ? 'Sistem Transparansi Digital Terverifikasi Utuh' : 'Peringatan Keamanan Catatan' }}
                </h2>
                <p class="mt-1 text-xs leading-relaxed text-slate-300">
                    {{ number_format($chainStatus['checked'], 0, ',', '.') }} catatan transaksi diperiksa otomatis.
                    @if ($chainStatus['valid'])
                        Setiap transaksi tersambung secara terenkripsi dan tidak dapat diubah secara sepihak.
                    @else
                        Ketidakcocokan ditemukan pada catatan #{{ $chainStatus['broken_at'] }}. {{ $chainStatus['reason'] }}
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
                                        <button type="button"
                                                @click="activeCampaign = campaignsMap[{{ $campaign->id }}]; showModal = true"
                                                class="text-xs font-black text-[#99ff04] hover:underline cursor-pointer">
                                            Rincian &rarr;
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($campaigns->hasPages())
                    <div class="border-t border-white/10 bg-[#13111c] p-4">
                        {{ $campaigns->links() }}
                    </div>
                @endif
            </div>
        @endif
    </section>

    {{-- SIDE MODAL RINCIAN PENGGUNAAN DANA (TRANSPARANSI) --}}
    <div x-show="showModal && activeCampaign" x-cloak
         class="fixed inset-0 z-[100] flex justify-end"
         role="dialog" aria-modal="true"
         @keydown.escape.window="showModal = false">

        {{-- Backdrop blur & darken --}}
        <div x-show="showModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/80 backdrop-blur-md"
             @click="showModal = false"
             aria-hidden="true"></div>

        {{-- Drawer Container --}}
        <div x-show="showModal"
             x-transition:enter="transition ease-out duration-300 transform sm:duration-500"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200 transform sm:duration-300"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="relative w-full max-w-2xl bg-[#12101c] border-l border-white/15 p-6 sm:p-8 shadow-2xl z-10 h-full overflow-y-auto flex flex-col custom-scrollbar">

            <template x-if="activeCampaign">
                <div class="flex flex-col h-full">
                    {{-- Header --}}
                    <div class="flex items-start justify-between border-b border-white/10 pb-5 shrink-0">
                        <div>
                            <span class="inline-block rounded bg-[#99ff04] px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-black mb-2" x-text="activeCampaign.category_label"></span>
                            <h2 class="text-xl sm:text-2xl font-black text-white" x-text="activeCampaign.title"></h2>
                        </div>
                        <button type="button" @click="showModal = false"
                                class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-white/5 text-slate-400 hover:bg-white/10 hover:text-white transition-colors cursor-pointer">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                    </div>

                    {{-- Content Scrollable --}}
                    <div class="flex-1 space-y-6 py-6 overflow-y-auto custom-scrollbar">

                        {{-- Posisi Dana --}}
                        <div class="rounded-3xl border border-white/10 bg-[#1b182a] p-5 shadow-xl">
                            <h3 class="text-sm font-black text-white">Posisi Dana Saat Ini</h3>

                            <div class="mt-4 flex h-3.5 w-full gap-0.5 overflow-hidden rounded-full bg-[#12101c] p-0.5 border border-white/5">
                                <div class="h-full rounded-l-full bg-[#99ff04]" :style="`width: ${Math.round((activeCampaign.disbursed_amount / Math.max(1, activeCampaign.collected_amount)) * 100)}%`"></div>
                                <div class="h-full rounded-r-full bg-amber-400" :style="`width: ${Math.round((activeCampaign.remaining_balance / Math.max(1, activeCampaign.collected_amount)) * 100)}%`"></div>
                            </div>

                            <dl class="mt-5 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl border border-white/5 bg-[#231f36] p-3.5">
                                    <dt class="flex items-center gap-1.5 text-[10px] font-extrabold tracking-wide text-slate-400 uppercase">
                                        <span class="h-2 w-2 rounded-full bg-slate-500"></span> Terkumpul
                                    </dt>
                                    <dd class="mt-1.5 text-base font-black text-white tabular-nums" x-text="activeCampaign.collected_formatted"></dd>
                                    <dd class="mt-0.5 text-[10px] text-slate-400" x-text="`${activeCampaign.donatur_count} donatur`"></dd>
                                </div>
                                <div class="rounded-2xl border border-white/5 bg-[#231f36] p-3.5">
                                    <dt class="flex items-center gap-1.5 text-[10px] font-extrabold tracking-wide text-slate-400 uppercase">
                                        <span class="h-2 w-2 rounded-full bg-[#99ff04]"></span> Dicairkan
                                    </dt>
                                    <dd class="mt-1.5 text-base font-black text-[#99ff04] tabular-nums" x-text="activeCampaign.disbursed_formatted"></dd>
                                    <dd class="mt-0.5 text-[10px] text-slate-400">acc admin platform</dd>
                                </div>
                                <div class="rounded-2xl border border-white/5 bg-[#231f36] p-3.5">
                                    <dt class="flex items-center gap-1.5 text-[10px] font-extrabold tracking-wide text-slate-400 uppercase">
                                        <span class="h-2 w-2 rounded-full bg-amber-400"></span> Tertahan
                                    </dt>
                                    <dd class="mt-1.5 text-base font-black text-amber-300 tabular-nums" x-text="activeCampaign.remaining_formatted"></dd>
                                    <dd class="mt-0.5 text-[10px] text-slate-400">aman di sistem</dd>
                                </div>
                            </dl>
                        </div>

                        {{-- Tahapan Pencairan --}}
                        <div class="rounded-3xl border border-white/10 bg-[#1b182a] p-5 shadow-xl">
                            <h3 class="text-sm font-black text-white">Tahapan Pencairan Dana</h3>
                            <p class="mt-1 text-xs text-slate-400">
                                Dana dicairkan bertahap dan tahap berikutnya terkunci sampai laporan nota disetujui.
                            </p>

                            <ol class="mt-4 space-y-2.5">
                                <template x-for="m in activeCampaign.milestones" :key="m.sequence">
                                    <li class="flex items-center justify-between gap-3 rounded-2xl border p-3 text-xs"
                                        :class="{
                                            'border-[#99ff04]/30 bg-[#99ff04]/5': m.is_done,
                                            'border-amber-500/30 bg-amber-500/5': m.is_active,
                                            'border-white/10 bg-[#231f36]': !m.is_done && !m.is_active
                                        }">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-xl text-xs font-black"
                                                  :class="{
                                                      'bg-[#99ff04] text-black': m.is_done,
                                                      'bg-amber-400 text-black': m.is_active,
                                                      'bg-white/10 text-slate-400': !m.is_done && !m.is_active
                                                  }" x-text="m.sequence"></span>
                                            <div class="min-w-0">
                                                <p class="font-bold text-white truncate" x-text="m.title"></p>
                                                <p class="text-[10px] text-slate-400" x-text="m.status_label"></p>
                                            </div>
                                        </div>
                                        <p class="font-black text-[#99ff04] tabular-nums shrink-0" x-text="m.amount_formatted"></p>
                                    </li>
                                </template>
                            </ol>
                        </div>

                        {{-- Riwayat Pencairan --}}
                        <div class="rounded-3xl border border-white/10 bg-[#1b182a] p-5 shadow-xl">
                            <h3 class="text-sm font-black text-white">Riwayat Pencairan ke Pengaju</h3>
                            <template x-if="activeCampaign.disbursements.length === 0">
                                <p class="mt-3 text-xs text-slate-400">Belum ada pencairan dana yang dilakukan.</p>
                            </template>
                            <template x-if="activeCampaign.disbursements.length > 0">
                                <ul class="mt-3 divide-y divide-white/5 text-xs">
                                    <template x-for="d in activeCampaign.disbursements" :key="d.reference">
                                        <li class="py-3 flex flex-wrap items-center justify-between gap-2">
                                            <div>
                                                <p class="font-bold text-white">Tahap <span x-text="d.sequence"></span> &middot; <span class="font-mono text-slate-300" x-text="d.reference"></span></p>
                                                <p class="text-[11px] text-slate-400 mt-0.5" x-text="d.purpose"></p>
                                                <p class="text-[10px] text-slate-500" x-text="`Tujuan: ${d.masked_payee}`"></p>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-black text-[#99ff04] tabular-nums" x-text="d.amount_formatted"></p>
                                                <span class="rounded bg-[#99ff04]/20 px-1.5 py-0.5 text-[9px] font-black text-[#99ff04] uppercase" x-text="d.status_label"></span>
                                            </div>
                                        </li>
                                    </template>
                                </ul>
                            </template>
                        </div>

                        {{-- Laporan Pengeluaran / Bukti Nota --}}
                        <div class="rounded-3xl border border-white/10 bg-[#1b182a] p-5 shadow-xl">
                            <h3 class="text-sm font-black text-white">Laporan Pengeluaran &amp; Bukti Nota</h3>
                            <template x-if="activeCampaign.expense_reports.length === 0">
                                <p class="mt-3 text-xs text-slate-400">Belum ada laporan bukti pengeluaran yang diunggah.</p>
                            </template>
                            <template x-if="activeCampaign.expense_reports.length > 0">
                                <ul class="mt-3 divide-y divide-white/5 text-xs">
                                    <template x-for="e in activeCampaign.expense_reports" :key="e.title">
                                        <li class="py-3 flex flex-wrap items-start justify-between gap-2">
                                            <div>
                                                <p class="font-bold text-white" x-text="e.title"></p>
                                                <p class="text-[10px] text-slate-400 mt-0.5" x-text="e.spent_on_formatted"></p>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-black text-white tabular-nums" x-text="e.amount_formatted"></p>
                                                <template x-if="e.receipt_url">
                                                    <a :href="e.receipt_url" target="_blank" rel="noopener" class="text-[11px] font-bold text-[#99ff04] hover:underline">Lihat nota &rarr;</a>
                                                </template>
                                            </div>
                                        </li>
                                    </template>
                                </ul>
                            </template>
                        </div>

                    </div>

                    {{-- Footer Link --}}
                    <div class="border-t border-white/10 pt-4 flex items-center justify-between gap-3 shrink-0">
                        <a :href="activeCampaign.show_url" class="text-xs font-bold text-[#99ff04] hover:underline flex items-center gap-1">
                            <span>Buka Halaman Kampanye &rarr;</span>
                        </a>
                        <button type="button" @click="showModal = false" class="rounded-full bg-white/10 px-5 py-2 text-xs font-bold text-white hover:bg-white/20 transition-colors cursor-pointer">
                            Tutup
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection
