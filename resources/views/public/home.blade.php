@extends('layouts.app')

@section('title', 'For the love of human kindness · DonasiTrust')

@section('content')

<div x-data="{
    searchQuery: '{{ request('q', '') }}',
    activeCategory: '{{ request('kategori', 'semua') }}',
    isSearching: false,
    isLoadingCategory: false,
    isSticky: false,
    searchHtml: '',
    initScroll() {
        const checkScroll = () => {
            const el = document.getElementById('static-navtab');
            if (el) {
                const rect = el.getBoundingClientRect();
                this.isSticky = rect.bottom < 60;
            } else {
                this.isSticky = window.scrollY > 300;
            }
        };
        window.addEventListener('scroll', checkScroll, { passive: true });
        checkScroll();
    },
    async doSearch() {
        const q = this.searchQuery.trim();
        if (!q) {
            this.isSearching = false;
            this.searchHtml = '';
            return;
        }
        this.isSearching = true;
        try {
            const res = await fetch(`/?ajax=search&q=${encodeURIComponent(q)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (data.is_search) {
                this.searchHtml = data.html;
            } else {
                this.isSearching = false;
                this.searchHtml = '';
            }
        } catch (e) {
            console.error('Search error', e);
        }
    },
    async switchCategory(cat) {
        if (this.isLoadingCategory) return;
        this.activeCategory = cat;
        this.isLoadingCategory = true;
        try {
            const res = await fetch(`/?ajax=category&kategori=${encodeURIComponent(cat)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            const container = document.getElementById('campaign-grid-container');
            if (container && data.html) {
                container.innerHTML = data.html;
            }
        } catch (e) {
            console.error('Category error', e);
        } finally {
            this.isLoadingCategory = false;
        }
    },
    clearSearch() {
        this.searchQuery = '';
        this.isSearching = false;
        this.searchHtml = '';
    }
}" x-init="initScroll(); if (searchQuery) { doSearch(); }">

{{-- VGen Hero Header Section --}}
<section class="relative bg-[#12101c] pt-8 pb-8 text-white w-full">
    <div class="relative w-full">
        
        {{-- Title & Hero Main Search Bar Row (Initial Position at top) --}}
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between mb-8">
            <div>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight text-white">
                    For the love of human kindness
                </h1>
                <p class="mt-2 text-sm sm:text-base text-slate-300">
                    Donasi transparan bertahap dengan jejak audit kuitansi yang dapat diverifikasi publik.
                </p>
            </div>

            {{-- Initial Hero Search Bar --}}
            <div class="w-full lg:max-w-md">
                <div class="relative flex items-center">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>
                    <input type="text"
                           x-model="searchQuery"
                           @input.debounce.300ms="doSearch()"
                           placeholder="Search for a tag, category, applicant, or cause..."
                           class="w-full rounded-2xl border border-white/15 bg-[#231f36] py-3.5 pl-12 pr-20 text-sm font-medium text-white placeholder-slate-400 shadow-xl backdrop-blur-md transition-all focus:border-[#99ff04] focus:outline-none focus:ring-1 focus:ring-[#99ff04]">
                    <button type="button"
                            x-show="searchQuery"
                            @click="clearSearch()"
                            class="absolute right-3 text-xs font-extrabold text-slate-400 hover:text-white bg-white/10 hover:bg-white/20 rounded-lg px-2 py-1 transition-all">
                        Clear ✕
                    </button>
                </div>
            </div>
        </div>

        {{-- Hero Dynamic Block (Swaps between 3 Hero Cards and Search Results if search query is active) --}}
        <div id="hero-dynamic-block" class="w-full min-h-[250px]">
            {{-- Live Search Result Replacement --}}
            <div x-show="isSearching" x-cloak class="w-full transition-opacity duration-300">
                <div x-html="searchHtml"></div>
            </div>

            {{-- Default 3 Hero Banners (Mobile Horizontal Scrollable with Side Scroll, Desktop 3 Columns) --}}
            <div x-show="!isSearching" class="w-full">
                <div class="flex overflow-x-auto gap-4 scrollbar-none snap-x snap-mandatory md:grid md:grid-cols-3 pb-3 -mx-4 px-4 sm:mx-0 sm:px-0">
                    {{-- Banner 1 --}}
                    <div class="photo-banner-card snap-start shrink-0 w-[84vw] sm:w-[380px] md:w-auto group relative flex h-60 flex-col justify-end overflow-hidden rounded-3xl border border-white/10 p-6 shadow-xl transition-all duration-300 hover:border-purple-500/50 hover:shadow-2xl">
                        <img src="https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=800&q=80"
                             alt="Made for trust" class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
                        <div class="absolute inset-0 bg-[#12101c]/80"></div>

                        <div class="relative z-10">
                            <span class="inline-block rounded bg-[#99ff04] px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-black mb-2">
                                Pencairan Bertahap
                            </span>
                            <h3 class="text-xl font-black text-white">Made for trust</h3>
                            <p class="mt-1 text-xs text-slate-300 leading-relaxed line-clamp-2">
                                Dana dicairkan setahap demi setahap. Tahap berikutnya terkunci sampai bukti nota dilaporkan.
                            </p>
                            <div class="mt-3 flex items-center justify-end">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-black/70 px-2.5 py-1 text-[10px] font-extrabold text-white backdrop-blur-md border border-white/10">
                                    <span class="h-2 w-2 rounded-full bg-[#99ff04]"></span>
                                    @yayasan_peduli
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Banner 2 --}}
                    <div class="photo-banner-card snap-start shrink-0 w-[84vw] sm:w-[380px] md:w-auto group relative flex h-60 flex-col justify-end overflow-hidden rounded-3xl border border-white/10 p-6 shadow-xl transition-all duration-300 hover:border-purple-500/50 hover:shadow-2xl">
                        <img src="https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=800&q=80"
                             alt="No Hidden Fees" class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
                        <div class="absolute inset-0 bg-[#12101c]/80"></div>

                        <div class="relative z-10">
                            <span class="inline-block rounded bg-cyan-400 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-black mb-2">
                                Ledger Publik HMAC
                            </span>
                            <h3 class="text-xl font-black text-white">No Hidden Fees</h3>
                            <p class="mt-1 text-xs text-slate-300 leading-relaxed line-clamp-2">
                                Setiap transaksi tersambung dalam rantai hash kriptografi yang tidak dapat dimanipulasi.
                            </p>
                            <div class="mt-3 flex items-center justify-end">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-black/70 px-2.5 py-1 text-[10px] font-extrabold text-white backdrop-blur-md border border-white/10">
                                    <span class="h-2 w-2 rounded-full bg-cyan-400"></span>
                                    @donasitrust_official
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Banner 3 --}}
                    <div class="photo-banner-card snap-start shrink-0 w-[84vw] sm:w-[380px] md:w-auto group relative flex h-60 flex-col justify-end overflow-hidden rounded-3xl border border-white/10 p-6 shadow-xl transition-all duration-300 hover:border-purple-500/50 hover:shadow-2xl">
                        <img src="https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=800&q=80"
                             alt="Verified but safe" class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
                        <div class="absolute inset-0 bg-[#12101c]/80"></div>

                        <div class="relative z-10">
                            <span class="inline-block rounded bg-purple-400 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-black mb-2">
                                Identitas KTP Valid
                            </span>
                            <h3 class="text-xl font-black text-white">Verified but safe</h3>
                            <p class="mt-1 text-xs text-slate-300 leading-relaxed line-clamp-2">
                                Seluruh pengaju diverifikasi identitas KTP & legalitas lembaga oleh admin sebelum tayang.
                            </p>
                            <div class="mt-3 flex items-center justify-end">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-black/70 px-2.5 py-1 text-[10px] font-extrabold text-white backdrop-blur-md border border-white/10">
                                    <span class="h-2 w-2 rounded-full bg-purple-400"></span>
                                    @relawan_nusantara
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

