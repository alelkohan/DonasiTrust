<footer class="mt-16 border-t border-white/10 bg-[#0d0b15] text-slate-400 w-full transition-colors duration-300">
    <div class="w-full px-1.5 sm:px-2 lg:px-3 py-12">
        <div class="grid gap-10 md:grid-cols-4">
            
            {{-- Brand Info --}}
            <div class="md:col-span-2">
                <div class="flex items-center gap-2.5">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-[#99ff04] text-black font-black shadow-md" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 3 4 6.2v5.1c0 4.6 3.2 8.4 8 9.7 4.8-1.3 8-5.1 8-9.7V6.2Z"/>
                            <path d="m9 12 2.2 2.2L15.4 10"/>
                        </svg>
                    </span>
                    <span class="text-xl font-black tracking-tight text-white">
                        Donasi<span class="text-[#99ff04]">Trust</span>
                    </span>
                </div>
                <p class="mt-4 max-w-md text-xs sm:text-sm leading-relaxed text-slate-400">
                    Platform donasi transparan berbasis bukti digital. Dana dicairkan bertahap berdasarkan tahapan RAB, setiap pengeluaran wajib dilengkapi bukti nota fisik, dan tersambung dalam jejak audit kriptografi HMAC yang dapat diverifikasi publik secara teruka.
                </p>
            </div>

            {{-- Quick Links --}}
            <div>
                <h2 class="text-xs font-black uppercase tracking-wider text-white">Jelajahi</h2>
                <ul class="mt-4 space-y-2.5 text-xs font-bold">
                    <li><a class="text-slate-300 hover:text-[#99ff04] transition-colors" href="{{ route('kampanye.index') }}">Semua Kampanye</a></li>
                    <li><a class="text-slate-300 hover:text-[#99ff04] transition-colors" href="{{ route('transparansi') }}">Public Ledger Audit</a></li>
                    <li><a class="text-slate-300 hover:text-[#99ff04] transition-colors" href="{{ route('verifikasi.form') }}">Verifikasi Kuitansi HMAC</a></li>
                </ul>
            </div>

            {{-- Account Links --}}
            <div>
                <h2 class="text-xs font-black uppercase tracking-wider text-white">Akun & Portal</h2>
                <ul class="mt-4 space-y-2.5 text-xs font-bold">
                    <li><a class="text-slate-300 hover:text-[#99ff04] transition-colors" href="{{ route('register') }}">Daftar Donatur</a></li>
                    <li><a class="text-slate-300 hover:text-[#99ff04] transition-colors" href="{{ route('register') }}">Ajukan Kampanye (+Pengaju)</a></li>
                    <li><a class="text-slate-300 hover:text-[#99ff04] transition-colors" href="{{ route('login') }}">Masuk Ke Akun</a></li>
                </ul>
            </div>
        </div>

        {{-- Bottom Copyright & Disclaimer --}}
        <div class="mt-10 flex flex-col gap-3 border-t border-white/10 pt-6 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ date('Y') }} DonasiTrust. Sistem Transparansi Donasi Berbasis Jejak Audit Terverifikasi.</p>
            <p class="flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-[#99ff04]"></span>
                Platform Akuntabilitas Donasi Indonesia
            </p>
        </div>
    </div>
</footer>
