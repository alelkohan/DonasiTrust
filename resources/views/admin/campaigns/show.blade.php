@extends('layouts.dashboard')
@section('title', 'Tinjau: '.$campaign->title)

@php($menu = \App\Support\AdminMenu::items('kampanye'))

@php
    $flaggedItemNames = collect($campaign->ai_analysis['flags'] ?? [])->pluck('item_name')->map(fn($n) => strtolower($n))->all();
@endphp

@section('panel')
    <nav class="mb-5 text-sm text-slate-400">
        <a href="{{ route('admin.kampanye.index', ['status' => 'pending']) }}" wire:navigate class="hover:text-[#99ff04] transition-colors">&larr; Kembali ke antrean</a>
    </nav>

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-black tracking-tight text-white">{{ $campaign->title }}</h1>
            <p class="mt-1.5 text-sm font-medium text-slate-400">
                {{ $campaign->categoryLabel() }} &middot; target <span class="text-white font-bold">{{ rupiah($campaign->target_amount) }}</span>
                @if ($campaign->deadline) &middot; batas {{ $campaign->deadline->translatedFormat('d F Y') }} @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($campaign->hasAiAnalysis())
                <x-badge :tone="$campaign->aiRiskTone()">
                    AI: {{ $campaign->aiRiskLabel() }}
                </x-badge>
            @endif
            <x-badge :tone="match($campaign->status) {
                'approved', 'completed' => 'success',
                'pending' => 'warning',
                'rejected' => 'danger',
                default => 'neutral',
            }">{{ $campaign->statusLabel() }}</x-badge>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1.5fr_1fr] lg:items-start"
         x-data="{
             isAuditing: false,
             auditError: '',
             aiAnalysis: {{ Illuminate\Support\Js::from($campaign->ai_analysis) }},
             aiRiskLevel: '{{ $campaign->ai_risk_level ?? 'low' }}',
             aiRiskLabel: '{{ $campaign->aiRiskLabel() }}',
             aiRiskTone: '{{ $campaign->aiRiskTone() }}',

             // Decision & Modal State
             showApproveModal: false,
             showRejectModal: false,
             isSubmitting: false,
             decisionError: '',
             approveNote: '',
             rejectNote: '',

             openApproveModal() {
                 this.decisionError = '';
                 const approveInput = document.getElementById('note-approve');
                 if (approveInput && !this.approveNote) {
                     this.approveNote = approveInput.value;
                 }
                 this.showApproveModal = true;
             },

             openRejectModal() {
                 this.decisionError = '';
                 const noteInput = document.getElementById('note-reject');
                 const noteVal = (this.rejectNote || (noteInput ? noteInput.value : '') || '').trim();
                 this.rejectNote = noteVal;
                 if (!noteVal) {
                     if (noteInput) {
                         noteInput.focus();
                         noteInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                         noteInput.classList.add('ring-2', 'ring-rose-500');
                         setTimeout(() => noteInput.classList.remove('ring-2', 'ring-rose-500'), 2500);
                     }
                     this.decisionError = 'Alasan penolakan wajib diisi sebelum menolak kampanye.';
                     return;
                 }
                 this.showRejectModal = true;
             },

             async submitDecision(actionType) {
                 if (this.isSubmitting) return;
                 this.isSubmitting = true;
                 this.decisionError = '';

                 const url = actionType === 'approve'
                     ? '{{ route('admin.kampanye.approve', $campaign) }}'
                     : '{{ route('admin.kampanye.reject', $campaign) }}';

                 const noteVal = actionType === 'approve' ? this.approveNote : this.rejectNote;

                 try {
                     const res = await fetch(url, {
                         method: 'POST',
                         headers: {
                             'Content-Type': 'application/json',
                             'Accept': 'application/json',
                             'X-Requested-With': 'XMLHttpRequest',
                             'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']')?.content || ''
                         },
                         body: JSON.stringify({ note: noteVal })
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
                         this.decisionError = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Terjadi kesalahan saat memproses keputusan.');
                     }
                 } catch (err) {
                     this.isSubmitting = false;
                     this.decisionError = 'Terjadi gangguan koneksi internet. Silakan coba lagi.';
                 }
             },

             async rerunAiAudit() {
                 if (this.isAuditing) return;
                 this.isAuditing = true;
                 this.auditError = '';

                 try {
                     const res = await fetch('{{ route('admin.kampanye.audit-ai', $campaign) }}', {
                         method: 'POST',
                         headers: {
                             'Accept': 'application/json',
                             'X-Requested-With': 'XMLHttpRequest',
                             'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']')?.content || ''
                         }
                     });
                     const data = await res.json().catch(() => ({}));
                     if (res.ok && data.success) {
                         this.aiAnalysis = data.analysis;
                         this.aiRiskLevel = data.risk_level;
                         this.aiRiskLabel = data.risk_label;
                         this.aiRiskTone = data.risk_tone;
                     } else {
                         this.auditError = data.message || 'Gagal menjalankan audit AI.';
                     }
                 } catch (e) {
                     this.auditError = 'Terjadi gangguan jaringan saat memproses audit AI.';
                 } finally {
                     this.isAuditing = false;
                 }
             },

             copyAiNotesToReject() {
                 const noteInput = document.getElementById('note-reject');
                 if (!noteInput || !this.aiAnalysis) return;

                 let text = 'Mohon perbaiki rincian anggaran kampanye Anda berdasarkan evaluasi berikut:\n';
                 
                 if (this.aiAnalysis.flags && this.aiAnalysis.flags.length > 0) {
                     this.aiAnalysis.flags.forEach((f, idx) => {
                         text += `- Pos ${f.item_name}: ${f.reason}\n`;
                     });
                 }
                 
                 if (this.aiAnalysis.admin_recommendations && this.aiAnalysis.admin_recommendations.length > 0) {
                     text += '\nRekomendasi Perbaikan:\n';
                     this.aiAnalysis.admin_recommendations.forEach(r => {
                         text += `* ${r}\n`;
                     });
                 }

                 this.rejectNote = text.trim();
                 noteInput.value = text.trim();
                 noteInput.style.height = 'auto';
                 noteInput.style.height = (noteInput.scrollHeight + 6) + 'px';
                 noteInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                 noteInput.focus();
             }
         }">

        <div class="space-y-6">

            {{-- 1. AI Budget Auditor Insights Card --}}
            <section class="relative overflow-hidden rounded-3xl border border-white/10 bg-[#1b182a] p-5 sm:p-6 shadow-xl backdrop-blur-md">
                <div aria-hidden="true" class="absolute -top-20 -right-20 h-56 w-56 rounded-full bg-[#99ff04]/10 blur-3xl pointer-events-none"></div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-[#99ff04]/10 text-[#99ff04] border border-[#99ff04]/20 shadow-md">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-black text-white">AI Budget Auditor</h2>
                                <span class="rounded-full border border-white/10 bg-[#231f36] px-2.5 py-0.5 text-[10px] font-black uppercase text-[#99ff04]">
                                    Anti-Mark Up
                                </span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-400">Analisis kecocokan tujuan, harga satuan pasar, dan proporsi RAB</p>
                        </div>
                    </div>

                    <button type="button" @click="rerunAiAudit()" :disabled="isAuditing"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-white/15 bg-[#231f36] px-3.5 py-1.5 text-xs font-bold text-slate-200 hover:border-[#99ff04] hover:text-[#99ff04] transition-all cursor-pointer">
                        <svg x-show="!isAuditing" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <svg x-show="isAuditing" x-cloak class="h-3.5 w-3.5 animate-spin text-[#99ff04]" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span x-text="isAuditing ? 'Menganalisis...' : 'Audit Ulang AI'">Audit Ulang AI</span>
                    </button>
                </div>

                <div x-show="auditError" x-cloak class="mt-4 rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-xs text-rose-300">
                    <span x-text="auditError"></span>
                </div>

                <template x-if="aiAnalysis">
                    <div class="mt-4 space-y-4">
                        
                        {{-- Risk Score & Summary Banner --}}
                        <div class="rounded-2xl border p-4 transition-all"
                             :class="{
                                 'border-emerald-500/30 bg-emerald-500/10': aiRiskLevel === 'low',
                                 'border-amber-500/30 bg-amber-500/10': aiRiskLevel === 'medium',
                                 'border-rose-500/30 bg-rose-500/10': aiRiskLevel === 'high'
                             }">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="grid h-3 w-3 rounded-full"
                                          :class="{
                                              'bg-emerald-400 ring-4 ring-emerald-400/20': aiRiskLevel === 'low',
                                              'bg-amber-400 ring-4 ring-amber-400/20': aiRiskLevel === 'medium',
                                              'bg-rose-500 ring-4 ring-rose-500/20': aiRiskLevel === 'high'
                                          }"></span>
                                    <span class="text-xs font-black uppercase tracking-wider"
                                          :class="{
                                              'text-emerald-300': aiRiskLevel === 'low',
                                              'text-amber-300': aiRiskLevel === 'medium',
                                              'text-rose-300': aiRiskLevel === 'high'
                                          }"
                                          x-text="aiRiskLabel"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] font-bold text-slate-400">Skor Indikasi Risiko:</span>
                                    <span class="rounded-lg px-2.5 py-0.5 text-xs font-black tabular-nums border"
                                          :class="{
                                              'bg-emerald-500/20 text-emerald-300 border-emerald-500/30': aiRiskLevel === 'low',
                                              'bg-amber-500/20 text-amber-300 border-amber-500/30': aiRiskLevel === 'medium',
                                              'bg-rose-500/20 text-rose-300 border-rose-500/30': aiRiskLevel === 'high'
                                          }"
                                          x-text="`${aiAnalysis.risk_score || 0} / 100`"></span>
                                </div>
                            </div>

                            <p class="mt-3 text-xs leading-relaxed text-slate-200" x-text="aiAnalysis.summary"></p>
                        </div>

                        {{-- Flags / Temuan Anomali --}}
                        <template x-if="aiAnalysis.flags && aiAnalysis.flags.length > 0">
                            <div class="space-y-2.5">
                                <p class="text-xs font-black uppercase tracking-wider text-rose-400 flex items-center gap-1.5">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <span>Temuan &amp; Potensi Anomali Anggaran (<span x-text="aiAnalysis.flags.length"></span>)</span>
                                </p>

                                <div class="grid gap-2.5">
                                    <template x-for="(flag, idx) in aiAnalysis.flags" :key="idx">
                                        <div class="rounded-2xl border border-rose-500/20 bg-[#231f36]/80 p-3.5 text-xs">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-black text-white" x-text="flag.item_name"></span>
                                                    <span class="rounded-full px-2 py-0.5 text-[9px] font-black uppercase border"
                                                          :class="{
                                                              'bg-rose-500/20 text-rose-300 border-rose-500/30': flag.severity === 'high',
                                                              'bg-amber-500/20 text-amber-300 border-amber-500/30': flag.severity === 'medium',
                                                              'bg-slate-500/20 text-slate-300 border-slate-500/30': flag.severity === 'low'
                                                          }"
                                                          x-text="flag.severity === 'high' ? 'Tinggi' : (flag.severity === 'medium' ? 'Sedang' : 'Rendah')"></span>
                                                </div>
                                                <span class="text-[10px] font-bold text-slate-400 capitalize" x-text="flag.type ? flag.type.replace('_', ' ') : ''"></span>
                                            </div>
                                            <p class="mt-1.5 leading-relaxed text-slate-300" x-text="flag.reason"></p>
                                            <template x-if="flag.suggested_action">
                                                <div class="mt-2 rounded-xl bg-white/5 px-3 py-1.5 text-[11px] text-slate-300 border border-white/5">
                                                    <strong class="text-[#99ff04]">Saran:</strong> <span x-text="flag.suggested_action"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- Positive Aspects & Admin Recommendations Grid --}}
                        <div class="grid gap-3 sm:grid-cols-2 text-xs">
                            <template x-if="aiAnalysis.positive_aspects && aiAnalysis.positive_aspects.length > 0">
                                <div class="rounded-2xl border border-white/10 bg-[#231f36]/40 p-3.5 space-y-2">
                                    <p class="font-extrabold text-emerald-400 flex items-center gap-1.5">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Kewajaran &amp; Poin Positif</span>
                                    </p>
                                    <ul class="space-y-1 text-slate-300 list-disc list-inside text-[11px] leading-relaxed">
                                        <template x-for="(pos, idx) in aiAnalysis.positive_aspects" :key="idx">
                                            <li x-text="pos"></li>
                                        </template>
                                    </ul>
                                </div>
                            </template>

                            <template x-if="aiAnalysis.admin_recommendations && aiAnalysis.admin_recommendations.length > 0">
                                <div class="rounded-2xl border border-white/10 bg-[#231f36]/40 p-3.5 space-y-2">
                                    <p class="font-extrabold text-amber-300 flex items-center gap-1.5">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        <span>Rekomendasi Verifikasi</span>
                                    </p>
                                    <ul class="space-y-1 text-slate-300 list-disc list-inside text-[11px] leading-relaxed">
                                        <template x-for="(rec, idx) in aiAnalysis.admin_recommendations" :key="idx">
                                            <li x-text="rec"></li>
                                        </template>
                                    </ul>
                                </div>
                            </template>
                        </div>

                        {{-- Quick Action: Copy to Rejection Note --}}
                        @if ($campaign->status === 'pending')
                            <template x-if="aiAnalysis.flags && aiAnalysis.flags.length > 0">
                                <div class="border-t border-white/10 pt-3 flex justify-end">
                                    <button type="button" @click="copyAiNotesToReject()"
                                            class="inline-flex items-center gap-1.5 text-xs font-extrabold text-rose-300 hover:text-rose-200 transition-colors cursor-pointer">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        <span>Salin Temuan AI ke Form Penolakan / Revisi</span>
                                    </button>
                                </div>
                            </template>
                        @endif

                    </div>
                </template>
            </section>

            {{-- 2. Ringkasan & Cerita --}}
            <section class="dt-card p-5 sm:p-6">
                <h2 class="text-lg font-black text-white">Ringkasan &amp; cerita</h2>
                <p class="mt-3 text-sm font-semibold text-slate-200">{{ $campaign->summary }}</p>
                <div class="mt-4 max-h-96 overflow-y-auto border-t border-white/10 pt-4 text-sm leading-relaxed whitespace-pre-line text-slate-300">{{ $campaign->description }}</div>
            </section>

            {{-- 3. Rincian Anggaran (RAB) with AI Anomaly Highlighting --}}
            <section class="dt-card overflow-hidden">
                <div class="p-5 sm:p-6 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black text-white">Rincian anggaran (RAB)</h2>
                        <p class="mt-1 text-sm font-medium text-slate-400">Periksa kewajaran harga sebelum menyetujui.</p>
                    </div>
                    @if ($campaign->hasAiAnalysis() && $campaign->aiFlagsCount() > 0)
                        <span class="rounded-full border border-rose-500/30 bg-rose-500/10 px-3 py-1 text-xs font-extrabold text-rose-300 flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-rose-500 animate-pulse"></span>
                            <span>{{ $campaign->aiFlagsCount() }} Item Disorot AI</span>
                        </span>
                    @endif
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
                                @php
                                    $isFlagged = false;
                                    $flagReason = null;
                                    foreach ($campaign->ai_analysis['flags'] ?? [] as $f) {
                                        if (str_contains(strtolower($item->name), strtolower($f['item_name'] ?? '')) || str_contains(strtolower($f['item_name'] ?? ''), strtolower($item->name))) {
                                            $isFlagged = true;
                                            $flagReason = $f['reason'] ?? null;
                                            break;
                                        }
                                    }
                                @endphp
                                <tr @class([
                                    'bg-rose-500/5' => $isFlagged,
                                ])>
                                    <td class="font-bold text-white">
                                        <div class="flex items-center gap-2">
                                            <span>{{ $item->name }}</span>
                                            @if ($isFlagged)
                                                <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase bg-rose-500/20 text-rose-300 border border-rose-500/30"
                                                      title="{{ $flagReason }}">
                                                    Disorot AI
                                                </span>
                                            @endif
                                        </div>
                                    </td>
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

            {{-- 4. Tahapan Pencairan --}}
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
                <section class="dt-card p-5 sm:p-6">
                    <h2 class="text-lg font-black text-white">Keputusan</h2>
                    <p class="mt-1 text-xs font-medium text-slate-400">Keputusan Anda tercatat permanen di jejak audit.</p>

                    <div x-show="decisionError" x-cloak class="mt-4 rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-xs text-rose-300">
                        <span x-text="decisionError"></span>
                    </div>

                    {{-- Form Setujui --}}
                    <div class="mt-5 space-y-3">
                        <div>
                            <label for="note-approve" class="dt-label">Catatan Persetujuan <span class="font-normal text-slate-500">(opsional)</span></label>
                            <textarea id="note-approve" x-model="approveNote" rows="2" class="dt-input text-xs transition-[height] duration-100" maxlength="1000"
                                      placeholder="Catatan untuk pengaju"
                                      oninput="this.style.height = 'auto'; this.style.height = (this.scrollHeight + 4) + 'px'"></textarea>
                        </div>
                        <button type="button" @click="openApproveModal()" class="dt-btn-primary w-full py-3 inline-flex items-center justify-center gap-2 cursor-pointer shadow-lg shadow-[#99ff04]/10">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Setujui &amp; tayangkan</span>
                        </button>
                    </div>

                    <div class="my-5 border-t border-white/10"></div>

                    {{-- Form Tolak --}}
                    <div class="space-y-3">
                        <div>
                            <label for="note-reject" class="dt-label">Alasan penolakan <span class="text-rose-400">*</span></label>
                            <textarea id="note-reject" x-model="rejectNote" rows="4" required class="dt-input text-xs transition-[height] duration-100" maxlength="1000"
                                      placeholder="Jelaskan apa yang perlu diperbaiki agar pengaju bisa mengajukan ulang."
                                      oninput="this.style.height = 'auto'; this.style.height = (this.scrollHeight + 4) + 'px'"></textarea>
                        </div>
                        <button type="button" @click="openRejectModal()" class="dt-btn-danger w-full py-2.5 inline-flex items-center justify-center gap-2 cursor-pointer shadow-lg shadow-rose-600/10">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span>Tolak kampanye</span>
                        </button>
                    </div>
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

    {{-- 1. Modal Konfirmasi Setujui & Tayangkan --}}
    <template x-teleport="body">
        <div x-show="showApproveModal" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[99999] flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
             role="dialog" aria-modal="true"
             style="position: fixed; inset: 0; width: 100vw; height: 100vh; z-index: 99999;"
             @keydown.escape.window="if (!isSubmitting) showApproveModal = false">
            
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/75 backdrop-blur-md transition-opacity"
                 style="position: fixed; inset: 0; width: 100vw; height: 100vh;"
                 @click="if (!isSubmitting) showApproveModal = false"></div>

            {{-- Modal Card --}}
            <div class="relative w-full max-w-lg overflow-hidden rounded-3xl border border-white/15 bg-[#1b182a] p-6 sm:p-7 shadow-2xl z-10 my-auto"
                 @click.stop
                 x-show="showApproveModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2">
                
                {{-- Ambient Glow --}}
                <div aria-hidden="true" class="absolute -top-16 -right-16 h-44 w-44 rounded-full bg-[#99ff04]/10 blur-3xl pointer-events-none"></div>

                <div class="flex items-start gap-4">
                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#99ff04]/10 text-[#99ff04] border border-[#99ff04]/20 shadow-md">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-lg font-black text-white">Setujui &amp; Tayangkan Kampanye?</h3>
                        <p class="mt-1 text-xs text-slate-400">
                            Kampanye ini akan aktif dan langsung tampil ke publik untuk menerima donasi masyarakat.
                        </p>
                    </div>
                </div>

                {{-- Summary Details --}}
                <div class="mt-5 rounded-2xl border border-white/10 bg-[#231f36]/70 p-4 space-y-2.5">
                    <div class="text-xs">
                        <span class="text-slate-400">Judul Kampanye:</span>
                        <p class="mt-0.5 font-black text-white">{{ $campaign->title }}</p>
                    </div>
                    <div class="flex items-center justify-between border-t border-white/10 pt-2 text-xs">
                        <span class="text-slate-400">Pengaju:</span>
                        <span class="font-bold text-white">{{ $campaign->user->name }}</span>
                    </div>
                    <div class="flex items-center justify-between border-t border-white/10 pt-2 text-xs">
                        <span class="text-slate-400">Target Dana:</span>
                        <span class="font-black text-[#99ff04] tabular-nums">{{ rupiah($campaign->target_amount) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-t border-white/10 pt-2 text-xs">
                        <span class="text-slate-400">Tahap Pencairan:</span>
                        <span class="font-bold text-white tabular-nums">{{ $campaign->milestones->count() }} Tahap</span>
                    </div>
                    <div class="flex items-center justify-between border-t border-white/10 pt-2 text-xs">
                        <span class="text-slate-400">Indikator AI Auditor:</span>
                        <span class="inline-flex items-center gap-1.5 font-bold"
                              :class="{
                                  'text-emerald-400': aiRiskTone === 'success',
                                  'text-amber-300': aiRiskTone === 'warning',
                                  'text-rose-400': aiRiskTone === 'danger'
                              }">
                            <span class="h-2 w-2 rounded-full"
                                  :class="{
                                      'bg-emerald-400': aiRiskTone === 'success',
                                      'bg-amber-400': aiRiskTone === 'warning',
                                      'bg-rose-500': aiRiskTone === 'danger'
                                  }"></span>
                            <span x-text="aiRiskLabel"></span>
                        </span>
                    </div>
                    <template x-if="approveNote.trim()">
                        <div class="border-t border-white/10 pt-2 text-xs">
                            <span class="text-slate-400">Catatan Tambahan Admin:</span>
                            <p class="mt-1 rounded-xl bg-[#12101c] p-2.5 text-slate-300 whitespace-pre-wrap" x-text="approveNote.trim()"></p>
                        </div>
                    </template>
                </div>

                {{-- Notice --}}
                <div class="mt-4 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-3.5 text-xs text-emerald-300 flex items-start gap-2.5">
                    <svg class="h-4 w-4 shrink-0 text-emerald-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="leading-relaxed">
                        Persetujuan ini akan tercatat permanen di <strong>Jejak Audit</strong> dan notifikasi aktivasi kampanye akan otomatis dikirimkan ke pengaju.
                    </div>
                </div>

                <div x-show="decisionError" x-cloak class="mt-3 rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-xs text-rose-300 font-medium">
                    <span x-text="decisionError"></span>
                </div>

                {{-- Action Buttons --}}
                <div class="mt-6 flex flex-col-reverse sm:flex-row items-center justify-end gap-3">
                    <button type="button" @click="showApproveModal = false" :disabled="isSubmitting"
                            class="w-full sm:w-auto dt-btn-secondary px-5 py-2.5 text-xs font-bold cursor-pointer">
                        Batal
                    </button>
                    <button type="button" @click="submitDecision('approve')" :disabled="isSubmitting"
                            class="w-full sm:w-auto dt-btn-primary px-6 py-2.5 text-xs font-black inline-flex items-center justify-center gap-2 cursor-pointer shadow-lg shadow-[#99ff04]/20">
                        <svg x-show="isSubmitting" x-cloak class="h-4 w-4 animate-spin text-black" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span x-text="isSubmitting ? 'Memproses...' : 'Ya, Setujui & Tayangkan'">Ya, Setujui &amp; Tayangkan</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- 2. Modal Konfirmasi Tolak Kampanye --}}
    <template x-teleport="body">
        <div x-show="showRejectModal" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[99999] flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
             role="dialog" aria-modal="true"
             style="position: fixed; inset: 0; width: 100vw; height: 100vh; z-index: 99999;"
             @keydown.escape.window="if (!isSubmitting) showRejectModal = false">
            
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/75 backdrop-blur-md transition-opacity"
                 style="position: fixed; inset: 0; width: 100vw; height: 100vh;"
                 @click="if (!isSubmitting) showRejectModal = false"></div>

            {{-- Modal Card --}}
            <div class="relative w-full max-w-lg overflow-hidden rounded-3xl border border-rose-500/30 bg-[#1b182a] p-6 sm:p-7 shadow-2xl z-10 my-auto"
                 @click.stop
                 x-show="showRejectModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2">
                
                {{-- Ambient Glow --}}
                <div aria-hidden="true" class="absolute -top-16 -right-16 h-44 w-44 rounded-full bg-rose-500/10 blur-3xl pointer-events-none"></div>

                <div class="flex items-start gap-4">
                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-rose-500/10 text-rose-400 border border-rose-500/20 shadow-md">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-lg font-black text-white">Konfirmasi Penolakan Kampanye</h3>
                        <p class="mt-1 text-xs text-slate-400">
                            Status kampanye akan diubah menjadi Ditolak dan catatan perbaikan akan dikirimkan kepada pengaju.
                        </p>
                    </div>
                </div>

                {{-- Summary Details --}}
                <div class="mt-5 rounded-2xl border border-white/10 bg-[#231f36]/70 p-4 space-y-2.5">
                    <div class="text-xs">
                        <span class="text-slate-400">Judul Kampanye:</span>
                        <p class="mt-0.5 font-bold text-white">{{ $campaign->title }}</p>
                    </div>
                    <div class="border-t border-white/10 pt-2 text-xs">
                        <span class="text-slate-400 font-semibold">Alasan Penolakan / Catatan Perbaikan:</span>
                        <div class="mt-1.5 max-h-36 overflow-y-auto rounded-xl border border-rose-500/20 bg-[#12101c] p-3 text-xs text-rose-200 whitespace-pre-wrap leading-relaxed"
                             x-text="rejectNote.trim()"></div>
                    </div>
                </div>

                {{-- Warning --}}
                <div class="mt-4 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-3.5 text-xs text-rose-300 flex items-start gap-2.5">
                    <svg class="h-4 w-4 shrink-0 text-rose-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="leading-relaxed">
                        Pengaju dapat memperbaiki data kampanye di dasbor mereka dan mengajukan ulang setelah memenuhi catatan di atas.
                    </div>
                </div>

                <div x-show="decisionError" x-cloak class="mt-3 rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-xs text-rose-300 font-medium">
                    <span x-text="decisionError"></span>
                </div>

                {{-- Action Buttons --}}
                <div class="mt-6 flex flex-col-reverse sm:flex-row items-center justify-end gap-3">
                    <button type="button" @click="showRejectModal = false" :disabled="isSubmitting"
                            class="w-full sm:w-auto dt-btn-secondary px-5 py-2.5 text-xs font-bold cursor-pointer">
                        Kembali / Edit Alasan
                    </button>
                    <button type="button" @click="submitDecision('reject')" :disabled="isSubmitting"
                            class="w-full sm:w-auto dt-btn-danger px-6 py-2.5 text-xs font-black inline-flex items-center justify-center gap-2 cursor-pointer shadow-lg shadow-rose-600/20">
                        <svg x-show="isSubmitting" x-cloak class="h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span x-text="isSubmitting ? 'Memproses...' : 'Ya, Tolak Kampanye'">Ya, Tolak Kampanye</span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
