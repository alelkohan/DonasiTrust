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
                    @php $isActive = old('role', 'donatur') === $value; @endphp
                    <label class="group relative flex cursor-pointer flex-col justify-between rounded-xl border p-3.5 transition-all duration-200 select-none has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50/70 has-[:checked]:ring-2 has-[:checked]:ring-brand-500/20 has-[:checked]:shadow-xs"
                           :class="role === '{{ $value }}' ? 'border-brand-600 bg-brand-50/70 ring-2 ring-brand-500/20 shadow-xs' : 'border-ink-200 hover:border-ink-300 hover:bg-ink-50/60'">
                        <input type="radio" name="role" value="{{ $value }}" x-model="role"
                               {{ $isActive ? 'checked' : '' }} class="sr-only">
                        <div class="flex items-start justify-between gap-2">
                            <span class="block text-sm font-bold text-ink-900 group-has-[:checked]:text-brand-900"
                                  :class="role === '{{ $value }}' ? 'text-brand-900' : 'text-ink-900'">
                                {{ $label }}
                            </span>
                            <span class="grid h-4 w-4 shrink-0 place-items-center rounded-full border transition-all duration-200"
                                  :class="role === '{{ $value }}' ? 'border-brand-600 bg-brand-600 text-white' : 'border-ink-300 bg-white text-transparent'">
                                <svg class="h-2.5 w-2.5 stroke-current" viewBox="0 0 12 12" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2.5 6L5 8.5L9.5 3.5"/>
                                </svg>
                            </span>
                        </div>
                        <span class="mt-1 block text-xs text-ink-500 group-has-[:checked]:text-brand-800/80">{{ $hint }}</span>
                    </label>
                @endforeach
            </div>
            @error('role') <p class="dt-error">{{ $message }}</p> @enderror
        </fieldset>

        <div class="pt-2">
            <a :href="'{{ route('auth.google') }}?role=' + role" class="flex w-full items-center justify-center gap-3 rounded-xl border border-ink-200 bg-white py-3 px-4 text-sm font-semibold text-ink-700 shadow-xs hover:bg-ink-50 hover:border-ink-300 transition-colors">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                <span>Daftar dengan Google</span>
            </a>
        </div>

        <div class="relative my-4">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-ink-200"></div>
            </div>
            <div class="relative flex justify-center text-xs text-ink-500 uppercase">
                <span class="bg-white px-3">atau isi formulir manual</span>
            </div>
        </div>

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
