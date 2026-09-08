@extends('layouts.dashboard')
@section('title', 'Kelola Tahapan - ' . $campaign->title)


@section('panel')
    <header class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-white/10 pb-5">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-white">Kelola tahapan pencairan</h1>
            <p class="mt-1 text-sm font-medium text-slate-400">{{ $campaign->title }}</p>
        </div>
        <a href="{{ route('pengaju.kampanye.index') }}" class="dt-btn-secondary inline-flex items-center gap-1.5 text-xs">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            Kembali
        </a>
    </header>

    <div class="space-y-6">
        @foreach ($campaign->milestones as $index => $milestone)
            <div class="dt-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-white/10 bg-[#231f36]/60 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-[#99ff04] text-xs font-black text-black shadow-md shadow-[#99ff04]/20">
                            {{ $milestone->sequence }}
                        </span>
                        <div>
                            <h3 class="font-black text-white text-base">{{ $milestone->title }}</h3>
                            <p class="text-xs font-semibold text-slate-400 tabular-nums">Rp{{ number_format($milestone->amount, 0, ',', '.') }}</p>
                        </div>
                    </div>
                    <x-badge :tone="match($milestone->status) {
                        'available' => 'success',
                        'requested' => 'warning',
                        'approved', 'disbursed', 'reported' => 'brand',
                        default => 'neutral'
                    }">
                        {{ $milestone->statusLabel() }}
                    </x-badge>
                </div>
                
                <div class="p-5 sm:p-6">
                    @if ($milestone->description)
                        <p class="mb-4 text-sm font-medium text-slate-300 leading-relaxed">{{ $milestone->description }}</p>
                    @endif

                    {{-- Yang dibutuhkan pengaju bukan nama status, tapi langkah berikutnya. --}}
                    <div @class([
                        'mb-4 flex items-start gap-2.5 rounded-2xl border px-4 py-3.5 text-xs font-medium leading-relaxed',
                        'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' => $milestone->status === \App\Models\Milestone::STATUS_AVAILABLE,
                        'border-amber-500/30 bg-amber-500/10 text-amber-300' => in_array($milestone->status, ['requested', 'approved', 'disbursed'], true),
                        'border-white/10 bg-[#231f36]/60 text-slate-300' => in_array($milestone->status, ['locked', 'reported'], true),
                    ])>
                        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/><path d="M12 16v-4.5M12 8h.01"/>
                        </svg>
                        <span>{{ $milestone->guidance() }}</span>
                    </div>

                    {{-- Form Ajukan pencairan --}}
                    @if ($milestone->status === \App\Models\Milestone::STATUS_AVAILABLE)
                        <form action="{{ route('pengaju.kampanye.disbursement.request', [$campaign, $milestone]) }}" method="POST" class="mt-4 border-t border-white/10 pt-4" x-data="{ open: false }">
                            @csrf
                            <button type="button" @click="open = !open" class="dt-btn-primary text-xs" x-show="!open">Ajukan pencairan</button>
                            <div x-show="open" x-cloak class="space-y-3">
                                <div>
                                    <label class="dt-label text-xs">Tujuan pencairan</label>
                                    <textarea name="purpose" rows="3" required class="dt-input text-xs" placeholder="Misal: Pembayaran DP material ke toko bangunan..."></textarea>
                                </div>

                                {{-- Rekening tujuan tidak bisa dipilih di sini: diambil dari
                                     profil yang sudah diverifikasi admin. --}}
                                <div class="rounded-2xl border p-4 text-xs font-medium {{ $campaign->user->hasPayoutAccount() ? 'border-white/10 bg-[#231f36]/60 text-slate-300' : 'border-amber-500/30 bg-amber-500/10 text-amber-300' }}">
                                    @if ($campaign->user->hasPayoutAccount())
                                        <p class="text-[10px] font-black tracking-wider text-slate-400 uppercase">Dana akan dikirim ke</p>
                                        <p class="mt-1 font-black text-white text-sm">{{ $campaign->user->bank_name }} &middot; <span class="font-mono text-[#99ff04]">{{ $campaign->user->bank_account_number }}</span></p>
                                        <p class="text-slate-300">a.n. {{ $campaign->user->bank_account_holder }}</p>
                                        <p class="mt-2 text-xs leading-relaxed text-slate-400">
                                            Rekening ini terkunci pada profil terverifikasi Anda dan tercatat
                                            permanen pada pengajuan ini.
                                        </p>
                                    @else
                                        <p class="font-bold text-amber-300">Rekening tujuan belum terdaftar</p>
                                        <p class="mt-1 text-xs leading-relaxed text-amber-200">
                                            Lengkapi dulu lewat
                                            <a href="{{ route('verifikasi.identitas') }}" class="font-bold underline text-white">halaman verifikasi identitas</a>,
                                            baru pencairan bisa diajukan.
                                        </p>
                                    @endif
                                </div>

                                <div class="rounded-2xl border border-white/10 bg-[#1b182a] p-4">
                                    <x-otp-input purpose="disbursement_request" label="Kode Verifikasi Email" />
                                </div>

                                <div class="flex gap-2">
                                    <button type="submit" class="dt-btn-primary text-xs">Kirim pengajuan</button>
                                    <button type="button" @click="open = false" class="dt-btn-secondary text-xs">Batal</button>
                                </div>
                            </div>
                        </form>
                    @endif

                    {{-- Info Pencairan --}}
                    @if ($milestone->disbursements->isNotEmpty())
                        <div class="mt-4 rounded-2xl border border-white/10 bg-[#231f36]/60 p-4 sm:p-5">
                            <h4 class="text-sm font-black text-white">Status pengajuan</h4>
                            @foreach ($milestone->disbursements as $disb)
                                <div class="mt-2.5 space-y-1.5 text-xs text-slate-300">
                                    <p><span class="text-slate-400">Tujuan:</span> {{ $disb->purpose }}</p>
                                    <p><span class="text-slate-400">Status:</span> <strong class="text-white font-bold">{{ $disb->statusLabel() }}</strong></p>
                                    @if ($disb->released_at)
                                        <p class="text-xs text-slate-400">
                                            Ditransfer pada {{ $disb->released_at->translatedFormat('d F Y, H:i') }}
                                        </p>
                                    @endif
                                    @if ($disb->supporting_document_path)
                                        <div class="pt-2">
                                            <a href="{{ route('berkas.pencairan', $disb) }}" target="_blank" rel="noopener"
                                               class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/15 px-3.5 py-1.5 text-xs font-bold text-emerald-300 hover:bg-emerald-500/25 transition-colors">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                                Lihat bukti transfer
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Form Kirim LPJ --}}
                    @if ($milestone->status === \App\Models\Milestone::STATUS_DISBURSED)
                        <form action="{{ route('pengaju.kampanye.expense.submit', [$campaign, $milestone]) }}" method="POST" enctype="multipart/form-data" class="mt-4 border-t border-white/10 pt-4" x-data="{ open: false }">
                            @csrf
                            <button type="button" @click="open = !open" class="dt-btn-secondary text-xs" x-show="!open">Kirim laporan nota (LPJ)</button>
                            <div x-show="open" x-cloak class="space-y-4">
                                <h4 class="font-black text-white text-sm">Form laporan pengeluaran</h4>
                                
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="dt-label text-xs">Pilih item RAB (opsional)</label>
                                        <select name="campaign_item_id" class="dt-input text-xs">
                                            <option value="">-- Tidak terikat spesifik --</option>
                                            @foreach ($campaign->items as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }} (Rp{{ number_format($item->subtotal, 0, ',', '.') }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="dt-label text-xs">Tanggal belanja</label>
                                        <input type="date" name="spent_on" required class="dt-input text-xs" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="dt-label text-xs">Judul pengeluaran</label>
                                        <input type="text" name="title" required class="dt-input text-xs" placeholder="Misal: Beli Semen 10 Sak">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="dt-label text-xs">Keterangan tambahan</label>
                                        <textarea name="description" rows="2" required class="dt-input text-xs" placeholder="Penjelasan pemakaian dana..."></textarea>
                                    </div>
                                    <div>
                                        <label class="dt-label text-xs">Nominal (Rp)</label>
                                        <input type="text" inputmode="numeric" data-rupiah name="amount" required
                                               class="dt-input text-xs tabular-nums font-bold" placeholder="500.000">
                                    </div>
                                    <div>
                                        <label class="dt-label text-xs">Foto nota / kuitansi</label>
                                        <input type="file" name="receipt" required accept="image/*" class="dt-input text-xs">
                                        <p class="mt-1 text-xs text-slate-500">Format JPG/PNG, maks 4MB.</p>
                                    </div>
                                </div>

                                <div class="flex gap-2">
                                    <button type="submit" class="dt-btn-primary text-xs">Kirim LPJ</button>
                                    <button type="button" @click="open = false" class="dt-btn-secondary text-xs">Batal</button>
                                </div>
                            </div>
                        </form>
                    @endif

                    {{-- Info LPJ --}}
                    @if ($milestone->expenseReports->isNotEmpty())
                        <div class="mt-4 rounded-2xl border border-white/10 bg-[#231f36]/60 p-4 sm:p-5">
                            <h4 class="text-sm font-black text-white mb-3">Laporan pengeluaran</h4>
                            <div class="space-y-3">
                                @foreach ($milestone->expenseReports as $expense)
                                    <div class="flex justify-between items-start gap-4 text-xs bg-[#1b182a] p-3.5 rounded-xl border border-white/10">
                                        <div>
                                            <p class="font-bold text-white">{{ $expense->title }}</p>
                                            <p class="text-slate-400 mt-0.5">{{ $expense->description }}</p>
                                            <p class="mt-1.5 text-xs text-slate-500">
                                                <span class="font-bold text-[#99ff04]">Rp{{ number_format($expense->amount, 0, ',', '.') }}</span> &middot; {{ \Carbon\Carbon::parse($expense->spent_on)->translatedFormat('d M Y') }}
                                            </p>
                                        </div>
                                        <x-badge :tone="match($expense->status) {
                                            'verified' => 'success',
                                            'rejected' => 'danger',
                                            default => 'warning'
                                        }">{{ $expense->statusLabel() }}</x-badge>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endsection
