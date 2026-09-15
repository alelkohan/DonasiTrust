<footer class="mt-16 border-t border-white/10 bg-[#0d0b15] text-slate-400 w-full transition-colors duration-300 print:hidden">
    <div class="w-full max-w-[1650px] mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid gap-10 md:grid-cols-4">
            
            {{-- Brand Info --}}
            <div class="md:col-span-2">
                <div class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo-no-bg.png') }}" alt="DonasiTrust Logo" class="h-9 w-9 object-contain drop-shadow-md">
                    <span class="text-xl font-black tracking-tight text-white">
                        Donasi<span class="text-[#99ff04]">Trust</span>
                    </span>
                </div>
                <p class="mt-4 max-w-md text-xs sm:text-sm leading-relaxed text-slate-400">
                    Platform donasi transparan berbasis bukti digital. Dana dicairkan bertahap berdasarkan kemajuan proyek, setiap pengeluaran wajib dilengkapi bukti nota resmi, dan tersambung dalam jejak audit digital terverifikasi yang aman serta terbuka untuk publik.
                </p>
            </div>

            {{-- Quick Links --}}
            <div>
                <h2 class="text-xs font-black uppercase tracking-wider text-white">Jelajahi</h2>
                <ul class="mt-4 space-y-2.5 text-xs font-bold">
                    <li><a class="text-slate-300 hover:text-[#99ff04] transition-colors" href="{{ route('kampanye.index') }}">Semua Kampanye</a></li>
                    <li><a class="text-slate-300 hover:text-[#99ff04] transition-colors" href="{{ route('transparansi') }}">Jejak Audit Donasi</a></li>
                    <li><a class="text-slate-300 hover:text-[#99ff04] transition-colors" href="{{ route('verifikasi.form') }}">Cek Keaslian Kuitansi</a></li>
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
