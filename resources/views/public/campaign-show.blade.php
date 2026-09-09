@extends('layouts.app')
@section('title', $campaign->title.' · DonasiTrust')
@section('description', $campaign->summary)

@section('content')<div class="w-full py-8"
     x-data="{
         batal: false,
         sideModalTransparansi: false,
         bukaBatal() {
             this.batal = true;
         },
         tutupBatal() {
             this.batal = false;
         }
     }"
     @buka-batal-donasi.window="bukaBatal()"
     @tutup-batal-donasi.window="tutupBatal()">

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

    @if (!empty($pendingDonation))
        <div class="mb-6 rounded-3xl border border-amber-400/30 bg-gradient-to-r from-amber-500/15 via-[#1b182a] to-amber-500/10 p-5 sm:p-6 text-white shadow-2xl flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start sm:items-center gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-400/20 text-amber-300 shadow-inner">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="rounded bg-amber-400/20 border border-amber-400/40 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-amber-300">
                            Pembayaran Belum Selesai
                        </span>
                        <span class="font-mono text-xs text-slate-400">#{{ $pendingDonation->reference }}</span>
                    </div>
                    <p class="mt-1 text-sm font-bold text-white">
                        Anda memiliki transaksi donasi sebesar <span class="text-[#99ff04] font-black">{{ rupiah($pendingDonation->amount) }}</span> yang menunggu diselesaikan.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <a href="{{ route('donasi.checkout', $pendingDonation) }}"
                   class="inline-flex items-center gap-2 rounded-full bg-[#99ff04] px-6 py-3 text-xs font-black text-black hover:bg-[#84e000] transition-transform active:scale-95 shadow-lg shadow-[#99ff04]/20 cursor-pointer">
                    Lanjutkan Pembayaran &rarr;
                </a>
            </div>
        </div>
    @endif

    <div class="grid gap-8 lg:grid-cols-[1.65fr_1fr] lg:items-start">

        {{-- Kolom utama --}}
        <div class="min-w-0">
            <div class="rounded-3xl border border-white/10 bg-[#1b182a] overflow-hidden shadow-2xl">
                <div class="aspect-[16/9] bg-[#12101c] relative overflow-hidden">
                    <img src="{{ $campaign->coverUrl() }}" alt="{{ $campaign->title }}"
                         onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=800&q=80';"
                         class="h-full w-full object-cover">
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
                            <p class="mt-1 flex items-center gap-1.5 text-xs font-medium text-emerald-400"
                               title="Rekening pencairan pengaju ini terverifikasi admin dan dikunci verifikasi email.">
                                <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4.5" y="10.5" width="15" height="10" rx="2"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg>
                                Rekening pencairan terverifikasi &amp; terkunci
                            </p>
                        </div>
                    </div>

                    {{-- MOBILE QUICK ACTION (Hanya Tampil di Mode HP/Tablet) --}}
                    <div class="mt-6 border-t border-white/10 pt-5 lg:hidden" x-data="{ copied: false }">
                        <div class="rounded-2xl bg-[#231f36] p-4 border border-white/5 mb-4">
                            <div class="flex items-baseline justify-between gap-2">
                                <p class="text-xl font-black text-white tabular-nums">{{ rupiah($campaign->collected_amount) }}</p>
                                <p class="text-xs font-black text-[#99ff04] tabular-nums">{{ $campaign->progressPercent() }}%</p>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-0.5">terkumpul dari target {{ rupiah($campaign->target_amount) }} &middot; {{ number_format($totalDonorsCount, 0, ',', '.') }} donatur</p>
                            <div class="mt-2.5 h-2 w-full overflow-hidden rounded-full bg-[#12101c]">
                                <div class="h-full rounded-full bg-[#99ff04] transition-all duration-500" style="width: {{ min(100, $campaign->progressPercent()) }}%"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <button type="button"
                                    @click="document.getElementById('form-donasi')?.scrollIntoView({ behavior: 'smooth' })"
                                    class="flex items-center justify-center gap-2 rounded-2xl bg-[#99ff04] py-3.5 px-4 text-xs font-black text-black hover:bg-[#84e000] active:scale-95 transition-all shadow-lg shadow-[#99ff04]/20 cursor-pointer">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                                <span>Donasi Sekarang</span>
                            </button>

                            <button type="button"
                                    @click="if (navigator.share) { navigator.share({ title: '{{ e($campaign->title) }}', text: 'Bantu kampanye ini di DonasiTrust:', url: window.location.href }); } else { navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 3000); }"
                                    class="flex items-center justify-center gap-2 rounded-2xl border border-white/20 bg-white/10 py-3.5 px-4 text-xs font-bold text-white hover:bg-white/20 active:scale-95 transition-all cursor-pointer">
                                <svg x-show="!copied" class="h-4 w-4 text-[#99ff04]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                                <svg x-show="copied" class="h-4 w-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                <span x-text="copied ? 'Tersalin!' : 'Bagikan'"></span>
                            </button>
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

            {{-- Donatur Terbaru + Modal Donatur --}}
            <div x-data="{
                openModal: false,
                donors: [],
                page: 1,
                hasMore: true,
                loading: false,
                total: {{ $totalDonorsCount }},
                async fetchDonors() {
                    if (this.loading || !this.hasMore) return;
                    this.loading = true;
                    try {
                        const res = await fetch('{{ route('kampanye.donors', $campaign) }}?page=' + this.page);
                        const data = await res.json();
                        this.donors = [...this.donors, ...data.data];
                        this.hasMore = data.has_more;
                        this.total = data.total;
                        this.page++;
                    } catch (e) {
                        console.error('Gagal memuat donatur:', e);
                    } finally {
                        this.loading = false;
                    }
                },
                open() {
                    this.openModal = true;
                    if (this.donors.length === 0) {
                        this.fetchDonors();
                    }
                }
            }" class="rounded-3xl border border-white/10 bg-[#1b182a] mt-6 p-6 sm:p-8 shadow-2xl">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black text-white">Donatur Terbaru</h2>
                        <p class="text-xs text-slate-400 mt-0.5 flex items-center gap-1.5 font-medium">
                            <span class="inline-flex items-center gap-1 font-bold text-[#99ff04] bg-[#99ff04]/10 px-2 py-0.5 rounded-md border border-[#99ff04]/20">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                {{ number_format($totalDonorsCount, 0, ',', '.') }} Donatur
                            </span>
                            telah berdonasi
                        </p>
                    </div>
                </div>

                @if ($recentDonations->isEmpty())
                    <p class="mt-4 text-xs text-slate-400">Belum ada donasi masuk. Jadilah yang pertama membantu kampanye ini.</p>
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

                    @if ($totalDonorsCount > 0)
                        <button type="button" @click="open()" class="mt-5 w-full rounded-2xl border border-white/15 bg-[#231f36] py-3 px-4 text-center text-xs font-black text-[#99ff04] hover:bg-[#99ff04] hover:text-black transition-all shadow-md flex items-center justify-center gap-2 group">
                            <span>Lihat Semua Donatur ({{ number_format($totalDonorsCount, 0, ',', '.') }})</span>
                            <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </button>
                    @endif
                @endif

                {{-- Modal Semua Donatur --}}
                <div x-show="openModal"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
                     style="display: none;">
                    {{-- Backdrop --}}
                    <div @click="openModal = false" class="fixed inset-0 bg-black/80 backdrop-blur-md"></div>

                    {{-- Modal Box --}}
                    <div class="relative w-full max-w-lg overflow-hidden rounded-3xl border border-[#99ff04]/30 bg-[#1b182a] p-6 shadow-2xl z-10 flex flex-col max-h-[85vh]">
                        {{-- Header --}}
                        <div class="flex items-center justify-between border-b border-white/10 pb-4">
                            <div>
                                <h3 class="text-base font-black text-white">Semua Donatur Kampanye</h3>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    Total <span class="font-bold text-[#99ff04]">{{ number_format($totalDonorsCount, 0, ',', '.') }} orang</span> telah berdonasi
                                </p>
                            </div>
                            <button type="button" @click="openModal = false" class="grid h-8 w-8 place-items-center rounded-xl bg-white/5 text-slate-400 hover:bg-white/10 hover:text-white transition-colors">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                            </button>
                        </div>

                        {{-- Body: Scrollable Donors List --}}
                        <div class="flex-1 overflow-y-auto py-4 space-y-3 pr-1 custom-scrollbar">
                            <template x-for="d in donors" :key="d.id">
                                <div class="flex items-start gap-3 rounded-2xl border border-white/5 bg-[#231f36] p-3.5">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-[#1b182a] text-xs font-black text-[#99ff04] border border-white/10" x-text="d.initials"></span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                                            <p class="text-xs font-bold text-white" x-text="d.name"></p>
                                            <p class="text-xs font-black text-[#99ff04] tabular-nums" x-text="d.amount_formatted"></p>
                                        </div>
                                        <template x-if="d.message">
                                            <p class="mt-1 text-xs text-slate-300 italic" x-text="'&ldquo;' + d.message + '&rdquo;'"></p>
                                        </template>
                                        <p class="mt-1 text-[10px] text-slate-500" x-text="d.time_ago"></p>
                                    </div>
                                </div>
                            </template>

                            <template x-if="loading">
                                <div class="py-4 text-center text-xs text-slate-400 flex items-center justify-center gap-2">
                                    <svg class="h-4 w-4 animate-spin text-[#99ff04]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10" class="opacity-25"/><path d="M12 2a10 10 0 0 1 10 10" class="opacity-75"/></svg>
                                    <span>Memuat donatur...</span>
                                </div>
                            </template>
                        </div>

                        {{-- Footer: Load More / Status --}}
                        <div class="border-t border-white/10 pt-4 text-center">
                            <template x-if="hasMore">
                                <button type="button" @click="fetchDonors()" :disabled="loading" class="w-full rounded-2xl bg-[#99ff04] py-3 px-4 text-xs font-black text-black hover:bg-[#84e000] disabled:opacity-50 transition-all shadow-lg flex items-center justify-center gap-2">
                                    <span>Lihat Lainnya</span>
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                            </template>
                            <template x-if="!hasMore && donors.length > 0">
                                <p class="text-xs text-slate-400 flex items-center justify-center gap-1.5 py-1">
                                    <svg class="h-4 w-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 12 4.5 4.5L19 7.5"/></svg>
                                    <span>Semua {{ number_format($totalDonorsCount, 0, ',', '.') }} donatur telah ditampilkan</span>
                                </p>
                            </template>
                        </div>
                    </div>
                </div>
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

                <button type="button" @click="sideModalTransparansi = true"
                        class="mt-5 inline-flex w-full items-center justify-center gap-2.5 rounded-2xl border border-[#99ff04]/30 bg-[#99ff04]/10 px-4 py-3.5 text-xs font-black text-[#99ff04] hover:bg-[#99ff04] hover:text-black transition-all shadow-lg group cursor-pointer">
                    <svg class="h-4 w-4 transition-transform group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M4 19V5m0 14h16M8 15V9m4 6V7m4 8v-4"/></svg>
                    <span>Rincian Penggunaan Dana</span>
                </button>
            </div>

            <div id="form-donasi" class="scroll-mt-24">
                @livewire('donation-form', ['campaign' => $campaign])
            </div>
        </aside>
    </div>

    @if (!empty($pendingDonation))
        {{-- MODAL KONFIRMASI BATALKAN PEMBAYARAN (Mobile: Bottom Sheet Drawer, Desktop: Centered Glassmorphism Modal) --}}
        <div x-show="batal" x-cloak
             class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
             role="dialog" aria-modal="true"
             @keydown.escape.window="tutupBatal()">

            {{-- Backdrop blur & darken --}}
            <div x-show="batal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-black/80 backdrop-blur-md"
                 @click="tutupBatal()"
                 aria-hidden="true"></div>

            {{-- Modal Box --}}
            <div x-show="batal"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="translate-y-full sm:translate-y-0 sm:scale-95 sm:opacity-0"
                 x-transition:enter-end="translate-y-0 sm:scale-100 sm:opacity-100"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="translate-y-0 sm:scale-100 sm:opacity-100"
                 x-transition:leave-end="translate-y-full sm:translate-y-0 sm:scale-95 sm:opacity-0"
                 class="relative w-full sm:max-w-md rounded-t-[2.5rem] sm:rounded-3xl border border-white/15 bg-[#1b182a]/95 backdrop-blur-xl p-6 sm:p-8 shadow-2xl z-10 max-h-[90vh] overflow-y-auto"
                 @click.stop>

                <div class="mx-auto mb-4 h-1.5 w-12 rounded-full bg-white/25 sm:hidden"></div>

                <div class="flex items-center gap-3.5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-500/20 text-rose-400 shadow-inner">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-black text-white">Batalkan Pembayaran?</h3>
                        <p class="text-xs text-slate-400">Transaksi #{{ $pendingDonation->reference }} akan dibatalkan.</p>
                    </div>
                </div>

                <p class="my-4 text-xs leading-relaxed text-slate-300">
                    Setelah dibatalkan, nomor transaksi ini tidak dapat digunakan lagi dan formulir donasi akan terbuka untuk membuat donasi baru.
                </p>

                <div class="space-y-2.5">
                    <form method="POST" action="{{ route('donasi.cancel', $pendingDonation) }}">
                        @csrf
                        <button type="submit"
                                class="flex w-full items-center justify-center gap-2 rounded-full bg-rose-500 py-3.5 px-6 text-sm font-black text-white hover:bg-rose-600 shadow-xl shadow-rose-500/20 transition-transform active:scale-95 cursor-pointer">
                            Ya, Batalkan Transaksi Ini
                        </button>
                    </form>
                    <button type="button" @click="tutupBatal()"
                            class="w-full py-2.5 text-xs font-bold text-slate-400 hover:text-white transition-colors cursor-pointer">
                        Jangan Batalkan
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- SIDE MODAL RINCIAN PENGGUNAAN DANA (TRANSPARANSI) --}}
    <div x-show="sideModalTransparansi" x-cloak
         class="fixed inset-0 z-[100] flex justify-end"
         role="dialog" aria-modal="true"
         @keydown.escape.window="sideModalTransparansi = false">

        {{-- Backdrop blur & darken --}}
        <div x-show="sideModalTransparansi"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/80 backdrop-blur-md"
             @click="sideModalTransparansi = false"
             aria-hidden="true"></div>

        {{-- Drawer Container (Right side slide-over) --}}
        <div x-show="sideModalTransparansi"
             x-transition:enter="transition ease-out duration-300 transform sm:duration-500"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200 transform sm:duration-300"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="relative w-full max-w-2xl bg-[#12101c] border-l border-white/15 p-6 sm:p-8 shadow-2xl z-10 h-full overflow-y-auto flex flex-col custom-scrollbar">

            {{-- Header --}}
            <div class="flex items-start justify-between border-b border-white/10 pb-5">
                <div>
                    <span class="inline-block rounded bg-[#99ff04] px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-black mb-2">
                        Real-Time Transparansi
                    </span>
                    <h2 class="text-xl sm:text-2xl font-black text-white">Rincian Penggunaan Dana</h2>
                    <p class="mt-1 text-xs text-slate-300 line-clamp-1">{{ $campaign->title }}</p>
                </div>
                <button type="button" @click="sideModalTransparansi = false"
                        class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-white/5 text-slate-400 hover:bg-white/10 hover:text-white transition-colors cursor-pointer">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            {{-- Content Scrollable --}}
            <div class="flex-1 space-y-6 py-6">

                @php
                    $terkumpul = $campaign->collected_amount;
                    $tercairkan = $campaign->disbursed_amount;
                    $saldo = $campaign->remainingBalance();
                    $totalBar = max(1, $terkumpul);
                @endphp

                {{-- Posisi Dana --}}
                <div class="rounded-3xl border border-white/10 bg-[#1b182a] p-5 shadow-xl">
                    <h3 class="text-sm font-black text-white">Posisi Dana Saat Ini</h3>

                    <div class="mt-4 flex h-3.5 w-full gap-0.5 overflow-hidden rounded-full bg-[#12101c] p-0.5 border border-white/5">
                        <div class="h-full rounded-l-full bg-[#99ff04]" style="width: {{ round($tercairkan / $totalBar * 100, 2) }}%"></div>
                        <div class="h-full rounded-r-full bg-amber-400" style="width: {{ round($saldo / $totalBar * 100, 2) }}%"></div>
                    </div>

                    <dl class="mt-5 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-white/5 bg-[#231f36] p-3.5">
                            <dt class="flex items-center gap-1.5 text-[10px] font-extrabold tracking-wide text-slate-400 uppercase">
                                <span class="h-2 w-2 rounded-full bg-slate-500"></span> Terkumpul
                            </dt>
                            <dd class="mt-1.5 text-base font-black text-white tabular-nums">{{ rupiah($terkumpul) }}</dd>
                            <dd class="mt-0.5 text-[10px] text-slate-400">{{ number_format($totalDonorsCount, 0, ',', '.') }} donatur</dd>
                        </div>
                        <div class="rounded-2xl border border-white/5 bg-[#231f36] p-3.5">
                            <dt class="flex items-center gap-1.5 text-[10px] font-extrabold tracking-wide text-slate-400 uppercase">
                                <span class="h-2 w-2 rounded-full bg-[#99ff04]"></span> Dicairkan
                            </dt>
                            <dd class="mt-1.5 text-base font-black text-[#99ff04] tabular-nums">{{ rupiah($tercairkan) }}</dd>
                            <dd class="mt-0.5 text-[10px] text-slate-400">acc admin platform</dd>
                        </div>
                        <div class="rounded-2xl border border-white/5 bg-[#231f36] p-3.5">
                            <dt class="flex items-center gap-1.5 text-[10px] font-extrabold tracking-wide text-slate-400 uppercase">
                                <span class="h-2 w-2 rounded-full bg-amber-400"></span> Tertahan
                            </dt>
                            <dd class="mt-1.5 text-base font-black text-amber-300 tabular-nums">{{ rupiah($saldo) }}</dd>
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
                        @foreach ($campaign->milestones as $milestone)
                            @php
                                $done = in_array($milestone->status, ['disbursed', 'reported'], true);
                                $active = in_array($milestone->status, ['available', 'requested', 'approved'], true);
                            @endphp
                            <li @class([
                                'flex items-center justify-between gap-3 rounded-2xl border p-3 text-xs',
                                'border-[#99ff04]/30 bg-[#99ff04]/5' => $done,
                                'border-amber-500/30 bg-amber-500/5' => $active,
                                'border-white/10 bg-[#231f36]' => ! $done && ! $active,
                            ])>
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span @class([
                                        'grid h-7 w-7 shrink-0 place-items-center rounded-xl text-xs font-black',
                                        'bg-[#99ff04] text-black' => $done,
                                        'bg-amber-400 text-black' => $active,
                                        'bg-white/10 text-slate-400' => ! $done && ! $active,
                                    ])>{{ $milestone->sequence }}</span>
                                    <div class="min-w-0">
                                        <p class="font-bold text-white truncate">{{ $milestone->title }}</p>
                                        <p class="text-[10px] text-slate-400">{{ $milestone->statusLabel() }}</p>
                                    </div>
                                </div>
                                <p class="font-black text-[#99ff04] tabular-nums shrink-0">{{ rupiah($milestone->amount) }}</p>
                            </li>
                        @endforeach
                    </ol>
                </div>

                {{-- Riwayat Pencairan --}}
                <div class="rounded-3xl border border-white/10 bg-[#1b182a] p-5 shadow-xl">
                    <h3 class="text-sm font-black text-white">Riwayat Pencairan ke Pengaju</h3>
                    @if ($disbursements->isEmpty())
                        <p class="mt-3 text-xs text-slate-400">Belum ada pencairan dana yang dilakukan.</p>
                    @else
                        <ul class="mt-3 divide-y divide-white/5 text-xs">
                            @foreach ($disbursements as $d)
                                <li class="py-3 flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <p class="font-bold text-white">Tahap {{ $d->milestone?->sequence ?? '—' }} &middot; <span class="font-mono text-slate-300">{{ $d->reference }}</span></p>
                                        <p class="text-[11px] text-slate-400 mt-0.5">{{ $d->purpose }}</p>
                                        <p class="text-[10px] text-slate-500">Tujuan: {{ $d->maskedPayee() ?? '—' }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-black text-[#99ff04] tabular-nums">{{ rupiah($d->amount) }}</p>
                                        <span class="rounded bg-[#99ff04]/20 px-1.5 py-0.5 text-[9px] font-black text-[#99ff04] uppercase">{{ $d->statusLabel() }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Laporan Pengeluaran / Bukti Nota --}}
                <div class="rounded-3xl border border-white/10 bg-[#1b182a] p-5 shadow-xl">
                    <h3 class="text-sm font-black text-white">Laporan Pengeluaran &amp; Bukti Nota</h3>
                    @if ($expenses->isEmpty())
                        <p class="mt-3 text-xs text-slate-400">Belum ada laporan bukti pengeluaran yang diunggah.</p>
                    @else
                        <ul class="mt-3 divide-y divide-white/5 text-xs">
                            @foreach ($expenses as $expense)
                                <li class="py-3 flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <p class="font-bold text-white">{{ $expense->title }}</p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">{{ $expense->spent_on->translatedFormat('d F Y') }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-black text-white tabular-nums">{{ rupiah($expense->amount) }}</p>
                                        @if ($expense->receipt_path)
                                            <a href="{{ route('berkas.lpj', $expense) }}" target="_blank" rel="noopener" class="text-[11px] font-bold text-[#99ff04] hover:underline">Lihat nota &rarr;</a>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

            </div>

            {{-- Footer Link Full Page --}}
            <div class="border-t border-white/10 pt-4 flex items-center justify-between gap-3">
                <a href="{{ route('kampanye.transparansi', $campaign) }}" target="_blank" class="text-xs font-bold text-[#99ff04] hover:underline flex items-center gap-1">
                    <span>Buka laporan di halaman penuh</span>
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                </a>
                <button type="button" @click="sideModalTransparansi = false" class="rounded-full bg-white/10 px-5 py-2 text-xs font-bold text-white hover:bg-white/20 transition-colors cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