{{-- STATIC IN-FLOW CATEGORY NAVTAB (Non-sticky, no shadow, no overflow-hidden) --}}
<div id="static-navtab" class="w-full py-4 border-b border-white/10 mb-6">
    <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
            <button type="button"
                    @click="switchCategory('semua')"
                    :class="(activeCategory === 'semua' || !activeCategory) ? 'category-btn-active scale-105' : 'category-btn-inactive'"
                    class="flex shrink-0 items-center justify-center rounded-full border px-4 py-1.5 text-xs font-black transition-all">
                Semua
            </button>

            @foreach ($categories as $key => $label)
                <button type="button"
                        @click="switchCategory('{{ $key }}')"
                        :class="activeCategory === '{{ $key }}' ? 'category-btn-active scale-105' : 'category-btn-inactive'"
                        class="flex shrink-0 items-center justify-center rounded-full border px-4 py-1.5 text-xs font-black transition-all">
                    {{ $label }}
                </button>
            @endforeach

            <a href="{{ route('kampanye.index') }}"
               class="category-btn-inactive flex shrink-0 items-center justify-center rounded-full border px-4 py-1.5 text-xs font-bold transition-all">
                All categories &rarr;
            </a>
        </div>

        <div class="hidden md:flex items-center gap-3 shrink-0">
            <span class="category-stats-text text-xs font-bold text-white">
                {{ number_format($stats['kampanye']) }}+ kampanye aktif
            </span>
        </div>
    </div>
