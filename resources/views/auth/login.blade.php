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

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
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
            <li><code class="rounded bg-ink-100 px-1.5 py-0.5 text-xs">admin@donasitrust.test</code> &mdash; Administrator</li>
            <li><code class="rounded bg-ink-100 px-1.5 py-0.5 text-xs">pengaju@donasitrust.test</code> &mdash; Pengaju kampanye</li>
            <li><code class="rounded bg-ink-100 px-1.5 py-0.5 text-xs">donatur@donasitrust.test</code> &mdash; Donatur</li>
        </ul>
        <p class="mt-2.5 text-xs text-ink-500">Kata sandi semua akun demo: <code class="rounded bg-ink-100 px-1.5 py-0.5">password123</code></p>
    </div>
@endsection
