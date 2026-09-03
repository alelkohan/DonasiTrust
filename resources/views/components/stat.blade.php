@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'neutral',
    'href' => null,        // kartu jadi tautan bila diisi
    'emptyHint' => null,   // dipakai menggantikan hint saat nilainya nol
])

@php
    $accents = [
        'neutral' => 'text-ink-900',
        'success' => 'text-brand-700',
        'warning' => 'text-amber-700',
        'danger'  => 'text-rose-700',
    ];

    // Nilai nol tidak boleh mengundang klik ke halaman kosong.
    $isZero = is_numeric($value) && (int) $value === 0;
    $tautan = $isZero ? null : $href;
    $catatan = $isZero ? ($emptyHint ?? $hint) : $hint;
    $tag = $tautan ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($tautan) href="{{ $tautan }}" @endif
    {{ $attributes->class([
        'dt-card flex h-full flex-col p-5',
        'transition-shadow hover:shadow-[0_2px_4px_rgba(15,23,42,0.05),0_12px_28px_-14px_rgba(15,23,42,0.2)]' => (bool) $tautan,
    ]) }}>
    {{-- min-h menjaga angka tetap sebaris walau judulnya turun dua baris --}}
    <p class="min-h-[2.25rem] text-xs font-semibold tracking-wide text-ink-500 uppercase">{{ $label }}</p>
    <p class="mt-1 text-2xl font-extrabold tracking-tight tabular-nums {{ $accents[$tone] ?? $accents['neutral'] }}">{{ $value }}</p>
    @if ($catatan)
        <p class="mt-auto pt-1.5 text-xs {{ $isZero ? 'text-ink-400' : 'text-ink-500' }}">{{ $catatan }}</p>
    @endif
</{{ $tag }}>
