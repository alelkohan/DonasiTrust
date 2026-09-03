@extends('layouts.dashboard')
@section('title', 'LPJ: '.$expense->title)

@php($menu = \App\Support\AdminMenu::items('lpj'))

@section('panel')
    <nav class="mb-5 text-sm text-ink-500">
        <a href="{{ route('admin.lpj.index') }}" class="hover:text-brand-700">&larr; Kembali ke daftar LPJ</a>
    </nav>

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-extrabold tracking-tight text-ink-900">{{ $expense->title }}</h1>
            <p class="mt-1.5 text-sm text-ink-600">
                Dilaporkan {{ $expense->created_at->translatedFormat('d F Y, H:i') }}
                oleh {{ $expense->author?->name ?? '—' }}
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
            <h2 class="text-lg font-bold text-ink-900">Rincian pengeluaran</h2>

            <dl class="mt-4 space-y-3.5 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-500">Nominal</dt>
                    <dd class="font-bold text-ink-900 tabular-nums">{{ rupiah($expense->amount) }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-500">Tanggal belanja</dt>
                    <dd class="font-semibold text-ink-900">{{ $expense->spent_on->translatedFormat('d F Y') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-500">Kampanye</dt>
                    <dd class="text-right font-semibold text-ink-900">
                        <a href="{{ route('admin.kampanye.show', $expense->campaign) }}" class="dt-link">
                            {{ $expense->campaign->title }}
                        </a>
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-500">Tahap</dt>
                    <dd class="text-right font-semibold text-ink-900">
                        @if ($expense->milestone)
                            Tahap {{ $expense->milestone->sequence }} &mdash; {{ $expense->milestone->title }}
                        @else
                            &mdash;
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-500">Item RAB</dt>
                    <dd class="text-right font-semibold text-ink-900">{{ $expense->item?->name ?? 'Tidak terikat item' }}</dd>
                </div>
                @if ($expense->item)
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-500">Anggaran item ini</dt>
                        <dd class="font-semibold text-ink-900 tabular-nums">{{ rupiah($expense->item->subtotal) }}</dd>
                    </div>
                @endif
            </dl>

            <div class="mt-5 border-t border-ink-100 pt-5">
                <h3 class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Keterangan pelapor</h3>
                <p class="mt-2 text-sm leading-relaxed text-ink-700">{{ $expense->description }}</p>
            </div>

            @if ($expense->review_note)
                <div class="mt-5 border-t border-ink-100 pt-5">
                    <h3 class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Catatan review</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-700">{{ $expense->review_note }}</p>
                    <p class="mt-2 text-xs text-ink-500">
                        {{ $expense->reviewed_at?->translatedFormat('d F Y, H:i') }}
                    </p>
                </div>
            @endif
        </section>

        <div class="space-y-6">
            <section class="dt-card p-5 sm:p-6">
                <h2 class="text-lg font-bold text-ink-900">Bukti nota</h2>
                @if ($expense->receipt_path)
                    <p class="mt-1 text-sm text-ink-600">
                        Nota ini juga terbuka untuk publik di halaman transparansi kampanye.
                    </p>
                    <a href="{{ route('berkas.lpj', $expense) }}" target="_blank" rel="noopener"
                       class="mt-4 block overflow-hidden rounded-xl border border-ink-200 bg-ink-50">
                        <img src="{{ route('berkas.lpj', $expense) }}" alt="Nota {{ $expense->title }}"
                             class="max-h-96 w-full object-contain">
                    </a>
                    <p class="dt-hint text-center">Klik gambar untuk membuka ukuran penuh.</p>

                    @if ($expense->receipt_hash)
                        <div class="mt-4 rounded-xl border border-ink-200 bg-ink-50/60 p-3.5">
                            <p class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Sidik jari berkas (SHA-256)</p>
                            <p class="mt-1 font-mono text-xs break-all text-ink-700">{{ $expense->receipt_hash }}</p>
                            <p class="mt-2 text-xs leading-relaxed text-ink-500">
                                Sistem menolak berkas nota dengan sidik jari yang sama, sehingga satu nota
                                tidak bisa dipakai ulang untuk pengeluaran, tahap, atau kampanye lain.
                            </p>
                        </div>
                    @endif
                @else
                    <p class="mt-4 rounded-xl border border-dashed border-ink-300 px-4 py-6 text-center text-sm text-ink-500">
                        Pelapor tidak melampirkan nota.
                    </p>
                @endif
            </section>

            @if ($expense->status === \App\Models\ExpenseReport::STATUS_PENDING)
                <section class="dt-card p-5 sm:p-6" x-data="{ tolak: false }">
                    <h2 class="text-lg font-bold text-ink-900">Keputusan</h2>
                    <p class="mt-1 text-sm text-ink-600">
                        Verifikasi berarti Anda sudah mencocokkan nota dengan nominal dan item RAB-nya.
                    </p>

                    <form method="POST" action="{{ route('admin.lpj.verify', $expense) }}" class="mt-5">
                        @csrf
                        <button type="submit" class="dt-btn-primary w-full py-3">Tandai nota sah</button>
                    </form>

                    <button type="button" @click="tolak = ! tolak" x-show="!tolak"
                            class="dt-btn-secondary mt-3 w-full text-rose-600">Tolak laporan</button>

                    <form method="POST" action="{{ route('admin.lpj.reject', $expense) }}"
                          x-show="tolak" x-cloak class="mt-3 space-y-3">
                        @csrf
                        <div>
                            <label for="reason" class="dt-label">Alasan penolakan</label>
                            <textarea id="reason" name="reason" rows="3" required maxlength="500" class="dt-input"
                                      placeholder="Misalnya: nominal di nota tidak sama dengan yang dilaporkan."></textarea>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="dt-btn-danger flex-1">Kirim penolakan</button>
                            <button type="button" @click="tolak = false" class="dt-btn-secondary">Batal</button>
                        </div>
                    </form>
                </section>
            @endif
        </div>
    </div>
@endsection
