@extends('layouts.dashboard')
@section('title', 'Verifikasi LPJ')

@php($menu = \App\Support\AdminMenu::items('lpj'))

@section('panel')
    <h1 class="text-2xl font-black tracking-tight text-white">Verifikasi laporan pertanggungjawaban</h1>
    <p class="mt-1.5 text-sm font-medium text-slate-400">
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
                'rounded-xl px-3.5 py-2 text-xs transition-all',
                'bg-[#99ff04] text-black font-black shadow-lg shadow-[#99ff04]/20' => $active,
                'border border-white/10 bg-[#1b182a] text-slate-300 hover:bg-[#231f36] hover:text-white font-bold' => ! $active,
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
        <input type="text" placeholder="Cari nota, kampanye, atau pelapor..." class="dt-input max-w-[280px] text-xs py-2"
               x-model.debounce.500ms="search">
               
        <input type="date" class="dt-input max-w-[160px] text-xs py-2"
               x-model="date">
               
        <template x-if="search || date">
            <button type="button" @click="search = ''; date = ''; applyFilter()" class="text-xs font-bold text-slate-400 hover:text-white transition-colors">Reset</button>
        </template>
        <span x-show="loading" x-cloak class="text-xs text-[#99ff04] font-bold">Mencari...</span>
    </div>

    <div id="table-container">
        @if ($expenses->isEmpty())
            <x-empty-state class="mt-6" title="Tidak ada laporan nota pada filter ini" />
        @else
            <div class="dt-card mt-6 overflow-x-auto">
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
                    <tbody>
                        @foreach ($expenses as $expense)
                            <tr>
                                <td class="align-top">
                                    <p class="font-bold text-white leading-tight mb-1">{{ Str::limit($expense->campaign->title, 50) }}</p>
                                    <p class="text-xs font-medium text-slate-300">{{ $expense->title }}</p>
                                    @if ($expense->milestone)
                                        <div class="mt-1.5 inline-flex items-center gap-1.5 rounded-lg bg-[#231f36] px-2 py-0.5 text-[11px] font-bold text-slate-300 border border-white/10">
                                            Tahap {{ $expense->milestone->sequence }}
                                        </div>
                                    @endif
                                </td>
                                <td class="align-top">
                                    <p class="font-semibold text-slate-200">{{ $expense->author->name }}</p>
                                    <p class="mt-1 text-xs text-slate-400 max-w-xs leading-relaxed">{{ $expense->description }}</p>
                                </td>
                                <td class="text-right font-black text-white whitespace-nowrap tabular-nums align-top">
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
                                                <button type="button" @click="openReject = !openReject" class="dt-btn-secondary py-1 px-3 text-xs text-rose-400 hover:text-rose-300">Tolak</button>
                                            @endif
                                        </div>

                                        @if ($expense->status === \App\Models\ExpenseReport::STATUS_PENDING)
                                            <div x-show="openReject" x-cloak class="mt-2 w-72 text-left p-4 rounded-2xl border border-rose-500/30 bg-[#231f36] shadow-xl">
                                                <form method="POST" action="{{ route('admin.lpj.reject', $expense) }}" class="space-y-3">
                                                    @csrf
                                                    <label class="dt-label text-xs">Alasan Penolakan</label>
                                                    <input type="text" name="reason" required placeholder="Jelaskan alasan penolakan..." class="dt-input text-xs">
                                                    <div class="flex justify-end gap-2 pt-1">
                                                        <button type="button" @click="openReject = false" class="dt-btn-secondary py-1 px-3 text-xs">Batal</button>
                                                        <button type="submit" class="dt-btn-danger py-1 px-3 text-xs">Kirim</button>
                                                    </div>
                                                </form>
                                            </div>
                                        @elseif (!$expense->receipt_path)
                                            <span class="text-xs text-slate-500 italic">Tanpa lampiran</span>
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
    </div>
@endsection
