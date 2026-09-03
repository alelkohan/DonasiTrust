@extends('layouts.guest')
@section('title', 'Daftar')

@section('content')
    <h1 class="text-2xl font-extrabold tracking-tight text-ink-900">Buat akun DonasiTrust</h1>
    <p class="mt-2 text-sm text-ink-600">
        Sudah punya akun? <a href="{{ route('login') }}" class="dt-link">Masuk di sini</a>
    </p>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4" x-data="{ role: '{{ old('role', 'donatur') }}' }">
        @csrf

        <fieldset>
            <legend class="dt-label">Saya ingin</legend>
            <div class="grid gap-2.5 sm:grid-cols-2">
                @foreach ([
                    ['donatur', 'Berdonasi', 'Langsung bisa donasi & lacak kuitansi.'],
                    ['pengaju', 'Menggalang dana', 'Perlu verifikasi identitas dulu.'],
                ] as [$value, $label, $hint])
                    <label class="cursor-pointer rounded-xl border p-3.5 transition-colors"
                           :class="role === '{{ $value }}' ? 'border-brand-600 bg-brand-50' : 'border-ink-200 hover:bg-ink-50'">
                        <input type="radio" name="role" value="{{ $value }}" x-model="role" class="sr-only">
                        <span class="block text-sm font-semibold text-ink-900">{{ $label }}</span>
                        <span class="mt-0.5 block text-xs text-ink-500">{{ $hint }}</span>
                    </label>
                @endforeach
            </div>
            @error('role') <p class="dt-error">{{ $message }}</p> @enderror
        </fieldset>

        <div>
            <label for="name" class="dt-label">Nama lengkap</label>
            <input id="name" name="name" type="text" required autocomplete="name"
                   value="{{ old('name') }}" class="dt-input" placeholder="Nama sesuai identitas">
            @error('name') <p class="dt-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="dt-label">Email</label>
            <input id="email" name="email" type="email" required autocomplete="email"
                   value="{{ old('email') }}" class="dt-input" placeholder="nama@contoh.com">
            @error('email') <p class="dt-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="phone" class="dt-label">Nomor WhatsApp <span class="font-normal text-ink-400">(opsional)</span></label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" class="dt-input" placeholder="08xxxxxxxxxx">
            @error('phone') <p class="dt-error">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="password" class="dt-label">Kata sandi</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" class="dt-input">
                <p class="dt-hint">Minimal 8 karakter, ada huruf dan angka.</p>
                @error('password') <p class="dt-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="dt-label">Ulangi kata sandi</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       autocomplete="new-password" class="dt-input">
            </div>
        </div>

        <label class="flex items-start gap-2.5 text-sm text-ink-700">
            <input type="checkbox" name="terms" value="1" required
                   class="mt-0.5 h-4 w-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500">
            <span>Saya setuju data yang saya kirim diperiksa admin untuk keperluan verifikasi.</span>
        </label>
        @error('terms') <p class="dt-error">{{ $message }}</p> @enderror

        <button type="submit" class="dt-btn-primary w-full py-3 text-base">Buat akun</button>
    </form>
@endsection
