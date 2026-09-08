@extends('layouts.dashboard')
@section('title', 'Review kampanye')

@php($menu = \App\Support\AdminMenu::items('kampanye'))

@section('panel')
    <h1 class="text-2xl font-black tracking-tight text-white">Review kampanye</h1>

    <nav class="mt-5 flex flex-wrap gap-2" aria-label="Filter status">
        @foreach ([
            'pending' => 'Menunggu review',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'draft' => 'Draf',
        ] as $key => $label)
            @php($active = (request('status') ?: 'pending') === $key)
            <a href="{{ route('admin.kampanye.index', ['status' => $key]) }}" @class([
                'rounded-xl px-3.5 py-2 text-xs transition-all',
                'bg-[#99ff04] text-black font-black shadow-lg shadow-[#99ff04]/20' => $active,
                'border border-white/10 bg-[#1b182a] text-slate-300 hover:bg-[#231f36] hover:text-white font-bold' => ! $active,
            ])>{{ $label }}</a>
        @endforeach
    </nav>

    @if ($campaigns->isEmpty())
        <x-empty-state class="mt-6" title="Tidak ada kampanye di status ini" />
    @else
        <div class="dt-card mt-6 overflow-x-auto">
            <table class="dt-table min-w-[820px]">
                <thead>
                    <tr>
                        <th scope="col">Kampanye</th>
                        <th scope="col">Pengaju</th>
                        <th scope="col" class="text-right">Target</th>
                        <th scope="col">Status</th>
                        <th scope="col">Diajukan</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($campaigns as $campaign)
                        <tr>
                            <td>
                                <p class="font-bold text-white">{{ Str::limit($campaign->title, 55) }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">{{ $campaign->categoryLabel() }}</p>
                            </td>
                            <td>
                                <p class="font-semibold text-slate-200">{{ $campaign->user->name }}</p>
                                @if ($campaign->user->organization)
                                    <p class="mt-0.5 text-xs text-slate-400">{{ $campaign->user->organization }}</p>
                                @endif
                            </td>
                            <td class="text-right font-black text-white whitespace-nowrap tabular-nums">{{ rupiah($campaign->target_amount) }}</td>
                            <td>
                                <x-badge :tone="match($campaign->status) {
                                    'approved', 'completed' => 'success',
                                    'pending' => 'warning',
                                    'rejected' => 'danger',
                                    default => 'neutral',
                                  }">{{ $campaign->statusLabel() }}</x-badge>
                            </td>
                            <td class="whitespace-nowrap text-xs font-medium text-slate-400">
                                {{ $campaign->submitted_at?->translatedFormat('d M Y') ?? '—' }}
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.kampanye.show', $campaign) }}" class="dt-link text-xs whitespace-nowrap">Tinjau &rarr;</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $campaigns->links() }}</div>
    @endif
@endsection
