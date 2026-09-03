@props(['tone' => 'neutral'])

@php
$tones = [
    'neutral' => 'bg-ink-100 text-ink-700',
    'success' => 'bg-brand-100 text-brand-800',
    'warning' => 'bg-amber-100 text-amber-800',
    'danger'  => 'bg-rose-100 text-rose-700',
    'info'    => 'bg-sky-100 text-sky-800',
    'brand'   => 'bg-brand-600 text-white',
];
@endphp

<span {{ $attributes->class(['dt-badge', $tones[$tone] ?? $tones['neutral']]) }}>{{ $slot }}</span>
