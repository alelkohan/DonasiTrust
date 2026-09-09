@extends('layouts.app')

@section('title', 'Niat Baikmu, Bukti Nyatanya · DonasiTrust')

@section('content')

<div x-data="{
    searchQuery: '{{ request('q', '') }}',
    activeCategory: '{{ request('kategori', 'semua') }}',
    isSearching: false,
    isLoadingCategory: false,
    isSticky: false,
    heroScrollProgress: 0,
    searchHtml: '',
    initScroll() {
        const checkScroll = () => {
            const scrollY = window.scrollY;
            const maxScroll = 500;
            this.heroScrollProgress = Math.min(1, Math.max(0, scrollY / maxScroll));

            const el = document.getElementById('static-navtab-v2');
            if (el) {
                const rect = el.getBoundingClientRect();
                this.isSticky = rect.top <= 60;
            } else {
                this.isSticky = scrollY > 400;
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

{{-- Floating Sticky Search & Category Bar (Appears when scrolled past static navtab) --}}
<div x-show="isSticky"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 -translate-y-4"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-4"
     class="navtab-floating-bar fixed top-[60px] left-0 right-0 z-30 shadow-xl backdrop-blur-md transition-all duration-300"
     style="display: none;">
    <div class="max-w-[1650px] mx-auto px-4 sm:px-6 lg:px-8 py-2.5 flex flex-col md:flex-row md:items-center justify-between gap-3">
        
        {{-- Search Input --}}
        <div class="w-full md:w-80 shrink-0">
            <div class="relative flex items-center">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input type="text"
                       x-model="searchQuery"
                       @input.debounce.300ms="doSearch()"
                       placeholder="Cari kampanye atau lokasi..."
                       class="navtab-search-input w-full rounded-2xl py-2 pl-10 pr-14 text-xs font-medium placeholder-slate-400 shadow-md focus:border-[#99ff04] focus:outline-none">
                <button type="button"
                        x-show="searchQuery"
                        @click="clearSearch()"
                        class="absolute right-2 text-[10px] font-extrabold text-slate-400 hover:text-white bg-white/10 rounded px-1.5 py-0.5">
                    Clear ✕
                </button>
            </div>
        </div>

        {{-- Category Pills --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-1 md:pb-0 scrollbar-none">
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
</div>

{{-- -------------------------------------------------------------------------
   SECTION 1: HERO SECTION (3D Depth Parallax Shrink & Blur on Scroll)
------------------------------------------------------------------------- --}}
<div class="sticky top-0 z-0 w-full overflow-hidden" style="perspective: 1200px; -webkit-perspective: 1200px;">
    <section class="hero-section-v2 relative bg-[#12101c] text-white overflow-hidden transition-all duration-75 ease-out origin-center"
             style="padding-top: 80px; padding-bottom: 90px;"
             :style="`
                 transform: perspective(1200px) scale(${1 - heroScrollProgress * 0.12}) translateZ(${-heroScrollProgress * 150}px) translateY(${heroScrollProgress * 40}px);
                 filter: blur(${heroScrollProgress * 14}px);
                 opacity: ${1 - heroScrollProgress * 0.85};
                 will-change: transform, filter, opacity;
             `">
    
    {{-- Animated Background Sliding Cards Marquee (Shifted Right on Desktop for Text Legibility) --}}
    @if ($heroCampaigns->isNotEmpty())
        <div class="hero-marquee-container absolute inset-0 lg:left-[8%] lg:right-0 z-0 overflow-hidden pointer-events-none opacity-25 select-none flex justify-center lg:justify-end gap-6 p-4" style="transform: rotate(-22deg) scale(1.45);">
            
            {{-- Column 1: Scrolls Up --}}
            <div class="flex flex-col gap-6 animate-hero-marquee-up w-72 shrink-0">
                @foreach ($heroCampaigns->concat($heroCampaigns) as $cmp)
                    @php
                        $catRaw = $cmp->category ?? $cmp->kategori ?? 'sosial';
                        $catLabel = method_exists($cmp, 'categoryLabel') ? $cmp->categoryLabel() : (\App\Models\Campaign::CATEGORIES[$catRaw] ?? (is_string($catRaw) ? $catRaw : 'Umum'));
                        $img = $cmp->cover_path ?? $cmp->gambar ?? '';
                        if (!empty($img) && !Str::startsWith($img, 'http')) {
                            $img = asset('storage/' . $img);
                        }
                        if (empty($img)) {
                            $img = match($catRaw) {
                                'bencana' => 'https://images.unsplash.com/photo-1547683905-f686c993aae5?auto=format&fit=crop&w=800&q=80',
                                'pendidikan' => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=800&q=80',
                                'kesehatan' => 'https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?auto=format&fit=crop&w=800&q=80',
                                'infrastruktur' => 'https://images.unsplash.com/photo-1541888946425-d0fbb186a5b3?auto=format&fit=crop&w=800&q=80',
                                'lingkungan' => 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=800&q=80',
                                default => 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=800&q=80',
                            };
                        }
                        $title = $cmp->title ?? $cmp->judul ?? '';
                        $summary = $cmp->summary ?? 'Program donasi terverifikasi dengan audit transparansi real-time.';
                        $collected = $cmp->collected_amount ?? $cmp->terkumpul ?? 0;
                        $target = $cmp->target_amount ?? $cmp->target_dana ?? 1;
                        $pct = $target > 0 ? min(100, round(($collected / $target) * 100)) : 100;
                    @endphp
                    <article class="hero-marquee-card flex flex-col overflow-hidden rounded-2xl border border-white/15 bg-[#1b182a] shadow-xl w-72 shrink-0">
                        <div class="relative aspect-[16/9] overflow-hidden bg-slate-900">
                            <img src="{{ $img }}" alt="{{ $title }}" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=800&q=80';" class="h-full w-full object-cover">
                            <div class="absolute inset-0 bg-black/20 pointer-events-none"></div>
                            <div class="absolute top-2.5 left-2.5 flex items-center gap-1.5 z-10">
                                <span class="rounded bg-[#99ff04] px-2 py-0.5 text-[9px] font-black tracking-wider text-black uppercase shadow-sm">OPEN</span>
                                <span class="rounded bg-black/70 px-2 py-0.5 text-[9px] font-extrabold text-white backdrop-blur-md uppercase border border-white/10">{{ $catLabel }}</span>
                            </div>
                        </div>
                        <div class="flex flex-1 flex-col p-3.5">
                            <h3 class="text-xs font-extrabold leading-snug line-clamp-1 text-white">{{ $title }}</h3>
                            <p class="mt-1 line-clamp-2 text-[11px] leading-relaxed text-slate-400">{{ $summary }}</p>
                            <div class="mt-3 pt-2">
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-[#2a253e]">
                                    <div class="h-full rounded-full bg-[#99ff04]" style="width: {{ $pct }}%"></div>
                                </div>
                                <div class="mt-2 flex items-baseline justify-between gap-2 text-[11px]">
                                    <span class="font-black text-white">Rp{{ number_format($collected, 0, ',', '.') }}</span>
                                    <span class="text-[10px] text-slate-400">target Rp{{ number_format($target / 1000000, 0) }}Jt</span>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Column 2: Scrolls Down --}}
            <div class="flex flex-col gap-6 animate-hero-marquee-down w-72 shrink-0 -mt-32">
                @foreach ($heroCampaigns->reverse()->concat($heroCampaigns->reverse()) as $cmp)
                    @php
                        $catRaw = $cmp->category ?? $cmp->kategori ?? 'sosial';
                        $catLabel = method_exists($cmp, 'categoryLabel') ? $cmp->categoryLabel() : (\App\Models\Campaign::CATEGORIES[$catRaw] ?? (is_string($catRaw) ? $catRaw : 'Umum'));
                        $img = $cmp->cover_path ?? $cmp->gambar ?? '';
                        if (!empty($img) && !Str::startsWith($img, 'http')) {
                            $img = asset('storage/' . $img);
                        }
                        if (empty($img)) {
                            $img = match($catRaw) {
                                'bencana' => 'https://images.unsplash.com/photo-1547683905-f686c993aae5?auto=format&fit=crop&w=800&q=80',
                                'pendidikan' => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=800&q=80',
                                'kesehatan' => 'https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?auto=format&fit=crop&w=800&q=80',
                                'infrastruktur' => 'https://images.unsplash.com/photo-1541888946425-d0fbb186a5b3?auto=format&fit=crop&w=800&q=80',
                                'lingkungan' => 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=800&q=80',
                                default => 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=800&q=80',
                            };
                        }
                        $title = $cmp->title ?? $cmp->judul ?? '';
                        $summary = $cmp->summary ?? 'Program donasi terverifikasi dengan audit transparansi real-time.';
                        $collected = $cmp->collected_amount ?? $cmp->terkumpul ?? 0;
                        $target = $cmp->target_amount ?? $cmp->target_dana ?? 1;
                        $pct = $target > 0 ? min(100, round(($collected / $target) * 100)) : 100;
                    @endphp
                    <article class="hero-marquee-card flex flex-col overflow-hidden rounded-2xl border border-white/15 bg-[#1b182a] shadow-xl w-72 shrink-0">
                        <div class="relative aspect-[16/9] overflow-hidden bg-slate-900">
                            <img src="{{ $img }}" alt="{{ $title }}" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=800&q=80';" class="h-full w-full object-cover">
                            <div class="absolute inset-0 bg-black/20 pointer-events-none"></div>
                            <div class="absolute top-2.5 left-2.5 flex items-center gap-1.5 z-10">
                                <span class="rounded bg-[#99ff04] px-2 py-0.5 text-[9px] font-black tracking-wider text-black uppercase shadow-sm">OPEN</span>
                                <span class="rounded bg-black/70 px-2 py-0.5 text-[9px] font-extrabold text-white backdrop-blur-md uppercase border border-white/10">{{ $catLabel }}</span>
                            </div>
                        </div>
                        <div class="flex flex-1 flex-col p-3.5">
                            <h3 class="text-xs font-extrabold leading-snug line-clamp-1 text-white">{{ $title }}</h3>
                            <p class="mt-1 line-clamp-2 text-[11px] leading-relaxed text-slate-400">{{ $summary }}</p>
                            <div class="mt-3 pt-2">
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-[#2a253e]">
                                    <div class="h-full rounded-full bg-[#99ff04]" style="width: {{ $pct }}%"></div>
                                </div>
                                <div class="mt-2 flex items-baseline justify-between gap-2 text-[11px]">
                                    <span class="font-black text-white">Rp{{ number_format($collected, 0, ',', '.') }}</span>
                                    <span class="text-[10px] text-slate-400">target Rp{{ number_format($target / 1000000, 0) }}Jt</span>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Column 3: Scrolls Up --}}
            <div class="flex flex-col gap-6 animate-hero-marquee-up w-72 shrink-0 -mt-16">
                @foreach ($heroCampaigns->concat($heroCampaigns) as $cmp)
                    @php
                        $catRaw = $cmp->category ?? $cmp->kategori ?? 'sosial';
                        $catLabel = method_exists($cmp, 'categoryLabel') ? $cmp->categoryLabel() : (\App\Models\Campaign::CATEGORIES[$catRaw] ?? (is_string($catRaw) ? $catRaw : 'Umum'));
                        $img = $cmp->cover_path ?? $cmp->gambar ?? '';
                        if (!empty($img) && !Str::startsWith($img, 'http')) {
                            $img = asset('storage/' . $img);
                        }
                        if (empty($img)) {
                            $img = match($catRaw) {
                                'bencana' => 'https://images.unsplash.com/photo-1547683905-f686c993aae5?auto=format&fit=crop&w=800&q=80',
                                'pendidikan' => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=800&q=80',
                                'kesehatan' => 'https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?auto=format&fit=crop&w=800&q=80',
                                'infrastruktur' => 'https://images.unsplash.com/photo-1541888946425-d0fbb186a5b3?auto=format&fit=crop&w=800&q=80',
                                'lingkungan' => 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=800&q=80',
                                default => 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=800&q=80',
                            };
                        }
                        $title = $cmp->title ?? $cmp->judul ?? '';
                        $summary = $cmp->summary ?? 'Program donasi terverifikasi dengan audit transparansi real-time.';
                        $collected = $cmp->collected_amount ?? $cmp->terkumpul ?? 0;
                        $target = $cmp->target_amount ?? $cmp->target_dana ?? 1;
                        $pct = $target > 0 ? min(100, round(($collected / $target) * 100)) : 100;
                    @endphp
                    <article class="hero-marquee-card flex flex-col overflow-hidden rounded-2xl border border-white/15 bg-[#1b182a] shadow-xl w-72 shrink-0">
                        <div class="relative aspect-[16/9] overflow-hidden bg-slate-900">
                            <img src="{{ $img }}" alt="{{ $title }}" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=800&q=80';" class="h-full w-full object-cover">
                            <div class="absolute inset-0 bg-black/20 pointer-events-none"></div>
                            <div class="absolute top-2.5 left-2.5 flex items-center gap-1.5 z-10">
                                <span class="rounded bg-[#99ff04] px-2 py-0.5 text-[9px] font-black tracking-wider text-black uppercase shadow-sm">OPEN</span>
                                <span class="rounded bg-black/70 px-2 py-0.5 text-[9px] font-extrabold text-white backdrop-blur-md uppercase border border-white/10">{{ $catLabel }}</span>
                            </div>
                        </div>
                        <div class="flex flex-1 flex-col p-3.5">
                            <h3 class="text-xs font-extrabold leading-snug line-clamp-1 text-white">{{ $title }}</h3>
                            <p class="mt-1 line-clamp-2 text-[11px] leading-relaxed text-slate-400">{{ $summary }}</p>
                            <div class="mt-3 pt-2">
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-[#2a253e]">
                                    <div class="h-full rounded-full bg-[#99ff04]" style="width: {{ $pct }}%"></div>
                                </div>
                                <div class="mt-2 flex items-baseline justify-between gap-2 text-[11px]">
                                    <span class="font-black text-white">Rp{{ number_format($collected, 0, ',', '.') }}</span>
                                    <span class="text-[10px] text-slate-400">target Rp{{ number_format($target / 1000000, 0) }}Jt</span>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

        </div>
    @endif

    {{-- Gradient Blur Overlay Mask (Adaptive Theme Fade from Bottom to Top) --}}
    <div class="hero-marquee-overlay-v2 absolute inset-0 z-1 backdrop-blur-[2px] pointer-events-none transition-colors duration-300"></div>
    
    {{-- Ambient Glow Backdrop --}}
    <div class="pointer-events-none absolute left-1/3 top-1/2 -translate-x-1/2 -translate-y-1/2 h-[400px] w-[600px] rounded-full bg-gradient-to-tr from-[#99ff04]/15 via-emerald-500/10 to-purple-600/10 blur-[130px] opacity-70 z-2"></div>

    <div class="relative z-10 w-full">
        
        <div class="max-w-2xl space-y-6">
            
            {{-- Small Subtitle Tag --}}
            <div class="text-xs font-extrabold tracking-widest text-slate-400 uppercase">
                KEBAIKAN YANG BISA DITELUSURI
            </div>

            {{-- Main Title --}}
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-[1.12]">
                Niat baikmu.<br>
                <span>Bukti nyatanya.</span>
            </h1>

            {{-- Subtitle Paragraph --}}
            <p class="text-base sm:text-lg text-slate-300 leading-relaxed max-w-xl">
                Pantau donasimu dari dana terkumpul, pencairan bertahap, hingga bukti penggunaan.
            </p>

            {{-- CTA Buttons --}}
            <div class="pt-2 flex flex-wrap items-center gap-3.5">
                <a href="#kampanye-section" class="inline-flex items-center gap-2 rounded-xl bg-[#99ff04] px-6 py-3.5 text-sm font-black text-black shadow-lg shadow-[#99ff04]/20 hover:bg-[#84e000] hover:scale-[1.02] transition-all">
                    <span>Jelajahi Kampanye</span>
                    <svg class="h-4 w-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M17 7H7M17 7V17"/></svg>
                </a>
                <a href="#cara-kerja" class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/5 px-6 py-3.5 text-sm font-bold text-white hover:bg-white/10 transition-all">
                    Lihat Cara Kerja
                </a>
            </div>

            {{-- Trust Indicators / Checklist --}}
            <div class="pt-2 flex flex-wrap items-center gap-6 text-xs sm:text-sm font-semibold text-slate-300">
                <div class="flex items-center gap-2">
                    <span class="grid h-5 w-5 place-items-center rounded-full bg-[#99ff04]/20 text-[#99ff04] font-black text-xs">
                        ✓
                    </span>
                    <span>Pencairan bertahap</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="grid h-5 w-5 place-items-center rounded-full bg-[#99ff04]/20 text-[#99ff04] font-black text-xs">
                        ✓
                    </span>
                    <span>Bukti bisa diperiksa</span>
                </div>
            </div>

        </div>

    </div>
</section>
</div>

{{-- -------------------------------------------------------------------------
   OVERLAPPING SHEET CONTAINER (Slides UP over the blurring 3D Hero on Scroll)
------------------------------------------------------------------------- --}}
<div class="hero-sheet-container relative z-20 bg-[#12101c] rounded-t-[2.5rem] sm:rounded-t-[3.5rem] border-t border-white/10 shadow-[0_-25px_60px_rgba(0,0,0,0.8)] -mt-10 pt-4 pb-16 transition-colors duration-300">
    <div class="max-w-[1650px] mx-auto px-4 sm:px-6 lg:px-8">

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
</div>

</div>

@endsection
