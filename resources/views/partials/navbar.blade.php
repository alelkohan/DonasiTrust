@php($u = auth()->user())

<header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-ink-200/80 bg-white/90 backdrop-blur">
    <nav class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3 sm:px-6 lg:px-8" aria-label="Navigasi utama">

        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-600 text-white shadow-sm" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 3 4 6.2v5.1c0 4.6 3.2 8.4 8 9.7 4.8-1.3 8-5.1 8-9.7V6.2Z"/>
                    <path d="m9 12 2.2 2.2L15.4 10"/>
                </svg>
            </span>
            <span class="text-lg font-extrabold tracking-tight text-ink-900">
                Donasi<span class="text-brand-600">Trust</span>
            </span>
        </a>

        <div class="hidden items-center gap-1 md:flex">
            @if (! ($is_dashboard ?? false))
                <x-nav-link :href="route('kampanye.index')" :active="request()->routeIs('kampanye.index')">Kampanye</x-nav-link>
                <x-nav-link :href="route('transparansi')" :active="request()->routeIs('transparansi')">Transparansi</x-nav-link>
                <x-nav-link :href="route('verifikasi.form')" :active="request()->routeIs('verifikasi.*')">Cek Kuitansi</x-nav-link>
            @endif
        </div>

        <div class="ml-auto hidden items-center gap-2 md:flex">
            @guest
                <a href="{{ route('login') }}" class="dt-btn-secondary">Masuk</a>
                <a href="{{ route('register') }}" class="dt-btn-primary">Daftar</a>
            @else
                <a href="{{ $u->homeRoute() }}" class="dt-btn-secondary">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
                    Dasbor
                </a>
                <div class="relative" x-data="{ menu: false }" @keydown.escape="menu = false">
                    <button type="button" @click="menu = !menu" :aria-expanded="menu.toString()"
                            class="flex items-center gap-2 rounded-xl border border-ink-200 py-1.5 pr-3 pl-1.5 text-sm font-semibold text-ink-700 hover:bg-ink-50">
                        <span class="grid h-7 w-7 place-items-center rounded-lg bg-brand-100 text-xs font-bold text-brand-800">
                            {{ Str::upper(Str::substr($u->name, 0, 2)) }}
                        </span>
                        {{ Str::of($u->name)->explode(" ")->first() }}
                    </button>
                    <div x-show="menu" x-cloak @click.outside="menu = false" x-transition.origin.top.right
                         class="dt-card absolute right-0 mt-2 w-60 overflow-hidden p-1.5">
                        <div class="px-3 py-2">
                            <p class="truncate text-sm font-semibold text-ink-900">{{ $u->name }}</p>
                            <p class="truncate text-xs text-ink-500">{{ $u->email }}</p>
                            <p class="mt-1.5 text-xs font-medium text-brand-700">{{ $u->roleLabel() }}</p>
                        </div>
                        <hr class="my-1 border-ink-100">
                        <a href="{{ route('profil.edit') }}" class="block rounded-lg px-3 py-2 text-sm text-ink-700 hover:bg-ink-50">Profil saya</a>
                        @if (! $u->isAdmin())
                            <a href="{{ route('verifikasi.identitas') }}" class="block rounded-lg px-3 py-2 text-sm text-ink-700 hover:bg-ink-50">
                                Verifikasi identitas
                            </a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50">
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            @endguest
        </div>

        <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-controls="menu-mobile"
                class="ml-auto grid h-10 w-10 place-items-center rounded-xl border border-ink-200 text-ink-700 md:hidden">
            <span class="sr-only">Buka menu</span>
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path x-show="!open" d="M4 7h16M4 12h16M4 17h16"/>
                <path x-show="open" x-cloak d="m6 6 12 12M18 6 6 18"/>
            </svg>
        </button>
    </nav>

    <div id="menu-mobile" x-show="open" x-cloak x-collapse class="border-t border-ink-200 bg-white md:hidden">
        <div class="space-y-1 px-4 py-3">
            @if (! ($is_dashboard ?? false))
                <a href="{{ route('kampanye.index') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-ink-700 hover:bg-ink-50">Kampanye</a>
                <a href="{{ route('transparansi') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-ink-700 hover:bg-ink-50">Transparansi</a>
                <a href="{{ route('verifikasi.form') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-ink-700 hover:bg-ink-50">Cek Kuitansi</a>
                <hr class="my-2 border-ink-100">
            @endif
            @guest
                <a href="{{ route('login') }}" class="dt-btn-secondary w-full">Masuk</a>
                <a href="{{ route('register') }}" class="dt-btn-primary mt-2 w-full">Daftar</a>
            @else
                <a href="{{ $u->homeRoute() }}" class="dt-btn-primary w-full">Dasbor saya</a>
                <a href="{{ route('profil.edit') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-ink-700 hover:bg-ink-50">Profil</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="w-full rounded-lg px-3 py-2.5 text-left text-sm font-medium text-rose-600 hover:bg-rose-50">Keluar</button>
                </form>
            @endguest
        </div>
    </div>
</header>
