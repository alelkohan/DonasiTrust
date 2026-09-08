@extends('layouts.dashboard')
@section('title', 'Verifikasi pengguna')

@php($menu = \App\Support\AdminMenu::items('pengguna'))

@section('panel')
    <h1 class="text-2xl font-black tracking-tight text-white">Verifikasi pengguna</h1>
    <p class="mt-1.5 text-sm font-medium text-slate-400">
        Verifikasi dilakukan manual. Tidak ada pemeriksaan otomatis ke basis data kependudukan —
        cocokkan nama dan 4 digit terakhir NIK dengan dokumen yang diunggah.
    </p>

    <nav class="mt-5 flex flex-wrap gap-2" aria-label="Filter status">
        @foreach ([
            'pending' => 'Menunggu',
            'verified' => 'Terverifikasi',
            'rejected' => 'Ditolak',
            'unverified' => 'Belum mengajukan',
        ] as $key => $label)
            @php($active = (request('status') ?: 'pending') === $key)
            <a href="{{ route('admin.pengguna.index', ['status' => $key]) }}" @class([
                'rounded-xl px-3.5 py-2 text-xs transition-all',
                'bg-[#99ff04] text-black font-black shadow-lg shadow-[#99ff04]/20' => $active,
                'border border-white/10 bg-[#1b182a] text-slate-300 hover:bg-[#231f36] hover:text-white font-bold' => ! $active,
            ])>{{ $label }}</a>
        @endforeach
    </nav>

    @if ($users->isEmpty())
        <x-empty-state class="mt-6" title="Tidak ada pengguna di status ini" />
    @else
        <div class="dt-card mt-6 overflow-x-auto">
            <table class="dt-table min-w-[680px]">
                <thead>
                    <tr>
                        <th scope="col">Nama</th>
                        <th scope="col">Peran</th>
                        <th scope="col">Lembaga</th>
                        <th scope="col">Status</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>
                                <p class="font-bold text-white">{{ $user->name }}</p>
                                <p class="mt-0.5 text-xs break-all text-slate-400">{{ $user->email }}</p>
                            </td>
                            <td class="whitespace-nowrap text-xs font-semibold text-slate-300">{{ $user->roleLabel() }}</td>
                            <td class="text-xs font-medium text-slate-300">{{ $user->organization ?: '—' }}</td>
                            <td>
                                <x-badge :tone="match($user->verification_status) {
                                    'verified' => 'success',
                                    'pending' => 'warning',
                                    'rejected' => 'danger',
                                    default => 'neutral',
                                }">{{ $user->verificationLabel() }}</x-badge>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.pengguna.show', $user) }}" class="dt-link text-xs whitespace-nowrap">Tinjau &rarr;</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $users->links() }}</div>
    @endif
@endsection