</div>

{{-- FLOATING DUPLICATE NAVTAB (Pop out from behind navbar when scrolled past static navtab) --}}
<div x-show="isSticky"
     x-transition:enter="transition ease-out duration-300 transform"
     x-transition:enter-start="-translate-y-full opacity-0"
     x-transition:enter-end="translate-y-0 opacity-100"
     x-transition:leave="transition ease-in duration-200 transform"
     x-transition:leave-start="translate-y-0 opacity-100"
     x-transition:leave-end="-translate-y-full opacity-0"
     class="navtab-floating-bar fixed top-[60px] left-0 right-0 z-30 backdrop-blur-md py-2.5 transition-colors"
     style="display: none;">
    <div class="w-full max-w-[1650px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            
            {{-- Category Pills --}}
            <div class="flex items-center gap-2 overflow-x-auto pb-0.5 scrollbar-none">
                <button type="button"
                        @click="switchCategory('semua')"
                        :class="(activeCategory === 'semua' || !activeCategory) ? 'category-btn-active scale-105' : 'category-btn-inactive'"
                        class="flex shrink-0 items-center justify-center rounded-full border px-3.5 py-1 text-xs font-black transition-all">
                    Semua
                </button>

                @foreach ($categories as $key => $label)
                    <button type="button"
                            @click="switchCategory('{{ $key }}')"
                            :class="activeCategory === '{{ $key }}' ? 'category-btn-active scale-105' : 'category-btn-inactive'"
                            class="flex shrink-0 items-center justify-center rounded-full border px-3.5 py-1 text-xs font-black transition-all">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Compact Search Bar --}}
            <div class="w-full md:w-80 shrink-0">
                <div class="relative flex items-center">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>
                    <input type="text"
                           x-model="searchQuery"
                           @input.debounce.300ms="doSearch()"
                           placeholder="Cari kampanye / kategori..."
                           class="navtab-search-input w-full rounded-full py-1.5 pl-9 pr-14 text-xs font-medium focus:border-[#99ff04] focus:outline-none">
                    <button type="button"
                            x-show="searchQuery"
                            @click="clearSearch()"
                            class="absolute right-2 text-[10px] font-extrabold text-slate-400 hover:text-white bg-white/10 hover:bg-white/20 rounded px-1.5 py-0.5 transition-all">
                        Clear ✕
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MAIN CAMPAIGN GRID SECTION --}}
<section class="w-full pt-6 pb-16">
    <div id="campaign-grid-container" class="grid gap-5 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5 min-h-[300px] transition-all duration-300">
        @include('partials.campaign-grid')
    </div>
