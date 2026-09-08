@extends('layouts.app', ['is_dashboard' => true])

@section('content')
<div class="w-full py-8">
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

                {{-- Tombol Logout di Dasbor --}}
                <li class="shrink-0 lg:pt-3 lg:border-t lg:border-white/10 lg:mt-2">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center justify-between gap-2.5 rounded-2xl px-4 py-3 text-xs font-extrabold whitespace-nowrap text-rose-400 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 transition-all cursor-pointer">
                            <span>Keluar Akun</span>
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>
                </li>
            </ul>
        </nav>

        <div class="min-w-0">
            @yield('panel')
        </div>
    </div>
</div>
@endsection
