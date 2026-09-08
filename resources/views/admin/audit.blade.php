@extends('layouts.dashboard')
@section('title', 'Jejak audit')

@php($menu = \App\Support\AdminMenu::items('audit'))

@section('panel')
    <h1 class="text-2xl font-black tracking-tight text-white">Jejak audit</h1>
    <p class="mt-1.5 max-w-2xl text-sm font-medium text-slate-400">
        Setiap entri menyimpan hash entri sebelumnya. Mengubah satu catatan lama akan membuat
        seluruh rantai sesudahnya gagal diverifikasi — jadi manipulasi diam-diam bisa terdeteksi.
    </p>

    <section @class([
        'mt-6 rounded-2xl border p-5 transition-all',
        'border-emerald-500/30 bg-emerald-500/10' => $chainStatus['valid'],
        'border-rose-500/30 bg-rose-500/10' => ! $chainStatus['valid'],
    ])>
        <div class="flex items-start gap-3.5">
            <span @class([
                'grid h-10 w-10 shrink-0 place-items-center rounded-xl font-bold',
                'bg-[#99ff04] text-black shadow-lg shadow-[#99ff04]/20' => $chainStatus['valid'],
                'bg-rose-500 text-white shadow-lg shadow-rose-500/20' => ! $chainStatus['valid'],
            ]) aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                    @if ($chainStatus['valid'])
                        <path d="m5 12 4.5 4.5L19 7.5"/>
                    @else
                        <circle cx="12" cy="12" r="9"/><path d="M12 8v5m0 3.5h.01"/>
                    @endif
                </svg>
            </span>
            <div>
                <h2 @class(['font-bold', 'text-white' => $chainStatus['valid'], 'text-rose-300' => ! $chainStatus['valid']])>
                    {{ $chainStatus['valid'] ? 'Rantai utuh' : 'Rantai terputus' }}
                </h2>
                <p class="mt-1 text-sm {{ $chainStatus['valid'] ? 'text-slate-300' : 'text-rose-200' }}">
                    {{ number_format($chainStatus['checked'], 0, ',', '.') }} entri dihitung ulang hash-nya barusan.
                    @unless ($chainStatus['valid'])
                        Masalah pertama di entri #{{ $chainStatus['broken_at'] }} — {{ $chainStatus['reason'] }}
                    @endunless
                </p>
            </div>
        </div>
    </section>

    @if ($actions->isNotEmpty())
        <form method="GET" class="mt-6 flex flex-wrap items-end gap-3">
            <div>
                <label for="action" class="dt-label">Saring aksi</label>
                <select id="action" name="action" class="dt-input sm:w-72">
                    <option value="">Semua aksi</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="dt-btn-secondary text-xs">Terapkan</button>
            @if (request('action'))
                <a href="{{ route('admin.audit.index') }}" class="dt-link text-xs">Reset</a>
            @endif
        </form>
    @endif

    @if ($logs->isEmpty())
        <x-empty-state class="mt-6" title="Belum ada entri audit" />
    @else
        <div class="dt-card mt-6 overflow-x-auto">
            <table class="dt-table min-w-[820px]">
                <thead>
                    <tr>
                        <th scope="col" class="w-16">#</th>
                        <th scope="col">Aksi</th>
                        <th scope="col">Pelaku</th>
                        <th scope="col">Waktu</th>
                        <th scope="col">Hash entri</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td class="font-mono text-xs text-slate-500">{{ $log->id }}</td>
                            <td>
                                <p class="font-bold text-white">{{ $log->actionLabel() }}</p>
                                <p class="mt-0.5 font-mono text-xs text-[#99ff04]">{{ $log->action }}</p>
                                @if ($log->metadata)
                                    <ul class="mt-1.5 space-y-0.5 text-xs text-slate-300">
                                        @foreach ($log->metadata as $key => $value)
                                            @continue(is_array($value) || is_null($value))
                                            <li><span class="text-slate-500">{{ $key }}:</span> {{ Str::limit((string) $value, 60) }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                            <td class="whitespace-nowrap">
                                <p class="font-semibold text-slate-200">{{ $log->actor_label ?: 'Sistem' }}</p>
                                @if ($log->ip_address)
                                    <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $log->ip_address }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-xs text-slate-300">
                                {{ $log->created_at?->translatedFormat('d M Y') }}
                                <span class="block text-[11px] text-slate-500">{{ $log->created_at?->format('H:i:s') }}</span>
                            </td>
                            <td>
                                <p class="font-mono text-xs break-all text-slate-300">{{ substr($log->current_hash, 0, 24) }}…</p>
                                <p class="mt-0.5 font-mono text-xs break-all text-slate-500">
                                    prev: {{ $log->previous_hash ? substr($log->previous_hash, 0, 16).'…' : 'genesis' }}
                                </p>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $logs->links() }}</div>
    @endif
@endsection
