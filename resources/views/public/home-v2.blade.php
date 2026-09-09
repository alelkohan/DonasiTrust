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
   SECTION 1: HERO SECTION (Split Layout - Text Left, Live Tracking Demo Right)
------------------------------------------------------------------------- --}}
<section class="relative bg-[#12101c] pt-8 pb-12 text-white w-full">
    <div class="grid gap-12 lg:grid-cols-12 lg:items-center">
        
        {{-- Left Hero Content --}}
        <div class="lg:col-span-6 space-y-6">
            <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3.5 py-1 text-xs font-black uppercase tracking-widest text-slate-300 backdrop-blur-md">
                <span class="h-2 w-2 rounded-full bg-[#99ff04] animate-pulse"></span>
                Kebaikan yang bisa ditelusuri
            </div>

            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight text-white leading-[1.1]">
                Niat baikmu,<br>
                <span>Bukti nyatanya.</span>
            </h1>

            <p class="text-base sm:text-lg font-medium text-slate-300 leading-relaxed max-w-xl">
                Pantau donasimu dari dana terkumpul, pencairan bertahap, hingga bukti penggunaan secara transparan dan akuntabel.
            </p>

            {{-- Action Buttons --}}
            <div class="flex flex-wrap items-center gap-4 pt-2">
                <a href="#kampanye-section" class="inline-flex items-center gap-2 rounded-full bg-[#99ff04] px-7 py-3.5 text-sm font-black text-black transition-transform hover:scale-105 hover:bg-[#84e000] active:scale-95 shadow-xl shadow-[#99ff04]/20">
                    Jelajahi Kampanye
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M17 7H7M17 7V17"/></svg>
                </a>
                <a href="{{ route('transparansi') }}" class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-[#231f36] px-6 py-3.5 text-sm font-bold text-white transition-all hover:bg-white/15">
                    Lihat Cara Kerja
                </a>
            </div>

            {{-- Feature Badges --}}
            <div class="flex flex-wrap items-center gap-6 pt-4 text-xs font-extrabold text-slate-300">
                <div class="flex items-center gap-2">
                    <span class="grid h-5 w-5 place-items-center rounded-full bg-[#99ff04]/20 text-[#99ff04]">
                        ✓
                    </span>
                    Pencairan bertahap
                </div>
                <div class="flex items-center gap-2">
                    <span class="grid h-5 w-5 place-items-center rounded-full bg-[#99ff04]/20 text-[#99ff04]">
                        ✓
                    </span>
                    Bukti bisa diperiksa
                </div>
            </div>
        </div>

        {{-- Right Hero: Option 2 Interactive Stacked Campaign Cards Showcase --}}
        <div class="lg:col-span-6 relative pt-4 pb-8" x-data="{
            heroIndex: 0,
            totalCards: 3,
            next() {
                this.heroIndex = (this.heroIndex + 1) % this.totalCards;
            },
            prev() {
                this.heroIndex = (this.heroIndex - 1 + this.totalCards) % this.totalCards;
            }
        }">
            {{-- Stack Control Bar Header --}}
            <div class="flex items-center justify-between mb-4 px-2">
                <div class="flex items-center gap-2">
                    <span class="grid h-6 w-6 place-items-center rounded-full bg-[#99ff04] text-black font-black text-xs shadow-md">
                        ★
                    </span>
                    <span class="text-xs font-black uppercase tracking-wider text-slate-300">
                        Kampanye Pilihan Mendesak
                    </span>
                </div>
                <div class="flex items-center gap-1.5">
                    <button type="button" @click="prev()" title="Sebelumnya" 
                            class="grid h-8 w-8 place-items-center rounded-full border border-white/15 bg-[#231f36] text-slate-300 hover:border-[#99ff04] hover:text-white transition-all active:scale-95">
                        ←
                    </button>
                    <span class="text-xs font-mono font-bold text-slate-400 px-1">
                        <span x-text="heroIndex + 1">1</span>/3
                    </span>
                    <button type="button" @click="next()" title="Berikutnya" 
                            class="grid h-8 w-8 place-items-center rounded-full border border-white/15 bg-[#231f36] text-slate-300 hover:border-[#99ff04] hover:text-white transition-all active:scale-95">
                        →
                    </button>
                </div>
            </div>

            {{-- 3D Stack Deck Container --}}
            <div class="relative h-[370px] sm:h-[400px] w-full cursor-pointer select-none" @click="next()">
                
                {{-- Stack Card 1 --}}
                <div class="absolute inset-0 rounded-3xl border border-white/15 bg-[#1b182a] p-5 shadow-2xl transition-all duration-500 ease-out"
                     :class="{
                        'z-30 opacity-100 translate-y-0 scale-100 pointer-events-auto border-[#99ff04]/40': heroIndex === 0,
                        'z-20 opacity-80 translate-y-4 translate-x-4 scale-95 pointer-events-none': heroIndex === 1,
                        'z-10 opacity-50 translate-y-8 translate-x-8 scale-90 pointer-events-none': heroIndex === 2
                     }">
                    <div class="flex flex-col h-full justify-between">
                        <div class="relative h-44 sm:h-48 w-full overflow-hidden rounded-2xl bg-slate-800">
                            <img src="https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=800&q=80" 
                                 alt="Air Bersih Dusun Ngroto" class="h-full w-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-[#1b182a] via-transparent to-transparent opacity-80"></div>
                            <span class="absolute top-3 left-3 rounded-full bg-black/70 border border-white/10 px-3 py-1 text-[10px] font-black uppercase text-white backdrop-blur-md">
                                Lingkungan · Tahap 1 dari 3
                            </span>
                        </div>
                        <div class="space-y-3 pt-2">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-base font-black text-white">Air Bersih untuk Dusun Ngroto</h3>
                                    <p class="text-xs text-slate-400">Kendal, Jawa Tengah · Yayasan Sumber Kehidupan</p>
                                </div>
                                <span class="rounded bg-[#99ff04]/20 border border-[#99ff04]/40 px-2.5 py-1 text-[11px] font-black text-[#99ff04]">
                                    22%
                                </span>
                            </div>
                            <div class="space-y-1">
                                <div class="h-2 w-full rounded-full bg-[#2a253e] overflow-hidden">
                                    <div class="h-full rounded-full bg-[#99ff04] w-[22%]"></div>
                                </div>
                                <div class="flex justify-between text-xs pt-0.5">
                                    <span class="font-black text-white">Rp7.150.000 <span class="text-[10px] text-slate-400 font-normal">terkumpul</span></span>
                                    <span class="text-slate-400 font-medium">Target Rp32.000.000</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Stack Card 2 --}}
                <div class="absolute inset-0 rounded-3xl border border-white/15 bg-[#1b182a] p-5 shadow-2xl transition-all duration-500 ease-out"
                     :class="{
                        'z-30 opacity-100 translate-y-0 scale-100 pointer-events-auto border-[#99ff04]/40': heroIndex === 1,
                        'z-20 opacity-80 translate-y-4 translate-x-4 scale-95 pointer-events-none': heroIndex === 2,
                        'z-10 opacity-50 translate-y-8 translate-x-8 scale-90 pointer-events-none': heroIndex === 0
                     }">
                    <div class="flex flex-col h-full justify-between">
                        <div class="relative h-44 sm:h-48 w-full overflow-hidden rounded-2xl bg-slate-800">
                            <img src="https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=800&q=80" 
                                 alt="Perbaiki Atap Madrasah Al-Hikmah" class="h-full w-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-[#1b182a] via-transparent to-transparent opacity-80"></div>
                            <span class="absolute top-3 left-3 rounded-full bg-black/70 border border-white/10 px-3 py-1 text-[10px] font-black uppercase text-white backdrop-blur-md">
                                Pendidikan · Tahap 2 dari 3
                            </span>
                        </div>
                        <div class="space-y-3 pt-2">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-base font-black text-white">Perbaiki Atap Madrasah Al-Hikmah</h3>
                                    <p class="text-xs text-slate-400">Demak, Jawa Tengah · Yayasan Pendidikan Umat</p>
                                </div>
                                <span class="rounded bg-[#99ff04]/20 border border-[#99ff04]/40 px-2.5 py-1 text-[11px] font-black text-[#99ff04]">
                                    81%
                                </span>
                            </div>
                            <div class="space-y-1">
                                <div class="h-2 w-full rounded-full bg-[#2a253e] overflow-hidden">
                                    <div class="h-full rounded-full bg-[#99ff04] w-[81%]"></div>
                                </div>
                                <div class="flex justify-between text-xs pt-0.5">
                                    <span class="font-black text-white">Rp39.410.000 <span class="text-[10px] text-slate-400 font-normal">terkumpul</span></span>
                                    <span class="text-slate-400 font-medium">Target Rp48.500.000</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Stack Card 3 --}}
                <div class="absolute inset-0 rounded-3xl border border-white/15 bg-[#1b182a] p-5 shadow-2xl transition-all duration-500 ease-out"
                     :class="{
                        'z-30 opacity-100 translate-y-0 scale-100 pointer-events-auto border-[#99ff04]/40': heroIndex === 2,
                        'z-20 opacity-80 translate-y-4 translate-x-4 scale-95 pointer-events-none': heroIndex === 0,
                        'z-10 opacity-50 translate-y-8 translate-x-8 scale-90 pointer-events-none': heroIndex === 1
                     }">
                    <div class="flex flex-col h-full justify-between">
                        <div class="relative h-44 sm:h-48 w-full overflow-hidden rounded-2xl bg-slate-800">
                            <img src="https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=800&q=80" 
                                 alt="Perlengkapan Sekolah 60 Anak" class="h-full w-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-[#1b182a] via-transparent to-transparent opacity-80"></div>
                            <span class="absolute top-3 left-3 rounded-full bg-black/70 border border-white/10 px-3 py-1 text-[10px] font-black uppercase text-white backdrop-blur-md">
                                Kemanusiaan · Tahap 1 dari 2
                            </span>
                        </div>
                        <div class="space-y-3 pt-2">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-base font-black text-white">Perlengkapan Sekolah untuk 60 Anak</h3>
                                    <p class="text-xs text-slate-400">Semarang, Jawa Tengah · Komunitas Anak Negeri</p>
                                </div>
                                <span class="rounded bg-[#99ff04]/20 border border-[#99ff04]/40 px-2.5 py-1 text-[11px] font-black text-[#99ff04]">
                                    60%
                                </span>
                            </div>
                            <div class="space-y-1">
                                <div class="h-2 w-full rounded-full bg-[#2a253e] overflow-hidden">
                                    <div class="h-full rounded-full bg-[#99ff04] w-[60%]"></div>
                                </div>
                                <div class="flex justify-between text-xs pt-0.5">
                                    <span class="font-black text-white">Rp12.600.000 <span class="text-[10px] text-slate-400 font-normal">terkumpul</span></span>
                                    <span class="text-slate-400 font-medium">Target Rp21.000.000</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Click Hint Footnote --}}
            <p class="mt-3 text-center text-[11px] font-medium text-slate-400">
                Klik kartu atau panah untuk berganti kampanye unggulan.
            </p>
        </div>

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
