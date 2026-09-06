@extends('layouts.guest')
@section('title', 'Masuk')

@section('content')
    <h1 class="text-2xl font-extrabold tracking-tight text-ink-900">Masuk ke akun Anda</h1>
    <p class="mt-2 text-sm text-ink-600">
        Belum punya akun?
        <a href="{{ route('register') }}" class="dt-link">Daftar gratis</a>
    </p>

    @if ($errors->any())
        <div role="alert" class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="mt-6">
        <a href="{{ route('auth.google') }}" class="flex w-full items-center justify-center gap-3 rounded-xl border border-ink-200 bg-white py-3 px-4 text-sm font-semibold text-ink-700 shadow-xs hover:bg-ink-50 hover:border-ink-300 transition-colors">
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            <span>Lanjutkan dengan Google</span>
        </a>
    </div>

    <div class="relative my-6">
        <div class="absolute inset-0 flex items-center" aria-hidden="true">
            <div class="w-full border-t border-ink-200"></div>
        </div>
        <div class="relative flex justify-center text-xs text-ink-500 uppercase">
            <span class="bg-white px-3">atau masuk dengan email</span>
        </div>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="dt-label">Email</label>
            <input id="email" name="email" type="email" required autofocus autocomplete="username"
                   value="{{ old('email') }}" class="dt-input" placeholder="nama@contoh.com">
        </div>

        <div>
            <label for="password" class="dt-label">Kata sandi</label>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   class="dt-input" placeholder="••••••••">
        </div>

        <label class="flex items-center gap-2.5 text-sm text-ink-700">
            <input type="checkbox" name="remember" value="1"
                   class="h-4 w-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500">
            Ingat saya di perangkat ini
        </label>

        <button type="submit" class="dt-btn-primary w-full py-3 text-base">Masuk</button>
    </form>

    <div class="mt-8 rounded-xl border border-ink-200 bg-white p-4">
        <p class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Akun demo</p>
        <ul class="mt-2.5 space-y-1.5 text-sm text-ink-600">
            <li><code class="rounded bg-ink-100 px-1.5 py-0.5 text-xs">jokibuat121@gmail.com</code> &mdash; Administrator</li>
            <li><code class="rounded bg-ink-100 px-1.5 py-0.5 text-xs">pengaju@donasitrust.test</code> &mdash; Pengaju kampanye</li>
            <li><code class="rounded bg-ink-100 px-1.5 py-0.5 text-xs">donatur@donasitrust.test</code> &mdash; Donatur</li>
        </ul>
        <p class="mt-2.5 text-xs text-ink-500">Kata sandi semua akun demo: <code class="rounded bg-ink-100 px-1.5 py-0.5">password123</code></p>
    </div>
@endsection
