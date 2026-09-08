@php
    $u = auth()->user();
    $isHome = request()->routeIs('home');
    $isKampanye = request()->routeIs('kampanye.*');
    $isTransparansi = request()->routeIs('transparansi');
    $isRiwayat = request()->routeIs('donatur.dashboard');
    $isDashboard = request()->routeIs('*.dashboard') || request()->routeIs('profil.*') || request()->routeIs('admin.*') || request()->routeIs('pengaju.*');
@endphp

{{-- Floating Pill Style Bottom Navigation for Mobile --}}
<nav class="fixed bottom-3 inset-x-4 z-50 max-w-md mx-auto rounded-full border border-white/15 bg-[#13111c]/90 text-slate-300 shadow-lg shadow-black/40 backdrop-blur-xl md:hidden transition-all duration-300 mobile-pill-nav" aria-label="Navigasi Bawah Mobile">
    <div class="flex h-13 items-center justify-around px-2">

        {{-- 1. Beranda --}}
        <a href="{{ route('home') }}" class="mobile-nav-item flex flex-col items-center justify-center flex-1 h-full py-1 text-[10px] leading-none transition-colors {{ $isHome ? 'is-active text-[#99ff04] font-black' : 'text-slate-400 hover:text-white font-bold' }}">
            <div class="{{ $isHome ? 'nav-icon-box bg-[#99ff04] text-black rounded-full p-1.5 mb-0.5 shadow-sm flex items-center justify-center' : 'p-1.5 mb-0.5 flex items-center justify-center' }}">
                <svg class="h-4 w-4 {{ $isHome ? 'stroke-black' : '' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $isHome ? '2.3' : '1.7' }}" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </div>
            <span class="truncate">Beranda</span>
        </a>

        {{-- 2. Kampanye / Donasi --}}
        <a href="{{ route('kampanye.index') }}" class="mobile-nav-item flex flex-col items-center justify-center flex-1 h-full py-1 text-[10px] leading-none transition-colors {{ $isKampanye ? 'is-active text-[#99ff04] font-black' : 'text-slate-400 hover:text-white font-bold' }}">
            <div class="{{ $isKampanye ? 'nav-icon-box bg-[#99ff04] text-black rounded-full p-1.5 mb-0.5 shadow-sm flex items-center justify-center' : 'p-1.5 mb-0.5 flex items-center justify-center' }}">
                <svg class="h-4 w-4 {{ $isKampanye ? 'stroke-black' : '' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $isKampanye ? '2.3' : '1.7' }}" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                </svg>
            </div>
            <span class="truncate">Donasi</span>
        </a>

        {{-- 3. Transparansi --}}
        <a href="{{ route('transparansi') }}" class="mobile-nav-item flex flex-col items-center justify-center flex-1 h-full py-1 text-[10px] leading-none transition-colors {{ $isTransparansi ? 'is-active text-[#99ff04] font-black' : 'text-slate-400 hover:text-white font-bold' }}">
            <div class="{{ $isTransparansi ? 'nav-icon-box bg-[#99ff04] text-black rounded-full p-1.5 mb-0.5 shadow-sm flex items-center justify-center' : 'p-1.5 mb-0.5 flex items-center justify-center' }}">
                <svg class="h-4 w-4 {{ $isTransparansi ? 'stroke-black' : '' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $isTransparansi ? '2.3' : '1.7' }}" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <path d="m9 12 2 2 4-4"/>
                </svg>
            </div>
            <span class="truncate">Audit</span>
        </a>

        {{-- 4. Riwayat Donasi Saya --}}
        @guest
            <a href="{{ route('login') }}" class="mobile-nav-item flex flex-col items-center justify-center flex-1 h-full py-1 text-[10px] leading-none transition-colors text-slate-400 hover:text-white font-bold">
                <div class="p-1.5 mb-0.5 flex items-center justify-center">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 8v4l3 3"/>
                        <circle cx="12" cy="12" r="9"/>
                    </svg>
                </div>
                <span class="truncate">Riwayat</span>
            </a>
        @else
            <a href="{{ route('donatur.dashboard') }}" class="mobile-nav-item flex flex-col items-center justify-center flex-1 h-full py-1 text-[10px] leading-none transition-colors {{ $isRiwayat ? 'is-active text-[#99ff04] font-black' : 'text-slate-400 hover:text-white font-bold' }}">
                <div class="{{ $isRiwayat ? 'nav-icon-box bg-[#99ff04] text-black rounded-full p-1.5 mb-0.5 shadow-sm flex items-center justify-center' : 'p-1.5 mb-0.5 flex items-center justify-center' }}">
                    <svg class="h-4 w-4 {{ $isRiwayat ? 'stroke-black' : '' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $isRiwayat ? '2.3' : '1.7' }}" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 8v4l3 3"/>
                        <circle cx="12" cy="12" r="9"/>
                    </svg>
                </div>
                <span class="truncate">Riwayat</span>
            </a>
        @endguest

        {{-- 5. Akun / Dasbor --}}
        @guest
            <a href="{{ route('login') }}" class="mobile-nav-item flex flex-col items-center justify-center flex-1 h-full py-1 text-[10px] leading-none transition-colors text-slate-400 hover:text-white font-bold">
                <div class="p-1.5 mb-0.5 flex items-center justify-center">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <span class="truncate">Masuk</span>
            </a>
        @else
            <a href="{{ $u->homeRoute() }}" class="mobile-nav-item flex flex-col items-center justify-center flex-1 h-full py-1 text-[10px] leading-none transition-colors {{ $isDashboard && ! $isRiwayat ? 'is-active text-[#99ff04] font-black' : 'text-slate-400 hover:text-white font-bold' }}">
                <div class="{{ $isDashboard && ! $isRiwayat ? 'nav-icon-box bg-[#99ff04] text-black rounded-full p-1.5 mb-0.5 shadow-sm flex items-center justify-center' : 'p-1.5 mb-0.5 flex items-center justify-center' }}">
                    <span class="grid h-4 w-4 place-items-center rounded-full bg-[#99ff04] text-black font-black text-[9px] shadow-xs">
                        {{ Str::upper(Str::substr($u->name, 0, 1)) }}
                    </span>
                </div>
                <span class="truncate">Akun</span>
            </a>
        @endguest

    </div>
</nav>
