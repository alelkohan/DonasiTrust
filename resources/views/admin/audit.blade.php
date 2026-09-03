@extends('layouts.dashboard')
@section('title', 'Jejak audit')

@php($menu = \App\Support\AdminMenu::items('audit'))

@section('panel')
    <h1 class="text-2xl font-extrabold tracking-tight text-ink-900">Jejak audit</h1>
    <p class="mt-1.5 max-w-2xl text-sm text-ink-600">
        Setiap entri menyimpan hash entri sebelumnya. Mengubah satu catatan lama akan membuat
        seluruh rantai sesudahnya gagal diverifikasi — jadi manipulasi diam-diam bisa terdeteksi.
    </p>

    <section @class([
        'mt-6 rounded-2xl border p-5',
        'border-brand-200 bg-brand-50' => $chainStatus['valid'],
        'border-rose-300 bg-rose-50' => ! $chainStatus['valid'],
    ])>
        <div class="flex items-start gap-3.5">
            <span @class([
                'grid h-10 w-10 shrink-0 place-items-center rounded-xl text-white',
                'bg-brand-600' => $chainStatus['valid'],
                'bg-rose-600' => ! $chainStatus['valid'],
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
                <h2 @class(['font-bold', 'text-brand-900' => $chainStatus['valid'], 'text-rose-900' => ! $chainStatus['valid']])>
                    {{ $chainStatus['valid'] ? 'Rantai utuh' : 'Rantai terputus' }}
                </h2>
                <p class="mt-1 text-sm {{ $chainStatus['valid'] ? 'text-brand-900/80' : 'text-rose-900/80' }}">
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
            <button type="submit" class="dt-btn-secondary">Terapkan</button>
            @if (request('action'))
                <a href="{{ route('admin.audit.index') }}" class="dt-link text-sm">Reset</a>
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
                            <td class="font-mono text-xs text-ink-400">{{ $log->id }}</td>
                            <td>
                                <p class="font-semibold text-ink-900">{{ $log->actionLabel() }}</p>
                                <p class="mt-0.5 font-mono text-xs text-ink-400">{{ $log->action }}</p>
                                @if ($log->metadata)
                                    <ul class="mt-1.5 space-y-0.5 text-xs text-ink-600">
                                        @foreach ($log->metadata as $key => $value)
                                            @continue(is_array($value) || is_null($value))
                                            <li><span class="text-ink-400">{{ $key }}:</span> {{ Str::limit((string) $value, 60) }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                            <td class="whitespace-nowrap">
                                <p class="text-ink-800">{{ $log->actor_label ?: 'Sistem' }}</p>
                                @if ($log->ip_address)
                                    <p class="mt-0.5 font-mono text-xs text-ink-400">{{ $log->ip_address }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-ink-600">
                                {{ $log->created_at?->translatedFormat('d M Y') }}
                                <span class="block text-xs text-ink-400">{{ $log->created_at?->format('H:i:s') }}</span>
                            </td>
                            <td>
                                <p class="font-mono text-xs break-all text-ink-700">{{ substr($log->current_hash, 0, 24) }}…</p>
                                <p class="mt-0.5 font-mono text-xs break-all text-ink-400">
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
