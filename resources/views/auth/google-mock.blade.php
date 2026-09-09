@extends('layouts.guest')
@section('title', 'Pilih Akun Google (Simulasi)')

@section('content')
    <div class="rounded-2xl border border-white/15 bg-[#1b182a] p-6 shadow-xl sm:p-8 backdrop-blur-xl">
        <div class="flex items-center gap-3">
            <svg class="h-7 w-7 shrink-0" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            <div>
                <h1 class="text-lg font-black text-white">Simulasi Akun Google</h1>
                <p class="text-xs text-slate-400">Mode Pengujian Lokal</p>
            </div>
        </div>

        <div class="mt-4 rounded-xl border border-amber-400/30 bg-amber-400/10 p-3.5 text-xs leading-relaxed text-amber-300">
            <strong>Info Simulasi:</strong> Kredensial <code>GOOGLE_CLIENT_ID</code> belum dikonfigurasi di <code>.env</code>. Pilih akun simulasi di bawah untuk menguji alur SSO secara instan.
        </div>

        <div class="mt-6 space-y-3">
            <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">Pilih akun simulasi cepat:</p>

            <form method="POST" action="{{ route('auth.google.mock') }}">
                @csrf
                <input type="hidden" name="name" value="Ahmad Rizky">
                <input type="hidden" name="email" value="ahmad.rizky@gmail.com">
                <input type="hidden" name="google_id" value="mock-google-1001">
                <button type="submit" class="flex w-full items-center gap-3 rounded-2xl border border-white/10 bg-[#12101c] p-3.5 text-left transition-all hover:border-[#99ff04] hover:bg-white/5 cursor-pointer">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#99ff04] font-black text-black text-xs">AR</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-white">Ahmad Rizky</p>
                        <p class="truncate text-xs text-slate-400">ahmad.rizky@gmail.com</p>
                    </div>
                    <span class="text-xs font-bold text-[#99ff04]">Pilih &rarr;</span>
                </button>
            </form>

            <form method="POST" action="{{ route('auth.google.mock') }}">
                @csrf
                <input type="hidden" name="name" value="Siti Aminah">
                <input type="hidden" name="email" value="siti.aminah@gmail.com">
                <input type="hidden" name="google_id" value="mock-google-1002">
                <button type="submit" class="flex w-full items-center gap-3 rounded-2xl border border-white/10 bg-[#12101c] p-3.5 text-left transition-all hover:border-[#99ff04] hover:bg-white/5 cursor-pointer">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-emerald-500 font-black text-black text-xs">SA</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-white">Siti Aminah</p>
                        <p class="truncate text-xs text-slate-400">siti.aminah@gmail.com</p>
                    </div>
                    <span class="text-xs font-bold text-[#99ff04]">Pilih &rarr;</span>
                </button>
            </form>
        </div>

        <div class="relative my-6">
            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-white/10"></div></div>
            <div class="relative flex justify-center text-[10px] uppercase font-extrabold text-slate-500"><span class="bg-[#1b182a] px-3">atau masukkan email Google kustom</span></div>
        </div>

        <form method="POST" action="{{ route('auth.google.mock') }}" class="space-y-3">
            @csrf
            <div>
                <label for="name" class="block text-xs font-extrabold uppercase tracking-wider text-slate-300 mb-1">Nama Lengkap</label>
                <input id="name" name="name" type="text" required placeholder="Nama Anda" class="w-full rounded-2xl border border-white/15 bg-[#12101c] py-2.5 px-4 text-sm font-semibold text-white placeholder-slate-500 focus:border-[#99ff04] focus:outline-none">
            </div>
            <div>
                <label for="email" class="block text-xs font-extrabold uppercase tracking-wider text-slate-300 mb-1">Email Gmail</label>
                <input id="email" name="email" type="email" required placeholder="contoh@gmail.com" class="w-full rounded-2xl border border-white/15 bg-[#12101c] py-2.5 px-4 text-sm font-semibold text-white placeholder-slate-500 focus:border-[#99ff04] focus:outline-none">
            </div>
            <button type="submit" class="w-full inline-flex items-center justify-center rounded-2xl bg-[#99ff04] py-3 text-xs font-black text-black shadow-lg hover:bg-[#85e000] cursor-pointer">Masuk dengan Akun Ini</button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-xs font-bold text-slate-400 hover:text-white transition-colors">&larr; Kembali ke halaman Masuk</a>
        </div>
    </div>
@endsection
