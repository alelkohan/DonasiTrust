@extends('layouts.app')
@section('title', $campaign->title.' · DonasiTrust')
@section('description', $campaign->summary)

@section('content')
<div class="w-full py-8">

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <nav class="text-xs font-bold text-slate-400" aria-label="Breadcrumb">
            <a href="{{ route('kampanye.index') }}" class="hover:text-[#99ff04] transition-colors">Kampanye</a>
            <span class="mx-2 text-slate-600" aria-hidden="true">/</span>
            <span class="text-white truncate max-w-xs sm:max-w-md inline-block align-bottom">{{ $campaign->title }}</span>
        </nav>
        @if (auth()->check() && (auth()->id() === $campaign->user_id || auth()->user()->isPengaju()))
            <a href="{{ route('pengaju.kampanye.index') }}" class="inline-flex items-center gap-1.5 text-xs font-black text-[#99ff04] hover:text-[#84e000] transition-colors">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m15 18-6-6 6-6"/></svg>
                Kembali ke Kampanye Saya
            </a>
        @endif
    </div>

    <div class="grid gap-8 lg:grid-cols-[1.65fr_1fr] lg:items-start">

        {{-- Kolom utama --}}
        <div class="min-w-0">
            <div class="rounded-3xl border border-white/10 bg-[#1b182a] overflow-hidden shadow-2xl">
                <div class="aspect-[16/9] bg-[#12101c] relative overflow-hidden">
                    <img src="{{ $campaign->coverUrl() }}" alt="{{ $campaign->title }}" class="h-full w-full object-cover">
                    <div class="absolute inset-0 bg-[#12101c]/30"></div>
                </div>

                <div class="p-6 sm:p-8">
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        <span class="rounded bg-[#99ff04] px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-black">
                            {{ $campaign->categoryLabel() }}
                        </span>
                        @if ($campaign->status === \App\Models\Campaign::STATUS_COMPLETED)
                            <span class="rounded bg-emerald-500/20 border border-emerald-500/40 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-emerald-400">
                                Selesai
                            </span>
                        @endif
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-black leading-tight tracking-tight text-white">
                        {{ $campaign->title }}
                    </h1>

                    <p class="mt-3 text-sm leading-relaxed text-slate-300">{{ $campaign->summary }}</p>

                    <div class="mt-6 flex items-center gap-3 border-t border-white/10 pt-5">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-[#99ff04] text-xs font-black text-black shadow-md">
                            {{ Str::upper(Str::substr($campaign->user->name, 0, 2)) }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-white flex items-center gap-1.5">
                                {{ $campaign->user->organization ?: $campaign->user->name }}
                                <svg class="h-4 w-4 text-sky-400 fill-current" viewBox="0 0 20 20" title="Pengaju Terverifikasi KTP"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
                            </p>
                            <p class="text-xs text-slate-400">
                                Identitas KTP terverifikasi admin
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab: Cerita / RAB / Tahapan --}}
            <div class="rounded-3xl border border-white/10 bg-[#1b182a] mt-6 overflow-hidden shadow-2xl" x-data="{ tab: 'cerita' }">
                <div class="flex gap-2 overflow-x-auto border-b border-white/10 px-4 pt-3 bg-[#13111c]" role="tablist">
                    @foreach ([
                        'cerita' => 'Cerita Lengkap',
                        'rab' => 'Rincian Anggaran (RAB)',
                        'tahapan' => 'Tahapan Pencairan',
                    ] as $key => $label)
                        <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="(tab === '{{ $key }}').toString()"
                                class="shrink-0 rounded-t-2xl px-5 py-3 text-xs font-black transition-all"
                                :class="tab === '{{ $key }}' ? 'bg-[#1b182a] text-[#99ff04] border-t-2 border-[#99ff04]' : 'text-slate-400 hover:text-white'">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <div class="p-6 sm:p-8">
                    <div x-show="tab === 'cerita'" role="tabpanel">
                        <div class="space-y-4 text-sm leading-relaxed whitespace-pre-line text-slate-200">{{ $campaign->description }}</div>
                    </div>

                    <div x-show="tab === 'rab'" x-cloak role="tabpanel">
                        <p class="text-xs text-slate-400 mb-4">
                            Total Rincian Anggaran Biaya (RAB) seimbang 100% dengan target pendanaan kampanye.
                        </p>
                        <div class="overflow-x-auto rounded-2xl border border-white/10">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-white/10 bg-[#231f36] text-slate-400 font-extrabold uppercase">
                                        <th class="py-3 px-4">Item</th>
                                        <th class="py-3 px-4 text-right">Jumlah</th>
                                        <th class="py-3 px-4 text-right">Harga Satuan</th>
                                        <th class="py-3 px-4 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    @foreach ($campaign->items as $item)
                                        <tr>
                                            <td class="py-3.5 px-4 font-bold text-white">{{ $item->name }}</td>
                                            <td class="py-3.5 px-4 text-right text-slate-300 tabular-nums">{{ $item->quantity }} {{ $item->unit }}</td>
                                            <td class="py-3.5 px-4 text-right text-slate-300 tabular-nums">{{ rupiah($item->unit_price) }}</td>
                                            <td class="py-3.5 px-4 text-right font-black text-[#99ff04] tabular-nums">{{ rupiah($item->subtotal) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="border-t border-white/10 bg-[#231f36]">
                                        <td colspan="3" class="py-3 px-4 text-right text-xs font-black text-white">Total Pendanaan</td>
                                        <td class="py-3 px-4 text-right text-sm font-black text-[#99ff04] tabular-nums">{{ rupiah($campaign->target_amount) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div x-show="tab === 'tahapan'" x-cloak role="tabpanel">
                        <p class="text-xs text-slate-400 mb-5">
                            Pencairan dana dikunci bertahap. Tahap berikutnya hanya akan disetujui jika bukti pengeluaran dilaporkan.
                        </p>
                        <ol class="space-y-3">
                            @foreach ($campaign->milestones as $milestone)
                                <li class="flex gap-4 rounded-2xl border border-white/5 bg-[#231f36] p-4">
                                    <span @class([
                                        'grid h-8 w-8 shrink-0 place-items-center rounded-xl text-xs font-black',
                                        'bg-[#99ff04] text-black' => in_array($milestone->status, ['disbursed', 'reported']),
                                        'bg-amber-400 text-black' => in_array($milestone->status, ['available', 'requested', 'approved']),
                                        'bg-white/10 text-slate-500' => $milestone->status === 'locked',
                                    ])>{{ $milestone->sequence }}</span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                                            <p class="text-xs font-bold text-white">{{ $milestone->title }}</p>
                                            <p class="text-xs font-black text-[#99ff04] tabular-nums">{{ rupiah($milestone->amount) }}</p>
                                        </div>
                                        @if ($milestone->description)
                                            <p class="mt-1 text-xs leading-relaxed text-slate-300">{{ $milestone->description }}</p>
                                        @endif
                                        <p class="mt-1.5 text-[10px] font-black uppercase text-slate-400">{{ $milestone->statusLabel() }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </div>

            {{-- Donatur Terbaru --}}
            <div class="rounded-3xl border border-white/10 bg-[#1b182a] mt-6 p-6 sm:p-8 shadow-2xl">
                <h2 class="text-lg font-black text-white">Donatur Terbaru</h2>
                @if ($recentDonations->isEmpty())
                    <p class="mt-3 text-xs text-slate-400">Belum ada donasi masuk. Jadilah yang pertama membantu kampanye ini.</p>
                @else
                    <ul class="mt-4 divide-y divide-white/5">
                        @foreach ($recentDonations as $donation)
                            <li class="flex items-start gap-3 py-3">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-[#231f36] text-xs font-black text-[#99ff04] border border-white/10">
                                    {{ Str::upper(Str::substr($donation->displayName(), 0, 2)) }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                                        <p class="text-xs font-bold text-white">{{ $donation->displayName() }}</p>
                                        <p class="text-xs font-black text-[#99ff04] tabular-nums">{{ rupiah($donation->amount) }}</p>
                                    </div>
                                    @if ($donation->message)
                                        <p class="mt-0.5 text-xs text-slate-300 italic">&ldquo;{{ $donation->message }}&rdquo;</p>
                                    @endif
                                    <p class="mt-0.5 text-[10px] text-slate-500">{{ $donation->paid_at?->diffForHumans() }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- Sidebar Form Donasi --}}
        <aside class="space-y-6 lg:sticky lg:top-24">
            <div class="rounded-3xl border border-white/10 bg-[#1b182a] p-6 shadow-2xl">
                <p class="text-3xl font-black tracking-tight text-white tabular-nums">{{ rupiah($campaign->collected_amount) }}</p>
                <p class="mt-1 text-xs text-slate-400">terkumpul dari target {{ rupiah($campaign->target_amount) }}</p>

                <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-[#231f36]">
                    <div class="h-full rounded-full bg-[#99ff04] transition-all duration-500" style="width: {{ min(100, $campaign->progressPercent()) }}%"></div>
                </div>

                <dl class="mt-5 grid grid-cols-3 gap-3 border-t border-white/10 pt-4 text-center">
                    <div>
                        <dt class="text-[10px] text-slate-400 uppercase font-bold">Tercapai</dt>
                        <dd class="mt-0.5 text-xs font-black text-[#99ff04] tabular-nums">{{ $campaign->progressPercent() }}%</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] text-slate-400 uppercase font-bold">Donatur</dt>
                        <dd class="mt-0.5 text-xs font-black text-white tabular-nums">{{ $campaign->donations()->where('status', 'paid')->count() }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] text-slate-400 uppercase font-bold">Sisa Waktu</dt>
                        <dd class="mt-0.5 text-xs font-black text-white tabular-nums">
                            {{ $campaign->daysLeft() !== null ? $campaign->daysLeft().' hari' : '—' }}
                        </dd>
                    </div>
                </dl>

                <a href="{{ route('kampanye.transparansi', $campaign) }}"
                   class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-white/10 bg-[#231f36] px-4 py-3 text-xs font-black text-slate-200 hover:text-white hover:bg-white/10 transition-all">
                    <svg class="h-4 w-4 text-[#99ff04]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V5m0 14h16M8 15V9m4 6V7m4 8v-4"/></svg>
                    Lihat Ledger Transparansi
                </a>
            </div>

            @livewire('donation-form', ['campaign' => $campaign])
        </aside>
    </div>
</div>
@endsection
