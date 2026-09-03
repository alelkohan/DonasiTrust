@extends('layouts.dashboard')
@section('title', 'Tinjau: '.$campaign->title)

@php($menu = \App\Support\AdminMenu::items('kampanye'))

@section('panel')
    <nav class="mb-5 text-sm text-ink-500">
        <a href="{{ route('admin.kampanye.index') }}" class="hover:text-brand-700">&larr; Kembali ke antrean</a>
    </nav>

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-extrabold tracking-tight text-ink-900">{{ $campaign->title }}</h1>
            <p class="mt-1.5 text-sm text-ink-600">
                {{ $campaign->categoryLabel() }} &middot; target {{ rupiah($campaign->target_amount) }}
                @if ($campaign->deadline) &middot; batas {{ $campaign->deadline->translatedFormat('d F Y') }} @endif
            </p>
        </div>
        <x-badge :tone="match($campaign->status) {
            'approved', 'completed' => 'success',
            'pending' => 'warning',
            'rejected' => 'danger',
            default => 'neutral',
        }">{{ $campaign->statusLabel() }}</x-badge>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1.5fr_1fr] lg:items-start">
        <div class="space-y-6">
            <section class="dt-card p-5 sm:p-6">
                <h2 class="text-lg font-bold text-ink-900">Ringkasan &amp; cerita</h2>
                <p class="mt-3 text-sm font-medium text-ink-800">{{ $campaign->summary }}</p>
                <div class="mt-4 max-h-96 overflow-y-auto border-t border-ink-100 pt-4 text-sm leading-relaxed whitespace-pre-line text-ink-700">{{ $campaign->description }}</div>
            </section>

            <section class="dt-card overflow-hidden">
                <div class="p-5 sm:p-6">
                    <h2 class="text-lg font-bold text-ink-900">Rincian anggaran (RAB)</h2>
                    <p class="mt-1 text-sm text-ink-600">Periksa kewajaran harga sebelum menyetujui.</p>
                </div>
                <div class="overflow-x-auto border-t border-ink-100">
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
                                <td colspan="3" class="px-4 py-3 text-right text-sm font-semibold text-ink-700">Total RAB</td>
                                <td class="px-4 py-3 text-right font-extrabold text-ink-900 tabular-nums">{{ rupiah($campaign->items->sum('subtotal')) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>

            <section class="dt-card p-5 sm:p-6">
                <h2 class="text-lg font-bold text-ink-900">Tahapan pencairan</h2>
                <ol class="mt-4 space-y-3">
                    @foreach ($campaign->milestones as $milestone)
                        <li class="flex gap-3.5 rounded-xl border border-ink-200 p-4">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-ink-100 text-xs font-bold text-ink-600">
                                {{ $milestone->sequence }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <p class="font-semibold text-ink-900">{{ $milestone->title }}</p>
                                    <p class="font-bold text-ink-900 tabular-nums">{{ rupiah($milestone->amount) }}</p>
                                </div>
                                @if ($milestone->description)
                                    <p class="mt-1 text-sm text-ink-600">{{ $milestone->description }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>

        <aside class="space-y-6 lg:sticky lg:top-24">
            <section class="dt-card p-5 sm:p-6">
                <h2 class="text-lg font-bold text-ink-900">Pengaju</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-ink-500">Nama</dt>
                        <dd class="mt-0.5 font-semibold text-ink-900">{{ $campaign->user->name }}</dd>
                    </div>
                    @if ($campaign->user->organization)
                        <div>
                            <dt class="text-xs text-ink-500">Lembaga</dt>
                            <dd class="mt-0.5 font-semibold text-ink-900">{{ $campaign->user->organization }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-xs text-ink-500">Email</dt>
                        <dd class="mt-0.5 font-semibold break-all text-ink-900">{{ $campaign->user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ink-500">Status identitas</dt>
                        <dd class="mt-1">
                            <x-badge :tone="$campaign->user->isVerified() ? 'success' : 'warning'">
                                {{ $campaign->user->verificationLabel() }}
                            </x-badge>
                        </dd>
                    </div>
                </dl>
                <a href="{{ route('admin.pengguna.show', $campaign->user) }}" class="dt-btn-secondary mt-4 w-full">
                    Lihat berkas identitas
                </a>
            </section>

            @if ($campaign->status === 'pending')
                <section class="dt-card p-5 sm:p-6">
                    <h2 class="text-lg font-bold text-ink-900">Keputusan</h2>
                    <p class="mt-1 text-sm text-ink-600">Keputusan Anda tercatat permanen di jejak audit.</p>

                    <form method="POST" action="{{ route('admin.kampanye.approve', $campaign) }}" class="mt-5 space-y-3">
                        @csrf
                        <div>
                            <label for="note-approve" class="dt-label">Catatan <span class="font-normal text-ink-400">(opsional)</span></label>
                            <textarea id="note-approve" name="note" rows="2" class="dt-input" maxlength="1000"
                                      placeholder="Catatan untuk pengaju"></textarea>
                        </div>
                        <button type="submit" class="dt-btn-primary w-full py-3">Setujui &amp; tayangkan</button>
                    </form>

                    <div class="my-5 border-t border-ink-100"></div>

                    <form method="POST" action="{{ route('admin.kampanye.reject', $campaign) }}" class="space-y-3"
                          x-data @submit="if (! confirm('Tolak kampanye ini?')) $event.preventDefault()">
                        @csrf
                        <div>
                            <label for="note-reject" class="dt-label">Alasan penolakan <span class="text-rose-600">*</span></label>
                            <textarea id="note-reject" name="note" rows="3" required class="dt-input" maxlength="1000"
                                      placeholder="Jelaskan apa yang perlu diperbaiki agar pengaju bisa mengajukan ulang."></textarea>
                        </div>
                        <button type="submit" class="dt-btn-danger w-full">Tolak kampanye</button>
                    </form>
                </section>
            @elseif ($campaign->review_note)
                <section class="dt-card p-5 sm:p-6">
                    <h2 class="text-lg font-bold text-ink-900">Catatan review</h2>
                    <p class="mt-2 text-sm text-ink-700">{{ $campaign->review_note }}</p>
                    <p class="mt-3 text-xs text-ink-500">
                        Oleh {{ $campaign->reviewer?->name ?? 'admin' }} &middot;
                        {{ $campaign->reviewed_at?->translatedFormat('d F Y, H:i') }}
                    </p>
                </section>
            @endif
        </aside>
    </div>
@endsection
