@extends('layouts.guest')
@section('title', 'Daftar Akun Baru')

@section('content')
<div>
    {{-- Heading --}}
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white brand-logo-text">
            Buat akun DonasiTrust ✨
        </h1>
        <p class="mt-2 text-xs sm:text-sm font-medium text-slate-400">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="font-extrabold text-[#99ff04] hover:underline transition-all">
                Masuk di sini
            </a>
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4" x-data="{ role: '{{ old('role', 'donatur') }}' }">
        @csrf

        {{-- Role Selection Radio --}}
        <div>
            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-300 mb-2">
                Saya ingin mendaftar sebagai:
            </label>
            <div class="grid gap-2.5 sm:grid-cols-2">
                @foreach ([
                    ['donatur', 'Berdonasi', 'Langsung donasi & lacak bukti kuitansi.'],
                    ['pengaju', 'Menggalang Dana', 'Memerlukan verifikasi identitas (KTP).'],
                ] as [$value, $label, $hint])
                    <label class="cursor-pointer rounded-2xl border p-3.5 transition-all select-none"
                           :class="role === '{{ $value }}' ? 'border-[#99ff04] bg-[#99ff04]/10 shadow-md' : 'border-white/15 bg-[#1b182a] hover:border-white/30'">
                        <input type="radio" name="role" value="{{ $value }}" x-model="role" class="sr-only">
                        <span class="block text-sm font-extrabold text-white" :class="role === '{{ $value }}' ? 'text-[#99ff04]' : ''">{{ $label }}</span>
                        <span class="mt-0.5 block text-[11px] font-medium text-slate-400">{{ $hint }}</span>
                    </label>
                @endforeach
            </div>
            @error('role') <p class="mt-1 text-xs font-bold text-rose-400">{{ $message }}</p> @enderror
        </div>

        {{-- Full Name --}}
        <div>
            <label for="name" class="block text-xs font-extrabold uppercase tracking-wider text-slate-300 mb-1.5">
                Nama Lengkap
            </label>
            <input id="name" name="name" type="text" required autocomplete="name"
                   value="{{ old('name') }}" 
                   placeholder="Sesuai kartu identitas (KTP/SIM)"
                   class="w-full rounded-2xl border border-white/15 bg-[#1b182a] py-3 px-4 text-sm font-semibold text-white placeholder-slate-500 shadow-md backdrop-blur-md transition-all focus:border-[#99ff04] focus:outline-none focus:ring-1 focus:ring-[#99ff04] dt-input">
            @error('name') <p class="mt-1 text-xs font-bold text-rose-400">{{ $message }}</p> @enderror
        </div>

        {{-- Email --}}
        <div>
            <label for="email" class="block text-xs font-extrabold uppercase tracking-wider text-slate-300 mb-1.5">
                Alamat Email
            </label>
            <input id="email" name="email" type="email" required autocomplete="email"
                   value="{{ old('email') }}" 
                   placeholder="nama@contoh.com"
                   class="w-full rounded-2xl border border-white/15 bg-[#1b182a] py-3 px-4 text-sm font-semibold text-white placeholder-slate-500 shadow-md backdrop-blur-md transition-all focus:border-[#99ff04] focus:outline-none focus:ring-1 focus:ring-[#99ff04] dt-input">
            @error('email') <p class="mt-1 text-xs font-bold text-rose-400">{{ $message }}</p> @enderror
        </div>

        {{-- Phone --}}
        <div>
            <label for="phone" class="block text-xs font-extrabold uppercase tracking-wider text-slate-300 mb-1.5">
                Nomor WhatsApp <span class="font-normal text-slate-400">(opsional)</span>
            </label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" 
                   placeholder="081234567890"
                   class="w-full rounded-2xl border border-white/15 bg-[#1b182a] py-3 px-4 text-sm font-semibold text-white placeholder-slate-500 shadow-md backdrop-blur-md transition-all focus:border-[#99ff04] focus:outline-none focus:ring-1 focus:ring-[#99ff04] dt-input">
            @error('phone') <p class="mt-1 text-xs font-bold text-rose-400">{{ $message }}</p> @enderror
        </div>

        {{-- Password Fields --}}
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="password" class="block text-xs font-extrabold uppercase tracking-wider text-slate-300 mb-1.5">
                    Kata Sandi
                </label>
                <input id="password" name="password" type="password" required autocomplete="new-password" placeholder="••••••••"
                       class="w-full rounded-2xl border border-white/15 bg-[#1b182a] py-3 px-4 text-sm font-semibold text-white placeholder-slate-500 shadow-md backdrop-blur-md transition-all focus:border-[#99ff04] focus:outline-none focus:ring-1 focus:ring-[#99ff04] dt-input">
                @error('password') <p class="mt-1 text-xs font-bold text-rose-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-xs font-extrabold uppercase tracking-wider text-slate-300 mb-1.5">
                    Ulangi Sandi
                </label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       autocomplete="new-password" placeholder="••••••••"
                       class="w-full rounded-2xl border border-white/15 bg-[#1b182a] py-3 px-4 text-sm font-semibold text-white placeholder-slate-500 shadow-md backdrop-blur-md transition-all focus:border-[#99ff04] focus:outline-none focus:ring-1 focus:ring-[#99ff04] dt-input">
            </div>
        </div>

        {{-- Terms Checkbox --}}
        <label class="flex items-start gap-2.5 cursor-pointer text-xs font-bold text-slate-300 select-none pt-1">
            <input type="checkbox" name="terms" value="1" required
                   class="mt-0.5 h-4 w-4 rounded-md border-white/20 bg-[#1b182a] text-[#99ff04] focus:ring-1 focus:ring-[#99ff04]">
            <span>Saya setuju data yang saya kirimkan diperiksa untuk verifikasi dan akuntabilitas.</span>
        </label>
        @error('terms') <p class="mt-1 text-xs font-bold text-rose-400">{{ $message }}</p> @enderror

        {{-- Submit Button --}}
        <button type="submit" 
                class="w-full inline-flex items-center justify-center gap-2 rounded-2xl bg-[#99ff04] py-3.5 px-4 text-sm font-black text-black shadow-lg shadow-[#99ff04]/25 transition-all hover:bg-[#85e000] hover:scale-[1.01] active:scale-[0.99] cursor-pointer">
            <span>Buat Akun Sekarang</span>
            <svg class="h-4 w-4 stroke-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
        </button>
    </form>

    {{-- Divider --}}
    <div class="relative my-6">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-white/10"></div>
        </div>
        <div class="relative flex justify-center text-[10px] font-extrabold uppercase tracking-widest">
            <span class="bg-[#12101c] px-3 text-slate-500 rounded-full">atau daftar dengan</span>
        </div>
    </div>

    {{-- Social Google SSO Button with dynamic role parameter --}}
    <a :href="'{{ route('auth.google') }}?role=' + role"
       class="flex w-full items-center justify-center gap-3 rounded-2xl border border-white/15 bg-[#1b182a] py-3 px-4 text-xs font-bold text-white shadow-md transition-all hover:bg-white/10 hover:border-white/30 focus:outline-none">
        <svg class="h-4 w-4" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
        </svg>
        <span>Daftar Cepat dengan Google</span>
    </a>
</div>
@endsection
