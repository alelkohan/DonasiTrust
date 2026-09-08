@props(['campaign'])

@php
    $days = $campaign->daysLeft();
    $percent = $campaign->progressPercent();
@endphp

<article class="group flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-[#1b182a] hover:border-[#99ff04]/50 shadow-lg hover:shadow-2xl transition-all duration-300">
    
    {{-- Cover Image Container --}}
    <div class="relative aspect-[16/9] overflow-hidden bg-[#12101c]">
        <a href="{{ route('kampanye.show', $campaign) }}" class="block h-full w-full">
            <img src="{{ $campaign->coverUrl() }}" alt="{{ $campaign->title }}"
                 loading="lazy"
                 onerror="this.onerror=null;this.src='{{ asset('images/no-cover.svg') }}';"
                 class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
            <div class="absolute inset-0 bg-[#12101c]/40 opacity-60 group-hover:opacity-30 transition-opacity"></div>
        </a>

        {{-- Top Left Badges (Solid VGen Style) --}}
        <div class="absolute top-3 left-3 flex flex-wrap items-center gap-1.5 pointer-events-none">
            <span class="rounded bg-[#99ff04] px-2 py-0.5 text-[10px] font-black tracking-wider text-black uppercase shadow-sm">
                OPEN
            </span>
            <span class="card-cover-badge rounded bg-black/70 px-2 py-0.5 text-[10px] font-extrabold text-white backdrop-blur-md uppercase tracking-wide border border-white/10">
                {{ $campaign->categoryLabel() }}
            </span>
        </div>

        <!-- {{-- Top Right Bookmark Button --}}
        <button type="button" title="Simpan Kampanye"
                class="absolute top-3 right-3 grid h-8 w-8 place-items-center rounded-full bg-black/50 text-white backdrop-blur-md transition-all hover:bg-black/80 hover:scale-110 active:scale-95">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
            </svg>
        </button> -->

        {{-- Bottom Image Tag --}}
        @if ($days !== null && $days <= 7)
            <div class="absolute bottom-2 left-3 pointer-events-none">
                <span class="rounded bg-rose-600 px-2 py-0.5 text-[10px] font-bold text-white uppercase tracking-wide">
                    {{ $days === 0 ? 'Hari terakhir' : $days.' hari lagi' }}
                </span>
            </div>
        @endif
    </div>

    {{-- Card Body --}}
    <div class="flex flex-1 flex-col p-4">
        {{-- Title --}}
        <h3 class="text-sm font-extrabold leading-snug line-clamp-1 text-white group-hover:text-[#99ff04] transition-colors">
            <a href="{{ route('kampanye.show', $campaign) }}">{{ $campaign->title }}</a>
        </h3>

        {{-- Summary --}}
        <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-slate-400">
            {{ $campaign->summary }}
        </p>

        {{-- Progress Bar (Solid Neon Green VGen Style) --}}
        <div class="mt-auto pt-4">
            <div class="h-1.5 w-full overflow-hidden rounded-full bg-[#2a253e]">
                <div class="h-full rounded-full bg-[#99ff04] transition-all duration-500"
                     style="width: {{ $percent }}%"></div>
            </div>

            <div class="mt-2 flex items-baseline justify-between gap-2 text-xs">
                <span class="font-black tabular-nums text-white">
                    {{ rupiah($campaign->collected_amount) }}
                </span>
                <span class="text-[11px] tabular-nums text-slate-400">
                    target {{ rupiah_ringkas($campaign->target_amount) }}
                </span>
            </div>

            <div class="mt-3 flex items-center justify-between border-t border-white/10 pt-2.5 text-xs">
                {{-- Creator Info --}}
                <div class="flex items-center gap-1.5 truncate">
                    <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-[#99ff04] text-[10px] font-black text-black">
                        {{ Str::upper(Str::substr($campaign->user->organization ?: $campaign->user->name, 0, 1)) }}
                    </span>
                    <span class="truncate text-[11px] font-semibold text-slate-300">
                        {{ $campaign->user->organization ?: $campaign->user->name }}
                    </span>
                    @if ($campaign->user->isVerified())
                        <svg class="h-3.5 w-3.5 shrink-0 text-sky-400 fill-current" viewBox="0 0 20 20" title="Pengaju Terverifikasi KTP">
                            <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                        </svg>
                    @endif
                </div>

                {{-- Donatur Count --}}
                <span class="flex shrink-0 items-center gap-1 rounded bg-[#231f36] px-2 py-0.5 text-[10px] font-bold text-slate-300 border border-white/10">
                    <span>★ 5.0</span>
                    <span>({{ $campaign->paidDonations->count() ?: 1 }})</span>
                </span>
            </div>
        </div>
    </div>
</article>
