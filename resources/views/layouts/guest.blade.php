@php
    try {
        $heroCampaigns = \App\Models\Campaign::with(['user', 'paidDonations'])->where('status', 'approved')->latest()->take(6)->get();
    } catch (\Throwable $e) {
        $heroCampaigns = collect();
    }
    if ($heroCampaigns->isEmpty()) {
        $heroCampaigns = collect([
            (object)[
                'title' => 'Bantuan Tanggap Darurat & Gizi Bencana Alam',
                'summary' => 'Penyaluran sembako, obat-obatan, dan tenda darurat untuk 500+ keluarga terdampak.',
                'category' => 'bencana',
                'collected_amount' => 125000000,
                'target_amount' => 150000000,
                'cover_path' => 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?w=500&q=80',
                'user' => (object)['name' => 'Yayasan Peduli Nusantara', 'organization' => 'Peduli Nusantara']
            ],
            (object)[
                'title' => 'Renovasi Sekolah & Fasilitas Edukasi Desa',
                'summary' => 'Perbaikan atap bocor, meja belajar, dan perpustakaan digital untuk anak pelosok.',
                'category' => 'pendidikan',
                'collected_amount' => 84000000,
                'target_amount' => 100000000,
                'cover_path' => 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?w=500&q=80',
                'user' => (object)['name' => 'Pena Masa Depan', 'organization' => 'Pena Masa Depan']
            ],
            (object)[
                'title' => 'Operasi & Pengobatan Medis Anak Spesialis',
                'summary' => 'Bantuan dana tindakan operasi bedah jantung anak kurang mampu.',
                'category' => 'kesehatan',
                'collected_amount' => 45000000,
                'target_amount' => 50000000,
                'cover_path' => 'https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?w=500&q=80',
                'user' => (object)['name' => 'Relawan Medis Kita', 'organization' => 'Relawan Medis']
            ],
            (object)[
                'title' => 'Pembangunan Sumur Air Bersih Pelosok',
                'summary' => 'Pembuatan 3 titik sumur bor dan jaringan pipa air bersih warga desa.',
                'category' => 'infrastruktur',
                'collected_amount' => 92000000,
                'target_amount' => 92000000,
                'cover_path' => 'https://images.unsplash.com/photo-1509099836639-18ba1795216d?w=500&q=80',
                'user' => (object)['name' => 'Air Untuk Semua', 'organization' => 'Air Untuk Semua']
            ]
        ]);
    }
