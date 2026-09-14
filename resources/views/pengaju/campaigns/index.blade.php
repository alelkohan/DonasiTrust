@extends('layouts.dashboard')
@section('title', 'Kampanye Saya')

@section('panel')
<div class="space-y-6"
     x-data="{
         // Modal Ajukan Review
         showSubmitModal: false,
         submitCampaignId: null,
         submitCampaignTitle: '',
         submitCampaignTarget: '',
         submitCampaignMilestonesCount: 0,
         submitUrl: '',
         isSubmitting: false,
         submitError: '',

         openSubmitModal(id, title, target, milestonesCount, url) {
             this.submitCampaignId = id;
             this.submitCampaignTitle = title;
             this.submitCampaignTarget = target;
             this.submitCampaignMilestonesCount = milestonesCount;
             this.submitUrl = url;
             this.submitError = '';
             this.showSubmitModal = true;
         },

         async confirmSubmit() {
             if (this.isSubmitting) return;
             this.isSubmitting = true;
             this.submitError = '';

             try {
                 const res = await fetch(this.submitUrl, {
                     method: 'POST',
                     headers: {
                         'Accept': 'application/json',
                         'Content-Type': 'application/json',
                         'X-Requested-With': 'XMLHttpRequest',
                         'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']')?.content || ''
                     }
                 });

                 const data = await res.json().catch(() => ({}));
                 if (res.ok && data.success) {
                     this.showSubmitModal = false;
                     if (window.Livewire && typeof window.Livewire.navigate === 'function') {
                         window.Livewire.navigate(data.redirect || window.location.href);
                     } else {
                         window.location.reload();
                     }
                 } else {
                     this.isSubmitting = false;
                     this.submitError = data.message || 'Gagal mengajukan kampanye. Pastikan minimal ada satu tahap pencairan.';
                 }
             } catch (e) {
                 this.isSubmitting = false;
                 this.submitError = 'Terjadi gangguan jaringan. Silakan coba lagi.';
             }
         },

         // Modal Hapus Kampanye
         showDeleteModal: false,
         deleteCampaignId: null,
         deleteCampaignTitle: '',
         deleteUrl: '',
         isDeleting: false,
         deleteError: '',

         openDeleteModal(id, title, url) {
             this.deleteCampaignId = id;
             this.deleteCampaignTitle = title;
             this.deleteUrl = url;
             this.deleteError = '';
             this.showDeleteModal = true;
         },

         async confirmDelete() {
             if (this.isDeleting) return;
             this.isDeleting = true;
             this.deleteError = '';

             try {
                 const res = await fetch(this.deleteUrl, {
                     method: 'POST',
                     headers: {
                         'Accept': 'application/json',
                         'Content-Type': 'application/json',
                         'X-Requested-With': 'XMLHttpRequest',
                         'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']')?.content || ''
                     },
                     body: JSON.stringify({
                         _method: 'DELETE'
                     })
                 });

                 const data = await res.json().catch(() => ({}));
                 if (res.ok && data.success) {
                     this.showDeleteModal = false;
                     if (window.Livewire && typeof window.Livewire.navigate === 'function') {
                         window.Livewire.navigate(data.redirect || window.location.href);
                     } else {
                         window.location.reload();
                     }
                 } else {
                     this.isDeleting = false;
                     this.deleteError = data.message || 'Gagal menghapus kampanye.';
                 }
             } catch (e) {
                 this.isDeleting = false;
                 this.deleteError = 'Terjadi gangguan jaringan. Silakan coba lagi.';
             }
         }
     }"
     @keydown.escape.window="if (!isSubmitting && !isDeleting) { showSubmitModal = false; showDeleteModal = false; }">

    {{-- Page Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-white/10 pb-5">
        <div>
            <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white">Kampanye Saya</h1>
            <p class="mt-1 text-xs sm:text-sm font-medium text-slate-400">Kelola penggalangan dana, milestone pencairan, dan laporan LPJ</p>
        </div>
        @if (auth()->user()->canSubmitCampaign())
            <a href="{{ route('pengaju.kampanye.create') }}" wire:navigate class="inline-flex items-center justify-center gap-2 rounded-full bg-[#99ff04] px-5 py-2.5 text-xs font-black text-black hover:bg-[#84e000] transition-all hover:scale-105 active:scale-95 shadow-md shadow-[#99ff04]/20">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Buat Kampanye Baru</span>
            </a>
        @endif
    </div>

    @if ($campaigns->isEmpty())
        <div class="rounded-3xl border border-white/10 bg-[#1b182a] p-12 text-center shadow-xl">
            <div class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-[#231f36] text-[#99ff04] border border-white/10 mb-4">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <h2 class="text-lg font-black text-white">Belum Ada Kampanye</h2>
            <p class="mt-2 text-xs sm:text-sm text-slate-400 max-w-sm mx-auto">
                Kampanye yang Anda susun akan muncul di sini lengkap dengan status peninjauan &amp; realisasi dana.
            </p>
            @if (auth()->user()->canSubmitCampaign())
                <a href="{{ route('pengaju.kampanye.create') }}" wire:navigate class="mt-6 inline-flex items-center gap-2 rounded-full bg-[#99ff04] px-6 py-2.5 text-xs font-black text-black hover:bg-[#84e000] shadow-md shadow-[#99ff04]/20">
                    <span>Mulai Buat Kampanye</span>
                </a>
            @endif
        </div>
    @else
        <div class="space-y-4">
            @foreach ($campaigns as $campaign)
                <article class="rounded-3xl border border-white/10 bg-[#1b182a] p-5 sm:p-6 shadow-xl backdrop-blur-md transition-all hover:border-[#99ff04]/30">
                    
                    <!-- Side-by-side Flex Container: Gambar di Kiri (220px), Content di Kanan (Flex-1) -->
                    <div class="flex flex-col md:flex-row items-start gap-5">
                        
                        <!-- Kolom Gambar di Kiri -->
                        <div class="w-full md:w-52 shrink-0">
                            <img src="{{ $campaign->coverUrl() }}" alt="{{ $campaign->title }}" 
                                 class="w-full h-44 rounded-2xl object-cover border border-white/10 shadow-md bg-[#231f36]">
                        </div>

                        <!-- Kolom Seluruh Konten di Kanan (Flex-1) -->
                        <div class="flex-1 min-w-0 space-y-4 w-full">
                            
                            <!-- Header: Judul, Status, Kategori & Tombol Aksi -->
                            <div class="flex flex-col sm:flex-row items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="text-base sm:text-lg font-black text-white line-clamp-1 tracking-tight">{{ $campaign->title }}</h2>
                                        
                                        {{-- Dynamic Status Badge --}}
                                        <span @class([
                                            'rounded-full px-3 py-0.5 text-xs font-extrabold border',
                                            'border-slate-500/30 bg-slate-500/10 text-slate-300' => $campaign->status === 'draft',
                                            'border-amber-500/30 bg-amber-500/10 text-amber-300' => $campaign->status === 'pending',
                                            'border-[#99ff04]/30 bg-[#99ff04]/10 text-[#99ff04]' => $campaign->status === 'approved',
                                            'border-rose-500/30 bg-rose-500/10 text-rose-300' => $campaign->status === 'rejected',
                                            'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' => $campaign->status === 'completed',
                                        ])>
                                            {{ $campaign->statusLabel() }}
                                        </span>

                                        <span class="rounded-full border border-[#99ff04]/20 bg-[#99ff04]/10 px-2.5 py-0.5 text-[11px] font-extrabold text-[#99ff04]">
                                            {{ $campaign->categoryLabel() }}
                                        </span>
                                    </div>
                                    <p class="mt-2 line-clamp-2 text-xs leading-relaxed text-slate-400 font-medium">{{ $campaign->summary }}</p>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex shrink-0 flex-wrap gap-2">
                                    @if ($campaign->isPublished())
                                        <a href="{{ route('kampanye.show', $campaign) }}" class="rounded-xl border border-white/20 bg-[#231f36] px-3.5 py-1.5 text-xs font-extrabold text-white transition-all hover:border-[#99ff04] hover:text-[#99ff04]">
                                            Lihat Publik
                                        </a>
                                        <a href="{{ route('kampanye.transparansi', $campaign) }}" class="rounded-xl border border-white/20 bg-[#231f36] px-3.5 py-1.5 text-xs font-extrabold text-white transition-all hover:border-[#99ff04] hover:text-[#99ff04]">
                                            Ledger Publik
                                        </a>
                                        <a href="{{ route('pengaju.kampanye.milestones', $campaign) }}" wire:navigate class="rounded-xl bg-[#99ff04] px-4 py-1.5 text-xs font-black text-black shadow-md shadow-[#99ff04]/20 transition-all hover:bg-[#84e000]">
                                            Kelola Tahapan
                                        </a>
                                    @endif
                                    @if ($campaign->isEditable())
                                        <a href="{{ route('pengaju.kampanye.edit', $campaign) }}" wire:navigate class="rounded-xl bg-[#99ff04] px-4 py-1.5 text-xs font-black text-black shadow-md shadow-[#99ff04]/20 transition-all hover:bg-[#84e000]">
                                            Ubah Kampanye
                                        </a>
                                    @endif
                                </div>
                            </div>

                            {{-- Notice: Menunggu Review --}}
                            @if ($campaign->status === 'pending')
                                <div class="rounded-2xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-xs text-amber-300 flex items-center gap-2.5">
                                    <svg class="h-4 w-4 shrink-0 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="font-medium text-amber-200">
                                        <strong>Sedang Ditinjau Admin:</strong> Kampanye telah diajukan dan sedang dalam antrean verifikasi (1x24 jam). Data kampanye terkunci dan tidak dapat diubah selama proses review.
                                    </p>
                                </div>
                            @endif

                            {{-- Notice: Catatan Penolakan Admin --}}
                            @if ($campaign->status === 'rejected' && $campaign->review_note)
                                <div class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-xs text-rose-300">
                                    <p class="font-extrabold text-rose-200">Catatan Review Admin:</p>
                                    <p class="mt-1 leading-relaxed">{{ $campaign->review_note }}</p>
                                    <p class="mt-2 text-[11px] text-rose-300/80">Silakan klik <strong>Ubah Kampanye</strong> untuk memperbaiki poin di atas, lalu ajukan kembali.</p>
                                </div>
                            @endif

                            <!-- Bagian Progress Bar & Statistik -->
                            <div class="border-t border-white/10 pt-3">
                                <div class="h-2 w-full overflow-hidden rounded-full bg-[#231f36]">
                                    <div class="h-full bg-[#99ff04]" style="width: {{ $campaign->progressPercent() }}%"></div>
                                </div>
                                <dl class="mt-3 grid grid-cols-2 gap-3 text-xs sm:grid-cols-4">
                                    <div class="rounded-xl border border-white/5 bg-[#231f36] p-2.5">
                                        <dt class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Terkumpul</dt>
                                        <dd class="mt-1 font-black text-white text-xs sm:text-sm tabular-nums">{{ rupiah($campaign->collected_amount) }}</dd>
                                    </div>
                                    <div class="rounded-xl border border-white/5 bg-[#231f36] p-2.5">
                                        <dt class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Target</dt>
                                        <dd class="mt-1 font-black text-white text-xs sm:text-sm tabular-nums">{{ rupiah($campaign->target_amount) }}</dd>
                                    </div>
                                    <div class="rounded-xl border border-white/5 bg-[#231f36] p-2.5">
                                        <dt class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Dicairkan</dt>
                                        <dd class="mt-1 font-black text-[#99ff04] text-xs sm:text-sm tabular-nums">{{ rupiah($campaign->disbursed_amount) }}</dd>
                                    </div>
                                    <div class="rounded-xl border border-white/5 bg-[#231f36] p-2.5">
                                        <dt class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Donatur</dt>
                                        <dd class="mt-1 font-black text-white text-xs sm:text-sm tabular-nums">{{ $campaign->donations_count }} Orang</dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Bagian Footer (Tombol Ajukan & Hapus dengan Modal Konfirmasi) -->
                            @if ($campaign->isEditable() || in_array($campaign->status, ['draft', 'pending', 'rejected']))
                                <div class="flex flex-wrap items-center justify-between border-t border-white/10 pt-3 gap-3">
                                    @if ($campaign->isEditable())
                                        <button type="button"
                                                @click="openSubmitModal({{ $campaign->id }}, @js($campaign->title), @js(rupiah($campaign->target_amount)), {{ $campaign->milestones()->count() }}, @js(route('pengaju.kampanye.submit', $campaign)))"
                                                class="rounded-full bg-[#99ff04] px-5 py-2 text-xs font-black text-black shadow-md shadow-[#99ff04]/20 transition-all hover:bg-[#84e000] hover:scale-105 active:scale-95 cursor-pointer inline-flex items-center gap-2">
                                            <span>Ajukan untuk Review Admin</span>
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                            </svg>
                                        </button>
                                    @else
                                        <div></div>
                                    @endif

                                    @if (in_array($campaign->status, ['draft', 'pending', 'rejected']))
                                        <button type="button"
                                                @click="openDeleteModal({{ $campaign->id }}, @js($campaign->title), @js(route('pengaju.kampanye.destroy', $campaign)))"
                                                class="text-xs font-bold text-rose-400 transition-colors hover:text-rose-300 hover:underline cursor-pointer inline-flex items-center gap-1.5">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <span>Hapus Kampanye</span>
                                        </button>
                                    @endif
                                </div>
                            @endif

                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-6">{{ $campaigns->links() }}</div>
    @endif

    {{-- Modal Konfirmasi Ajukan Review (Teleported to Body for Full Viewport Coverage) --}}
    <template x-teleport="body">
        <div x-show="showSubmitModal" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[99999] flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
             role="dialog" aria-modal="true"
             style="position: fixed; inset: 0; width: 100vw; height: 100vh; z-index: 99999;"
             @keydown.escape.window="if (!isSubmitting) showSubmitModal = false">
            
            {{-- Fullscreen Backdrop Blur --}}
            <div class="fixed inset-0 bg-black/75 backdrop-blur-md transition-opacity"
                 style="position: fixed; inset: 0; width: 100vw; height: 100vh;"
                 @click="if (!isSubmitting) showSubmitModal = false"></div>

            {{-- Modal Content Card --}}
            <div class="relative w-full max-w-lg overflow-hidden rounded-3xl border border-white/15 bg-[#1b182a] p-6 sm:p-7 shadow-2xl z-10 my-auto"
                 @click.stop
                 x-show="showSubmitModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2">
                
                {{-- Glow Effect --}}
                <div aria-hidden="true" class="absolute -top-16 -right-16 h-44 w-44 rounded-full bg-[#99ff04]/10 blur-3xl pointer-events-none"></div>

                <div class="flex items-start gap-4">
                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#99ff04]/10 text-[#99ff04] border border-[#99ff04]/20 shadow-md">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-lg font-black text-white">Ajukan Kampanye untuk Review?</h3>
                        <p class="mt-1 text-xs text-slate-400">
                            Admin akan memeriksa kesesuaian cerita, target dana, dan rincian RAB sebelum disetujui.
                        </p>
                    </div>
                </div>

                <div class="mt-5 rounded-2xl border border-white/10 bg-[#231f36]/70 p-4 space-y-2">
                    <div class="text-xs">
                        <span class="text-slate-400">Judul Kampanye:</span>
                        <p class="mt-0.5 font-black text-white line-clamp-2" x-text="submitCampaignTitle"></p>
                    </div>
                    <div class="flex items-center justify-between border-t border-white/10 pt-2 text-xs">
                        <span class="text-slate-400">Target Dana:</span>
                        <span class="font-black text-[#99ff04] tabular-nums" x-text="submitCampaignTarget"></span>
                    </div>
                    <div class="flex items-center justify-between border-t border-white/10 pt-2 text-xs">
                        <span class="text-slate-400">Jumlah Tahap Pencairan:</span>
                        <span class="font-black text-white tabular-nums" x-text="`${submitCampaignMilestonesCount} Tahap`"></span>
                    </div>
                </div>

                {{-- Warning: Tidak bisa diubah setelah diajukan --}}
                <div class="mt-4 rounded-2xl border border-amber-500/30 bg-amber-500/10 p-3.5 text-xs text-amber-300 flex items-start gap-2.5">
                    <svg class="h-4 w-4 shrink-0 text-amber-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="leading-relaxed">
                        <strong class="font-bold text-amber-200">Perhatian:</strong>
                        Setelah diajukan, kampanye Anda <strong>tidak dapat diubah kembali</strong> selama dalam antrean peninjauan oleh tim admin DonasiTrust.
                    </div>
                </div>

                <div x-show="submitError" x-cloak class="mt-3 rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-xs text-rose-300 font-medium">
                    <span x-text="submitError"></span>
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                    <button type="button" @click="showSubmitModal = false" :disabled="isSubmitting"
                            class="rounded-xl border border-white/15 bg-[#231f36] px-4 py-2.5 text-xs font-extrabold text-white hover:bg-white/10 transition-all cursor-pointer">
                        Batal
                    </button>
                    <button type="button" @click="confirmSubmit()" :disabled="isSubmitting"
                            class="rounded-xl bg-[#99ff04] px-5 py-2.5 text-xs font-black text-black hover:bg-[#84e000] shadow-md shadow-[#99ff04]/20 transition-all cursor-pointer inline-flex items-center gap-2">
                        <svg x-show="isSubmitting" x-cloak class="h-4 w-4 animate-spin text-black" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span x-text="isSubmitting ? 'Mengajukan...' : 'Ya, Ajukan Kampanye'">Ya, Ajukan Kampanye</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- Modal Konfirmasi Hapus Kampanye (Teleported to Body for Full Viewport Coverage) --}}
    <template x-teleport="body">
        <div x-show="showDeleteModal" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[99999] flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
             role="dialog" aria-modal="true"
             style="position: fixed; inset: 0; width: 100vw; height: 100vh; z-index: 99999;"
             @keydown.escape.window="if (!isDeleting) showDeleteModal = false">
            
            {{-- Fullscreen Backdrop Blur --}}
            <div class="fixed inset-0 bg-black/75 backdrop-blur-md transition-opacity"
                 style="position: fixed; inset: 0; width: 100vw; height: 100vh;"
                 @click="if (!isDeleting) showDeleteModal = false"></div>

            {{-- Modal Content Card --}}
            <div class="relative w-full max-w-md overflow-hidden rounded-3xl border border-rose-500/20 bg-[#1b182a] p-6 sm:p-7 shadow-2xl z-10 my-auto"
                 @click.stop
                 x-show="showDeleteModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2">
                
                <div class="flex items-start gap-4">
                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-rose-500/10 text-rose-400 border border-rose-500/20 shadow-md">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-lg font-black text-white">Hapus Kampanye?</h3>
                        <p class="mt-1 text-xs text-slate-400">
                            Tindakan ini permanen dan data draf atau pengajuan kampanye tidak dapat dipulihkan.
                        </p>
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-white/10 bg-[#231f36]/70 p-4">
                    <p class="text-xs text-slate-400">Kampanye yang akan dihapus:</p>
                    <p class="mt-1 text-sm font-black text-white line-clamp-2" x-text="deleteCampaignTitle"></p>
                </div>

                <div x-show="deleteError" x-cloak class="mt-3 rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-xs text-rose-300 font-medium">
                    <span x-text="deleteError"></span>
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                    <button type="button" @click="showDeleteModal = false" :disabled="isDeleting"
                            class="rounded-xl border border-white/15 bg-[#231f36] px-4 py-2.5 text-xs font-extrabold text-white hover:bg-white/10 transition-all cursor-pointer">
                        Batal
                    </button>
                    <button type="button" @click="confirmDelete()" :disabled="isDeleting"
                            class="rounded-xl bg-rose-600 px-5 py-2.5 text-xs font-black text-white hover:bg-rose-500 shadow-md shadow-rose-600/20 transition-all cursor-pointer inline-flex items-center gap-2">
                        <svg x-show="isDeleting" x-cloak class="h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span x-text="isDeleting ? 'Menghapus...' : 'Ya, Hapus Kampanye'">Ya, Hapus Kampanye</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

</div>
@endsection
