@extends('layouts.dashboard')
@section('title', 'Pencairan '.$disbursement->reference)

@php($menu = \App\Support\AdminMenu::items('pencairan'))
@php($guard = app(\App\Services\TotpGuard::class))
@php($jendelaTerbuka = auth()->user()->hasTwoFactorEnabled() && $guard->windowOpenFor(auth()->user()))
@php($sisaJendela = $jendelaTerbuka ? $guard->windowMinutesLeft(auth()->user()) : 0)

@section('panel')
    <nav class="mb-5 text-sm text-ink-500">
        <a href="{{ route('admin.pencairan.index') }}" class="hover:text-brand-700">&larr; Kembali ke daftar pencairan</a>
    </nav>

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-mono text-2xl font-extrabold tracking-tight text-ink-900">{{ $disbursement->reference }}</h1>
            <p class="mt-1.5 text-sm text-ink-600">
                Diajukan {{ $disbursement->created_at->translatedFormat('d F Y, H:i') }}
            </p>
        </div>
        <x-badge :tone="match($disbursement->status) {
            'released' => 'success',
            'approved' => 'brand',
            'rejected' => 'danger',
            default => 'warning',
        }">{{ $disbursement->statusLabel() }}</x-badge>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2 lg:items-start">
        <section class="dt-card p-5 sm:p-6">
            <h2 class="text-lg font-bold text-ink-900">Rincian pengajuan</h2>

            <dl class="mt-4 space-y-3.5 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-500">Nominal</dt>
                    <dd class="font-bold text-ink-900 tabular-nums">{{ rupiah($disbursement->amount) }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-500">Kampanye</dt>
                    <dd class="text-right font-semibold text-ink-900">
                        <a href="{{ route('admin.kampanye.show', $disbursement->campaign) }}" class="dt-link">
                            {{ $disbursement->campaign->title }}
                        </a>
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-500">Tahap</dt>
                    <dd class="text-right font-semibold text-ink-900">
                        @if ($disbursement->milestone)
                            Tahap {{ $disbursement->milestone->sequence }} &mdash; {{ $disbursement->milestone->title }}
                        @else
                            &mdash;
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-500">Pemohon</dt>
                    <dd class="font-semibold text-ink-900">{{ $disbursement->requester?->name ?? '—' }}</dd>
                </div>
                @if ($disbursement->reviewed_at)
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-500">Ditinjau</dt>
                        <dd class="text-right font-semibold text-ink-900">
                            {{ $disbursement->reviewed_at->translatedFormat('d F Y, H:i') }}
                            @if ($disbursement->reviewer)
                                <span class="block text-xs font-normal text-ink-500">oleh {{ $disbursement->reviewer->name }}</span>
                            @endif
                        </dd>
                    </div>
                @endif
                @if ($disbursement->released_at)
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-500">Dana dilepas</dt>
                        <dd class="font-semibold text-ink-900">{{ $disbursement->released_at->translatedFormat('d F Y, H:i') }}</dd>
                    </div>
                @endif
            </dl>

            <div class="mt-5 border-t border-ink-100 pt-5">
                <h3 class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Tujuan pencairan</h3>
                <p class="mt-2 text-sm leading-relaxed text-ink-700">{{ $disbursement->purpose }}</p>
            </div>

            {{-- Rekening tujuan: disalin saat pengajuan dibuat, dari profil
                 pengaju yang sudah diverifikasi. Tidak bisa diubah pengaju. --}}
            <div class="mt-5 rounded-xl border border-brand-200 bg-brand-50/60 p-4">
                <h3 class="text-xs font-semibold tracking-wide text-brand-800 uppercase">Rekening tujuan transfer</h3>

                @if (filled($disbursement->payee_account_number))
                    <p class="mt-2 text-lg font-bold text-ink-900">
                        {{ strtoupper($disbursement->payee_bank_name) }}
                        <span class="font-mono">{{ $disbursement->payee_account_number }}</span>
                    </p>
                    <p class="text-sm font-semibold text-ink-700">a.n. {{ $disbursement->payee_account_holder }}</p>
                    <p class="mt-2 text-xs leading-relaxed text-brand-900/75">
                        Terkunci pada pengajuan ini sejak dibuat. Publik hanya melihat versi tersamar:
                        <span class="font-mono">{{ $disbursement->maskedPayee() }}</span>
                    </p>
                @else
                    <p class="mt-2 text-sm text-amber-800">
                        Pengajuan ini dibuat sebelum rekening tujuan dicatat sistem. Verifikasi tujuan
                        transfernya secara manual sebelum melepas dana.
                    </p>
                @endif
            </div>

            @if ($disbursement->review_note)
                <div class="mt-5 border-t border-ink-100 pt-5">
                    <h3 class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Catatan review</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-700">{{ $disbursement->review_note }}</p>
                </div>
            @endif
        </section>

        <div class="space-y-6">
            <section class="dt-card p-5 sm:p-6">
                <h2 class="text-lg font-bold text-ink-900">Bukti transfer</h2>
                @if ($disbursement->supporting_document_path)
                    <p class="mt-1 text-sm text-ink-600">Diunggah admin saat menandai dana dicairkan.</p>
                    <a href="{{ route('berkas.pencairan', $disbursement) }}" target="_blank" rel="noopener"
                       class="dt-btn-secondary mt-4 w-full">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                        Buka bukti transfer
                    </a>
                    <p class="dt-hint text-center">Disimpan di disk privat, bukan URL publik.</p>
                @else
                    <p class="mt-4 rounded-xl border border-dashed border-ink-300 px-4 py-6 text-center text-sm text-ink-500">
                        Belum ada bukti transfer diunggah.
                    </p>
                @endif
            </section>

            @if ($disbursement->status === \App\Models\Disbursement::STATUS_PENDING)
                <section class="dt-card p-5 sm:p-6" x-data="{ tolak: false }">
                    <h2 class="text-lg font-bold text-ink-900">Keputusan</h2>
                    <p class="mt-1 text-sm text-ink-600">Keputusan Anda tercatat permanen di jejak audit.</p>

                    <form method="POST" action="{{ route('admin.pencairan.approve', $disbursement) }}" class="mt-5">
                        @csrf
                        <button type="submit" class="dt-btn-primary w-full py-3">Setujui pencairan</button>
                    </form>

                    <button type="button" @click="tolak = ! tolak" x-show="!tolak"
                            class="dt-btn-secondary mt-3 w-full text-rose-600">Tolak pengajuan</button>

                    <form method="POST" action="{{ route('admin.pencairan.reject', $disbursement) }}"
                          x-show="tolak" x-cloak class="mt-3 space-y-3">
                        @csrf
                        <div>
                            <label for="reason" class="dt-label">Alasan penolakan</label>
                            <textarea id="reason" name="reason" rows="3" required maxlength="500" class="dt-input"
                                      placeholder="Jelaskan apa yang perlu diperbaiki pengaju."></textarea>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="dt-btn-danger flex-1">Kirim penolakan</button>
                            <button type="button" @click="tolak = false" class="dt-btn-secondary">Batal</button>
                        </div>
                    </form>
                </section>
            @elseif ($disbursement->status === \App\Models\Disbursement::STATUS_APPROVED)
                <section class="dt-card p-5 sm:p-6">
                    <h2 class="text-lg font-bold text-ink-900">Tandai dana dicairkan</h2>
                    <p class="mt-1 text-sm text-ink-600">
                        Transfer manual ke rekening di sebelah kiri, lalu unggah buktinya di sini.
                    </p>
                    <form method="POST" action="{{ route('admin.pencairan.release', $disbursement) }}"
                          enctype="multipart/form-data" class="mt-5 space-y-3">
                        @csrf
                        <div>
                            <label for="proof" class="dt-label">Bukti transfer</label>
                            <input id="proof" name="proof" type="file" required accept="image/*"
                                   class="dt-input file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-brand-700">
                        </div>

                        {{-- Langkah terakhir dan tidak bisa ditarik kembali: setelah ini
                             sistem menyatakan dana sudah keluar dan tahap berikutnya
                             ikut terbuka. --}}
                        @if (auth()->user()->hasTwoFactorEnabled() && $jendelaTerbuka)
                            <p class="flex items-start gap-2 rounded-xl border border-brand-200 bg-brand-50/70 p-3 text-xs leading-relaxed text-brand-900">
                                <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7.5V12l2.8 1.7"/></svg>
                                <span>
                                    Sesi verifikasi dua langkah Anda masih berlaku {{ $sisaJendela }} menit lagi. Setiap rilis tetap tercatat di jejak audit.
                                </span>
                            </p>
                        @else
                            <div class="rounded-xl border border-ink-200 bg-white p-3.5">
                                <x-otp-input purpose="disbursement_release" label="Kode Verifikasi Email Admin" />
                            </div>
                            @if (auth()->user()->hasTwoFactorEnabled())
                                <div class="relative my-2">
                                    <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-ink-200"></div></div>
                                    <div class="relative flex justify-center text-xs text-ink-500 uppercase"><span class="bg-white px-2">atau gunakan TOTP</span></div>
                                </div>
                                <div>
                                    <label for="totp_code" class="dt-label">Kode Aplikasi Authenticator</label>
                                    <input id="totp_code" name="totp_code" inputmode="numeric"
                                           autocomplete="one-time-code" maxlength="9" placeholder="000000"
                                           class="dt-input text-center font-mono text-lg tracking-[0.4em]">
                                </div>
                            @endif
                        @endif

                        <button type="submit" class="dt-btn-primary w-full py-3">Tandai sudah dicairkan</button>
                    </form>
                </section>
            @endif
        </div>
    </div>
@endsection
