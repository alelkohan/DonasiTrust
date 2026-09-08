@extends('layouts.app', ['is_dashboard' => true])

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="lg:grid lg:grid-cols-[240px_1fr] lg:gap-8">

        {{-- Navigasi samping dasbor --}}
        <nav aria-label="Navigasi dasbor" class="mb-6 lg:mb-0">
            <ul class="flex gap-2 overflow-x-auto pb-2 lg:sticky lg:top-24 lg:flex-col lg:overflow-visible lg:pb-0">
                @foreach ($menu ?? [] as $item)
                    <li class="shrink-0">
                        <a href="{{ $item['url'] }}" wire:navigate @class([
                            'flex items-center justify-between gap-2.5 rounded-2xl px-4 py-3 text-xs font-extrabold whitespace-nowrap transition-all shadow-sm',
                            'bg-[#99ff04] text-black shadow-lg shadow-[#99ff04]/20' => $item['active'] ?? false,
                            'bg-[#1b182a] text-slate-300 hover:bg-[#231f36] hover:text-white border border-white/10' => ! ($item['active'] ?? false),
                        ]) @if($item['active'] ?? false) aria-current="page" @endif>
                            <span>{{ $item['label'] }}</span>
                            @if (! empty($item['badge']))
                                <span @class([
                                    'ml-auto rounded-full px-2 py-0.5 text-[10px] font-black',
                                    'bg-black/20 text-black' => $item['active'] ?? false,
                                    'bg-rose-500/20 text-rose-300 border border-rose-500/30' => ! ($item['active'] ?? false),
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
