@props(['title', 'description' => null])

<div {{ $attributes->class(['dt-card flex flex-col items-center px-6 py-14 text-center']) }}>
    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-[#231f36] text-slate-400 border border-white/10" aria-hidden="true">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 7.5 12 4l8 3.5v9L12 20l-8-3.5Z"/><path d="M4 7.5 12 11l8-3.5M12 11v9"/>
        </svg>
    </span>
    <h3 class="mt-4 text-base font-bold text-white">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1.5 max-w-sm text-sm text-slate-400">{{ $description }}</p>
    @endif
    @if (trim($slot) !== '')
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
