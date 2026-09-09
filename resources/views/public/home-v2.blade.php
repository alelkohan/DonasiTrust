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
{{-- -------------------------------------------------------------------------
   SECTION 1: HERO SECTION (3D Coverflow Infinity Marquee Showcase)
------------------------------------------------------------------------- --}}
@php
    $heroCardList = $campaigns->map(function($c) {
        return [
            'id' => $c->id,
            'title' => $c->title,
            'cover' => $c->coverUrl(),
            'category' => $c->categoryLabel(),
            'collected' => rupiah($c->collected_amount),
            'target' => rupiah_ringkas($c->target_amount),
            'percent' => $c->progressPercent(),
            'url' => route('kampanye.show', $c),
            'organization' => $c->user->organization ?: $c->user->name,
        ];
    })->values()->toArray();

    // Fallback sample data if array is small
    if (count($heroCardList) < 5) {
        $heroCardList = array_merge($heroCardList, [
            [
                'id' => 901,
                'title' => 'Air Bersih untuk Dusun Ngroto',
                'cover' => 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=800&q=80',
                'category' => 'Lingkungan',
                'collected' => 'Rp7.150.000',
                'target' => 'target Rp32jt',
                'percent' => 22,
                'url' => '#',
                'organization' => 'Yayasan Sumber Kehidupan',
            ],
            [
                'id' => 902,
                'title' => 'Perbaiki Atap Madrasah Al-Hikmah',
                'cover' => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=800&q=80',
                'category' => 'Pendidikan',
                'collected' => 'Rp39.410.000',
                'target' => 'target Rp48,5jt',
                'percent' => 81,
                'url' => '#',
                'organization' => 'Yayasan Pendidikan Umat',
            ],
            [
                'id' => 903,
                'title' => 'Perlengkapan Sekolah 60 Anak',
                'cover' => 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=800&q=80',
                'category' => 'Kemanusiaan',
                'collected' => 'Rp12.600.000',
                'target' => 'target Rp21jt',
                'percent' => 60,
                'url' => '#',
                'organization' => 'Komunitas Anak Negeri',
            ],
            [
                'id' => 904,
                'title' => 'Bantuan Alat Bantu Dengar Digital',
                'cover' => 'https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?auto=format&fit=crop&w=800&q=80',
                'category' => 'Kesehatan',
                'collected' => 'Rp32.963.665',
                'target' => 'target Rp40jt',
                'percent' => 82,
                'url' => '#',
                'organization' => 'Komunitas Konservasi',
            ]
        ]);
    }

    // Duplicate array for seamless infinite marquee loop
    $heroCardListDouble = array_merge($heroCardList, $heroCardList);
@endphp

