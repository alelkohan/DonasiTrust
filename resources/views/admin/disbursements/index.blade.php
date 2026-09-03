@extends('layouts.dashboard')
@section('title', 'Review pencairan dana')

@php($menu = \App\Support\AdminMenu::items('pencairan'))
@php($guard = app(\App\Services\TotpGuard::class))
@php($jendelaTerbuka = auth()->user()->hasTwoFactorEnabled() && $guard->windowOpenFor(auth()->user()))
@php($sisaJendela = $jendelaTerbuka ? $guard->windowMinutesLeft(auth()->user()) : 0)

@section('panel')
    <h1 class="text-2xl font-extrabold tracking-tight text-ink-900">Review pencairan dana</h1>
    <p class="mt-1.5 text-sm text-ink-600">
        Kelola permohonan pencairan dana bertahap dari penggalang dana.
    </p>

    <nav class="mt-5 flex flex-wrap gap-2" aria-label="Filter status">
        @foreach ([
            '' => 'Semua',
            'pending' => 'Menunggu review',
            'approved' => 'Disetujui (Siap transfer)',
            'released' => 'Dana dicairkan',
            'rejected' => 'Ditolak',
        ] as $key => $label)
            @php($active = request('status', '') === (string)$key)
            <a href="{{ route('admin.pencairan.index', $key ? ['status' => $key] : []) }}" @class([
                'rounded-xl px-3.5 py-2 text-sm font-medium transition-colors',
                'bg-brand-600 text-white' => $active,
                'border border-ink-200 bg-white text-ink-600 hover:bg-ink-50' => ! $active,
            ])>{{ $label }}</a>
        @endforeach
    </nav>

    <div x-data="{ 
            search: '{{ request('search') }}', 
            date: '{{ request('date') }}',
            status: '{{ request('status') }}',
            loading: false,
            init() {
                this.$watch('search', () => this.applyFilter());
                this.$watch('date', () => this.applyFilter());
            },
            applyFilter() {
                this.loading = true;
                let url = new URL(window.location.href);
                if (this.search) url.searchParams.set('search', this.search);
                else url.searchParams.delete('search');
                
                if (this.date) url.searchParams.set('date', this.date);
                else url.searchParams.delete('date');
                
                if (this.status) url.searchParams.set('status', this.status);
                else url.searchParams.delete('status');
                
                window.history.pushState({}, '', url);
                
                fetch(url)
                    .then(res => res.text())
                    .then(html => {
                        let doc = new DOMParser().parseFromString(html, 'text/html');
                        document.getElementById('table-container').innerHTML = doc.getElementById('table-container').innerHTML;
                        this.loading = false;
                    });
            }
        }" 
        class="mt-4 flex flex-wrap items-center gap-3">
        <input type="text" placeholder="Cari ref, kampanye, atau pemohon..." class="dt-input max-w-[280px] text-sm py-1.5"
               x-model.debounce.500ms="search">
               
        <input type="date" class="dt-input max-w-[160px] text-sm py-1.5"
               x-model="date">
               
        <template x-if="search || date">
            <button type="button" @click="search = ''; date = '';" class="text-sm font-medium text-ink-500 hover:text-ink-700">Reset</button>
        </template>
        <span x-show="loading" x-cloak class="text-xs text-brand-600 font-medium">Mencari...</span>
    </div>

    <div id="table-container">
        @if ($disbursements->isEmpty())
            <x-empty-state class="mt-6" title="Tidak ada data pencairan dana pada filter ini" />
        @else
            <div class="overflow-x-auto mt-6 rounded-xl border border-ink-200 bg-white shadow-sm">
                <table class="dt-table min-w-[900px]">
                    <thead>
                        <tr>
                            <th scope="col">Ref &amp; Kampanye</th>
                            <th scope="col">Pemohon &amp; Keperluan</th>
                            <th scope="col" class="text-right">Nominal</th>
                            <th scope="col" class="text-center">Status</th>
                            <th scope="col" class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($disbursements as $disb)
                            <tr>
                                <td class="align-top">
                                    <span class="font-mono text-xs font-bold text-brand-700 bg-brand-50 px-2 py-0.5 rounded border border-brand-200">{{ $disb->reference }}</span>
                                    <p class="mt-1.5 font-semibold text-ink-900">{{ Str::limit($disb->campaign->title, 40) }}</p>
                                    <p class="mt-0.5 text-xs text-ink-500">Tahap {{ $disb->milestone->sequence }}: {{ $disb->milestone->title }}</p>
                                </td>
                                <td class="align-top">
                                    <p class="font-medium text-ink-900">{{ $disb->requester->name }}</p>
                                    @if ($disb->payee_bank_name && $disb->payee_account_number)
                                        <p class="mt-0.5 text-[11px] font-semibold text-brand-700 bg-brand-50 inline-block px-1.5 py-0.5 rounded border border-brand-200">
                                            {{ strtoupper($disb->payee_bank_name) }} - {{ $disb->payee_account_number }}
                                        </p>
                                    @endif
                                    <p class="mt-1.5 text-xs text-ink-600 max-w-xs leading-relaxed">{{ $disb->purpose }}</p>
                                </td>
                                <td class="text-right font-bold text-ink-900 whitespace-nowrap tabular-nums align-top">
                                    {{ rupiah($disb->amount) }}
                            </td>
                            <td class="text-center whitespace-nowrap align-top">
                                <x-badge :tone="match($disb->status) {
                                    'released' => 'success',
                                    'approved' => 'brand',
                                    'rejected' => 'danger',
                                    default => 'warning'
                                }">{{ $disb->statusLabel() }}</x-badge>
                            </td>
                            <td class="text-right whitespace-nowrap align-top">
                                <div class="flex flex-col items-end gap-2" x-data="{ openReject: false, openRelease: false }">
                                    @if ($disb->status === \App\Models\Disbursement::STATUS_PENDING)
                                        <div class="flex items-center gap-2">
                                            <form method="POST" action="{{ route('admin.pencairan.approve', $disb) }}">
                                                @csrf
                                                <button type="submit" class="dt-btn-primary py-1 px-3 text-xs">Setujui</button>
                                            </form>
                                            <button type="button" @click="openReject = !openReject" class="dt-btn-secondary py-1 px-3 text-xs text-rose-600">Tolak</button>
                                        </div>

                                        <div x-show="openReject" x-cloak class="mt-2 w-72 text-left p-3 rounded-xl border border-rose-200 bg-rose-50/50">
                                            <form method="POST" action="{{ route('admin.pencairan.reject', $disb) }}" class="space-y-2">
                                                @csrf
                                                <label class="dt-label text-xs">Alasan Penolakan</label>
                                                <input type="text" name="reason" required placeholder="Jelaskan alasan penolakan..." class="dt-input text-xs">
                                                <div class="flex justify-end gap-1.5 pt-1">
                                                    <button type="button" @click="openReject = false" class="dt-btn-secondary py-0.5 px-2 text-xs">Batal</button>
                                                    <button type="submit" class="dt-btn-danger py-0.5 px-2 text-xs">Kirim</button>
                                                </div>
                                            </form>
                                        </div>
                                    @elseif ($disb->status === \App\Models\Disbursement::STATUS_APPROVED)
                                        <button type="button" @click="openRelease = !openRelease" class="dt-btn-primary py-1 px-3 text-xs">
                                            Tandai Dicairkan
                                        </button>

                                        <div x-show="openRelease" x-cloak class="mt-2 w-80 text-left p-4 rounded-xl border border-brand-200 bg-brand-50/60 shadow-sm">
                                            {{-- whitespace-normal: sel kolom AKSI memakai whitespace-nowrap
                                                 supaya tombol-tombolnya tidak terpotong, dan itu diwarisi ke
                                                 sini — tanpa ini kalimat penjelasnya menembus keluar kotak. --}}
                                            <form method="POST" action="{{ route('admin.pencairan.release', $disb) }}" enctype="multipart/form-data" class="space-y-2.5 whitespace-normal">
                                                @csrf
                                                <p class="text-xs font-semibold text-brand-900">Unggah Bukti Struk Transfer Bank</p>
                                                <input type="file" name="proof" required accept="image/*" class="dt-input text-xs bg-white">

                                                {{-- Gerbang dua langkah, sama seperti di halaman detail:
                                                     melepas dana tidak boleh cukup dengan satu klik dari
                                                     sesi yang sudah login. --}}
                                                @if (auth()->user()->hasTwoFactorEnabled())
                                                    @if ($jendelaTerbuka)
                                                        <p class="rounded-lg border border-brand-200 bg-brand-50/70 p-2.5 text-xs leading-relaxed text-brand-900">
                                                            Verifikasi Anda masih berlaku <strong>{{ $sisaJendela }} menit</strong> lagi —
                                                            kode tidak diminta ulang.
                                                        </p>
                                                    @else
                                                        <div>
                                                            <label class="dt-label text-xs">Kode verifikasi dua langkah</label>
                                                            <input type="text" name="totp_code" inputmode="numeric" required
                                                                   autocomplete="one-time-code" maxlength="9" placeholder="000000"
                                                                   class="dt-input bg-white text-center font-mono text-sm tracking-[0.3em]">
                                                            <p class="dt-hint text-xs">Berlaku {{ \App\Services\TotpGuard::SUDO_WINDOW_MINUTES }} menit untuk pencairan berikutnya.</p>
                                                        </div>
                                                    @endif

                                                    <div class="flex justify-end gap-1.5 pt-1">
                                                        <button type="button" @click="openRelease = false" class="dt-btn-secondary py-1 px-2.5 text-xs">Batal</button>
                                                        <button type="submit" class="dt-btn-primary py-1 px-3 text-xs">Simpan &amp; Rilis</button>
                                                    </div>
                                                @else
                                                    <p class="rounded-lg border border-amber-200 bg-amber-50 p-2.5 text-xs leading-relaxed text-amber-900">
                                                        Melepas dana butuh kode authenticator.
                                                        <a href="{{ route('keamanan.index') }}" class="font-semibold underline">Aktifkan dulu di halaman Keamanan</a>.
                                                    </p>

                                                    <div class="flex justify-end pt-1">
                                                        <button type="button" @click="openRelease = false" class="dt-btn-secondary py-1 px-2.5 text-xs">Tutup</button>
                                                    </div>
                                                @endif
                                            </form>
                                        </div>
                                    @elseif ($disb->status === \App\Models\Disbursement::STATUS_RELEASED)
                                        @if ($disb->supporting_document_path)
                                            <a href="{{ route('berkas.pencairan', $disb) }}" target="_blank" class="dt-link text-xs font-semibold inline-flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                Lihat Bukti Transfer
                                            </a>
                                        @else
                                            <span class="text-xs text-ink-400 italic">Tanpa lampiran</span>
                                        @endif
                                    @else
                                        <span class="text-xs text-ink-400">-</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $disbursements->links() }}</div>
    @endif
@endsection
