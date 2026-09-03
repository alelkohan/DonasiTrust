@extends('layouts.app')
@section('title', $campaign->title)
@section('description', $campaign->summary)

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <nav class="text-sm text-ink-500" aria-label="Breadcrumb">
            <a href="{{ route('kampanye.index') }}" class="hover:text-brand-700">Kampanye</a>
            <span class="mx-2" aria-hidden="true">/</span>
            <span class="text-ink-700">{{ Str::limit($campaign->title, 50) }}</span>
        </nav>
        @if (auth()->check() && (auth()->id() === $campaign->user_id || auth()->user()->isPengaju()))
            <a href="{{ route('pengaju.kampanye.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-brand-900 transition-colors">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                Kembali ke Kampanye Saya
            </a>
        @endif
    </div>

    <div class="grid gap-8 lg:grid-cols-[1.65fr_1fr] lg:items-start">

        {{-- Kolom utama --}}
        <div class="min-w-0">
            <div class="dt-card overflow-hidden">
                <div class="aspect-[16/9] bg-ink-100">
                    @if ($campaign->cover_path)
                        <img src="{{ asset('storage/'.$campaign->cover_path) }}" alt="" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full flex-col items-center justify-center gap-2.5 bg-ink-100" aria-label="Belum ada foto">
                            <svg class="h-12 w-12 text-ink-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="16" rx="2"/>
                                <circle cx="8.5" cy="9.5" r="1.5"/>
                                <path d="m4 17 4.5-4.5 3 3L15 11l5 5"/>
                            </svg>
                            <span class="text-sm font-medium text-ink-400">Belum ada foto</span>
                        </div>
                    @endif
                </div>

                <div class="p-6 sm:p-7">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-badge tone="info">{{ $campaign->categoryLabel() }}</x-badge>
                        @if ($campaign->status === \App\Models\Campaign::STATUS_COMPLETED)
                            <x-badge tone="success">Selesai</x-badge>
                        @endif
                    </div>

                    <h1 class="mt-3 text-2xl leading-tight font-extrabold tracking-tight text-ink-900 sm:text-3xl">
                        {{ $campaign->title }}
                    </h1>

                    <p class="mt-3 text-base leading-relaxed text-ink-600">{{ $campaign->summary }}</p>

                    <div class="mt-5 flex items-center gap-3 border-t border-ink-100 pt-5">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-100 text-sm font-bold text-brand-800">
                            {{ Str::upper(Str::substr($campaign->user->name, 0, 2)) }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-ink-900">
                                {{ $campaign->user->organization ?: $campaign->user->name }}
                            </p>
                            <p class="flex items-center gap-1.5 text-xs text-brand-700">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 6.2v5.1c0 4.6 3.2 8.4 8 9.7 4.8-1.3 8-5.1 8-9.7V6.2Z"/><path d="m9 12 2.2 2.2L15.4 10"/></svg>
                                Identitas terverifikasi admin
                            </p>

                            {{-- Dua langkah bersifat opsional bagi pengaju — banyak di antara
                                 mereka pengurus masjid atau keluarga pasien yang tidak terbiasa
                                 dengan aplikasi authenticator. Maka alih-alih dipaksakan, yang
                                 mengaktifkannya ditampilkan di sini: donatur bisa melihat sendiri
                                 siapa yang mengambil langkah pengamanan ekstra. --}}
                            @if ($campaign->user->twoFactorIsConfirmed())
                                <p class="mt-1 flex items-center gap-1.5 text-xs font-medium text-ink-500"
                                   title="Rekening pencairan pengaju ini hanya bisa diubah dengan kode dari aplikasi authenticator miliknya.">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4.5" y="10.5" width="15" height="10" rx="2"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg>
                                    Rekening pencairan dikunci verifikasi dua langkah
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab: cerita / RAB / tahapan --}}
            <div class="dt-card mt-6 overflow-hidden" x-data="{ tab: 'cerita' }">
                <div class="flex gap-1 overflow-x-auto border-b border-ink-200 px-2 pt-2" role="tablist">
                    @foreach ([
                        'cerita' => 'Cerita lengkap',
                        'rab' => 'Rincian anggaran',
                        'tahapan' => 'Tahapan pencairan',
                    ] as $key => $label)
                        <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="(tab === '{{ $key }}').toString()"
                                class="shrink-0 rounded-t-lg border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors"
                                :class="tab === '{{ $key }}' ? 'border-brand-600 text-brand-700' : 'border-transparent text-ink-500 hover:text-ink-800'">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <div class="p-6 sm:p-7">
                    <div x-show="tab === 'cerita'" role="tabpanel">
                        <div class="space-y-4 text-[15px] leading-relaxed whitespace-pre-line text-ink-700">{{ $campaign->description }}</div>
                    </div>

                    <div x-show="tab === 'rab'" x-cloak role="tabpanel">
                        <p class="text-sm text-ink-600">
                            Total RAB wajib sama persis dengan target dana. Sistem menolak kampanye yang tidak seimbang.
                        </p>
                        <div class="mt-4 -mx-2 overflow-x-auto">
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
                                            <td class="font-medium text-ink-900">{{ $item->name }}</td>
                                            <td class="text-right tabular-nums">{{ $item->quantity }} {{ $item->unit }}</td>
                                            <td class="text-right tabular-nums">{{ rupiah($item->unit_price) }}</td>
                                            <td class="text-right font-semibold tabular-nums">{{ rupiah($item->subtotal) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-right text-sm font-semibold text-ink-700">Total</td>
                                        <td class="px-4 py-3 text-right font-extrabold text-ink-900 tabular-nums">{{ rupiah($campaign->target_amount) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div x-show="tab === 'tahapan'" x-cloak role="tabpanel">
                        <p class="text-sm text-ink-600">
                            Dana tidak cair sekaligus. Tahap berikutnya baru terbuka setelah nota tahap sebelumnya masuk dan diverifikasi.
                        </p>
                        <ol class="mt-5 space-y-4">
                            @foreach ($campaign->milestones as $milestone)
                                <li class="flex gap-4">
                                    <span @class([
                                        'grid h-8 w-8 shrink-0 place-items-center rounded-full text-xs font-bold',
                                        'bg-brand-600 text-white' => in_array($milestone->status, ['disbursed', 'reported']),
                                        'bg-amber-500 text-white' => in_array($milestone->status, ['available', 'requested', 'approved']),
                                        'bg-ink-200 text-ink-600' => $milestone->status === 'locked',
                                    ])>{{ $milestone->sequence }}</span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                                            <p class="text-sm font-semibold text-ink-900">{{ $milestone->title }}</p>
                                            <p class="text-sm font-bold text-ink-900 tabular-nums">{{ rupiah($milestone->amount) }}</p>
                                        </div>
                                        @if ($milestone->description)
                                            <p class="mt-1 text-sm leading-relaxed text-ink-600">{{ $milestone->description }}</p>
                                        @endif
                                        <p class="mt-1.5 text-xs font-medium text-ink-500">{{ $milestone->statusLabel() }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </div>

            {{-- Donatur terbaru --}}
            <div class="dt-card mt-6 p-6 sm:p-7">
                <h2 class="text-lg font-bold text-ink-900">Donatur terbaru</h2>
                @if ($recentDonations->isEmpty())
                    <p class="mt-3 text-sm text-ink-500">Belum ada donasi masuk. Jadilah yang pertama.</p>
                @else
                    <ul class="mt-4 divide-y divide-ink-100">
                        @foreach ($recentDonations as $donation)
                            <li class="flex items-start gap-3 py-3">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-ink-100 text-xs font-bold text-ink-600">
                                    {{ Str::upper(Str::substr($donation->displayName(), 0, 2)) }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                                        <p class="text-sm font-semibold text-ink-900">{{ $donation->displayName() }}</p>
                                        <p class="text-sm font-bold text-brand-700 tabular-nums">{{ rupiah($donation->amount) }}</p>
                                    </div>
                                    @if ($donation->message)
                                        <p class="mt-0.5 text-sm text-ink-600">&ldquo;{{ $donation->message }}&rdquo;</p>
                                    @endif
                                    <p class="mt-0.5 text-xs text-ink-400">{{ $donation->paid_at?->diffForHumans() }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <aside class="space-y-6 lg:sticky lg:top-24">
            <div class="dt-card p-5 sm:p-6">
                <p class="text-3xl font-extrabold tracking-tight text-ink-900 tabular-nums">{{ rupiah($campaign->collected_amount) }}</p>
                <p class="mt-1 text-sm text-ink-500">terkumpul dari target {{ rupiah($campaign->target_amount) }}</p>

                <x-progress class="mt-4" :value="$campaign->progressPercent()" />

                <dl class="mt-5 grid grid-cols-3 gap-3 border-t border-ink-100 pt-4 text-center">
                    <div>
                        <dt class="text-xs text-ink-500">Tercapai</dt>
                        <dd class="mt-0.5 text-sm font-bold text-ink-900 tabular-nums">{{ $campaign->progressPercent() }}%</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ink-500">Donatur</dt>
                        <dd class="mt-0.5 text-sm font-bold text-ink-900 tabular-nums">{{ $campaign->donations()->where('status', 'paid')->count() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ink-500">Sisa waktu</dt>
                        <dd class="mt-0.5 text-sm font-bold text-ink-900 tabular-nums">
                            {{ $campaign->daysLeft() !== null ? $campaign->daysLeft().' hari' : '—' }}
                        </dd>
                    </div>
                </dl>

                <a href="{{ route('kampanye.transparansi', $campaign) }}"
                   class="dt-btn-secondary mt-5 w-full">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V5m0 14h16M8 15V9m4 6V7m4 8v-4"/></svg>
                    Lihat ledger kampanye ini
                </a>
            </div>

            @livewire('donation-form', ['campaign' => $campaign])
        </aside>
    </div>
</div>
@endsection
