@extends('layouts.dashboard')
@section('title', 'Profil saya')


@section('panel')
    <h1 class="text-2xl font-extrabold tracking-tight text-ink-900">Profil saya</h1>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="dt-card p-5 sm:p-6">
            <h2 class="text-lg font-bold text-ink-900">Data diri</h2>
            <form method="POST" action="{{ route('profil.update') }}" class="mt-5 space-y-4">
                @csrf @method('PUT')

                <div>
                    <label for="name" class="dt-label">Nama lengkap</label>
                    <input id="name" name="name" type="text" required class="dt-input" value="{{ old('name', $user->name) }}">
                </div>

                <div>
                    <label class="dt-label" for="email-display">Email</label>
                    <input id="email-display" type="email" class="dt-input bg-ink-50" value="{{ $user->email }}" disabled>
                    <p class="dt-hint">Email tidak bisa diubah sendiri — hubungi admin jika perlu diganti.</p>
                </div>

                <div>
                    <label for="phone" class="dt-label">Nomor WhatsApp</label>
                    <input id="phone" name="phone" type="tel" class="dt-input" value="{{ old('phone', $user->phone) }}">
                </div>

                @if ($user->isPengaju())
                    <div>
                        <label for="organization" class="dt-label">Lembaga / organisasi</label>
                        <input id="organization" name="organization" type="text" class="dt-input"
                               value="{{ old('organization', $user->organization) }}"
                               placeholder="Nama lembaga yang tampil di kampanye">
                    </div>
                @endif

                <button type="submit" class="dt-btn-primary">Simpan perubahan</button>
            </form>
        </section>

        <div class="space-y-6">
            <section class="dt-card p-5 sm:p-6">
                <h2 class="text-lg font-bold text-ink-900">Ganti kata sandi</h2>
                <form method="POST" action="{{ route('profil.password') }}" class="mt-5 space-y-4">
                    @csrf @method('PUT')

                    <div>
                        <label for="current_password" class="dt-label">Kata sandi saat ini</label>
                        <input id="current_password" name="current_password" type="password" required
                               autocomplete="current-password" class="dt-input">
                    </div>

                    <div>
                        <label for="new_password" class="dt-label">Kata sandi baru</label>
                        <input id="new_password" name="password" type="password" required
                               autocomplete="new-password" class="dt-input">
                        <p class="dt-hint">Minimal 8 karakter, ada huruf dan angka.</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="dt-label">Ulangi kata sandi baru</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                               autocomplete="new-password" class="dt-input">
                    </div>

                    <button type="submit" class="dt-btn-secondary">Perbarui kata sandi</button>
                </form>
            </section>

            <section class="dt-card p-5 sm:p-6">
                <h2 class="text-lg font-bold text-ink-900">Status akun</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-500">Peran</dt>
                        <dd class="font-semibold text-ink-900">{{ $user->roleLabel() }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-ink-500">Verifikasi identitas</dt>
                        <dd>
                            <x-badge :tone="match($user->verification_status) {
                                'verified' => 'success',
                                'pending' => 'warning',
                                'rejected' => 'danger',
                                default => 'neutral',
                            }">{{ $user->verificationLabel() }}</x-badge>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-ink-500">Verifikasi dua langkah</dt>
                        <dd>
                            <x-badge :tone="$user->hasTwoFactorEnabled() ? 'success' : 'warning'">
                                {{ $user->hasTwoFactorEnabled() ? 'Aktif' : 'Belum aktif' }}
                            </x-badge>
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-500">Bergabung</dt>
                        <dd class="font-semibold text-ink-900">{{ $user->created_at->translatedFormat('d F Y') }}</dd>
                    </div>
                </dl>

                <a href="{{ route('keamanan.index') }}" class="dt-btn-secondary mt-5 w-full">
                    {{ $user->hasTwoFactorEnabled() ? 'Kelola keamanan akun' : 'Aktifkan verifikasi dua langkah' }}
                </a>

                @if (! $user->isVerified() && ! $user->isAdmin())
                    <a href="{{ route('verifikasi.identitas') }}" class="dt-btn-primary mt-3 w-full">
                        Verifikasi identitas sekarang
                    </a>
                @endif
            </section>
        </div>
    </div>
@endsection
