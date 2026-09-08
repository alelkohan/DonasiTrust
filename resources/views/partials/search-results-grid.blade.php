<div class="w-full">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-black text-white flex items-center gap-2">
            <span class="grid h-6 w-6 place-items-center rounded-full bg-[#99ff04] text-black">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
            <span>Hasil Pencarian: "<span class="text-[#99ff04]">{{ $query }}</span>"</span>
            <span class="text-xs text-slate-400 font-normal">({{ $searchResults->count() }} kampanye)</span>
        </h3>
    </div>

    @if ($searchResults->isEmpty())
        <div class="rounded-3xl border border-white/10 bg-[#1b182a] p-8 text-center">
            <p class="text-sm font-bold text-white">Tidak ditemukan kampanye dengan kata kunci "{{ $query }}"</p>
            <p class="mt-1 text-xs text-slate-400">Coba gunakan kata kunci yang lebih umum seperti 'anak', 'sekolah', atau 'bencana'.</p>
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6">
            @foreach ($searchResults as $campaign)
                <x-campaign-card :campaign="$campaign" :dark="true" />
            @endforeach
        </div>
    @endif
</div>
