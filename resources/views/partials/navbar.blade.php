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
    <nav class="w-full h-full px-1.5 sm:px-2 lg:px-3 flex items-center gap-3" aria-label="Navigasi utama">

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
                <a href="{{ $u->homeRoute() }}" class="rounded-full border border-white/20 bg-white/10 px-3.5 py-1.5 text-xs font-bold text-white flex items-center gap-2">
                    <span class="grid h-5 w-5 place-items-center rounded-full bg-[#99ff04] text-[10px] font-black text-black">
                        {{ Str::upper(Str::substr($u->name, 0, 1)) }}
                    </span>
                    Dasbor
                </a>
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
</header>
