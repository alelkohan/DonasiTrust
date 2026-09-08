@extends('layouts.guest')
@section('title', 'Masuk')

@section('content')
<div x-data="{
    email: '{{ old('email', '') }}',
    password: '',
    showPassword: false
}">
    {{-- Heading --}}
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white brand-logo-text">
            Selamat datang kembali
        </h1>
        <p class="mt-2 text-xs sm:text-sm font-medium text-slate-400">
            Belum punya akun?
            <a href="{{ route('register') }}" class="font-extrabold hover:underline transition-all">
                Daftar gratis sekarang
            </a>
        </p>
    </div>

    {{-- Error Alert --}}
    @if ($errors->any())
        <div role="alert" class="mb-6 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-xs font-semibold text-rose-400 backdrop-blur-md flex items-center gap-3">
            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-xl bg-rose-500/20 text-rose-300">
                ⚠️
            </span>
            <div>{{ $errors->first() }}</div>
        </div>
    @endif

    {{-- Google Auth Button --}}
    <div class="mt-6">
        <a href="{{ route('auth.google') }}" class="flex w-full items-center justify-center gap-3 rounded-2xl border border-white/20 bg-white py-3 px-4 text-sm font-bold text-slate-800 shadow-md hover:bg-slate-100 hover:shadow-lg transition-all active:scale-[0.99]">
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            <span>Lanjutkan dengan Google</span>
        </a>
    </div>

    {{-- Divider --}}
    <div class="relative my-6">
        <div class="absolute inset-0 flex items-center" aria-hidden="true">
            <div class="w-full border-t border-white/10"></div>
        </div>
        <div class="relative flex justify-center text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
            <span class="bg-[#1b182a] px-3">atau masuk dengan email</span>
        </div>
    </div>

    {{-- Main Login Form --}}
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        {{-- Email Field --}}
        <div>
            <label for="email" class="block text-xs font-extrabold uppercase tracking-wider text-slate-300 mb-1.5">
                Alamat Email
            </label>
            <div class="relative flex items-center">
                <span class="absolute left-3.5 text-slate-400 pointer-events-none">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </span>
                <input id="email" 
                       name="email" 
                       type="email" 
                       x-model="email" 
                       required 
                       autofocus 
                       autocomplete="username"
                       placeholder="nama@contoh.com"
                       class="w-full rounded-2xl border border-white/15 bg-[#1b182a] py-3 pl-10 pr-4 text-sm font-semibold text-white placeholder-slate-500 shadow-md backdrop-blur-md transition-all focus:border-[#99ff04] focus:outline-none focus:ring-1 focus:ring-[#99ff04] dt-input">
            </div>
        </div>

        {{-- Password Field --}}
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-xs font-extrabold uppercase tracking-wider text-slate-300">
                    Kata Sandi
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs font-bold text-slate-400 hover:text-[#99ff04] transition-all">
                        Lupa kata sandi?
                    </a>
                @endif
            </div>
            <div class="relative flex items-center">
                <span class="absolute left-3.5 text-slate-400 pointer-events-none">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </span>
                <input id="password" 
                       name="password" 
                       :type="showPassword ? 'text' : 'password'" 
                       x-model="password" 
                       required 
                       autocomplete="current-password"
                       placeholder="••••••••"
                       class="w-full rounded-2xl border border-white/15 bg-[#1b182a] py-3 pl-10 pr-11 text-sm font-semibold text-white placeholder-slate-500 shadow-md backdrop-blur-md transition-all focus:border-[#99ff04] focus:outline-none focus:ring-1 focus:ring-[#99ff04] dt-input">
                
                {{-- Show/Hide Toggle --}}
                <button type="button" 
                        @click="showPassword = !showPassword" 
                        class="absolute right-3.5 text-slate-400 hover:text-white transition-all focus:outline-none">
                    <template x-if="!showPassword">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </template>
                    <template x-if="showPassword">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a8.962 8.962 0 012.122-.382c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21M3 3l18 18"/>
                        </svg>
                    </template>
                </button>
            </div>
        </div>

        {{-- Remember Me --}}
        <div class="flex items-center justify-between pt-1">
            <label class="flex items-center gap-2.5 cursor-pointer text-xs font-bold text-slate-300 select-none">
                <input type="checkbox" 
                       name="remember" 
                       value="1"
                       class="h-4 w-4 rounded-md border-white/20 bg-[#1b182a] text-[#99ff04] focus:ring-1 focus:ring-[#99ff04]">
                Ingat saya di perangkat ini
            </label>
        </div>

        {{-- Submit Button --}}
        <button type="submit" 
                class="w-full inline-flex items-center justify-center gap-2 rounded-2xl bg-[#99ff04] py-3.5 px-4 text-sm font-black text-black shadow-lg shadow-[#99ff04]/25 transition-all hover:bg-[#85e000] hover:scale-[1.01] active:scale-[0.99] cursor-pointer">
            <span>Masuk ke Akun</span>
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
            <span class="bg-[#12101c] px-3 text-slate-500 rounded-full">atau masuk dengan</span>
        </div>
    </div>

    {{-- Social Google SSO Button --}}
    <a href="{{ route('auth.google') }}" 
       class="flex w-full items-center justify-center gap-3 rounded-2xl border border-white/15 bg-[#1b182a] py-3 px-4 text-xs font-bold text-white shadow-md transition-all hover:bg-white/10 hover:border-white/30 focus:outline-none">
        <svg class="h-4 w-4" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
        </svg>
        <span>Lanjutkan dengan Google</span>
    </a>
    <div class="mt-8 rounded-2xl border border-white/10 bg-[#1b182a] p-4 text-xs">
        <p class="font-extrabold uppercase tracking-wider text-slate-400">Akun Demo Pengujian</p>
        <ul class="mt-2.5 space-y-1.5 font-medium text-slate-300">
            <li><code class="rounded bg-[#231f36] px-2 py-0.5 text-xs text-[#99ff04] font-mono">jokibuat121@gmail.com</code> &mdash; Administrator</li>
            <li><code class="rounded bg-[#231f36] px-2 py-0.5 text-xs text-[#99ff04] font-mono">pengaju@donasitrust.test</code> &mdash; Pengaju kampanye</li>
            <li><code class="rounded bg-[#231f36] px-2 py-0.5 text-xs text-[#99ff04] font-mono">donatur@donasitrust.test</code> &mdash; Donatur</li>
        </ul>
        <p class="mt-2.5 text-slate-400">Kata sandi semua akun demo: <code class="rounded bg-[#231f36] px-2 py-0.5 font-mono text-white">password123</code></p>
    </div>
</div>
@endsection
