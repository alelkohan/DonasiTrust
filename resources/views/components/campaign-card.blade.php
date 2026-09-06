@props(['campaign'])

@php
    $days = $campaign->daysLeft();
@endphp

<article class="dt-card group flex flex-col overflow-hidden transition-shadow hover:shadow-[0_2px_4px_rgba(15,23,42,0.05),0_16px_40px_-16px_rgba(15,23,42,0.22)]">
    <a href="{{ route('kampanye.show', $campaign) }}" class="block aspect-[16/9] overflow-hidden bg-ink-100">
        <img src="{{ $campaign->coverUrl() }}" alt="{{ $campaign->title }}"
             loading="lazy"
             class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]">
    </a>

    <div class="flex flex-1 flex-col p-5">
        <div class="flex items-center gap-2">
            <x-badge tone="info">{{ $campaign->categoryLabel() }}</x-badge>
            @if ($days !== null && $days <= 7)
                <x-badge tone="warning">{{ $days === 0 ? 'Hari terakhir' : $days.' hari lagi' }}</x-badge>
            @endif
        </div>

        <h3 class="mt-3 text-base leading-snug font-bold text-ink-900">
            <a href="{{ route('kampanye.show', $campaign) }}" class="hover:text-brand-700">{{ $campaign->title }}</a>
        </h3>

        <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-ink-600">{{ $campaign->summary }}</p>

        <div class="mt-auto pt-5">
            <x-progress :value="$campaign->progressPercent()" :label="'Progres '.$campaign->title" />

            <div class="mt-2.5 flex items-baseline justify-between gap-2">
                <p class="text-sm font-bold text-ink-900 tabular-nums">
                    {{ rupiah($campaign->collected_amount) }}
                </p>
                <p class="text-xs text-ink-500 tabular-nums">
                    dari {{ rupiah_ringkas($campaign->target_amount) }}
                </p>
            </div>

            <p class="mt-3 truncate text-xs text-ink-500">
                oleh {{ $campaign->user->organization ?: $campaign->user->name }}
            </p>
        </div>
    </div>
</article>
