<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Masuk') &middot; DonasiTrust</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full">
<div class="grid min-h-screen lg:grid-cols-2">

    {{-- Kolom kiri: form --}}
    <div class="flex flex-col px-5 py-8 sm:px-10 lg:px-16">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-600 text-white" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 3 4 6.2v5.1c0 4.6 3.2 8.4 8 9.7 4.8-1.3 8-5.1 8-9.7V6.2Z"/><path d="m9 12 2.2 2.2L15.4 10"/>
                </svg>
            </span>
            <span class="text-lg font-extrabold tracking-tight text-ink-900">Donasi<span class="text-brand-600">Trust</span></span>
        </a>

        <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center py-10">
            @yield('content')
        </div>

        <p class="text-center text-xs text-ink-400">&copy; {{ date('Y') }} DonasiTrust &middot; SwitchFest 2026</p>
    </div>

    {{-- Kolom kanan: konteks produk (disembunyikan di layar kecil) --}}
    <div class="relative hidden overflow-hidden bg-ink-900 lg:block">
        <div aria-hidden="true" class="absolute -top-24 -right-24 h-96 w-96 rounded-full bg-brand-600/20 blur-3xl"></div>
        <div aria-hidden="true" class="absolute -bottom-32 -left-20 h-96 w-96 rounded-full bg-sky-500/10 blur-3xl"></div>

        <div class="relative flex h-full flex-col justify-center px-16">
            <blockquote class="max-w-lg">
                <p class="text-3xl leading-snug font-extrabold tracking-tight text-white">
                    &ldquo;Terkumpul Rp120 juta&rdquo; tidak berarti apa-apa kalau tidak ada yang bisa
                    memeriksa ke mana perginya.
                </p>
                <footer class="mt-6 text-sm text-ink-400">
                    Itulah kenapa di sini setiap pencairan butuh persetujuan, dan setiap tahap butuh nota.
                </footer>
            </blockquote>

            <dl class="mt-12 grid max-w-lg grid-cols-3 gap-6 border-t border-white/10 pt-8">
                <div>
                    <dt class="text-xs font-semibold tracking-wide text-ink-400 uppercase">Pencairan</dt>
                    <dd class="mt-1 text-sm font-semibold text-white">Bertahap, per milestone</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold tracking-wide text-ink-400 uppercase">Kuitansi</dt>
                    <dd class="mt-1 text-sm font-semibold text-white">HMAC-SHA256</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold tracking-wide text-ink-400 uppercase">Audit</dt>
                    <dd class="mt-1 text-sm font-semibold text-white">Rantai hash</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
</body>
</html>