<section class="relative bg-[#12101c] pt-10 pb-16 text-white w-full overflow-hidden border-b border-white/10"
         x-data="{
             isHovered: false,
             isDragging: false,
             hoveredIndex: -1,
             startX: 0,
             scrollPos: 0,
             speed: 0.75,
             animFrame: null,

             updateCoverflow() {
                 const track = this.$refs.track;
                 const container = this.$refs.container;
                 if (!track || !container) return;

                 const containerWidth = container.clientWidth || window.innerWidth;
                 const containerCenterX = containerWidth / 2;
                 const cards = track.children;
                 const cardWidth = 244; // 224px width + 20px gap

                 for (let i = 0; i < cards.length; i++) {
                     const card = cards[i];
                     if (i === this.hoveredIndex) {
                         card.style.transform = 'perspective(1200px) rotateY(0deg) scale(1.18) translateZ(80px)';
                         card.style.opacity = '1';
                         card.style.zIndex = '999';
                         card.style.borderColor = '#99ff04';
                         card.style.boxShadow = '0 20px 40px rgba(153, 255, 4, 0.35)';
                         continue;
                     }

                     const cardCenterX = (i * cardWidth) - this.scrollPos + (cardWidth / 2);
                     const distRatio = (cardCenterX - containerCenterX) / (containerWidth / 2);
                     const clampedDist = Math.max(-1.4, Math.min(1.4, distRatio));
                     const absDist = Math.abs(clampedDist);

                     // Coverflow geometry: Largest flat in center, shrinking & fanning out towards edges
                     const scale = Math.max(0.72, 1.04 - absDist * 0.24);
                     const rotateY = clampedDist < 0 ? Math.min(26, absDist * 26) : Math.max(-26, -absDist * 26);
                     const translateZ = -absDist * 90;
                     const opacity = Math.max(0.6, 1 - absDist * 0.32);
                     const zIndex = Math.round(100 - absDist * 50);

                     card.style.transform = `perspective(1200px) rotateY(${rotateY}deg) scale(${scale}) translateZ(${translateZ}px)`;
                     card.style.opacity = opacity.toString();
                     card.style.zIndex = zIndex.toString();
                     card.style.borderColor = 'rgba(255, 255, 255, 0.12)';
                     card.style.boxShadow = '0 10px 30px rgba(0, 0, 0, 0.5)';
                 }
             },

             initMarquee() {
                 const step = () => {
                     if (!this.isHovered && !this.isDragging) {
                         this.scrollPos += this.speed;
                         const track = this.$refs.track;
                         if (track && this.scrollPos >= (track.scrollWidth / 2)) {
                             this.scrollPos = 0;
                         }
                     }
                     this.updateCoverflow();
                     this.animFrame = requestAnimationFrame(step);
                 };
                 this.animFrame = requestAnimationFrame(step);
             },

             startDrag(e) {
                 this.isDragging = true;
                 this.startX = e.clientX || (e.touches ? e.touches[0].clientX : 0);
             },

             onDrag(e) {
                 if (!this.isDragging) return;
                 const currentX = e.clientX || (e.touches ? e.touches[0].clientX : 0);
                 const delta = (this.startX - currentX) * 1.4;
                 this.scrollPos += delta;
                 this.startX = currentX;
                 
                 const track = this.$refs.track;
                 if (track) {
                     if (this.scrollPos < 0) this.scrollPos = track.scrollWidth / 2;
                     if (this.scrollPos >= track.scrollWidth / 2) this.scrollPos = 0;
                 }
             },

             endDrag() {
                 this.isDragging = false;
             }
         }"
         x-init="initMarquee()">

    {{-- Top Centered Headline & Action Buttons --}}
    <div class="text-center max-w-4xl mx-auto px-4 space-y-5 pb-8">
        <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-1.5 text-xs font-black uppercase tracking-widest text-slate-300 backdrop-blur-md">
            <span class="h-2.5 w-2.5 rounded-full bg-[#99ff04] animate-pulse"></span>
            Kebaikan Yang Bisa Ditelusuri
        </div>

        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight text-white leading-tight">
            Niat baikmu,<br>
            <span class="text-[#99ff04]">Bukti nyatanya.</span>
        </h1>

        <p class="text-base sm:text-lg font-medium text-slate-300 leading-relaxed max-w-2xl mx-auto">
            Pantau donasimu dari dana terkumpul, pencairan bertahap, hingga bukti penggunaan secara transparan dan akuntabel.
        </p>

        <div class="flex flex-wrap items-center justify-center gap-4 pt-2">
            <a href="#kampanye-section" class="inline-flex items-center gap-2 rounded-full bg-[#99ff04] px-8 py-3.5 text-sm font-black text-black transition-transform hover:scale-105 hover:bg-[#84e000] active:scale-95 shadow-xl shadow-[#99ff04]/20">
                Jelajahi Kampanye ↗
            </a>
            <a href="{{ route('transparansi') }}" class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-[#231f36] px-7 py-3.5 text-sm font-bold text-white transition-all hover:bg-white/15">
                Lihat Cara Kerja
            </a>
        </div>
    </div>

    {{-- 3D Coverflow Marquee Track Container --}}
    <div class="relative w-full py-6 select-none"
         x-ref="container"
         style="perspective: 1200px; -webkit-perspective: 1200px;"
         @mouseenter="isHovered = true"
         @mouseleave="isHovered = false; hoveredIndex = -1; endDrag()"
         @mousedown="startDrag($event)"
         @mousemove="onDrag($event)"
         @mouseup="endDrag()"
         @touchstart="startDrag($event)"
         @touchmove="onDrag($event)"
         @touchend="endDrag()">
        
        {{-- Side Gradient Fades (Mask-image Effect) --}}
        <div class="pointer-events-none absolute left-0 top-0 bottom-0 z-30 w-24 sm:w-44 bg-gradient-to-r from-[#12101c] via-[#12101c]/80 to-transparent"></div>
        <div class="pointer-events-none absolute right-0 top-0 bottom-0 z-30 w-24 sm:w-44 bg-gradient-to-l from-[#12101c] via-[#12101c]/80 to-transparent"></div>

        {{-- Marquee Track --}}
        <div class="flex gap-5 items-center py-10 transition-transform ease-linear duration-75 cursor-grab active:cursor-grabbing"
             x-ref="track"
             :style="`transform: translateX(-${scrollPos}px);`">
            
            @foreach ($heroCardListDouble as $idx => $card)
                <div class="hero-3d-card-coverflow group relative shrink-0 w-56 h-80 rounded-3xl overflow-hidden border bg-[#1b182a]"
                     @mouseenter="hoveredIndex = {{ $idx }}"
                     @mouseleave="hoveredIndex = -1">
                    
                    {{-- Cover Photo --}}
                    <img src="{{ $card['cover'] }}" 
                         alt="{{ $card['title'] }}" 
                         class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110">
                    
                    {{-- Gradient Overlay --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-[#12101c] via-[#12101c]/60 to-transparent"></div>

                    {{-- Top Category Pill & OPEN status --}}
                    <div class="absolute top-3 left-3 right-3 flex items-center justify-between pointer-events-none">
                        <span class="rounded-full bg-black/75 backdrop-blur-md border border-white/10 px-2.5 py-0.5 text-[10px] font-black uppercase text-white tracking-wider">
                            {{ $card['category'] }}
                        </span>
                        <span class="rounded-full bg-[#99ff04] px-2 py-0.5 text-[10px] font-black text-black uppercase tracking-wider">
                            OPEN
                        </span>
                    </div>

                    {{-- Bottom Card Body Content --}}
                    <div class="absolute bottom-0 inset-x-0 p-4 space-y-2.5">
                        <h3 class="text-xs sm:text-sm font-black text-white leading-tight line-clamp-2 group-hover:text-[#99ff04] transition-colors">
                            {{ $card['title'] }}
                        </h3>

                        {{-- Progress Bar & Stats --}}
                        <div class="space-y-1 pt-1">
                            <div class="h-1.5 w-full rounded-full bg-[#2a253e] overflow-hidden">
                                <div class="h-full rounded-full bg-[#99ff04] transition-all duration-500"
                                     style="width: {{ $card['percent'] }}%"></div>
                            </div>
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-black text-white">{{ $card['collected'] }}</span>
                                <span class="text-[#99ff04] font-bold">{{ $card['percent'] }}%</span>
                            </div>
                        </div>

                        {{-- Action Link on Hover --}}
                        <div class="pt-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="{{ $card['url'] }}" 
                               class="inline-flex w-full items-center justify-center gap-1.5 rounded-full bg-[#99ff04] py-1.5 text-[11px] font-black text-black shadow-md hover:bg-[#84e000]">
                                Lihat Kampanye ↗
                            </a>
                        </div>
                    </div>

                </div>
            @endforeach

        </div>

    </div>

    {{-- Interactive Drag / Hover Indicator Footnote --}}
    <div class="text-center text-xs font-medium text-slate-400">
        Geser ke kanan/kiri atau arahkan kursor ke kartu untuk melihat detail kampanye.
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
