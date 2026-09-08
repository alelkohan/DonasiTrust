@php($u = auth()->user())

<header x-data="{
    open: false,
    isDark: !(localStorage.getItem('dt_theme') === 'light'),
    init() {
        this.isDark = !(localStorage.getItem('dt_theme') === 'light');
        document.addEventListener('livewire:navigated', () => {
            this.isDark = !(localStorage.getItem('dt_theme') === 'light');
        });
    },
    toggleTheme(event) {
        const doToggle = () => {
            this.isDark = !this.isDark;
            if (this.isDark) {
                document.documentElement.classList.remove('theme-light');
                localStorage.setItem('dt_theme', 'dark');
            } else {
                document.documentElement.classList.add('theme-light');
                localStorage.setItem('dt_theme', 'light');
            }
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
}" class="sticky top-0 z-40 h-[60px] border-b border-white/10 bg-[#13111c]/95 text-white backdrop-blur-md transition-colors duration-300">
    <nav class="w-full h-full max-w-[1650px] mx-auto px-4 sm:px-6 lg:px-8 flex items-center gap-3" aria-label="Navigasi utama">

        {{-- Brand Logo --}}
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5 group">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-[#99ff04] text-black font-black transition-transform group-hover:scale-105" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 3 4 6.2v5.1c0 4.6 3.2 8.4 8 9.7 4.8-1.3 8-5.1 8-9.7V6.2Z"/>
                    <path d="m9 12 2.2 2.2L15.4 10"/>
                </svg>
            </span>
            <span class="text-lg font-black tracking-tight text-white group-hover:text-[#99ff04] transition-colors brand-logo-text">
                Donasi<span class="text-[#99ff04] brand-trust-text">Trust</span>
            </span>
        </a>

        {{-- Main Navigation Links --}}
        <div class="hidden items-center gap-1 md:flex ml-4">
            <a href="{{ route('kampanye.index') }}" class="nav-link-item px-3 py-1.5 text-xs font-bold transition-colors {{ request()->routeIs('kampanye.*') ? 'is-active text-[#99ff04]' : 'text-slate-300 hover:text-white' }}">
                Eksplor Kampanye
            </a>
            <a href="{{ route('transparansi') }}" class="nav-link-item px-3 py-1.5 text-xs font-bold transition-colors {{ request()->routeIs('transparansi*') ? 'is-active text-[#99ff04]' : 'text-slate-300 hover:text-white' }}">
                Audit Ledger HMAC
            </a>
            @if(Route::has('verifikasi.form'))
                <a href="{{ route('verifikasi.form') }}" class="nav-link-item px-3 py-1.5 text-xs font-bold transition-colors {{ request()->routeIs('verifikasi.*') ? 'is-active text-[#99ff04]' : 'text-slate-300 hover:text-white' }}">
                    Verifikasi Kuitansi
                </a>
            @endif
        </div>

        {{-- Desktop Right Actions --}}
        <div class="ml-auto hidden items-center gap-2.5 md:flex">
            {{-- Theme Switcher Button (Icon Only with Circular Reveal Animation) --}}
            <button type="button"
                    @click="toggleTheme($event)"
                    title="Ganti Mode Terang / Gelap"
                    aria-label="Ganti Mode Terang / Gelap"
                    class="grid h-9 w-9 place-items-center rounded-full border border-white/15 bg-[#231f36] text-slate-200 hover:border-[#99ff04] hover:text-white transition-all shadow-md cursor-pointer select-none">
                <template x-if="isDark">
                    <svg class="h-4 w-4 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                </template>
                <template x-if="!isDark">
                    <svg class="h-4 w-4 text-sky-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </template>
            </button>



            {{-- Solid Neon CTA Button --}}
            <a href="{{ route('register') }}" class="rounded-full bg-[#99ff04] px-4 py-1.5 text-xs font-black text-black hover:bg-[#84e000] transition-transform hover:scale-105 active:scale-95">
                Jadi Pengaju+
            </a>

            @guest
                <a href="{{ route('login') }}" class="rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-xs font-bold text-white hover:bg-white/20 transition-all">
                    Masuk
                </a>
            @else
                <a href="{{ $u->homeRoute() }}" class="rounded-full border border-white/20 bg-white/10 px-3.5 py-1.5 text-xs font-bold text-white flex items-center gap-2 hover:bg-white/20 transition-all">
                    <span class="grid h-5 w-5 place-items-center rounded-full bg-[#99ff04] text-[10px] font-black text-black">
                        {{ Str::upper(Str::substr($u->name, 0, 1)) }}
                    </span>
                    Dasbor
                </a>

                {{-- User Profile & Logout Dropdown --}}
                <div class="relative" x-data="{ userMenu: false }" @keydown.escape="userMenu = false">
                    <button type="button" @click="userMenu = !userMenu" :aria-expanded="userMenu.toString()"
                            title="Menu Pengguna"
                            class="grid h-9 w-9 place-items-center rounded-full border border-white/15 bg-[#231f36] text-slate-200 hover:border-[#99ff04] hover:text-white transition-all shadow-md cursor-pointer select-none">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </button>

                    <div x-show="userMenu" x-cloak @click.outside="userMenu = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                         class="absolute right-0 mt-2 w-64 origin-top-right rounded-2xl border border-white/15 bg-[#1b182a] p-2 text-white shadow-2xl backdrop-blur-xl z-50">
                        <div class="px-3 py-2.5 border-b border-white/10">
                            <p class="truncate text-xs font-black text-white">{{ $u->name }}</p>
                            <p class="truncate text-[11px] text-slate-400">{{ $u->email }}</p>
                            <span class="mt-1.5 inline-block rounded-md bg-[#99ff04]/10 border border-[#99ff04]/20 px-2 py-0.5 text-[10px] font-black text-[#99ff04] uppercase">
                                {{ $u->roleLabel() }}
                            </span>
                        </div>
                        <div class="py-1.5 flex flex-col gap-0.5">
                            <a href="{{ $u->homeRoute() }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-bold text-slate-300 hover:bg-white/10 hover:text-white transition-colors">
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                                <span>Dasbor Saya</span>
                            </a>
                            <a href="{{ route('profil.edit') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-bold text-slate-300 hover:bg-white/10 hover:text-white transition-colors">
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <span>Profil Saya</span>
                            </a>
                            @if (! $u->isAdmin())
                                <a href="{{ route('verifikasi.identitas') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-bold text-slate-300 hover:bg-white/10 hover:text-white transition-colors">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    <span>Verifikasi Identitas</span>
                                </a>
                            @endif
                        </div>
                        <div class="border-t border-white/10 pt-1.5">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-bold text-rose-400 hover:bg-rose-500/10 transition-colors cursor-pointer text-left">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    <span>Keluar Akun</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endguest
        </div>

        {{-- Mobile Header Right --}}
        <div class="ml-auto flex items-center gap-2 md:hidden">
            {{-- Mobile Theme Switcher --}}
            <button type="button"
                    @click="toggleTheme($event)"
                    title="Ganti Mode Terang / Gelap"
                    aria-label="Ganti Mode Terang / Gelap"
                    class="grid h-8 w-8 place-items-center rounded-full border border-white/15 bg-[#231f36] text-slate-200 hover:border-[#99ff04] hover:text-white transition-all shadow-md cursor-pointer select-none">
                <template x-if="isDark">
                    <svg class="h-4 w-4 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                </template>
                <template x-if="!isDark">
                    <svg class="h-4 w-4 text-sky-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </template>
            </button>

            <a href="{{ route('register') }}" class="rounded-full bg-[#99ff04] px-3 py-1 text-[11px] font-black text-black">
                + Pengaju
            </a>

            <button type="button" @click="open = !open" class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-[#231f36] text-slate-300">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    <path x-show="open" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </nav>

    {{-- Mobile Dropdown Menu Drawer --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-3"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-3"
         @click.away="open = false"
         class="mobile-nav-drawer md:hidden border-t border-b px-4 py-4 space-y-3"
         style="display: none;">
        
        <div class="flex flex-col gap-1.5">
            <a href="{{ route('kampanye.index') }}" 
               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold transition-all {{ request()->routeIs('kampanye.*') ? 'bg-[#99ff04]/10 text-[#99ff04]' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                <span>Eksplor Kampanye</span>
            </a>

            <a href="{{ route('transparansi') }}" 
               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold transition-all {{ request()->routeIs('transparansi*') ? 'bg-[#99ff04]/10 text-[#99ff04]' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                <span>Audit Ledger HMAC</span>
            </a>

            @if(Route::has('verifikasi.form'))
                <a href="{{ route('verifikasi.form') }}" 
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold transition-all {{ request()->routeIs('verifikasi.*') ? 'bg-[#99ff04]/10 text-[#99ff04]' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    <span>Verifikasi Kuitansi</span>
                </a>
            @endif
        </div>

        <div class="pt-3 border-t border-white/10 flex flex-col gap-2">
            @guest
                <a href="{{ route('login') }}" class="w-full text-center rounded-xl border border-white/20 bg-white/10 py-2.5 text-xs font-extrabold text-white hover:bg-white/20 transition-all">
                    Masuk Akun
                </a>
                <a href="{{ route('register') }}" class="w-full text-center rounded-xl bg-[#99ff04] py-2.5 text-xs font-black text-black hover:bg-[#84e000] transition-all">
                    Daftar Sebagai Pengaju+
                </a>
            @else
                <a href="{{ $u->homeRoute() }}" class="w-full flex items-center justify-center gap-2 rounded-xl bg-[#99ff04] py-2.5 text-xs font-black text-black">
                    <span>Ke Dasbor ({{ $u->name }})</span>
                </a>
                <a href="{{ route('profil.edit') }}" class="w-full flex items-center justify-center gap-2 rounded-xl border border-white/15 bg-[#231f36] py-2.5 text-xs font-extrabold text-white hover:bg-white/10 transition-all">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span>Profil Saya</span>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 rounded-xl border border-rose-500/30 bg-rose-500/10 py-2.5 text-xs font-extrabold text-rose-400 hover:bg-rose-500/20 transition-all cursor-pointer">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span>Keluar Akun</span>
                    </button>
                </form>
            @endguest
        </div>
    </div>
</header>