@endphp
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Masuk') &middot; DonasiTrust</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- PWA & Mobile Meta -->
    <meta name="theme-color" content="#12101c">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="DonasiTrust">

    <!-- Theme Initialization (Prevents FOUC & matches dt_theme) -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('dt_theme') || 'dark';
            if (savedTheme === 'light') {
                document.documentElement.classList.add('theme-light');
            } else {
                document.documentElement.classList.remove('theme-light');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>
<body class="min-h-full bg-[#12101c] text-white antialiased transition-colors duration-300">
<div class="grid min-h-screen lg:grid-cols-12">

    {{-- Left Column: Form & Actions (with Mobile Marquee Background) --}}
    <div class="guest-left-container relative lg:col-span-6 xl:col-span-5 flex flex-col justify-between px-6 py-8 sm:px-12 lg:px-16 z-10 overflow-hidden min-h-screen lg:min-h-0">
        
        {{-- Mobile Background Marquee (Visible on mobile/tablet < lg screens) --}}
        <div class="mobile-marquee-bg absolute inset-0 z-0 overflow-hidden pointer-events-none opacity-65 select-none flex justify-center gap-5 p-2 scale-135 rotate-[-20deg] lg:hidden">
            
            {{-- Column 1: Scrolls Up --}}
            <div class="flex flex-col gap-5 animate-hero-marquee-up w-64 shrink-0">
                @foreach ($heroCampaigns->concat($heroCampaigns) as $cmp)
                    @php
                        $img = $cmp->cover_path ?? $cmp->gambar ?? '';
                        if (method_exists($cmp, 'coverUrl')) {
                            $img = $cmp->coverUrl();
                        } elseif (!empty($img) && !Str::startsWith($img, 'http')) {
                            $img = asset('storage/' . $img);
                        }
                        $title = $cmp->title ?? $cmp->judul ?? '';
                        $summary = $cmp->summary ?? 'Program donasi terverifikasi dengan audit transparansi real-time.';
                        $catRaw = $cmp->category ?? $cmp->kategori ?? '';
                        $catLabel = method_exists($cmp, 'categoryLabel') ? $cmp->categoryLabel() : (\App\Models\Campaign::CATEGORIES[$catRaw] ?? (is_string($catRaw) ? $catRaw : 'Umum'));
                        $collected = $cmp->collected_amount ?? $cmp->terkumpul ?? 0;
                        $target = $cmp->target_amount ?? $cmp->target_dana ?? 1;
                        $pct = $target > 0 ? min(100, round(($collected / $target) * 100)) : 100;
                        $creatorName = is_object($cmp->user ?? null) ? ($cmp->user->organization ?: $cmp->user->name) : 'Yayasan Peduli';
                    @endphp

                    {{-- Replica of Home Campaign Card for Mobile Marquee --}}
                    <article class="hero-marquee-card flex flex-col overflow-hidden rounded-2xl border border-white/15 bg-[#1b182a] shadow-xl w-64 shrink-0">
                        <div class="relative aspect-[16/9] overflow-hidden bg-[#12101c]">
                            @if (!empty($img))
                                <img src="{{ $img }}" alt="{{ $title }}" class="h-full w-full object-cover">
                            @else
                                <div class="h-full w-full bg-slate-800"></div>
                            @endif
                            <div class="absolute inset-0 bg-[#12101c]/30"></div>
                            <div class="absolute top-2 left-2 flex items-center gap-1">
                                <span class="rounded bg-[#99ff04] px-1.5 py-0.5 text-[8px] font-black tracking-wider text-black uppercase shadow-sm">OPEN</span>
                                <span class="rounded bg-black/70 px-1.5 py-0.5 text-[8px] font-extrabold text-white backdrop-blur-md uppercase border border-white/10">{{ $catLabel }}</span>
                            </div>
                        </div>
                        <div class="flex flex-1 flex-col p-3">
                            <h3 class="text-[11px] font-extrabold leading-snug line-clamp-1 text-white">{{ $title }}</h3>
                            <p class="mt-0.5 line-clamp-2 text-[10px] leading-relaxed text-slate-400">{{ $summary }}</p>
                            <div class="mt-2 pt-1.5">
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-[#2a253e]">
                                    <div class="h-full rounded-full bg-[#99ff04]" style="width: {{ $pct }}%"></div>
                                </div>
                                <div class="mt-1.5 flex items-baseline justify-between gap-1 text-[10px]">
                                    <span class="font-black text-white">Rp{{ number_format($collected, 0, ',', '.') }}</span>
                                    <span class="text-[9px] text-slate-400">target Rp{{ number_format($target / 1000000, 0) }}Jt</span>
                                </div>
                                <div class="mt-2 flex items-center justify-between border-t border-white/10 pt-1.5 text-[9px]">
                                    <div class="flex items-center gap-1 truncate">
                                        <span class="grid h-3.5 w-3.5 shrink-0 place-items-center rounded-full bg-[#99ff04] text-[8px] font-black text-black">
                                            {{ Str::upper(Str::substr($creatorName, 0, 1)) }}
                                        </span>
                                        <span class="truncate font-semibold text-slate-300">{{ $creatorName }}</span>
                                    </div>
                                    <span class="flex shrink-0 items-center gap-0.5 rounded bg-[#231f36] px-1 py-0.5 text-[8px] font-bold text-slate-300 border border-white/10">★ 5.0</span>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Column 2: Scrolls Down --}}
            <div class="flex flex-col gap-5 animate-hero-marquee-down w-64 shrink-0 -mt-24">
                @foreach ($heroCampaigns->reverse()->concat($heroCampaigns->reverse()) as $cmp)
                    @php
                        $img = $cmp->cover_path ?? $cmp->gambar ?? '';
                        if (method_exists($cmp, 'coverUrl')) {
                            $img = $cmp->coverUrl();
                        } elseif (!empty($img) && !Str::startsWith($img, 'http')) {
                            $img = asset('storage/' . $img);
                        }
                        $title = $cmp->title ?? $cmp->judul ?? '';
                        $summary = $cmp->summary ?? 'Program donasi terverifikasi dengan audit transparansi real-time.';
                        $catRaw = $cmp->category ?? $cmp->kategori ?? '';
                        $catLabel = method_exists($cmp, 'categoryLabel') ? $cmp->categoryLabel() : (\App\Models\Campaign::CATEGORIES[$catRaw] ?? (is_string($catRaw) ? $catRaw : 'Umum'));
                        $collected = $cmp->collected_amount ?? $cmp->terkumpul ?? 0;
                        $target = $cmp->target_amount ?? $cmp->target_dana ?? 1;
                        $pct = $target > 0 ? min(100, round(($collected / $target) * 100)) : 100;
                        $creatorName = is_object($cmp->user ?? null) ? ($cmp->user->organization ?: $cmp->user->name) : 'Yayasan Peduli';
                    @endphp

                    {{-- Replica of Home Campaign Card for Mobile Marquee --}}
                    <article class="hero-marquee-card flex flex-col overflow-hidden rounded-2xl border border-white/15 bg-[#1b182a] shadow-xl w-64 shrink-0">
                        <div class="relative aspect-[16/9] overflow-hidden bg-[#12101c]">
                            @if (!empty($img))
                                <img src="{{ $img }}" alt="{{ $title }}" class="h-full w-full object-cover">
                            @else
                                <div class="h-full w-full bg-slate-800"></div>
                            @endif
                            <div class="absolute inset-0 bg-[#12101c]/30"></div>
                            <div class="absolute top-2 left-2 flex items-center gap-1">
                                <span class="rounded bg-[#99ff04] px-1.5 py-0.5 text-[8px] font-black tracking-wider text-black uppercase shadow-sm">OPEN</span>
                                <span class="rounded bg-black/70 px-1.5 py-0.5 text-[8px] font-extrabold text-white backdrop-blur-md uppercase border border-white/10">{{ $catLabel }}</span>
                            </div>
                        </div>
                        <div class="flex flex-1 flex-col p-3">
                            <h3 class="text-[11px] font-extrabold leading-snug line-clamp-1 text-white">{{ $title }}</h3>
                            <p class="mt-0.5 line-clamp-2 text-[10px] leading-relaxed text-slate-400">{{ $summary }}</p>
                            <div class="mt-2 pt-1.5">
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-[#2a253e]">
                                    <div class="h-full rounded-full bg-[#99ff04]" style="width: {{ $pct }}%"></div>
                                </div>
                                <div class="mt-1.5 flex items-baseline justify-between gap-1 text-[10px]">
                                    <span class="font-black text-white">Rp{{ number_format($collected, 0, ',', '.') }}</span>
                                    <span class="text-[9px] text-slate-400">target Rp{{ number_format($target / 1000000, 0) }}Jt</span>
                                </div>
                                <div class="mt-2 flex items-center justify-between border-t border-white/10 pt-1.5 text-[9px]">
                                    <div class="flex items-center gap-1 truncate">
                                        <span class="grid h-3.5 w-3.5 shrink-0 place-items-center rounded-full bg-[#99ff04] text-[8px] font-black text-black">
                                            {{ Str::upper(Str::substr($creatorName, 0, 1)) }}
                                        </span>
                                        <span class="truncate font-semibold text-slate-300">{{ $creatorName }}</span>
                                    </div>
                                    <span class="flex shrink-0 items-center gap-0.5 rounded bg-[#231f36] px-1 py-0.5 text-[8px] font-bold text-slate-300 border border-white/10">★ 5.0</span>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

        </div>

        {{-- Mobile Gradient Overlay Mask (Fade from Bottom to Top - Clear at top, Dark at bottom) --}}
        <div class="mobile-marquee-overlay absolute inset-0 z-0 bg-gradient-to-t from-[#12101c] via-[#12101c]/65 to-transparent backdrop-blur-[1px] lg:hidden"></div>

        {{-- Header Logo & Theme Toggle --}}
        <div class="relative z-10 flex items-center justify-between">
            <a href="{{ route('home') }}" class="group flex items-center gap-3 transition-transform hover:scale-105">
                <span class="grid h-10 w-10 place-items-center rounded-2xl bg-[#99ff04] text-black shadow-lg shadow-[#99ff04]/20 transition-all group-hover:rotate-6">
                    <svg class="h-6 w-6 stroke-black" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3 4 6.2v5.1c0 4.6 3.2 8.4 8 9.7 4.8-1.3 8-5.1 8-9.7V6.2Z"/>
                        <path d="m9 12 2.2 2.2L15.4 10"/>
                    </svg>
                </span>
                <span class="brand-logo-text text-xl font-black tracking-tight text-white">
                    Donasi<span class="brand-trust-text text-[#99ff04]">Trust</span>
                </span>
            </a>

            {{-- Theme Switcher Button (Icon Only - Circular Reveal) --}}
            <button type="button" 
                    id="guest-theme-toggle-btn"
                    onclick="toggleGuestTheme(event)"
                    title="Ganti Mode Terang / Gelap"
                    aria-label="Ganti Mode Terang / Gelap"
                    class="grid h-9 w-9 place-items-center rounded-full border border-white/15 bg-[#231f36] text-slate-200 hover:border-[#99ff04] hover:text-white transition-all shadow-md cursor-pointer select-none">
                
                {{-- Sun Icon for Dark Mode (click to switch to Light) --}}
                <svg id="guest-theme-sun" class="h-4 w-4 text-amber-300 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="5"/>
                    <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
                </svg>

                {{-- Moon Icon for Light Mode (click to switch to Dark) --}}
                <svg id="guest-theme-moon" class="h-4 w-4 text-sky-300 shrink-0 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
            </button>
        </div>

        {{-- Form Content Area - Positioned Downwards on Mobile --}}
        <div class="relative z-10 mx-auto flex w-full max-w-md flex-1 flex-col justify-end pt-24 pb-6 lg:py-8 lg:justify-center">
            @yield('content')
        </div>

        {{-- Footer --}}
        <div class="relative z-10 text-center text-xs font-medium text-slate-400 pt-4">
            &copy; {{ date('Y') }} DonasiTrust &middot; Platform Donasi Transparan & Terverifikasi
        </div>
    </div>

    {{-- Right Column: Modern Feature Showcase with 30-Degree Tilted Infinite Campaign Marquee (Desktop) --}}
    <div class="guest-right-hero relative hidden overflow-hidden bg-[#0b0914] border-l border-white/10 lg:col-span-6 xl:col-span-7 lg:flex flex-col justify-between p-12 xl:p-16 transition-colors duration-300">
        
        {{-- Infinite Campaign Cards Background Marquee Layer - Tilted 30 Degrees & High Visibility --}}
        <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none opacity-50 select-none flex justify-center gap-6 p-4" style="transform: rotate(-30deg) scale(1.45);">
            
            {{-- Column 1: Scrolls Up --}}
            <div class="flex flex-col gap-6 animate-hero-marquee-up w-72 shrink-0">
                @foreach ($heroCampaigns->concat($heroCampaigns) as $cmp)
                    @php
                        $img = $cmp->cover_path ?? $cmp->gambar ?? '';
                        if (method_exists($cmp, 'coverUrl')) {
                            $img = $cmp->coverUrl();
                        } elseif (!empty($img) && !Str::startsWith($img, 'http')) {
                            $img = asset('storage/' . $img);
                        }
                        $title = $cmp->title ?? $cmp->judul ?? '';
                        $summary = $cmp->summary ?? 'Program donasi terverifikasi dengan audit transparansi real-time.';
                        $catRaw = $cmp->category ?? $cmp->kategori ?? '';
                        $catLabel = method_exists($cmp, 'categoryLabel') ? $cmp->categoryLabel() : (\App\Models\Campaign::CATEGORIES[$catRaw] ?? (is_string($catRaw) ? $catRaw : 'Umum'));
                        $collected = $cmp->collected_amount ?? $cmp->terkumpul ?? 0;
                        $target = $cmp->target_amount ?? $cmp->target_dana ?? 1;
                        $pct = $target > 0 ? min(100, round(($collected / $target) * 100)) : 100;
                        $creatorName = is_object($cmp->user ?? null) ? ($cmp->user->organization ?: $cmp->user->name) : 'Yayasan Peduli';
                    @endphp

                    {{-- Exact Home Campaign Card Replica --}}
                    <article class="hero-marquee-card flex flex-col overflow-hidden rounded-2xl border border-white/15 bg-[#1b182a] shadow-xl w-72 shrink-0">
                        {{-- Cover Image --}}
                        <div class="relative aspect-[16/9] overflow-hidden bg-[#12101c]">
                            @if (!empty($img))
                                <img src="{{ $img }}" alt="{{ $title }}" class="h-full w-full object-cover">
                            @else
                                <div class="h-full w-full bg-slate-800"></div>
                            @endif
                            <div class="absolute inset-0 bg-[#12101c]/30"></div>
                            <div class="absolute top-2.5 left-2.5 flex items-center gap-1.5">
                                <span class="rounded bg-[#99ff04] px-2 py-0.5 text-[9px] font-black tracking-wider text-black uppercase shadow-sm">OPEN</span>
                                <span class="rounded bg-black/70 px-2 py-0.5 text-[9px] font-extrabold text-white backdrop-blur-md uppercase border border-white/10">{{ $catLabel }}</span>
                            </div>
                        </div>

                        {{-- Card Body --}}
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
                                <div class="mt-2.5 flex items-center justify-between border-t border-white/10 pt-2 text-[10px]">
                                    <div class="flex items-center gap-1.5 truncate">
                                        <span class="grid h-4 w-4 shrink-0 place-items-center rounded-full bg-[#99ff04] text-[9px] font-black text-black">
                                            {{ Str::upper(Str::substr($creatorName, 0, 1)) }}
                                        </span>
                                        <span class="truncate font-semibold text-slate-300">{{ $creatorName }}</span>
                                    </div>
                                    <span class="flex shrink-0 items-center gap-1 rounded bg-[#231f36] px-1.5 py-0.5 text-[9px] font-bold text-slate-300 border border-white/10">★ 5.0</span>
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
                        $img = $cmp->cover_path ?? $cmp->gambar ?? '';
                        if (method_exists($cmp, 'coverUrl')) {
                            $img = $cmp->coverUrl();
                        } elseif (!empty($img) && !Str::startsWith($img, 'http')) {
                            $img = asset('storage/' . $img);
                        }
                        $title = $cmp->title ?? $cmp->judul ?? '';
                        $summary = $cmp->summary ?? 'Program donasi terverifikasi dengan audit transparansi real-time.';
                        $catRaw = $cmp->category ?? $cmp->kategori ?? '';
                        $catLabel = method_exists($cmp, 'categoryLabel') ? $cmp->categoryLabel() : (\App\Models\Campaign::CATEGORIES[$catRaw] ?? (is_string($catRaw) ? $catRaw : 'Umum'));
                        $collected = $cmp->collected_amount ?? $cmp->terkumpul ?? 0;
                        $target = $cmp->target_amount ?? $cmp->target_dana ?? 1;
                        $pct = $target > 0 ? min(100, round(($collected / $target) * 100)) : 100;
                        $creatorName = is_object($cmp->user ?? null) ? ($cmp->user->organization ?: $cmp->user->name) : 'Yayasan Peduli';
                    @endphp

                    {{-- Exact Home Campaign Card Replica --}}
                    <article class="hero-marquee-card flex flex-col overflow-hidden rounded-2xl border border-white/15 bg-[#1b182a] shadow-xl w-72 shrink-0">
                        {{-- Cover Image --}}
                        <div class="relative aspect-[16/9] overflow-hidden bg-[#12101c]">
                            @if (!empty($img))
                                <img src="{{ $img }}" alt="{{ $title }}" class="h-full w-full object-cover">
                            @else
                                <div class="h-full w-full bg-slate-800"></div>
                            @endif
                            <div class="absolute inset-0 bg-[#12101c]/30"></div>
                            <div class="absolute top-2.5 left-2.5 flex items-center gap-1.5">
                                <span class="rounded bg-[#99ff04] px-2 py-0.5 text-[9px] font-black tracking-wider text-black uppercase shadow-sm">OPEN</span>
                                <span class="rounded bg-black/70 px-2 py-0.5 text-[9px] font-extrabold text-white backdrop-blur-md uppercase border border-white/10">{{ $catLabel }}</span>
                            </div>
                        </div>

                        {{-- Card Body --}}
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
                                <div class="mt-2.5 flex items-center justify-between border-t border-white/10 pt-2 text-[10px]">
                                    <div class="flex items-center gap-1.5 truncate">
                                        <span class="grid h-4 w-4 shrink-0 place-items-center rounded-full bg-[#99ff04] text-[9px] font-black text-black">
                                            {{ Str::upper(Str::substr($creatorName, 0, 1)) }}
                                        </span>
                                        <span class="truncate font-semibold text-slate-300">{{ $creatorName }}</span>
                                    </div>
                                    <span class="flex shrink-0 items-center gap-1 rounded bg-[#231f36] px-1.5 py-0.5 text-[9px] font-bold text-slate-300 border border-white/10">★ 5.0</span>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

        </div>

        {{-- Gradient Overlay Mask to Ensure Text Readability --}}
        <div class="hero-overlay-mask absolute inset-0 z-1 bg-gradient-to-t from-[#0b0914]/90 via-[#0b0914]/50 to-[#0b0914]/30 backdrop-blur-[1px]"></div>

        {{-- Ambient Orbs --}}
        <div aria-hidden="true" class="absolute -top-24 -right-24 h-96 w-96 rounded-full bg-[#99ff04]/15 blur-[120px] pointer-events-none z-2"></div>
        <div aria-hidden="true" class="absolute -bottom-32 -left-20 h-96 w-96 rounded-full bg-purple-600/20 blur-[140px] pointer-events-none z-2"></div>

        {{-- Foreground Main Content Area --}}
        <div class="relative z-10 my-auto max-w-xl">
            {{-- Badge --}}
            <div class="inline-flex items-center gap-2 rounded-full border border-[#99ff04]/30 bg-[#99ff04]/10 px-4 py-1.5 text-xs font-black text-[#99ff04] backdrop-blur-md mb-6">
                <span class="h-2 w-2 rounded-full bg-[#99ff04] animate-pulse"></span>
                Transparansi Finansial Berbasis Kriptografi
            </div>

            <h2 class="text-3xl xl:text-4xl font-black leading-tight tracking-tight text-white transition-colors duration-300">
                &ldquo;Terkumpul ratusan juta tidak ada artinya tanpa jaminan kejujuran.&rdquo;
            </h2>
            <p class="mt-4 text-base leading-relaxed text-slate-300 transition-colors duration-300">
                Setiap rupiah yang masuk dan didistribusikan dikunci otomatis dengan bukti kuitansi HMAC-SHA256 &amp; audit pencairan bertahap per milestone.
            </p>

            {{-- 3 Pillars Stat Cards --}}
            <div class="mt-10 grid grid-cols-3 gap-4 border-t border-white/10 pt-8">
                <div class="hero-glass-card rounded-2xl border border-white/10 bg-[#1b182a]/80 p-4 backdrop-blur-md transition-all hover:border-[#99ff04]/40">
                    <div class="card-sub text-xs font-bold uppercase tracking-wider text-slate-400">Pencairan</div>
                    <div class="card-title mt-1.5 text-sm font-extrabold text-white">Bertahap</div>
                    <div class="card-sub mt-0.5 text-[11px] text-slate-400">Via Milestone RAB</div>
                </div>

                <div class="hero-glass-card rounded-2xl border border-white/10 bg-[#1b182a]/80 p-4 backdrop-blur-md transition-all hover:border-[#99ff04]/40">
                    <div class="card-sub text-xs font-bold uppercase tracking-wider text-slate-400">Kuitansi</div>
                    <div class="mt-1.5 text-sm font-extrabold text-[#99ff04]">HMAC-SHA256</div>
                    <div class="card-sub mt-0.5 text-[11px] text-slate-400">Verifikasi Publik</div>
                </div>

                <div class="hero-glass-card rounded-2xl border border-white/10 bg-[#1b182a]/80 p-4 backdrop-blur-md transition-all hover:border-[#99ff04]/40">
                    <div class="card-sub text-xs font-bold uppercase tracking-wider text-slate-400">Audit Ledger</div>
                    <div class="card-title mt-1.5 text-sm font-extrabold text-white">Anti-Falsifikasi</div>
                    <div class="card-sub mt-0.5 text-[11px] text-slate-400">Hash Rantai Terkunci</div>
                </div>
            </div>
        </div>

        {{-- Verification Footer Badge --}}
        <div class="relative z-10 flex items-center justify-between border-t border-white/10 pt-6">
            <div class="flex items-center gap-3">
                <div class="grid h-8 w-8 place-items-center rounded-full bg-[#99ff04]/20 text-[#99ff04]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div class="card-title text-xs font-extrabold text-white">100% Keamanan Terjamin</div>
                    <div class="card-sub text-[11px] text-slate-400">Autentikasi terenkripsi &amp; proteksi data pribadi</div>
                </div>
            </div>
            <div class="card-sub text-xs font-bold text-slate-400">DonasiTrust v2.5</div>
        </div>

    </div>

</div>
@livewireScripts
<script>
    function updateGuestThemeUI() {
        const isLight = document.documentElement.classList.contains('theme-light');
        const sunIcon = document.getElementById('guest-theme-sun');
        const moonIcon = document.getElementById('guest-theme-moon');
        
        if (sunIcon && moonIcon) {
            if (isLight) {
                sunIcon.classList.add('hidden');
                moonIcon.classList.remove('hidden');
            } else {
                sunIcon.classList.remove('hidden');
                moonIcon.classList.add('hidden');
            }
        }
    }

    function toggleGuestTheme(event) {
        const doToggle = () => {
            const isLight = document.documentElement.classList.toggle('theme-light');
            localStorage.setItem('dt_theme', isLight ? 'light' : 'dark');
            updateGuestThemeUI();
        };

        if (!document.startViewTransition || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            doToggle();
            return;
        }

        const rect = event && event.currentTarget ? event.currentTarget.getBoundingClientRect() : null;
        const x = rect ? rect.left + rect.width / 2 : window.innerWidth / 2;
        const y = rect ? rect.top + rect.height / 2 : window.innerHeight / 2;
        const endRadius = Math.hypot(
            Math.max(x, window.innerWidth - x),
            Math.max(y, window.innerHeight - y)
        );

        const transition = document.startViewTransition(() => {
            doToggle();
        });

        transition.ready.then(() => {
            const clipPath = [
                `circle(0px at ${x}px ${y}px)`,
                `circle(${endRadius}px at ${x}px ${y}px)`
            ];
            document.documentElement.animate(
                { clipPath: clipPath },
                {
                    duration: 500,
                    easing: 'ease-in-out',
                    pseudoElement: '::view-transition-new(root)'
                }
            );
        });
    }

    document.addEventListener('DOMContentLoaded', updateGuestThemeUI);
</script>
>>>>>>> Stashed changes
@livewireScripts
</body>
</html>
