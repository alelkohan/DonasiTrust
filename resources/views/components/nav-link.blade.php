@props(['active' => false])

<a {{ $attributes->class([
    'rounded-lg px-3 py-2 text-sm font-medium transition-colors',
    'bg-brand-50 text-brand-800' => $active,
    'text-ink-600 hover:bg-ink-50 hover:text-ink-900' => ! $active,
]) }} @if($active) aria-current="page" @endif>{{ $slot }}</a>
