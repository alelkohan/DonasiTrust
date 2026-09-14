@extends('layouts.dashboard')
@section('title', 'LPJ: '.$expense->title)

@php($menu = \App\Support\AdminMenu::items('lpj'))

@section('panel')
    <nav class="mb-5 text-sm text-slate-400">
        <a href="{{ route('admin.lpj.index') }}" wire:navigate class="hover:text-[#99ff04] transition-colors">&larr; Kembali ke daftar LPJ</a>
    </nav>

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-black tracking-tight text-white">{{ $expense->title }}</h1>
            <p class="mt-1.5 text-sm font-medium text-slate-400">
                Dilaporkan {{ $expense->created_at->translatedFormat('d F Y, H:i') }}
                oleh <span class="text-white font-semibold">{{ $expense->author?->name ?? '—' }}</span>
            </p>
        </div>
        <x-badge :tone="match($expense->status) {
            'verified' => 'success',
            'rejected' => 'danger',
            default => 'warning',
        }">{{ $expense->statusLabel() }}</x-badge>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2 lg:items-start">
        <section class="dt-card p-5 sm:p-6">
            <h2 class="text-lg font-black text-white">Rincian pengeluaran</h2>

            <dl class="mt-4 space-y-3.5 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Nominal</dt>
                    <dd class="font-black text-white text-base tabular-nums">{{ rupiah($expense->amount) }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Tanggal belanja</dt>
                    <dd class="font-bold text-white">{{ $expense->spent_on->translatedFormat('d F Y') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Kampanye</dt>
                    <dd class="text-right font-semibold text-white">
                        <a href="{{ route('admin.kampanye.show', $expense->campaign) }}" wire:navigate class="dt-link text-sm">
                            {{ $expense->campaign->title }}
                        </a>
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Tahap</dt>
                    <dd class="text-right font-semibold text-slate-200">
                        @if ($expense->milestone)
                            Tahap {{ $expense->milestone->sequence }} &mdash; {{ $expense->milestone->title }}
                        @else
                            &mdash;
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Item RAB</dt>
                    <dd class="text-right font-semibold text-white">{{ $expense->item?->name ?? 'Tidak terikat item' }}</dd>
                </div>
                @if ($expense->item)
                    <div class="flex justify-between gap-4">
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Anggaran item ini</dt>
                        <dd class="font-black text-[#99ff04] tabular-nums">{{ rupiah($expense->item->subtotal) }}</dd>
                    </div>
                @endif
            </dl>

            <div class="mt-5 border-t border-white/10 pt-5">
                <h3 class="text-xs font-black tracking-wider text-slate-400 uppercase">Keterangan pelapor</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-300">{{ $expense->description }}</p>
            </div>

            @if ($expense->review_note)
                <div class="mt-5 border-t border-white/10 pt-5">
                    <h3 class="text-xs font-black tracking-wider text-slate-400 uppercase">Catatan review</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-300">{{ $expense->review_note }}</p>
                    <p class="mt-2 text-xs text-slate-500">
                        {{ $expense->reviewed_at?->translatedFormat('d F Y, H:i') }}
                    </p>
                </div>
            @endif
        </section>

        <div class="space-y-6">
            <section class="dt-card p-5 sm:p-6">
                <h2 class="text-lg font-black text-white">Bukti nota</h2>
                @if ($expense->receipt_path)
                    <p class="mt-1 text-xs font-medium text-slate-400">
                        Nota ini juga terbuka untuk publik di halaman transparansi kampanye.
                    </p>
                    <a href="{{ route('berkas.lpj', $expense) }}" target="_blank" rel="noopener"
                       class="mt-4 block overflow-hidden rounded-2xl border border-white/10 bg-[#231f36] hover:border-[#99ff04]/50 transition-colors">
                        <img src="{{ route('berkas.lpj', $expense) }}" alt="Nota {{ $expense->title }}"
                             class="max-h-96 w-full object-contain">
                    </a>
                    <p class="mt-2 text-center text-xs text-slate-500">Klik gambar untuk membuka ukuran penuh.</p>

                    @if ($expense->receipt_hash)
                        <div class="mt-4 rounded-2xl border border-white/10 bg-[#231f36]/60 p-4">
                            <p class="text-xs font-black tracking-wider text-[#99ff04] uppercase">Sidik jari berkas (SHA-256)</p>
                            <p class="mt-1 font-mono text-xs break-all text-slate-300">{{ $expense->receipt_hash }}</p>
                            <p class="mt-2 text-xs leading-relaxed text-slate-400">
                                Sistem menolak berkas nota dengan sidik jari yang sama, sehingga satu nota
                                tidak bisa dipakai ulang untuk pengeluaran, tahap, atau kampanye lain.
                            </p>
                        </div>
                    @endif
                @else
                    <p class="mt-4 rounded-2xl border border-dashed border-white/20 px-4 py-6 text-center text-xs font-medium text-slate-400">
                        Pelapor tidak melampirkan nota.
                    </p>
                @endif
            </section>

            @if ($expense->status === \App\Models\ExpenseReport::STATUS_PENDING)
                <section class="dt-card p-5 sm:p-6"
                         x-data="{
                             tolak: false,
                             isSubmitting: false,
                             errorMessage: '',
                             async handleDecision(e) {
                                 if (this.isSubmitting) return;
                                 this.errorMessage = '';
                                 const form = e.target;
                                 this.isSubmitting = true;
                                 try {
                                     const res = await fetch(form.action, {
                                         method: 'POST',
                                         headers: {
                                             'Accept': 'application/json',
                                             'X-Requested-With': 'XMLHttpRequest',
                                             'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']')?.content || ''
                                         },
                                         body: new FormData(form)
                                     });
                                     const data = await res.json().catch(() => ({}));
                                     if (res.ok && data.success) {
                                         if (data.redirect) {
                                             if (window.Livewire && typeof window.Livewire.navigate === 'function') {
                                                 window.Livewire.navigate(data.redirect);
                                             } else {
                                                 window.location.href = data.redirect;
                                             }
                                             return;
                                         }
                                         window.location.reload();
                                     } else {
                                         this.isSubmitting = false;
                                         this.errorMessage = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Terjadi kesalahan saat memproses keputusan.');
                                     }
                                 } catch (err) {
                                     this.isSubmitting = false;
                                     this.errorMessage = 'Terjadi gangguan koneksi internet. Silakan coba lagi.';
                                 }
                             }
                         }">
                    <h2 class="text-lg font-black text-white">Keputusan</h2>
                    <p class="mt-1 text-xs font-medium text-slate-400">
                        Verifikasi berarti Anda sudah mencocokkan nota dengan nominal dan item RAB-nya.
                    </p>

                    <div x-show="errorMessage" x-cloak class="mt-4 rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-xs text-rose-300">
                        <span x-text="errorMessage"></span>
                    </div>

                    <form method="POST" action="{{ route('admin.lpj.verify', $expense) }}" class="mt-5" data-no-loading
                          @submit.prevent="handleDecision($event)">
                        @csrf
                        <button type="submit" class="dt-btn-primary w-full py-3 inline-flex items-center justify-center gap-2" :disabled="isSubmitting">
                            <svg x-show="isSubmitting" x-cloak class="h-4 w-4 animate-spin text-black" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span x-text="isSubmitting ? 'Memproses...' : 'Tandai nota sah'">Tandai nota sah</span>
                        </button>
                    </form>

                    <button type="button" @click="tolak = ! tolak" x-show="!tolak"
                            class="dt-btn-secondary mt-3 w-full text-rose-400 hover:text-rose-300 text-xs">Tolak laporan</button>

                    <form method="POST" action="{{ route('admin.lpj.reject', $expense) }}"
                          x-show="tolak" x-cloak class="mt-3 space-y-3" data-no-loading
                          @submit.prevent="handleDecision($event)">
                        @csrf
                        <div>
                            <label for="reason" class="dt-label text-xs">Alasan penolakan</label>
                            <textarea id="reason" name="reason" rows="3" required maxlength="500" class="dt-input text-xs"
                                      placeholder="Misalnya: nominal di nota tidak sama dengan yang dilaporkan."></textarea>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="dt-btn-danger flex-1 py-2 text-xs inline-flex items-center justify-center gap-2" :disabled="isSubmitting">
                                <svg x-show="isSubmitting" x-cloak class="h-3.5 w-3.5 animate-spin text-white" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span x-text="isSubmitting ? 'Memproses...' : 'Kirim penolakan'">Kirim penolakan</span>
                            </button>
                            <button type="button" @click="tolak = false" class="dt-btn-secondary text-xs">Batal</button>
                        </div>
                    </form>
                </section>
            @endif
        </div>
    </div>
@endsection
