@props(['tone' => 'neutral'])

@php
$tones = [
    'neutral' => 'bg-[#231f36] text-slate-300 border border-white/10',
    'success' => 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30',
    'warning' => 'bg-amber-500/15 text-amber-300 border border-amber-500/30',
    'danger'  => 'bg-rose-500/15 text-rose-300 border border-rose-500/30',
    'info'    => 'bg-sky-500/15 text-sky-300 border border-sky-500/30',
    'brand'   => 'bg-[#99ff04] text-black font-black',
];
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-extrabold', $tones[$tone] ?? $tones['neutral']]) }}>
    {{ $slot }}
</span>
