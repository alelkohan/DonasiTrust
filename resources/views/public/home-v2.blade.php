@extends('layouts.app')

@section('title', 'Niat Baikmu, Bukti Nyatanya · DonasiTrust Redesain')

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
            const el = document.getElementById('static-navtab-v2');
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
            const container = document.getElementById('campaign-grid-container-v2');
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

{{-- -------------------------------------------------------------------------
   SECTION 1: HERO SECTION (Cleared for new redesign)
------------------------------------------------------------------------- --}}
<section class="relative bg-[#12101c] pt-12 pb-16 text-white w-full border-b border-white/10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center min-h-[300px] flex items-center justify-center">
        <!-- Hero section cleared -->
    </div>
</section>

{{-- -------------------------------------------------------------------------
   SECTION 2: 3-STEP VALUE PROPOSITION BAR (01 / 02 / 03)
------------------------------------------------------------------------- --}}
<section class="py-10 w-full">
    <div class="grid gap-6 md:grid-cols-3">
        <div class="flex items-start gap-4 p-4 rounded-2xl bg-white/5 border border-white/5">
            <span class="text-2xl font-black text-slate-500 font-mono">01</span>
            <div>
                <h3 class="text-sm font-black text-white">Dana dicairkan bertahap</h3>
                <p class="mt-1 text-xs text-slate-400 leading-relaxed">Dana disalurkan sesuai progres di lapangan secara terkontrol.</p>
            </div>
        </div>

        <div class="flex items-start gap-4 p-4 rounded-2xl bg-white/5 border border-white/5">
            <span class="text-2xl font-black text-slate-500 font-mono">02</span>
            <div>
                <h3 class="text-sm font-black text-white">Bukti penggunaan diperiksa</h3>
                <p class="mt-1 text-xs text-slate-400 leading-relaxed">Setiap pencairan dilengkapi bukti kuitansi yang relevan & valid.</p>
            </div>
        </div>

        <div class="flex items-start gap-4 p-4 rounded-2xl bg-white/5 border border-white/5">
            <span class="text-2xl font-black text-slate-500 font-mono">03</span>
            <div>
                <h3 class="text-sm font-black text-white">Riwayat dapat ditelusuri</h3>
                <p class="mt-1 text-xs text-slate-400 leading-relaxed">Seluruh catatan tersimpan dan dapat dilihat oleh publik secara transparan.</p>
            </div>
        </div>
    </div>
</section>

