@if (session('status') || session('warning') || session('error') || $errors->any())
    <div class="mx-auto mt-5 max-w-7xl px-4 sm:px-6 lg:px-8 print:hidden">
        @if (session('status'))
            <div role="status" class="flex items-start gap-3 rounded-2xl border border-[#99ff04]/30 bg-[#99ff04]/10 px-4 py-3 text-sm text-[#99ff04] shadow-md">
                <svg class="mt-0.5 h-4.5 w-4.5 shrink-0 text-[#99ff04]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.4 2.4 4.6-4.8"/></svg>
                <span class="font-bold text-white">{{ session('status') }}</span>
            </div>
        @endif

        @if (session('warning'))
            <div role="alert" class="mt-3 flex items-start gap-3 rounded-2xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-300 shadow-md">
                <svg class="mt-0.5 h-4.5 w-4.5 shrink-0 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4.5m0 3.5h.01M10.3 4.2 2.9 17.1a1.9 1.9 0 0 0 1.7 2.9h14.8a1.9 1.9 0 0 0 1.7-2.9L13.7 4.2a1.9 1.9 0 0 0-3.4 0Z"/></svg>
                <span class="font-bold">{{ session('warning') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div role="alert" class="mt-3 flex items-start gap-3 rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-300 shadow-md">
                <svg class="mt-0.5 h-4.5 w-4.5 shrink-0 text-rose-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6m0-6-6 6"/></svg>
                <span class="font-bold">{{ session('error') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="mt-3 rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-300 shadow-md">
                <p class="font-black text-rose-200">Ada {{ $errors->count() }} hal yang perlu diperbaiki:</p>
                <ul class="mt-1.5 list-disc space-y-1 pl-5 text-xs text-slate-300">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif
