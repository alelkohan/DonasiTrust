@extends('layouts.dashboard')
@section('title', 'Riwayat Donasi')

@section('panel')
<div class="space-y-6">

    {{-- Hero Welcome & Header Banner --}}
    <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-r from-[#1b182a] via-[#231f36] to-[#1b182a] p-6 sm:p-8 shadow-xl">
        <div aria-hidden="true" class="absolute -top-12 -right-12 h-64 w-64 rounded-full bg-[#99ff04]/10 blur-3xl pointer-events-none"></div>
        <div aria-hidden="true" class="absolute -bottom-16 -left-16 h-64 w-64 rounded-full bg-purple-600/15 blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <div class="relative grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-[#99ff04] text-black font-black text-xl shadow-lg shadow-[#99ff04]/20">
                    {{ Str::upper(Str::substr(auth()->user()->name, 0, 1)) }}
                    <span class="absolute -bottom-1 -right-1 grid h-5 w-5 place-items-center rounded-full bg-black text-[#99ff04] text-[10px] border border-[#99ff04]">
                        ✓
                    </span>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white flex items-center gap-2">
                        Halo, {{ auth()->user()->name }}
                    </h1>
                    <p class="mt-1 text-xs sm:text-sm font-medium text-slate-300">
                        Jejak kebaikan &amp; bukti kuitansi terverifikasi HMAC-SHA256 tersimpan di sini.
                    </p>
                </div>
            </div>

            <a href="{{ route('kampanye.index') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-[#99ff04] px-5 py-2.5 text-xs font-black text-black hover:bg-[#84e000] transition-all hover:scale-105 active:scale-95 shadow-md shadow-[#99ff04]/20 shrink-0">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Donasi Kampanye Baru</span>
            </a>
        </div>
    </div>

    {{-- Stat Highlights (3 Stat Cards Grid) --}}
    <div class="grid gap-4 sm:grid-cols-3">
        {{-- Card 1: Total Nominal Donasi --}}
        <div class="group relative overflow-hidden rounded-2xl border border-white/10 bg-[#1b182a] p-5 shadow-lg backdrop-blur-md transition-all hover:border-[#99ff04]/40">
            <div class="flex items-center justify-between">
                <span class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Total Donasi Anda</span>
                <span class="grid h-8 w-8 place-items-center rounded-xl bg-[#99ff04]/10 text-[#99ff04] border border-[#99ff04]/20">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3 text-2xl font-black text-white tracking-tight tabular-nums">
                {{ rupiah($totalDonated) }}
            </div>
            <div class="mt-2 flex items-center gap-1.5 text-[11px] font-semibold text-[#99ff04]">
                <span class="h-1.5 w-1.5 rounded-full bg-[#99ff04] animate-pulse"></span>
                <span>Tercatat Dalam Ledger Publik</span>
            </div>
        </div>

        {{-- Card 2: Total Transaksi --}}
        <div class="group relative overflow-hidden rounded-2xl border border-white/10 bg-[#1b182a] p-5 shadow-lg backdrop-blur-md transition-all hover:border-[#99ff04]/40">
            <div class="flex items-center justify-between">
                <span class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Total Transaksi</span>
                <span class="grid h-8 w-8 place-items-center rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3 text-2xl font-black text-white tracking-tight tabular-nums">
                {{ number_format($donations->total(), 0, ',', '.') }}
                <span class="text-xs font-bold text-slate-400">kali</span>
            </div>
            <div class="mt-2 text-[11px] font-medium text-slate-400">
                Keseluruhan riwayat donasi
            </div>
        </div>

        {{-- Card 3: Donasi Lunas --}}
        <div class="group relative overflow-hidden rounded-2xl border border-white/10 bg-[#1b182a] p-5 shadow-lg backdrop-blur-md transition-all hover:border-[#99ff04]/40">
            <div class="flex items-center justify-between">
                <span class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Donasi Lunas</span>
                <span class="grid h-8 w-8 place-items-center rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3 text-2xl font-black text-emerald-400 tracking-tight tabular-nums">
                {{ number_format($donations->getCollection()->where('status', 'paid')->count(), 0, ',', '.') }}
                <span class="text-xs font-bold text-slate-400">berhasil</span>
            </div>
            <div class="mt-2 text-[11px] font-medium text-slate-400">
                Lunas di halaman ini
            </div>
        </div>
    </div>

    {{-- Main History Table Card --}}
    <div class="rounded-3xl border border-white/10 bg-[#1b182a] shadow-xl overflow-hidden backdrop-blur-md">
        
        {{-- Section Title --}}
        <div class="flex items-center justify-between border-b border-white/10 px-6 py-5">
            <div class="flex items-center gap-3">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-[#231f36] text-[#99ff04] border border-white/10">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-base font-black text-white">Daftar Riwayat Transaksi</h2>
                    <p class="text-xs font-medium text-slate-400">Status donasi &amp; tautan bukti kuitansi resmi</p>
                </div>
            </div>
            <span class="rounded-full bg-[#231f36] px-3 py-1 text-xs font-extrabold text-slate-300 border border-white/10">
                {{ $donations->total() }} Data
            </span>
        </div>

        @if ($donations->isEmpty())
            {{-- Empty State --}}
            <div class="px-6 py-16 text-center">
                <div class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-[#231f36] text-slate-400 border border-white/10 mb-4">
                    <svg class="h-8 w-8 stroke-[#99ff04]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-black text-white">Belum Ada Riwayat Donasi</h3>
                <p class="mt-2 text-xs sm:text-sm font-medium text-slate-400 max-w-sm mx-auto">
                    Setiap kebaikan yang Anda salurkan akan tercatat otomatis dan aman di sini.
                </p>
                <a href="{{ route('kampanye.index') }}" class="mt-6 inline-flex items-center gap-2 rounded-full bg-[#99ff04] px-6 py-2.5 text-xs font-black text-black hover:bg-[#84e000] transition-all hover:scale-105 shadow-md shadow-[#99ff04]/20">
                    <span>Lihat Semua Kampanye</span>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        @else
            {{-- Desktop View Table (hidden on small mobile screens) --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#12101c]/80 text-[11px] font-black uppercase tracking-wider text-slate-400 border-b border-white/10">
                        <tr>
                            <th scope="col" class="px-6 py-4">Referensi</th>
                            <th scope="col" class="px-6 py-4">Kampanye</th>
                            <th scope="col" class="px-6 py-4">Tanggal</th>
                            <th scope="col" class="px-6 py-4 text-right">Nominal</th>
                            <th scope="col" class="px-6 py-4 text-center">Status</th>
                            <th scope="col" class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 font-semibold text-slate-200">
                        @foreach ($donations as $donation)
                            <tr class="hover:bg-white/5 transition-colors">
                                {{-- Reference Code --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-mono text-[11px] font-bold text-slate-300 bg-[#231f36] px-2.5 py-1 rounded-md border border-white/10">
                                        #{{ $donation->reference }}
                                    </span>
                                </td>

                                {{-- Campaign Title --}}
                                <td class="px-6 py-4 max-w-xs truncate">
                                    <a href="{{ route('kampanye.show', $donation->campaign) }}" class="font-extrabold text-white hover:text-[#99ff04] transition-colors line-clamp-1">
                                        {{ $donation->campaign->title }}
                                    </a>
                                </td>

                                {{-- Date --}}
                                <td class="px-6 py-4 whitespace-nowrap text-slate-400 text-[11px]">
                                    {{ $donation->created_at->translatedFormat('d M Y, H:i') }}
                                </td>

                                {{-- Amount --}}
                                <td class="px-6 py-4 text-right whitespace-nowrap font-black text-white text-sm tabular-nums">
                                    {{ rupiah($donation->amount) }}
                                </td>

                                {{-- Status Badge --}}
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    @if ($donation->isPaid())
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/15 px-3 py-1 text-[10px] font-black text-emerald-400 border border-emerald-500/30">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                            LUNAS
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/15 px-3 py-1 text-[10px] font-black text-amber-300 border border-amber-500/30">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                            {{ Str::upper($donation->statusLabel()) }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Action Button --}}
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    @if ($donation->isPaid())
                                        <a href="{{ route('kuitansi.show', $donation) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-white/20 bg-[#231f36] px-3 py-1.5 text-[11px] font-extrabold text-white hover:border-[#99ff04] hover:text-[#99ff04] transition-all shadow-sm">
                                            <svg class="h-3.5 w-3.5 text-[#99ff04]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            <span>Kuitansi</span>
                                        </a>
                                    @else
                                        <a href="{{ route('donasi.checkout', $donation) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-[#99ff04] px-3.5 py-1.5 text-[11px] font-black text-black hover:bg-[#84e000] transition-all shadow-sm shadow-[#99ff04]/20">
                                            <span>Bayar Now</span>
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                            </svg>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile View Card List (visible only on small mobile screens) --}}
            <div class="block sm:hidden divide-y divide-white/10">
                @foreach ($donations as $donation)
                    <div class="p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-[10px] font-bold text-slate-300 bg-[#231f36] px-2 py-0.5 rounded border border-white/10">
                                #{{ $donation->reference }}
                            </span>
                            @if ($donation->isPaid())
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-[9px] font-black text-emerald-400 border border-emerald-500/30">
                                    ✓ LUNAS
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-500/15 px-2.5 py-0.5 text-[9px] font-black text-amber-300 border border-amber-500/30">
                                    ⏳ {{ Str::upper($donation->statusLabel()) }}
                                </span>
                            @endif
                        </div>

                        <div>
                            <a href="{{ route('kampanye.show', $donation->campaign) }}" class="text-xs font-black text-white hover:text-[#99ff04] line-clamp-2">
                                {{ $donation->campaign->title }}
                            </a>
                            <div class="mt-1 flex items-center justify-between text-[11px]">
                                <span class="text-slate-400">{{ $donation->created_at->translatedFormat('d M Y, H:i') }}</span>
                                <span class="font-black text-white text-sm tabular-nums">{{ rupiah($donation->amount) }}</span>
                            </div>
                        </div>

                        <div class="pt-1">
                            @if ($donation->isPaid())
                                <a href="{{ route('kuitansi.show', $donation) }}" class="flex w-full items-center justify-center gap-1.5 rounded-xl border border-white/20 bg-[#231f36] py-2 text-xs font-black text-white hover:border-[#99ff04] hover:text-[#99ff04] transition-all">
                                    <svg class="h-4 w-4 text-[#99ff04]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span>Lihat Kuitansi HMAC</span>
                                </a>
                            @else
                                <a href="{{ route('donasi.checkout', $donation) }}" class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-[#99ff04] py-2 text-xs font-black text-black hover:bg-[#84e000] transition-all shadow-md">
                                    <span>Lanjutkan Pembayaran</span>
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination Footer --}}
            @if ($donations->hasPages())
                <div class="border-t border-white/10 px-6 py-4 bg-[#12101c]/50">
                    {{ $donations->links() }}
                </div>
            @endif
        @endif

    </div>

</div>
@endsection
