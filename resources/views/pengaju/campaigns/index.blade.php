@extends('layouts.dashboard')
@section('title', 'Kampanye Saya')

@section('panel')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-white/10 pb-5">
        <div>
            <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white">Kampanye Saya</h1>
            <p class="mt-1 text-xs sm:text-sm font-medium text-slate-400">Kelola penggalangan dana, milestone pencairan, dan laporan LPJ</p>
        </div>
        @if (auth()->user()->canSubmitCampaign())
            <a href="{{ route('pengaju.kampanye.create') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-[#99ff04] px-5 py-2.5 text-xs font-black text-black hover:bg-[#84e000] transition-all hover:scale-105 active:scale-95 shadow-md shadow-[#99ff04]/20">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Buat Kampanye Baru</span>
            </a>
        @endif
    </div>

    @if ($campaigns->isEmpty())
        <div class="rounded-3xl border border-white/10 bg-[#1b182a] p-12 text-center shadow-xl">
            <div class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-[#231f36] text-[#99ff04] border border-white/10 mb-4">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <h2 class="text-lg font-black text-white">Belum Ada Kampanye</h2>
            <p class="mt-2 text-xs sm:text-sm text-slate-400 max-w-sm mx-auto">
                Kampanye yang Anda susun akan muncul di sini lengkap dengan status peninjauan &amp; realisasi dana.
            </p>
            @if (auth()->user()->canSubmitCampaign())
                <a href="{{ route('pengaju.kampanye.create') }}" class="mt-6 inline-flex items-center gap-2 rounded-full bg-[#99ff04] px-6 py-2.5 text-xs font-black text-black hover:bg-[#84e000] shadow-md shadow-[#99ff04]/20">
                    <span>Mulai Buat Kampanye</span>
                </a>
            @endif
        </div>
    @else
        <div class="space-y-4">
            @foreach ($campaigns as $campaign)
                <article class="rounded-3xl border border-white/10 bg-[#1b182a] p-6 shadow-xl backdrop-blur-md transition-all hover:border-[#99ff04]/30">
                    <div class="flex flex-col sm:flex-row items-start justify-between gap-5">
                        <div class="flex items-start gap-4 flex-1 min-w-0">
                            <img src="{{ $campaign->coverUrl() }}" alt="{{ $campaign->title }}" class="h-24 w-32 shrink-0 rounded-2xl object-cover border border-white/10 shadow-md bg-[#231f36]">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <h2 class="text-base font-black text-white line-clamp-1">{{ $campaign->title }}</h2>
                                    <span class="rounded-full bg-[#231f36] px-3 py-0.5 text-xs font-extrabold text-slate-300 border border-white/10">
                                        {{ $campaign->statusLabel() }}
                                    </span>
                                </div>
                                <p class="mt-2 text-xs text-slate-400 line-clamp-2 leading-relaxed">{{ $campaign->summary }}</p>
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-wrap gap-2">
                            @if ($campaign->isPublished())
                                <a href="{{ route('kampanye.show', $campaign) }}" class="rounded-xl border border-white/20 bg-[#231f36] px-3.5 py-1.5 text-xs font-extrabold text-white hover:border-[#99ff04] hover:text-[#99ff04] transition-all">
                                    Lihat Publik
                                </a>
                                <a href="{{ route('kampanye.transparansi', $campaign) }}" class="rounded-xl border border-white/20 bg-[#231f36] px-3.5 py-1.5 text-xs font-extrabold text-white hover:border-[#99ff04] hover:text-[#99ff04] transition-all">
                                    Ledger Publik
                                </a>
                                <a href="{{ route('pengaju.kampanye.milestones', $campaign) }}" class="rounded-xl bg-[#99ff04] px-4 py-1.5 text-xs font-black text-black hover:bg-[#84e000] transition-all shadow-md shadow-[#99ff04]/20">
                                    Kelola Tahapan
                                </a>
                            @endif
                            @if ($campaign->isEditable())
                                <a href="{{ route('pengaju.kampanye.edit', $campaign) }}" class="rounded-xl bg-[#99ff04] px-4 py-1.5 text-xs font-black text-black hover:bg-[#84e000] transition-all shadow-md shadow-[#99ff04]/20">
                                    Ubah Kampanye
                                </a>
                            @endif
                        </div>
                    </div>

                    @if ($campaign->status === 'rejected' && $campaign->review_note)
                        <div class="mt-4 rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-xs text-rose-300">
                            <p class="font-extrabold text-rose-200">Catatan Review Admin:</p>
                            <p class="mt-1 leading-relaxed">{{ $campaign->review_note }}</p>
                        </div>
                    @endif

                    <div class="mt-5 border-t border-white/10 pt-4">
                        <div class="h-2 w-full overflow-hidden rounded-full bg-[#231f36]">
                            <div class="h-full bg-[#99ff04]" style="width: {{ $campaign->progressPercent() }}%"></div>
                        </div>
                        <dl class="mt-4 grid grid-cols-2 gap-4 text-xs sm:grid-cols-4">
                            <div class="rounded-xl bg-[#231f36] p-3 border border-white/5">
                                <dt class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Terkumpul</dt>
                                <dd class="mt-1 font-black text-white text-sm tabular-nums">{{ rupiah($campaign->collected_amount) }}</dd>
                            </div>
                            <div class="rounded-xl bg-[#231f36] p-3 border border-white/5">
                                <dt class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Target</dt>
                                <dd class="mt-1 font-black text-white text-sm tabular-nums">{{ rupiah($campaign->target_amount) }}</dd>
                            </div>
                            <div class="rounded-xl bg-[#231f36] p-3 border border-white/5">
                                <dt class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Dicairkan</dt>
                                <dd class="mt-1 font-black text-[#99ff04] text-sm tabular-nums">{{ rupiah($campaign->disbursed_amount) }}</dd>
                            </div>
                            <div class="rounded-xl bg-[#231f36] p-3 border border-white/5">
                                <dt class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Donatur</dt>
                                <dd class="mt-1 font-black text-white text-sm tabular-nums">{{ $campaign->donations_count }} Orang</dd>
                            </div>
                        </dl>
                    </div>

                    @if ($campaign->isEditable() || in_array($campaign->status, ['pending']))
                        <div class="mt-4 flex flex-wrap items-center justify-between border-t border-white/10 pt-4">
                            @if ($campaign->isEditable())
                                <form method="POST" action="{{ route('pengaju.kampanye.submit', $campaign) }}">
                                    @csrf
                                    <button type="submit" class="rounded-full bg-[#99ff04] px-5 py-2 text-xs font-black text-black hover:bg-[#84e000] shadow-md shadow-[#99ff04]/20 transition-all">
                                        Ajukan untuk Review Admin &rarr;
                                    </button>
                                </form>
                            @else
                                <div></div>
                            @endif
                            @if (in_array($campaign->status, ['draft', 'pending', 'rejected']))
                                <form method="POST" action="{{ route('pengaju.kampanye.destroy', $campaign) }}"
                                      x-data @submit="if (! confirm('Hapus kampanye ini? Tindakan ini tidak bisa dibatalkan.')) $event.preventDefault()">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs font-bold text-rose-400 hover:text-rose-300 transition-colors">
                                        Hapus Kampanye
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif
                </article>
            @endforeach
        </div>

        <div class="mt-6">{{ $campaigns->links() }}</div>
    @endif
</div>
@endsection
