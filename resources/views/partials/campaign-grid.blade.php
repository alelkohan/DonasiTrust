@if ($campaigns->isEmpty())
    <div class="col-span-full rounded-3xl border border-white/10 bg-[#1b182a] p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <h3 class="mt-4 text-lg font-bold text-white">Tidak ada kampanye ditemukan</h3>
        <p class="mt-2 text-xs text-slate-400">Coba ubah kata kunci pencarian atau pilih kategori lain.</p>
    </div>
@else
    @foreach ($campaigns as $campaign)
        <x-campaign-card :campaign="$campaign" :dark="true" />
    @endforeach
@endif
