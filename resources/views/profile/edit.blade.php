@extends('layouts.dashboard')
@section('title', 'Profil Saya')

@section('panel')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="border-b border-white/10 pb-5">
        <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white">Profil Saya</h1>
        <p class="mt-1 text-xs sm:text-sm font-medium text-slate-400">Kelola identitas, nomor WhatsApp, kata sandi, dan status keamanan akun Anda</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        
        {{-- Section 1: Data Diri Form --}}
        <section class="dt-card p-6">
            <h2 class="text-base font-black text-white flex items-center gap-2">
                <span class="grid h-7 w-7 place-items-center rounded-lg bg-[#99ff04] text-black text-xs font-black">1</span>
                <span>Informasi Data Diri</span>
            </h2>

            <form method="POST" action="{{ route('profil.update') }}" class="mt-6 space-y-4">
                @csrf @method('PUT')

                <div>
                    <label for="name" class="block text-xs font-bold text-slate-300 mb-1.5">Nama Lengkap</label>
                    <input id="name" name="name" type="text" required 
                           class="dt-input text-sm" 
                           value="{{ old('name', $user->name) }}">
                </div>

                <div>
                    <label for="email-display" class="block text-xs font-bold text-slate-300 mb-1.5">Alamat Email</label>
                    <input id="email-display" type="email" 
                           class="dt-input text-sm text-slate-400 cursor-not-allowed opacity-75" 
                           value="{{ $user->email }}" disabled>
                    <p class="mt-1 text-[11px] text-slate-400">Email terkunci demi keamanan — hubungi admin jika perlu perubahan.</p>
                </div>

                <div>
                    <label for="phone" class="block text-xs font-bold text-slate-300 mb-1.5">Nomor WhatsApp</label>
                    <input id="phone" name="phone" type="tel" 
                           class="dt-input text-sm" 
                           value="{{ old('phone', $user->phone) }}" placeholder="08xxxxxxxxxx">
                </div>

                @if ($user->isPengaju())
                    <div>
                        <label for="organization" class="block text-xs font-bold text-slate-300 mb-1.5">Lembaga / Organisasi</label>
                        <input id="organization" name="organization" type="text" 
                               class="dt-input text-sm"
                               value="{{ old('organization', $user->organization) }}"
                               placeholder="Nama lembaga yang akan tampil di halaman kampanye">
                    </div>
                @endif

                <div class="pt-2">
                    <button type="submit" class="w-full rounded-full bg-[#99ff04] px-6 py-2.5 text-xs font-black text-black hover:bg-[#84e000] shadow-md shadow-[#99ff04]/20 transition-all">
                        Simpan Perubahan Data Diri
                    </button>
                </div>
            </form>
        </section>

        {{-- Section 2: Kata Sandi & Status Akun --}}
        <div class="space-y-6">
            {{-- Form Ganti Kata Sandi --}}
            <section class="dt-card p-6">
                <h2 class="text-base font-black text-white flex items-center gap-2">
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-[#231f36] text-[#99ff04] text-xs font-black border border-white/10">2</span>
                    <span>{{ $user->password ? 'Perbarui Kata Sandi' : 'Buat Kata Sandi Akun' }}</span>
                </h2>

                <form method="POST" action="{{ route('profil.password') }}" class="mt-6 space-y-4">
                    @csrf @method('PUT')

                    @if ($user->password)
                        <div>
                            <label for="current_password" class="block text-xs font-bold text-slate-300 mb-1.5">Kata Sandi Saat Ini</label>
                            <input id="current_password" name="current_password" type="password" required
                                   autocomplete="current-password" 
                                   class="dt-input text-sm">
                            @error('current_password') <p class="mt-1 text-xs font-bold text-rose-400">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Akun Anda terhubung dengan Google. Anda dapat membuat kata sandi mandiri di bawah ini.
                        </p>
                    @endif

                    <div>
                        <label for="new_password" class="block text-xs font-bold text-slate-300 mb-1.5">Kata Sandi Baru</label>
                        <input id="new_password" name="password" type="password" required
                               autocomplete="new-password" 
                               class="dt-input text-sm">
                        <p class="mt-1 text-[11px] text-slate-400">Minimal 8 karakter, kombinasi huruf dan angka.</p>
                        @error('password') <p class="mt-1 text-xs font-bold text-rose-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold text-slate-300 mb-1.5">Ulangi Kata Sandi Baru</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                               autocomplete="new-password" 
                               class="dt-input text-sm">
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-[#12101c] p-4">
                        <x-otp-input purpose="password_change" label="Kode Verifikasi Email" />
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="dt-btn-secondary w-full text-xs">
                            {{ $user->password ? 'Perbarui Kata Sandi' : 'Simpan Kata Sandi' }}
                        </button>
                    </div>
                </form>
            </section>

            {{-- Ringkasan Status Akun --}}
            <section class="dt-card p-6">
                <h2 class="text-base font-black text-white">Status &amp; Keamanan Akun</h2>
                
                <dl class="mt-4 space-y-3.5 text-xs border-t border-white/10 pt-4">
                    <div class="flex justify-between items-center">
                        <dt class="text-slate-400">Peran Pengguna</dt>
                        <dd class="font-black text-white bg-[#231f36] px-3 py-1 rounded-full border border-white/10">
                            {{ $user->roleLabel() }}
                        </dd>
                    </div>

                    <div class="flex justify-between items-center">
                        <dt class="text-slate-400">Verifikasi Identitas</dt>
                        <dd>
                            @if ($user->isVerified())
                                <span class="rounded-full bg-emerald-500/15 px-3 py-1 text-[11px] font-black text-emerald-400 border border-emerald-500/30">
                                    ✓ Terverifikasi
                                </span>
                            @else
                                <span class="rounded-full bg-amber-500/15 px-3 py-1 text-[11px] font-black text-amber-300 border border-amber-500/30">
                                    {{ $user->verificationLabel() }}
                                </span>
                            @endif
                        </dd>
                    </div>

                    <div class="flex justify-between items-center">
                        <dt class="text-slate-400">Tanggal Bergabung</dt>
                        <dd class="font-bold text-white">{{ $user->created_at->translatedFormat('d F Y') }}</dd>
                    </div>
                </dl>

                @if (! $user->isVerified() && ! $user->isAdmin())
                    <div class="mt-6">
                        <a href="{{ route('verifikasi.identitas') }}" class="flex w-full items-center justify-center gap-2 rounded-full bg-[#99ff04] py-2.5 text-xs font-black text-black hover:bg-[#84e000] shadow-md shadow-[#99ff04]/20 transition-all">
                            <span>Verifikasi Identitas Sekarang &rarr;</span>
                        </a>
                    </div>
                @endif
            </section>

        </div>

    </div>

</div>
@endsection
