@php
    $u = auth()->user();
    $isHome = request()->routeIs('home');
    $isKampanye = request()->routeIs('kampanye.*');
    $isTransparansi = request()->routeIs('transparansi');
    $isRiwayat = request()->routeIs('donatur.dashboard');
    $isDashboard = request()->routeIs('*.dashboard') || request()->routeIs('profil.*') || request()->routeIs('admin.*') || request()->routeIs('pengaju.*');
@endphp

<nav class="fixed bottom-0 inset-x-0 z-50 border-t border-ink-200/90 bg-white/95 backdrop-blur-md md:hidden shadow-[0_-4px_16px_rgba(15,23,42,0.08)]" aria-label="Navigasi Bawah Mobile">
    <div class="flex h-14 items-center justify-around px-1">

        {{-- 1. Beranda --}}
        <a href="{{ route('home') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-[10px] leading-none transition-colors {{ $isHome ? 'text-brand-600 font-bold' : 'text-ink-500 hover:text-ink-900' }}">
            <svg class="h-5 w-5 mb-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $isHome ? '2.3' : '1.7' }}" stroke-linecap="round" stroke-linejoin="round">
                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <span class="truncate">Beranda</span>
        </a>

        {{-- 2. Kampanye / Donasi --}}
        <a href="{{ route('kampanye.index') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-[10px] leading-none transition-colors {{ $isKampanye ? 'text-brand-600 font-bold' : 'text-ink-500 hover:text-ink-900' }}">
            <svg class="h-5 w-5 mb-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $isKampanye ? '2.3' : '1.7' }}" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
            </svg>
            <span class="truncate">Donasi</span>
        </a>

        {{-- 3. Transparansi --}}
        <a href="{{ route('transparansi') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-[10px] leading-none transition-colors {{ $isTransparansi ? 'text-brand-600 font-bold' : 'text-ink-500 hover:text-ink-900' }}">
            <svg class="h-5 w-5 mb-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $isTransparansi ? '2.3' : '1.7' }}" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                <path d="m9 12 2 2 4-4"/>
            </svg>
            <span class="truncate">Audit</span>
        </a>

        {{-- 4. Riwayat Donasi Saya --}}
        @guest
            <a href="{{ route('login') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-[10px] leading-none transition-colors text-ink-500 hover:text-ink-900">
                <svg class="h-5 w-5 mb-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 8v4l3 3"/>
                    <circle cx="12" cy="12" r="9"/>
                </svg>
                <span class="truncate">Riwayat</span>
            </a>
        @else
            <a href="{{ route('donatur.dashboard') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-[10px] leading-none transition-colors {{ $isRiwayat ? 'text-brand-600 font-bold' : 'text-ink-500 hover:text-ink-900' }}">
                <svg class="h-5 w-5 mb-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $isRiwayat ? '2.3' : '1.7' }}" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 8v4l3 3"/>
                    <circle cx="12" cy="12" r="9"/>
                </svg>
                <span class="truncate">Riwayat</span>
            </a>
        @endguest

        {{-- 5. Akun / Dasbor --}}
        @guest
            <a href="{{ route('login') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-[10px] leading-none transition-colors text-ink-500 hover:text-ink-900">
                <svg class="h-5 w-5 mb-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <span class="truncate">Masuk</span>
            </a>
        @else
            <a href="{{ $u->homeRoute() }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-[10px] leading-none transition-colors {{ $isDashboard && ! $isRiwayat ? 'text-brand-600 font-bold' : 'text-ink-500 hover:text-ink-900' }}">
                <span class="grid h-5 w-5 mb-1 place-items-center rounded-full bg-brand-600 text-[10px] font-bold text-white shadow-xs">
                    {{ Str::upper(Str::substr($u->name, 0, 1)) }}
                </span>
                <span class="truncate">Akun</span>
            </a>
        @endguest

    </div>
</nav>
