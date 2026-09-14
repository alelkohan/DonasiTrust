@extends('layouts.dashboard')
@section('title', 'Tinjau: '.$campaign->title)

@php($menu = \App\Support\AdminMenu::items('kampanye'))

@section('panel')
    <nav class="mb-5 text-sm text-slate-400">
        <a href="{{ route('admin.kampanye.index') }}" wire:navigate class="hover:text-[#99ff04] transition-colors">&larr; Kembali ke antrean</a>
    </nav>

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-black tracking-tight text-white">{{ $campaign->title }}</h1>
            <p class="mt-1.5 text-sm font-medium text-slate-400">
                {{ $campaign->categoryLabel() }} &middot; target <span class="text-white font-bold">{{ rupiah($campaign->target_amount) }}</span>
                @if ($campaign->deadline) &middot; batas {{ $campaign->deadline->translatedFormat('d F Y') }} @endif
            </p>
        </div>
        <x-badge :tone="match($campaign->status) {
            'approved', 'completed' => 'success',
            'pending' => 'warning',
            'rejected' => 'danger',
            default => 'neutral',
        }">{{ $campaign->statusLabel() }}</x-badge>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1.5fr_1fr] lg:items-start">
        <div class="space-y-6">
            <section class="dt-card p-5 sm:p-6">
                <h2 class="text-lg font-black text-white">Ringkasan &amp; cerita</h2>
                <p class="mt-3 text-sm font-semibold text-slate-200">{{ $campaign->summary }}</p>
                <div class="mt-4 max-h-96 overflow-y-auto border-t border-white/10 pt-4 text-sm leading-relaxed whitespace-pre-line text-slate-300">{{ $campaign->description }}</div>
            </section>

            <section class="dt-card overflow-hidden">
                <div class="p-5 sm:p-6">
                    <h2 class="text-lg font-black text-white">Rincian anggaran (RAB)</h2>
                    <p class="mt-1 text-sm font-medium text-slate-400">Periksa kewajaran harga sebelum menyetujui.</p>
                </div>
                <div class="overflow-x-auto border-t border-white/10">
                    <table class="dt-table min-w-[520px]">
                        <thead>
                            <tr>
                                <th scope="col">Item</th>
                                <th scope="col" class="text-right">Jumlah</th>
                                <th scope="col" class="text-right">Harga satuan</th>
                                <th scope="col" class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($campaign->items as $item)
                                <tr>
                                    <td class="font-bold text-white">{{ $item->name }}</td>
                                    <td class="text-right tabular-nums text-slate-300">{{ $item->quantity }} {{ $item->unit }}</td>
                                    <td class="text-right tabular-nums text-slate-300">{{ rupiah($item->unit_price) }}</td>
                                    <td class="text-right font-black text-white tabular-nums">{{ rupiah($item->subtotal) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-white/10">
                                <td colspan="3" class="px-5 py-3.5 text-right text-xs font-black uppercase tracking-wider text-slate-400">Total RAB</td>
                                <td class="px-5 py-3.5 text-right font-black text-[#99ff04] text-base tabular-nums">{{ rupiah($campaign->items->sum('subtotal')) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>

            <section class="dt-card p-5 sm:p-6">
                <h2 class="text-lg font-black text-white">Tahapan pencairan</h2>
                <ol class="mt-4 space-y-3">
                    @foreach ($campaign->milestones as $milestone)
                        <li class="flex gap-3.5 rounded-2xl border border-white/10 bg-[#231f36]/40 p-4">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#231f36] border border-white/10 text-xs font-black text-white">
                                {{ $milestone->sequence }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <p class="font-bold text-white">{{ $milestone->title }}</p>
                                    <p class="font-black text-[#99ff04] tabular-nums">{{ rupiah($milestone->amount) }}</p>
                                </div>
                                @if ($milestone->description)
                                    <p class="mt-1 text-xs text-slate-400">{{ $milestone->description }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>

        <aside class="space-y-6 lg:sticky lg:top-24">
            <section class="dt-card p-5 sm:p-6">
                <h2 class="text-lg font-black text-white">Pengaju</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Nama</dt>
                        <dd class="mt-0.5 font-bold text-white">{{ $campaign->user->name }}</dd>
                    </div>
                    @if ($campaign->user->organization)
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Lembaga</dt>
                            <dd class="mt-0.5 font-bold text-white">{{ $campaign->user->organization }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Email</dt>
                        <dd class="mt-0.5 font-semibold break-all text-slate-300">{{ $campaign->user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Status identitas</dt>
                        <dd class="mt-1">
                            <x-badge :tone="$campaign->user->isVerified() ? 'success' : 'warning'">
                                {{ $campaign->user->verificationLabel() }}
                            </x-badge>
                        </dd>
                    </div>
                </dl>
                <a href="{{ route('admin.pengguna.show', $campaign->user) }}" wire:navigate class="dt-btn-secondary mt-5 w-full text-xs">
                    Lihat berkas identitas
                </a>
            </section>

            @if ($campaign->status === 'pending')
                <section class="dt-card p-5 sm:p-6"
                         x-data="{
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
                    <p class="mt-1 text-xs font-medium text-slate-400">Keputusan Anda tercatat permanen di jejak audit.</p>

                    <div x-show="errorMessage" x-cloak class="mt-4 rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-xs text-rose-300">
                        <span x-text="errorMessage"></span>
                    </div>

                    <form method="POST" action="{{ route('admin.kampanye.approve', $campaign) }}" class="mt-5 space-y-3" data-no-loading
                          @submit.prevent="handleDecision($event)">
                        @csrf
                        <div>
                            <label for="note-approve" class="dt-label">Catatan <span class="font-normal text-slate-500">(opsional)</span></label>
                            <textarea id="note-approve" name="note" rows="2" class="dt-input text-xs" maxlength="1000"
                                      placeholder="Catatan untuk pengaju"></textarea>
                        </div>
                        <button type="submit" class="dt-btn-primary w-full py-3 inline-flex items-center justify-center gap-2" :disabled="isSubmitting">
                            <svg x-show="isSubmitting" x-cloak class="h-4 w-4 animate-spin text-black" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span x-text="isSubmitting ? 'Memproses...' : 'Setujui & tayangkan'">Setujui &amp; tayangkan</span>
                        </button>
                    </form>

                    <div class="my-5 border-t border-white/10"></div>

                    <form method="POST" action="{{ route('admin.kampanye.reject', $campaign) }}" class="space-y-3" data-no-loading
                          @submit.prevent="if (confirm('Tolak kampanye ini?')) handleDecision($event)">
                        @csrf
                        <div>
                            <label for="note-reject" class="dt-label">Alasan penolakan <span class="text-rose-400">*</span></label>
                            <textarea id="note-reject" name="note" rows="3" required class="dt-input text-xs" maxlength="1000"
                                      placeholder="Jelaskan apa yang perlu diperbaiki agar pengaju bisa mengajukan ulang."></textarea>
                        </div>
                        <button type="submit" class="dt-btn-danger w-full py-2.5 inline-flex items-center justify-center gap-2" :disabled="isSubmitting">
                            <svg x-show="isSubmitting" x-cloak class="h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span x-text="isSubmitting ? 'Memproses...' : 'Tolak kampanye'">Tolak kampanye</span>
                        </button>
                    </form>
                </section>
            @elseif ($campaign->review_note)
                <section class="dt-card p-5 sm:p-6">
                    <h2 class="text-lg font-black text-white">Catatan review</h2>
                    <p class="mt-2 text-sm text-slate-300">{{ $campaign->review_note }}</p>
                    <p class="mt-3 text-xs text-slate-400">
                        Oleh {{ $campaign->reviewer?->name ?? 'admin' }} &middot;
                        {{ $campaign->reviewed_at?->translatedFormat('d F Y, H:i') }}
                    </p>
                </section>
            @endif
        </aside>
    </div>
@endsection
