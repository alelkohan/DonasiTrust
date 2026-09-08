@extends('layouts.dashboard')
@section('title', 'Tinjau: '.$user->name)

@php($menu = \App\Support\AdminMenu::items('pengguna'))

@section('panel')
    <nav class="mb-5 text-sm text-slate-400">
        <a href="{{ route('admin.pengguna.index') }}" class="hover:text-[#99ff04] transition-colors">&larr; Kembali ke daftar</a>
    </nav>

    <h1 class="text-2xl font-black tracking-tight text-white">{{ $user->name }}</h1>

    <div class="mt-6 grid gap-6 lg:grid-cols-2 lg:items-start">
        <section class="dt-card p-5 sm:p-6">
            <h2 class="text-lg font-black text-white">Data pengguna</h2>
            <dl class="mt-4 space-y-3.5 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Email</dt>
                    <dd class="text-right font-semibold break-all text-slate-200">{{ $user->email }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Telepon</dt>
                    <dd class="font-bold text-white">{{ $user->phone ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Peran</dt>
                    <dd class="font-bold text-white">{{ $user->roleLabel() }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Lembaga</dt>
                    <dd class="text-right font-bold text-white">{{ $user->organization ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">4 digit akhir NIK</dt>
                    <dd class="font-mono font-bold text-[#99ff04]">
                        {{ $user->identity_number_last4 ? '••••••••••••'.$user->identity_number_last4 : '—' }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Jumlah kampanye</dt>
                    <dd class="font-bold text-white">{{ $user->campaigns_count }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Status</dt>
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
            <div class="mt-5 rounded-2xl border p-4 {{ $user->hasPendingPayoutAccount() ? 'border-amber-500/30 bg-amber-500/10' : ($user->hasPayoutAccount() ? 'border-white/10 bg-[#231f36]/60' : 'border-amber-500/30 bg-amber-500/10') }}">
                @if ($user->hasPendingPayoutAccount())
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-amber-500/20 px-2.5 py-0.5 text-xs font-bold text-amber-300 border border-amber-500/30">Perubahan Rekening Diajukan</span>
                    </div>
                    <h3 class="mt-2 text-sm font-black text-white">Rekening Baru yang Diajukan</h3>
                    <dl class="mt-2.5 space-y-2 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Bank Baru</dt>
                            <dd class="font-bold text-white">{{ $user->pending_bank_name }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Nomor Rekening Baru</dt>
                            <dd class="font-mono font-bold text-[#99ff04]">{{ $user->pending_bank_account_number }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Atas Nama Baru</dt>
                            <dd class="text-right font-bold text-white">{{ $user->pending_bank_account_holder }}</dd>
                        </div>
                    </dl>

                    @if ($user->hasPayoutAccount())
                        <div class="mt-3.5 border-t border-amber-500/20 pt-3 text-xs text-slate-300">
                            <span class="font-bold text-amber-300">Rekening Saat Ini (Aktif):</span>
                            <p class="mt-0.5 font-mono text-slate-200">{{ $user->bank_name }} - {{ $user->bank_account_number }} a.n. {{ $user->bank_account_holder }}</p>
                            <p class="mt-1 text-slate-400">Jika ditolak, sistem otomatis membatalkan perubahan dan akun kembali memakai rekening aktif di atas.</p>
                        </div>
                    @endif
                @elseif ($user->hasPayoutAccount())
                    <h3 class="text-sm font-black text-white">Rekening tujuan pencairan</h3>
                    <dl class="mt-3 space-y-2.5 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Bank</dt>
                            <dd class="font-bold text-white">{{ $user->bank_name }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Nomor rekening</dt>
                            <dd class="font-mono font-bold text-[#99ff04]">{{ $user->bank_account_number }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Atas nama</dt>
                            <dd class="text-right font-bold text-white">{{ $user->bank_account_holder }}</dd>
                        </div>
                    </dl>
                    <p class="mt-3 text-xs leading-relaxed text-slate-400">
                        Cocokkan nama pemilik rekening dengan nama di KTP sebelum memverifikasi.
                        Setelah terverifikasi, seluruh pencairan kampanye milik pengguna ini hanya
                        bisa mengalir ke rekening ini.
                    </p>
                @else
                    <h3 class="text-sm font-black text-white">Rekening tujuan pencairan</h3>
                    <p class="mt-2 text-xs font-medium text-amber-300">
                        Belum diisi. Pengguna ini tidak akan bisa mengajukan pencairan sampai
                        rekening tujuannya terdaftar.
                    </p>
                @endif
            </div>

            @if ($user->identity_document_path)
                <a href="{{ route('berkas.identitas', $user) }}" target="_blank" rel="noopener"
                   class="dt-btn-secondary mt-5 w-full text-xs">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                    Buka dokumen identitas
                </a>
                <p class="mt-2 text-center text-xs text-slate-500">Dibuka lewat route terproteksi, bukan URL publik.</p>
            @else
                <p class="mt-5 rounded-2xl border border-dashed border-white/20 px-4 py-6 text-center text-xs font-medium text-slate-400">
                    Pengguna belum mengunggah dokumen identitas.
                </p>
            @endif
        </section>

        <section class="dt-card p-5 sm:p-6">
            <h2 class="text-lg font-black text-white">Keputusan verifikasi</h2>

            @if ($user->verification_note)
                <div class="mt-4 rounded-2xl border border-white/10 bg-[#231f36]/60 px-4 py-3 text-sm text-slate-300">
                    <p class="font-bold text-white text-xs uppercase tracking-wider">Catatan terakhir</p>
                    <p class="mt-1 text-sm">{{ $user->verification_note }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.pengguna.decide', $user) }}" class="mt-5 space-y-4"
                  x-data="{ keputusan: 'verified' }">
                @csrf

                <fieldset>
                    <legend class="dt-label text-xs">Keputusan</legend>
                    <div class="grid gap-2.5 sm:grid-cols-2">
                        @foreach ([['verified', 'Verifikasi'], ['rejected', 'Tolak']] as [$value, $label])
                            <label class="cursor-pointer rounded-2xl border p-3.5 text-center transition-all"
                                   :class="keputusan === '{{ $value }}'
                                       ? '{{ $value === 'verified' ? 'border-[#99ff04] bg-[#99ff04]/10 text-white font-bold' : 'border-rose-500 bg-rose-500/10 text-rose-300 font-bold' }}'
                                       : 'border-white/10 bg-[#231f36]/40 hover:bg-[#231f36] text-slate-400'">
                                <input type="radio" name="decision" value="{{ $value }}" x-model="keputusan" class="sr-only">
                                <span class="text-sm font-bold">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div>
                    <label for="note" class="dt-label text-xs">
                        Catatan <span x-show="keputusan === 'rejected'" class="text-rose-400">*</span>
                    </label>
                    <textarea id="note" name="note" rows="3" class="dt-input text-xs" maxlength="1000"
                              :required="keputusan === 'rejected'"
                              placeholder="Wajib diisi jika menolak — jelaskan apa yang kurang."></textarea>
                    @error('note') <p class="dt-error">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="dt-btn-primary w-full py-3">Simpan keputusan</button>
                <p class="text-center text-xs text-slate-500">Keputusan ini tercatat permanen di jejak audit.</p>
            </form>
        </section>
    </div>
@endsection
