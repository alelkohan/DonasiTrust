@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'neutral',
    'href' => null,
    'emptyHint' => null,
])

@php
    $accents = [
        'neutral' => 'text-white',
        'success' => 'text-[#99ff04]',
        'warning' => 'text-amber-400',
        'danger'  => 'text-rose-400',
    ];

    $isZero = is_numeric($value) && (int) $value === 0;
    $tautan = $isZero ? null : $href;
    $catatan = $isZero ? ($emptyHint ?? $hint) : $hint;
    $tag = $tautan ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($tautan) href="{{ $tautan }}" @endif
    {{ $attributes->class([
        'rounded-2xl border border-white/10 bg-[#1b182a] flex h-full flex-col p-5 shadow-lg backdrop-blur-md transition-all hover:border-[#99ff04]/30',
    ]) }}>
    <p class="min-h-[2rem] text-xs font-extrabold tracking-wider text-slate-400 uppercase">{{ $label }}</p>
    <p class="mt-1 text-2xl font-black tracking-tight tabular-nums {{ $accents[$tone] ?? $accents['neutral'] }}">{{ $value }}</p>
    @if ($catatan)
        <p class="mt-auto pt-2 text-xs font-medium {{ $isZero ? 'text-slate-500' : 'text-slate-400' }}">{{ $catatan }}</p>
    @endif
</{{ $tag }}>
