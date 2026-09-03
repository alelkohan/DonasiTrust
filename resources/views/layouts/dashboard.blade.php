@extends('layouts.app', ['is_dashboard' => true])

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="lg:grid lg:grid-cols-[220px_1fr] lg:gap-8">

        {{-- Navigasi samping. Di layar kecil jadi baris scroll horizontal. --}}
        <nav aria-label="Navigasi dasbor" class="mb-6 lg:mb-0">
            <ul class="flex gap-1 overflow-x-auto pb-2 lg:sticky lg:top-24 lg:flex-col lg:overflow-visible lg:pb-0">
                @foreach ($menu ?? [] as $item)
                    <li class="shrink-0">
                        <a href="{{ $item['url'] }}" wire:navigate @class([
                            'flex items-center gap-2.5 rounded-xl px-3.5 py-2.5 text-sm font-medium whitespace-nowrap transition-colors',
                            'bg-brand-600 text-white' => $item['active'] ?? false,
                            'text-ink-600 hover:bg-white hover:text-ink-900' => ! ($item['active'] ?? false),
                        ]) @if($item['active'] ?? false) aria-current="page" @endif>
                            {{ $item['label'] }}
                            @if (! empty($item['badge']))
                                <span @class([
                                    'ml-auto rounded-full px-1.5 py-0.5 text-xs font-bold',
                                    'bg-white/20 text-white' => $item['active'] ?? false,
                                    'bg-amber-100 text-amber-800' => ! ($item['active'] ?? false),
                                ])>{{ $item['badge'] }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="min-w-0">
            @yield('panel')
        </div>
    </div>
</div>
@endsection
