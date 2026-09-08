@extends('layouts.dashboard')
@section('title', 'Pencairan '.$disbursement->reference)

@php($menu = \App\Support\AdminMenu::items('pencairan'))

@section('panel')
    <nav class="mb-5 text-sm text-slate-400">
        <a href="{{ route('admin.pencairan.index') }}" class="hover:text-[#99ff04] transition-colors">&larr; Kembali ke daftar pencairan</a>
    </nav>

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-mono text-2xl font-black tracking-tight text-white">{{ $disbursement->reference }}</h1>
            <p class="mt-1.5 text-sm font-medium text-slate-400">
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
            <h2 class="text-lg font-black text-white">Rincian pengajuan</h2>

            <dl class="mt-4 space-y-3.5 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Nominal</dt>
                    <dd class="font-black text-white text-base tabular-nums">{{ rupiah($disbursement->amount) }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Kampanye</dt>
                    <dd class="text-right font-semibold text-white">
                        <a href="{{ route('admin.kampanye.show', $disbursement->campaign) }}" class="dt-link text-sm">
                            {{ $disbursement->campaign->title }}
                        </a>
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Tahap</dt>
                    <dd class="text-right font-semibold text-slate-200">
                        @if ($disbursement->milestone)
                            Tahap {{ $disbursement->milestone->sequence }} &mdash; {{ $disbursement->milestone->title }}
                        @else
                            &mdash;
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Pemohon</dt>
                    <dd class="font-bold text-white">{{ $disbursement->requester?->name ?? '—' }}</dd>
                </div>
                @if ($disbursement->reviewed_at)
                    <div class="flex justify-between gap-4">
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Ditinjau</dt>
                        <dd class="text-right font-semibold text-slate-300">
                            {{ $disbursement->reviewed_at->translatedFormat('d F Y, H:i') }}
                            @if ($disbursement->reviewer)
                                <span class="block text-xs font-normal text-slate-500">oleh {{ $disbursement->reviewer->name }}</span>
                            @endif
                        </dd>
                    </div>
                @endif
                @if ($disbursement->released_at)
                    <div class="flex justify-between gap-4">
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Dana dilepas</dt>
                        <dd class="font-bold text-[#99ff04]">{{ $disbursement->released_at->translatedFormat('d F Y, H:i') }}</dd>
                    </div>
                @endif
            </dl>

            <div class="mt-5 border-t border-white/10 pt-5">
                <h3 class="text-xs font-black tracking-wider text-slate-400 uppercase">Tujuan pencairan</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-300">{{ $disbursement->purpose }}</p>
            </div>

            {{-- Rekening tujuan: disalin saat pengajuan dibuat, dari profil
                 pengaju yang sudah diverifikasi. Tidak bisa diubah pengaju. --}}
            <div class="mt-5 rounded-2xl border border-white/10 bg-[#231f36]/60 p-4">
                <h3 class="text-xs font-black tracking-wider text-[#99ff04] uppercase">Rekening tujuan transfer</h3>

                @if (filled($disbursement->payee_account_number))
                    <p class="mt-2 text-lg font-black text-white">
                        {{ strtoupper($disbursement->payee_bank_name) }}
                        <span class="font-mono text-[#99ff04]">{{ $disbursement->payee_account_number }}</span>
                    </p>
                    <p class="text-sm font-semibold text-slate-300">a.n. {{ $disbursement->payee_account_holder }}</p>
                    <p class="mt-2 text-xs leading-relaxed text-slate-400">
                        Terkunci pada pengajuan ini sejak dibuat. Publik hanya melihat versi tersamar:
                        <span class="font-mono text-slate-300">{{ $disbursement->maskedPayee() }}</span>
                    </p>
                @else
                    <p class="mt-2 text-sm text-amber-300">
                        Pengajuan ini dibuat sebelum rekening tujuan dicatat sistem. Verifikasi tujuan
                        transfernya secara manual sebelum melepas dana.
                    </p>
                @endif
            </div>

            @if ($disbursement->review_note)
                <div class="mt-5 border-t border-white/10 pt-5">
                    <h3 class="text-xs font-black tracking-wider text-slate-400 uppercase">Catatan review</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-300">{{ $disbursement->review_note }}</p>
                </div>
            @endif
        </section>

        <div class="space-y-6">
            <section class="dt-card p-5 sm:p-6">
                <h2 class="text-lg font-black text-white">Bukti transfer</h2>
                @if ($disbursement->supporting_document_path)
                    <p class="mt-1 text-xs font-medium text-slate-400">Diunggah admin saat menandai dana dicairkan.</p>
                    <a href="{{ route('berkas.pencairan', $disbursement) }}" target="_blank" rel="noopener"
                       class="dt-btn-secondary mt-4 w-full text-xs">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                        Buka bukti transfer
                    </a>
                    <p class="mt-2 text-center text-xs text-slate-500">Disimpan di disk privat, bukan URL publik.</p>
                @else
                    <p class="mt-4 rounded-2xl border border-dashed border-white/20 px-4 py-6 text-center text-xs font-medium text-slate-400">
                        Belum ada bukti transfer diunggah.
                    </p>
                @endif
            </section>

            @if ($disbursement->status === \App\Models\Disbursement::STATUS_PENDING)
                <section class="dt-card p-5 sm:p-6" x-data="{ tolak: false }">
                    <h2 class="text-lg font-black text-white">Keputusan</h2>
                    <p class="mt-1 text-xs font-medium text-slate-400">Keputusan Anda tercatat permanen di jejak audit.</p>

                    <form method="POST" action="{{ route('admin.pencairan.approve', $disbursement) }}" class="mt-5">
                        @csrf
                        <button type="submit" class="dt-btn-primary w-full py-3">Setujui pencairan</button>
                    </form>

                    <button type="button" @click="tolak = ! tolak" x-show="!tolak"
                            class="dt-btn-secondary mt-3 w-full text-rose-400 hover:text-rose-300 text-xs">Tolak pengajuan</button>

                    <form method="POST" action="{{ route('admin.pencairan.reject', $disbursement) }}"
                          x-show="tolak" x-cloak class="mt-3 space-y-3">
                        @csrf
                        <div>
                            <label for="reason" class="dt-label text-xs">Alasan penolakan</label>
                            <textarea id="reason" name="reason" rows="3" required maxlength="500" class="dt-input text-xs"
                                      placeholder="Jelaskan apa yang perlu diperbaiki pengaju."></textarea>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="dt-btn-danger flex-1 py-2 text-xs">Kirim penolakan</button>
                            <button type="button" @click="tolak = false" class="dt-btn-secondary text-xs">Batal</button>
                        </div>
                    </form>
                </section>
            @elseif ($disbursement->status === \App\Models\Disbursement::STATUS_APPROVED)
                <section class="dt-card p-5 sm:p-6">
                    <h2 class="text-lg font-black text-white">Tandai dana dicairkan</h2>
                    <p class="mt-1 text-xs font-medium text-slate-400">
                        Transfer manual ke rekening di sebelah kiri, lalu unggah buktinya di sini.
                    </p>
                    <form method="POST" action="{{ route('admin.pencairan.release', $disbursement) }}"
                          enctype="multipart/form-data" class="mt-5 space-y-3">
                        @csrf
                        <div>
                            <label for="proof" class="dt-label text-xs">Bukti transfer</label>
                            <input id="proof" name="proof" type="file" required accept="image/*"
                                   class="dt-input text-xs">
                        </div>

                        {{-- Langkah terakhir dan tidak bisa ditarik kembali: setelah ini
                             sistem menyatakan dana sudah keluar dan tahap berikutnya
                             ikut terbuka. --}}
                        <div class="rounded-2xl border border-white/10 bg-[#1b182a] p-4">
                            <x-otp-input purpose="disbursement_release" label="Kode Verifikasi Email Admin" />
                        </div>

                        <button type="submit" class="dt-btn-primary w-full py-3">Tandai sudah dicairkan</button>
                    </form>
                </section>
            @endif
        </div>
    </div>
@endsection
