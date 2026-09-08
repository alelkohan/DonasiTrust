@extends('layouts.app')
@section('title', 'Semua Kampanye · DonasiTrust')

@section('content')
<div class="w-full py-6 relative"
     x-data="{
        loading: false,
        fetchUrl(url) {
            if (!url) return;
            this.loading = true;
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => res.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newGrid = doc.querySelector('#campaigns-grid-section');
                    const newBadge = doc.querySelector('#campaigns-total-badge');
                    
                    if (newGrid) {
                        document.querySelector('#campaigns-grid-section').innerHTML = newGrid.innerHTML;
                    }
                    if (newBadge) {
                        document.querySelector('#campaigns-total-badge').innerText = newBadge.innerText;
                    }
                    window.history.pushState({}, '', url);
                    this.loading = false;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                })
                .catch(err => {
                    console.error(err);
                    this.loading = false;
                    window.location.href = url;
                });
        },
        submitFilter(event) {
            const form = event.target.form || event.target;
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            const url = form.action + '?' + params.toString();
            this.fetchUrl(url);
        }
     }"
     @click="
        const a = $event.target.closest('#campaigns-grid-section a');
        if (a && a.href && (a.href.includes('page=') || a.closest('nav'))) {
            $event.preventDefault();
            fetchUrl(a.href);
        }
     ">

    {{-- Loading Overlay Indicator --}}
    <div x-show="loading" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs">
        <div class="flex items-center gap-3 rounded-full bg-[#1b182a] border border-[#99ff04]/40 px-6 py-3 text-xs font-black text-white shadow-2xl">
            <svg class="h-4 w-4 animate-spin text-[#99ff04]" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Memuat Kampanye...</span>
        </div>
    </div>

    {{-- Sticky Header & Inline Filter Controls --}}
    <div class="sticky top-14 sm:top-[60px] z-30 -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8 py-3 bg-[#12101c]/95 backdrop-blur-md border-b border-white/10 flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-6 transition-all">
        <div>
            <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white flex items-center gap-2">
                <span>Kampanye Aktif</span>
                <span id="campaigns-total-badge" class="rounded-full bg-[#99ff04] px-2.5 py-0.5 text-[10px] font-black text-black shadow-sm">
                    {{ $campaigns->total() }} Total
                </span>
            </h1>
        </div>

        {{-- Lightweight Compact Filter Form --}}
        <form method="GET" action="{{ route('kampanye.index') }}" @submit.prevent="submitFilter($event)" class="flex flex-wrap items-center gap-2">
            
            {{-- Compact Search Input --}}
            <div class="relative flex-1 min-w-[180px] sm:w-64">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input id="q" name="q" type="search" value="{{ request('q') }}"
                       class="w-full rounded-full border border-white/15 bg-[#231f36] py-2 pl-9 pr-4 text-xs font-medium text-white placeholder-slate-400 focus:border-[#99ff04] focus:outline-none transition-all"
                       placeholder="Cari kampanye / pengaju...">
            </div>

            {{-- Compact Category Dropdown (AJAX on change) --}}
            <select name="kategori" @change="submitFilter($event)"
                    class="rounded-full border border-white/15 bg-[#231f36] px-3.5 py-2 text-xs font-bold text-slate-200 focus:border-[#99ff04] focus:outline-none cursor-pointer">
                <option value="" class="bg-[#1b182a]">Semua Kategori</option>
                @foreach ($categories as $key => $label)
                    <option value="{{ $key }}" @selected(request('kategori') === $key) class="bg-[#1b182a]">{{ $label }}</option>
                @endforeach
            </select>

            {{-- Compact Sort Dropdown (AJAX on change) --}}
            <select name="urut" @change="submitFilter($event)"
                    class="rounded-full border border-white/15 bg-[#231f36] px-3.5 py-2 text-xs font-bold text-slate-200 focus:border-[#99ff04] focus:outline-none cursor-pointer">
                <option value="populer" @selected(request('urut') !== 'terbaru') class="bg-[#1b182a]">Terbanyak</option>
                <option value="terbaru" @selected(request('urut') === 'terbaru') class="bg-[#1b182a]">Terbaru</option>
            </select>

            @if(request()->hasAny(['q', 'kategori', 'urut']))
                <a href="{{ route('kampanye.index') }}" @click.prevent="fetchUrl($el.href)" title="Hapus Filter" class="grid h-8 w-8 place-items-center rounded-full bg-white/10 text-xs text-slate-400 hover:text-white transition-all">
                    ✕
                </a>
            @endif
        </form>
    </div>

    {{-- Campaign Cards Grid (AJAX Target Container) --}}
    <div id="campaigns-grid-section">
        @if ($campaigns->isEmpty())
            <div class="rounded-3xl border border-white/10 bg-[#1b182a] p-10 text-center my-6">
                <p class="text-sm font-bold text-white">Tidak ada kampanye yang cocok</p>
                <p class="mt-1 text-xs text-slate-400">Coba ubah kata kunci pencarian atau reset filter.</p>
                <a href="{{ route('kampanye.index') }}" @click.prevent="fetchUrl($el.href)" class="mt-4 inline-flex rounded-full bg-[#99ff04] px-5 py-2 text-xs font-black text-black">
                    Reset Filter
                </a>
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($campaigns as $campaign)
                    <x-campaign-card :campaign="$campaign" :dark="true" />
                @endforeach
            </div>

            <div class="mt-10 flex justify-center">{{ $campaigns->links() }}</div>
        @endif
    </div>
</div>
@endsection
