@props(['value' => 0, 'label' => null])

@php($pct = max(0, min(100, (float) $value)))

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    <div class="h-2 w-full overflow-hidden rounded-full bg-ink-100"
         role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"
         aria-label="{{ $label ?? 'Progres pengumpulan dana' }}">
        <div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-brand-600 transition-[width] duration-500"
             style="width: {{ $pct }}%"></div>
    </div>
</div>
