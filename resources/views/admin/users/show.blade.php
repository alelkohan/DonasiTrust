@extends('layouts.dashboard')
@section('title', 'Tinjau: '.$user->name)

@php($menu = \App\Support\AdminMenu::items('pengguna'))

@section('panel')
    <nav class="mb-5 text-sm text-ink-500">
        <a href="{{ route('admin.pengguna.index') }}" class="hover:text-brand-700">&larr; Kembali ke daftar</a>
    </nav>

    <h1 class="text-2xl font-extrabold tracking-tight text-ink-900">{{ $user->name }}</h1>

    <div class="mt-6 grid gap-6 lg:grid-cols-2 lg:items-start">
        <section class="dt-card p-5 sm:p-6">
            <h2 class="text-lg font-bold text-ink-900">Data pengguna</h2>
            <dl class="mt-4 space-y-3.5 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-500">Email</dt>
                    <dd class="text-right font-semibold break-all text-ink-900">{{ $user->email }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-500">Telepon</dt>
                    <dd class="font-semibold text-ink-900">{{ $user->phone ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-500">Peran</dt>
                    <dd class="font-semibold text-ink-900">{{ $user->roleLabel() }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-500">Lembaga</dt>
                    <dd class="text-right font-semibold text-ink-900">{{ $user->organization ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-500">4 digit akhir NIK</dt>
                    <dd class="font-mono font-semibold text-ink-900">
                        {{ $user->identity_number_last4 ? '••••••••••••'.$user->identity_number_last4 : '—' }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-500">Jumlah kampanye</dt>
                    <dd class="font-semibold text-ink-900">{{ $user->campaigns_count }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-ink-500">Status</dt>
                    <dd>
                        <x-badge :tone="match($user->verification_status) {
                            'verified' => 'success',
                            'pending' => 'warning',
                            'rejected' => 'danger',
                            default => 'neutral',
                        }">{{ $user->verificationLabel() }}</x-badge>
                    </dd>
                </div>
            </dl>

            {{-- Rekening tujuan pencairan: WAJIB diperiksa admin --}}
            <div class="mt-5 rounded-xl border p-4 {{ $user->hasPayoutAccount() ? 'border-ink-200 bg-ink-50/60' : 'border-amber-200 bg-amber-50' }}">
                <h3 class="text-sm font-bold text-ink-900">Rekening tujuan pencairan</h3>

                @if ($user->hasPayoutAccount())
                    <dl class="mt-3 space-y-2.5 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-500">Bank</dt>
                            <dd class="font-semibold text-ink-900">{{ $user->bank_name }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-500">Nomor rekening</dt>
                            <dd class="font-mono font-semibold text-ink-900">{{ $user->bank_account_number }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-500">Atas nama</dt>
                            <dd class="text-right font-semibold text-ink-900">{{ $user->bank_account_holder }}</dd>
                        </div>
                    </dl>
                    <p class="mt-3 text-xs leading-relaxed text-ink-600">
                        Cocokkan nama pemilik rekening dengan nama di KTP sebelum memverifikasi.
                        Setelah terverifikasi, seluruh pencairan kampanye milik pengguna ini hanya
                        bisa mengalir ke rekening ini.
                    </p>
                @else
                    <p class="mt-2 text-sm text-amber-900">
                        Belum diisi. Pengguna ini tidak akan bisa mengajukan pencairan sampai
                        rekening tujuannya terdaftar.
                    </p>
                @endif
            </div>

            @if ($user->identity_document_path)
                <a href="{{ route('berkas.identitas', $user) }}" target="_blank" rel="noopener"
                   class="dt-btn-secondary mt-5 w-full">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                    Buka dokumen identitas
                </a>
                <p class="dt-hint text-center">Dibuka lewat route terproteksi, bukan URL publik.</p>
            @else
                <p class="mt-5 rounded-xl border border-dashed border-ink-300 px-4 py-6 text-center text-sm text-ink-500">
                    Pengguna belum mengunggah dokumen identitas.
                </p>
            @endif
        </section>

        <section class="dt-card p-5 sm:p-6">
            <h2 class="text-lg font-bold text-ink-900">Keputusan verifikasi</h2>

            @if ($user->verification_note)
                <div class="mt-4 rounded-xl border border-ink-200 bg-ink-50 px-4 py-3 text-sm text-ink-700">
                    <p class="font-semibold">Catatan terakhir</p>
                    <p class="mt-1">{{ $user->verification_note }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.pengguna.decide', $user) }}" class="mt-5 space-y-4"
                  x-data="{ keputusan: 'verified' }">
                @csrf

                <fieldset>
                    <legend class="dt-label">Keputusan</legend>
                    <div class="grid gap-2.5 sm:grid-cols-2">
                        @foreach ([['verified', 'Verifikasi'], ['rejected', 'Tolak']] as [$value, $label])
                            <label class="cursor-pointer rounded-xl border p-3.5 text-center transition-colors"
                                   :class="keputusan === '{{ $value }}'
                                       ? '{{ $value === 'verified' ? 'border-brand-600 bg-brand-50' : 'border-rose-500 bg-rose-50' }}'
                                       : 'border-ink-200 hover:bg-ink-50'">
                                <input type="radio" name="decision" value="{{ $value }}" x-model="keputusan" class="sr-only">
                                <span class="text-sm font-semibold text-ink-900">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div>
                    <label for="note" class="dt-label">
                        Catatan <span x-show="keputusan === 'rejected'" class="text-rose-600">*</span>
                    </label>
                    <textarea id="note" name="note" rows="3" class="dt-input" maxlength="1000"
                              :required="keputusan === 'rejected'"
                              placeholder="Wajib diisi jika menolak — jelaskan apa yang kurang."></textarea>
                    @error('note') <p class="dt-error">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="dt-btn-primary w-full py-3">Simpan keputusan</button>
                <p class="text-center text-xs text-ink-500">Keputusan ini tercatat permanen di jejak audit.</p>
            </form>
        </section>
    </div>
@endsection
