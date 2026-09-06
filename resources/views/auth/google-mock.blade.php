@extends('layouts.guest')
@section('title', 'Pilih Akun Google (Simulasi)')

@section('content')
    <div class="rounded-2xl border border-ink-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex items-center gap-3">
            <svg class="h-7 w-7 shrink-0" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            <div>
                <h1 class="text-lg font-bold text-ink-900">Simulasi Akun Google</h1>
                <p class="text-xs text-ink-500">Mode Pengembangan Lokal</p>
            </div>
        </div>

        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-xs leading-relaxed text-amber-900">
            <strong>Catatan Pengembang:</strong> <code>GOOGLE_CLIENT_ID</code> belum diisi di file <code>.env</code>. Halaman ini mensimulasikan login akun Google agar alur aplikasi dapat langsung diuji coba.
        </div>

        <div class="mt-6 space-y-3">
            <p class="text-xs font-semibold uppercase tracking-wider text-ink-500">Pilih akun simulasi cepat:</p>

            <form method="POST" action="{{ route('auth.google.mock') }}">
                @csrf
                <input type="hidden" name="name" value="Ahmad Rizky">
                <input type="hidden" name="email" value="ahmad.rizky@gmail.com">
                <input type="hidden" name="google_id" value="mock-google-1001">
                <button type="submit" class="flex w-full items-center gap-3 rounded-xl border border-ink-200 p-3.5 text-left transition hover:border-brand-500 hover:bg-brand-50/50">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-brand-600 font-bold text-white">AR</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-ink-900">Ahmad Rizky</p>
                        <p class="truncate text-xs text-ink-500">ahmad.rizky@gmail.com</p>
                    </div>
                    <span class="text-xs font-semibold text-brand-700">Pilih &rarr;</span>
                </button>
            </form>

            <form method="POST" action="{{ route('auth.google.mock') }}">
                @csrf
                <input type="hidden" name="name" value="Siti Aminah">
                <input type="hidden" name="email" value="siti.aminah@gmail.com">
                <input type="hidden" name="google_id" value="mock-google-1002">
                <button type="submit" class="flex w-full items-center gap-3 rounded-xl border border-ink-200 p-3.5 text-left transition hover:border-brand-500 hover:bg-brand-50/50">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-emerald-600 font-bold text-white">SA</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-ink-900">Siti Aminah</p>
                        <p class="truncate text-xs text-ink-500">siti.aminah@gmail.com</p>
                    </div>
                    <span class="text-xs font-semibold text-brand-700">Pilih &rarr;</span>
                </button>
            </form>
        </div>

        <div class="relative my-6">
            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-ink-200"></div></div>
            <div class="relative flex justify-center text-xs uppercase text-ink-500"><span class="bg-white px-3">atau masukkan email Google kustom</span></div>
        </div>

        <form method="POST" action="{{ route('auth.google.mock') }}" class="space-y-3">
            @csrf
            <div>
                <label for="name" class="dt-label text-xs">Nama Lengkap</label>
                <input id="name" name="name" type="text" required placeholder="Nama Anda" class="dt-input text-sm">
            </div>
            <div>
                <label for="email" class="dt-label text-xs">Email Gmail</label>
                <input id="email" name="email" type="email" required placeholder="contoh@gmail.com" class="dt-input text-sm">
            </div>
            <button type="submit" class="dt-btn-primary w-full py-2.5 text-sm">Masuk dengan Akun Ini</button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-xs font-semibold text-ink-500 hover:text-ink-800">&larr; Kembali ke halaman Masuk</a>
        </div>
    </div>
@endsection
