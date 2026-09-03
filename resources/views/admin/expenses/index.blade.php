@extends('layouts.dashboard')
@section('title', 'Verifikasi LPJ')

@php($menu = \App\Support\AdminMenu::items('lpj'))

@section('panel')
    <h1 class="text-2xl font-extrabold tracking-tight text-ink-900">Verifikasi laporan pertanggungjawaban</h1>
    <p class="mt-1.5 text-sm text-ink-600">
        Periksa keabsahan bukti struk / nota pengeluaran dana sebelum tahapan berikutnya dibuka.
    </p>

    <nav class="mt-5 flex flex-wrap gap-2" aria-label="Filter status">
        @foreach ([
            '' => 'Semua',
            'pending' => 'Menunggu review',
            'verified' => 'Diverifikasi sah',
            'rejected' => 'Ditolak',
        ] as $key => $label)
            @php($active = request('status', '') === (string)$key)
            <a href="{{ route('admin.lpj.index', $key ? ['status' => $key] : []) }}" @class([
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
        <input type="text" placeholder="Cari nota, kampanye, atau pelapor..." class="dt-input max-w-[280px] text-sm py-1.5"
               x-model.debounce.500ms="search">
               
        <input type="date" class="dt-input max-w-[160px] text-sm py-1.5"
               x-model="date">
               
        <template x-if="search || date">
            <button type="button" @click="search = ''; date = ''; applyFilter()" class="text-sm font-medium text-ink-500 hover:text-ink-700">Reset</button>
        </template>
        <span x-show="loading" x-cloak class="text-xs text-brand-600 font-medium">Mencari...</span>
    </div>

    <div id="table-container">
        @if ($expenses->isEmpty())
            <x-empty-state class="mt-6" title="Tidak ada laporan nota pada filter ini" />
        @else
            <div class="overflow-x-auto mt-6 rounded-xl border border-ink-200 bg-white shadow-sm">
                <table class="dt-table min-w-[900px]">
                    <thead>
                        <tr>
                            <th scope="col">Nota &amp; Kampanye</th>
                            <th scope="col">Pelapor &amp; Rincian</th>
                            <th scope="col" class="text-right">Nominal</th>
                            <th scope="col" class="text-center">Status</th>
                            <th scope="col" class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($expenses as $expense)
                            <tr>
                                <td class="align-top">
                                    <p class="font-bold text-ink-900 leading-tight mb-1">{{ Str::limit($expense->campaign->title, 50) }}</p>
                                    <p class="text-sm font-medium text-ink-700">{{ $expense->title }}</p>
                                    @if ($expense->milestone)
                                        <div class="mt-1.5 inline-flex items-center gap-1.5 rounded bg-brand-50 px-2 py-0.5 text-[11px] font-bold text-brand-700 border border-brand-200">
                                            Tahap {{ $expense->milestone->sequence }}
                                        </div>
                                    @endif
                                </td>
                                <td class="align-top">
                                    <p class="font-medium text-ink-900">{{ $expense->author->name }}</p>
                                    <p class="mt-1 text-xs text-ink-600 max-w-xs leading-relaxed">{{ $expense->description }}</p>
                                </td>
                                <td class="text-right font-bold text-ink-900 whitespace-nowrap tabular-nums align-top">
                                    {{ rupiah($expense->amount) }}
                            </td>
                            <td class="text-center whitespace-nowrap align-top">
                                <x-badge :tone="match($expense->status) {
                                    'verified' => 'success',
                                    'rejected' => 'danger',
                                    default => 'warning'
                                }">{{ $expense->statusLabel() }}</x-badge>
                            </td>
                            <td class="text-right whitespace-nowrap align-top">
                                <div class="flex flex-col items-end gap-2" x-data="{ openReject: false }">
                                    <div class="flex items-center gap-2">
                                        @if ($expense->receipt_path)
                                            <a href="{{ route('berkas.lpj', $expense) }}" target="_blank" class="dt-link text-xs font-semibold inline-flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                Lihat Nota
                                            </a>
                                        @endif

                                        @if ($expense->status === \App\Models\ExpenseReport::STATUS_PENDING)
                                            <form method="POST" action="{{ route('admin.lpj.verify', $expense) }}">
                                                @csrf
                                                <button type="submit" class="dt-btn-primary py-1 px-3 text-xs">Tandai nota sah</button>
                                            </form>
                                            <button type="button" @click="openReject = !openReject" class="dt-btn-secondary py-1 px-3 text-xs text-rose-600">Tolak</button>
                                        @endif
                                    </div>

                                    @if ($expense->status === \App\Models\ExpenseReport::STATUS_PENDING)
                                        <div x-show="openReject" x-cloak class="mt-2 w-72 text-left p-3 rounded-xl border border-rose-200 bg-rose-50/50">
                                            <form method="POST" action="{{ route('admin.lpj.reject', $expense) }}" class="space-y-2">
                                                @csrf
                                                <label class="dt-label text-xs">Alasan Penolakan</label>
                                                <input type="text" name="reason" required placeholder="Jelaskan alasan penolakan..." class="dt-input text-xs">
                                                <div class="flex justify-end gap-1.5 pt-1">
                                                    <button type="button" @click="openReject = false" class="dt-btn-secondary py-0.5 px-2 text-xs">Batal</button>
                                                    <button type="submit" class="dt-btn-danger py-0.5 px-2 text-xs">Kirim</button>
                                                </div>
                                            </form>
                                        </div>
                                    @elseif (!$expense->receipt_path)
                                        <span class="text-xs text-ink-400 italic">Tanpa lampiran</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $expenses->links() }}</div>
    @endif
@endsection