{{-- -------------------------------------------------------------------------
   SECTION 3: MAIN CAMPAIGN EXPLORATION SECTION (3 Columns Grid)
------------------------------------------------------------------------- --}}
<section id="kampanye-section" class="py-12 w-full">
    
    {{-- Header & Title Row --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-8">
        <div>
            <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                Temukan kebaikan yang ingin kamu dukung.
            </h2>
        </div>
        <a href="{{ route('kampanye.index') }}" class="inline-flex items-center gap-1.5 text-xs font-black text-[#99ff04] hover:underline shrink-0">
            Lihat semua kampanye
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
        </a>
    </div>

    {{-- Integrated Search Bar & Category Pills Row --}}
    <div id="static-navtab-v2" class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-8 pb-4 border-b border-white/10">
        
        {{-- Search Input --}}
        <div class="w-full md:w-80">
            <div class="relative flex items-center">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input type="text"
                       x-model="searchQuery"
                       @input.debounce.300ms="doSearch()"
                       placeholder="Cari kampanye atau lokasi..."
                       class="w-full rounded-2xl border border-white/15 bg-[#231f36] py-2.5 pl-10 pr-14 text-xs font-medium text-white placeholder-slate-400 shadow-md focus:border-[#99ff04] focus:outline-none">
                <button type="button"
                        x-show="searchQuery"
                        @click="clearSearch()"
                        class="absolute right-2 text-[10px] font-extrabold text-slate-400 hover:text-white bg-white/10 rounded px-1.5 py-0.5">
                    Clear ✕
                </button>
            </div>
        </div>

        {{-- Category Pills --}}
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
        </div>

    </div>

    {{-- Campaign Grid 3 Columns --}}
    <div id="campaign-grid-container-v2" class="grid gap-6 sm:grid-cols-2 md:grid-cols-3 min-h-[300px] transition-all">
        @include('partials.campaign-grid')
    </div>

</section>

{{-- -------------------------------------------------------------------------
   SECTION 4: PUBLIC AUDIT LEDGER SHOWCASE SECTION ("Kepercayaan bukan sekadar janji")
------------------------------------------------------------------------- --}}
<section class="py-16 border-t border-white/10 w-full">
    <div class="grid gap-10 lg:grid-cols-12 lg:items-center">
        
        {{-- Left Info --}}
        <div class="lg:col-span-5 space-y-4">
            <h2 class="text-3xl font-black tracking-tight text-white">
                Kepercayaan bukan sekadar janji.
            </h2>
            <p class="text-sm text-slate-300 leading-relaxed">
                Lihat catatan pencairan dan bukti penggunaan dana dalam satu tempat yang dapat diakses publik kapan saja.
            </p>
            <div class="pt-2">
                <a href="{{ route('transparansi') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-6 py-3 text-xs font-black text-white hover:bg-white/20 transition-all">
                    Buka Jejak Audit ↗
                </a>
            </div>
        </div>

        {{-- Right Interactive Audit Log Table Mockup --}}
        <div class="lg:col-span-7">
            <div class="rounded-3xl border border-white/15 bg-[#1b182a] p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-white/10 pb-3">
                    <span class="text-xs font-black text-white uppercase tracking-wider">Contoh riwayat kampanye</span>
                    <span class="text-[10px] font-bold text-slate-400">Status Terbaru</span>
                </div>

                <div class="space-y-3 text-xs">
                    {{-- Row 1 --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-3 rounded-2xl bg-[#231f36] border border-white/10">
                        <div class="flex items-center gap-3">
                            <span class="text-slate-400 font-mono text-[11px] w-20 shrink-0">12 Jan 2025</span>
                            <div class="font-bold text-white">📄 Bukti penggunaan diperiksa</div>
                        </div>
                        <div class="text-slate-400 text-[11px]">Nota pembelian pipa dan dokumentasi lapangan</div>
                    </div>

                    {{-- Row 2 --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-3 rounded-2xl bg-[#231f36] border border-white/10">
                        <div class="flex items-center gap-3">
                            <span class="text-slate-400 font-mono text-[11px] w-20 shrink-0">28 Des 2024</span>
                            <div class="font-bold text-white">⇄ Pencairan tahap 1 dicatat</div>
                        </div>
                        <div class="text-[#99ff04] font-bold text-[11px]">Rp7.150.000 disalurkan ke rekening mitra</div>
                    </div>

                    {{-- Row 3 --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-3 rounded-2xl bg-[#231f36] border border-white/10">
                        <div class="flex items-center gap-3">
                            <span class="text-slate-400 font-mono text-[11px] w-20 shrink-0">20 Des 2024</span>
                            <div class="font-bold text-white">☑ Rencana anggaran disetujui</div>
                        </div>
                        <div class="text-slate-400 text-[11px]">Rincian anggaran dan timeline telah diverifikasi</div>
                    </div>
                </div>

                <div class="pt-2 flex items-center gap-2 text-[11px] text-slate-400 border-t border-white/10">
                    <span class="text-[#99ff04]">ⓘ</span>
                    Perubahan catatan dapat terdeteksi melalui pemeriksaan jejak audit.
                </div>
            </div>
        </div>

    </div>
</section>

{{-- -------------------------------------------------------------------------
   SECTION 5: BOTTOM CTA BANNER
------------------------------------------------------------------------- --}}
<section class="py-12 w-full">
    <div class="relative overflow-hidden rounded-3xl bg-[#1b182a] p-8 sm:p-12 border border-white/15 shadow-2xl">
        <div class="grid items-center gap-8 lg:grid-cols-[1.5fr_1fr]">
            <div>
                <h2 class="text-3xl font-black tracking-tight text-white sm:text-4xl">
                    Punya gerakan baik?<br>Mulai dari sini.
                </h2>
                <p class="mt-3 text-sm text-slate-300 leading-relaxed">
                    Ajak lebih banyak orang untuk menciptakan dampak nyata di komunitasmu.
                </p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row lg:justify-end">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-[#99ff04] px-8 py-3.5 text-sm font-black text-black hover:bg-[#84e000] transition-transform hover:scale-105 text-center shadow-xl">
                    Ajukan Kampanye
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            </div>
        </div>
    </div>
</section>

{{-- Floating Version Comparison Switcher --}}
<div class="fixed bottom-6 right-6 z-50 flex items-center gap-1.5 rounded-full border border-white/20 bg-[#1b182a]/95 p-1.5 shadow-2xl backdrop-blur-xl">
    <a href="{{ url('/?v=1') }}" class="rounded-full px-3.5 py-1.5 text-xs font-black transition-all text-slate-300 hover:text-white hover:bg-white/10">
        Versi 1 (Lama)
    </a>
    <a href="{{ url('/?v=2') }}" class="rounded-full px-3.5 py-1.5 text-xs font-black transition-all bg-[#99ff04] text-black shadow-md">
        Versi 2 (Redesain Baru) ↗
    </a>
</div>

</div>

@endsection
