@extends('layouts.dashboard')
@section('title', 'Kampanye saya')


@section('panel')
    <header class="flex flex-wrap items-end justify-between gap-4">
        <h1 class="text-2xl font-extrabold tracking-tight text-ink-900">Kampanye saya</h1>
        @if (auth()->user()->canSubmitCampaign())
            <a href="{{ route('pengaju.kampanye.create') }}" class="dt-btn-primary">Buat kampanye baru</a>
        @endif
    </header>

    @if ($campaigns->isEmpty())
        <x-empty-state class="mt-6" title="Belum ada kampanye"
                       description="Kampanye yang Anda buat akan muncul di sini, lengkap dengan statusnya." />
    @else
        <div class="mt-6 space-y-4">
            @foreach ($campaigns as $campaign)
                <article class="dt-card p-5 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-bold text-ink-900">{{ $campaign->title }}</h2>
                                <x-badge :tone="match($campaign->status) {
                                    'approved', 'completed' => 'success',
                                    'pending' => 'warning',
                                    'rejected' => 'danger',
                                    default => 'neutral',
                                }">{{ $campaign->statusLabel() }}</x-badge>
                            </div>
                            <p class="mt-1.5 text-sm text-ink-600">{{ Str::limit($campaign->summary, 140) }}</p>
                        </div>

                        <div class="flex shrink-0 flex-wrap gap-2">
                            @if ($campaign->isPublished())
                                <a href="{{ route('kampanye.show', $campaign) }}" class="dt-btn-secondary">Lihat publik</a>
                                <a href="{{ route('kampanye.transparansi', $campaign) }}" class="dt-btn-secondary">Ledger</a>
                                <a href="{{ route('pengaju.kampanye.milestones', $campaign) }}" class="dt-btn-primary">Kelola Tahapan</a>
                            @endif
                            @if ($campaign->isEditable())
                                <a href="{{ route('pengaju.kampanye.edit', $campaign) }}" class="dt-btn-primary">Ubah</a>
                            @endif
                        </div>
                    </div>

                    @if ($campaign->status === 'rejected' && $campaign->review_note)
                        <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
                            <p class="font-semibold">Catatan admin</p>
                            <p class="mt-1">{{ $campaign->review_note }}</p>
                        </div>
                    @endif

                    <div class="mt-4 border-t border-ink-100 pt-4">
                        <x-progress :value="$campaign->progressPercent()" />
                        <dl class="mt-3 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                            <div>
                                <dt class="text-xs text-ink-500">Terkumpul</dt>
                                <dd class="mt-0.5 font-bold text-ink-900 tabular-nums">{{ rupiah($campaign->collected_amount) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-ink-500">Target</dt>
                                <dd class="mt-0.5 font-bold text-ink-900 tabular-nums">{{ rupiah($campaign->target_amount) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-ink-500">Dicairkan</dt>
                                <dd class="mt-0.5 font-bold text-ink-900 tabular-nums">{{ rupiah($campaign->disbursed_amount) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-ink-500">Donatur</dt>
                                <dd class="mt-0.5 font-bold text-ink-900 tabular-nums">{{ $campaign->donations_count }}</dd>
                            </div>
                        </dl>
                    </div>

                    @if ($campaign->isEditable() || in_array($campaign->status, ['pending']))
                        <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-ink-100 pt-4">
                            @if ($campaign->isEditable())
                                <form method="POST" action="{{ route('pengaju.kampanye.submit', $campaign) }}">
                                    @csrf
                                    <button type="submit" class="dt-btn-primary">Ajukan untuk review</button>
                                </form>
                            @endif
                            @if (in_array($campaign->status, ['draft', 'pending', 'rejected']))
                                <form method="POST" action="{{ route('pengaju.kampanye.destroy', $campaign) }}"
                                      x-data @submit="if (! confirm('Hapus kampanye ini? Tindakan ini tidak bisa dibatalkan.')) $event.preventDefault()">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="dt-btn text-rose-600 hover:bg-rose-50">Hapus kampanye</button>
                                </form>
                            @endif
                        </div>
                    @endif
                </article>
            @endforeach
        </div>

        <div class="mt-6">{{ $campaigns->links() }}</div>
    @endif
@endsection