</section>

{{-- Three Pillars Section --}}
<section class="border-t border-white/10 py-16 w-full">
    <div class="w-full">
        <div class="max-w-2xl">
            <h2 class="text-3xl font-black tracking-tight text-white">3 Pilar Akuntabilitas DonasiTrust</h2>
            <p class="mt-2 text-sm text-slate-400">
                Setiap transaksi dan rincian pengeluaran dikunci secara otomatis oleh sistem.
            </p>
        </div>

        <div class="mt-10 grid gap-6 md:grid-cols-3">
            @foreach ([
                [
                    'Pencairan Bertahap',
                    'Dana wajib dipecah menjadi tahapan RAB. Tahap berikutnya hanya terbuka setelah nota tahap sebelumnya diunggah & diverifikasi.',
                    'M3 20h5v-5H3zM9.5 20h5V10h-5zM16 20h5V4h-5z',
                ],
                [
                    'Kuitansi Terverifikasi HMAC',
                    'Tiap donasi menghasilkan kode HMAC-SHA256 unik. Siapa pun bisa mencocokkan kuitansi di halaman verifikasi tanpa login.',
                    'M12 3 4 6.2v5.1c0 4.6 3.2 8.4 8 9.7 4.8-1.3 8-5.1 8-9.7V6.2Zm-3 8.9 2.2 2.2 4.2-4.4',
                ],
                [
                    'Jejak Audit Ber-Rantai',
                    'Setiap entri menyimpan hash entri sebelumnya. Mengubah satu catatan lama membuat seluruh rantai sesudahnya gagal diverifikasi.',
                    'M10.6 13.4a4 4 0 0 0 5.7 0l2.8-2.9a4 4 0 0 0-5.7-5.6l-1.6 1.6M13.4 10.6a4 4 0 0 0-5.7 0l-2.8 2.9a4 4 0 0 0 5.7 5.6l1.6-1.6',
                ],
            ] as $pilar)
                <div class="rounded-3xl border border-white/10 bg-[#1b182a] p-6 shadow-xl hover:border-purple-500/40 transition-all">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-[#99ff04] text-black shadow-md border border-black/10 transition-all">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="{{ $pilar[2] }}"/>
                        </svg>
                    </span>
                    <h3 class="mt-4 text-base font-extrabold text-white">{{ $pilar[0] }}</h3>
                    <p class="mt-2 text-xs leading-relaxed text-slate-300">{{ $pilar[1] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA Section --}}
<section class="w-full py-16">
    <div class="relative overflow-hidden rounded-3xl bg-[#1b182a] p-8 sm:p-12 border border-white/15 shadow-2xl">
        <div class="grid items-center gap-8 lg:grid-cols-[1.5fr_1fr]">
            <div>
                <span class="rounded bg-[#99ff04] px-3 py-1 text-xs font-black text-black uppercase tracking-wider">
                    Gabung Komunitas Pengaju
                </span>
                <h2 class="mt-4 text-3xl font-black tracking-tight text-white sm:text-4xl">
                    Punya program kebaikan yang butuh pendanaan?
                </h2>
                <p class="mt-3 text-sm text-slate-300 leading-relaxed">
                    Daftar sebagai pengaju, verifikasi identitas KTP Anda, lalu susun RAB & tahapan pencairan. Transparansi tinggi terbukti meningkatkan kepercayaan donatur!
                </p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row lg:justify-end">
                <a href="{{ route('register') }}" class="rounded-full bg-[#99ff04] px-8 py-3.5 text-sm font-black text-black hover:bg-[#84e000] transition-transform hover:scale-105 text-center">
                    Mulai Kampanye Sekarang
                </a>
            </div>
        </div>
    </div>
</section>

</div>

@endsection
