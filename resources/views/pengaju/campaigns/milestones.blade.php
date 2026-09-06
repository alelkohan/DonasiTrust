@extends('layouts.dashboard')
@section('title', 'Kelola Tahapan - ' . $campaign->title)


@section('panel')
    <header class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-ink-900">Kelola tahapan pencairan</h1>
            <p class="mt-1 text-sm text-ink-600">{{ $campaign->title }}</p>
        </div>
        <a href="{{ route('pengaju.kampanye.index') }}" class="dt-btn-secondary inline-flex items-center gap-1.5">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            Kembali
        </a>
    </header>

    <div class="space-y-6">
        @foreach ($campaign->milestones as $index => $milestone)
            <div class="dt-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-ink-100 bg-ink-50/50 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-brand-100 text-sm font-bold text-brand-800">
                            {{ $milestone->sequence }}
                        </span>
                        <div>
                            <h3 class="font-bold text-ink-900">{{ $milestone->title }}</h3>
                            <p class="text-xs text-ink-500 tabular-nums">Rp{{ number_format($milestone->amount, 0, ',', '.') }}</p>
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
                
                <div class="p-5">
                    @if ($milestone->description)
                        <p class="mb-3 text-sm text-ink-700">{{ $milestone->description }}</p>
                    @endif

                    {{-- Yang dibutuhkan pengaju bukan nama status, tapi langkah berikutnya. --}}
                    <div @class([
                        'mb-4 flex items-start gap-2.5 rounded-xl border px-3.5 py-3 text-sm leading-relaxed',
                        'border-brand-200 bg-brand-50/70 text-brand-900' => $milestone->status === \App\Models\Milestone::STATUS_AVAILABLE,
                        'border-amber-200 bg-amber-50/70 text-amber-900' => in_array($milestone->status, ['requested', 'approved', 'disbursed'], true),
                        'border-ink-200 bg-ink-50 text-ink-600' => in_array($milestone->status, ['locked', 'reported'], true),
                    ])>
                        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/><path d="M12 16v-4.5M12 8h.01"/>
                        </svg>
                        <span>{{ $milestone->guidance() }}</span>
                    </div>

                    {{-- Form Ajukan pencairan --}}
                    @if ($milestone->status === \App\Models\Milestone::STATUS_AVAILABLE)
                        <form action="{{ route('pengaju.kampanye.disbursement.request', [$campaign, $milestone]) }}" method="POST" class="mt-4 border-t border-ink-100 pt-4" x-data="{ open: false }">
                            @csrf
                            <button type="button" @click="open = !open" class="dt-btn-primary" x-show="!open">Ajukan pencairan</button>
                            <div x-show="open" x-cloak class="space-y-3">
                                <div>
                                    <label class="dt-label">Tujuan pencairan</label>
                                    <textarea name="purpose" rows="3" required class="dt-input" placeholder="Misal: Pembayaran DP material ke toko bangunan..."></textarea>
                                </div>

                                {{-- Rekening tujuan tidak bisa dipilih di sini: diambil dari
                                     profil yang sudah diverifikasi admin. --}}
                                <div class="rounded-xl border p-3.5 text-sm {{ $campaign->user->hasPayoutAccount() ? 'border-ink-200 bg-ink-50' : 'border-amber-200 bg-amber-50' }}">
                                    @if ($campaign->user->hasPayoutAccount())
                                        <p class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Dana akan dikirim ke</p>
                                        <p class="mt-1 font-semibold text-ink-900">{{ $campaign->user->bank_name }} &middot; {{ $campaign->user->bank_account_number }}</p>
                                        <p class="text-ink-700">a.n. {{ $campaign->user->bank_account_holder }}</p>
                                        <p class="mt-2 text-xs leading-relaxed text-ink-500">
                                            Rekening ini terkunci pada profil terverifikasi Anda dan tercatat
                                            permanen pada pengajuan ini.
                                        </p>
                                    @else
                                        <p class="font-semibold text-amber-900">Rekening tujuan belum terdaftar</p>
                                        <p class="mt-1 text-xs leading-relaxed text-amber-900/80">
                                            Lengkapi dulu lewat
                                            <a href="{{ route('verifikasi.identitas') }}" class="font-semibold underline">halaman verifikasi identitas</a>,
                                            baru pencairan bisa diajukan.
                                        </p>
                                    @endif
                                </div>

                                <div class="rounded-xl border border-ink-200 bg-white p-3.5">
                                    <x-otp-input purpose="disbursement_request" label="Kode Verifikasi Email" />
                                </div>

                                <div class="flex gap-2">
                                    <button type="submit" class="dt-btn-primary">Kirim pengajuan</button>
                                    <button type="button" @click="open = false" class="dt-btn-secondary">Batal</button>
                                </div>
                            </div>
                        </form>
                    @endif

                    {{-- Info Pencairan --}}
                    @if ($milestone->disbursements->isNotEmpty())
                        <div class="mt-4 rounded-xl border border-ink-200 bg-ink-50 p-4">
                            <h4 class="text-sm font-bold text-ink-900">Status pengajuan</h4>
                            @foreach ($milestone->disbursements as $disb)
                                <div class="mt-2 space-y-1.5 text-sm text-ink-700">
                                    <p>Tujuan: {{ $disb->purpose }}</p>
                                    <p>Status: <strong>{{ $disb->statusLabel() }}</strong></p>
                                    @if ($disb->released_at)
                                        <p class="text-xs text-ink-500">
                                            Ditransfer pada {{ $disb->released_at->translatedFormat('d F Y, H:i') }}
                                        </p>
                                    @endif
                                    @if ($disb->supporting_document_path)
                                        <div class="pt-1.5">
                                            <a href="{{ route('berkas.pencairan', $disb) }}" target="_blank" rel="noopener"
                                               class="inline-flex items-center gap-1.5 rounded-lg border border-brand-300 bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-800 hover:bg-brand-100 transition-colors">
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
                        <form action="{{ route('pengaju.kampanye.expense.submit', [$campaign, $milestone]) }}" method="POST" enctype="multipart/form-data" class="mt-4 border-t border-ink-100 pt-4" x-data="{ open: false }">
                            @csrf
                            <button type="button" @click="open = !open" class="dt-btn-secondary" x-show="!open">Kirim laporan nota (LPJ)</button>
                            <div x-show="open" x-cloak class="space-y-4">
                                <h4 class="font-bold text-ink-900">Form laporan pengeluaran</h4>
                                
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="dt-label">Pilih item RAB (opsional)</label>
                                        <select name="campaign_item_id" class="dt-input">
                                            <option value="">-- Tidak terikat spesifik --</option>
                                            @foreach ($campaign->items as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }} (Rp{{ number_format($item->subtotal, 0, ',', '.') }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="dt-label">Tanggal belanja</label>
                                        <input type="date" name="spent_on" required class="dt-input" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="dt-label">Judul pengeluaran</label>
                                        <input type="text" name="title" required class="dt-input" placeholder="Misal: Beli Semen 10 Sak">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="dt-label">Keterangan tambahan</label>
                                        <textarea name="description" rows="2" required class="dt-input" placeholder="Penjelasan pemakaian dana..."></textarea>
                                    </div>
                                    <div>
                                        <label class="dt-label">Nominal (Rp)</label>
                                        <input type="text" inputmode="numeric" data-rupiah name="amount" required
                                               class="dt-input tabular-nums" placeholder="500.000">
                                    </div>
                                    <div>
                                        <label class="dt-label">Foto nota / kuitansi</label>
                                        <input type="file" name="receipt" required accept="image/*" class="w-full text-sm">
                                        <p class="dt-hint">Format JPG/PNG, maks 4MB.</p>
                                    </div>
                                </div>

                                <div class="flex gap-2">
                                    <button type="submit" class="dt-btn-primary">Kirim LPJ</button>
                                    <button type="button" @click="open = false" class="dt-btn-secondary">Batal</button>
                                </div>
                            </div>
                        </form>
                    @endif

                    {{-- Info LPJ --}}
                    @if ($milestone->expenseReports->isNotEmpty())
                        <div class="mt-4 rounded-xl border border-brand-200 bg-brand-50 p-4">
                            <h4 class="text-sm font-bold text-brand-900 mb-2">Laporan pengeluaran</h4>
                            <div class="space-y-3">
                                @foreach ($milestone->expenseReports as $expense)
                                    <div class="flex justify-between items-start gap-4 text-sm bg-white p-3 rounded-lg border border-brand-100">
                                        <div>
                                            <p class="font-bold text-ink-900">{{ $expense->title }}</p>
                                            <p class="text-ink-600">{{ $expense->description }}</p>
                                            <p class="mt-1 text-xs text-ink-500">
                                                Rp{{ number_format($expense->amount, 0, ',', '.') }} &middot; {{ \Carbon\Carbon::parse($expense->spent_on)->translatedFormat('d M Y') }}
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
