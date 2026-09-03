<footer class="mt-16 border-t border-ink-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-10 md:grid-cols-4">
            <div class="md:col-span-2">
                <div class="flex items-center gap-2.5">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-600 text-white" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 3 4 6.2v5.1c0 4.6 3.2 8.4 8 9.7 4.8-1.3 8-5.1 8-9.7V6.2Z"/><path d="m9 12 2.2 2.2L15.4 10"/>
                        </svg>
                    </span>
                    <span class="text-lg font-extrabold tracking-tight text-ink-900">Donasi<span class="text-brand-600">Trust</span></span>
                </div>
                <p class="mt-4 max-w-md text-sm leading-relaxed text-ink-600">
                    Donasi online tumbuh cepat, tapi donatur hampir tidak pernah tahu ke mana uangnya pergi.
                    DonasiTrust mengunci alurnya: dana cair bertahap, tiap tahap butuh bukti, dan semua
                    perubahan tercatat dalam jejak audit yang bisa diperiksa siapa saja.
                </p>
            </div>

            <div>
                <h2 class="text-sm font-semibold text-ink-900">Jelajahi</h2>
                <ul class="mt-4 space-y-2.5 text-sm text-ink-600">
                    <li><a class="hover:text-brand-700" href="{{ route('kampanye.index') }}">Semua kampanye</a></li>
                    <li><a class="hover:text-brand-700" href="{{ route('transparansi') }}">Ledger publik</a></li>
                    <li><a class="hover:text-brand-700" href="{{ route('verifikasi.form') }}">Verifikasi kuitansi</a></li>
                </ul>
            </div>

            <div>
                <h2 class="text-sm font-semibold text-ink-900">Akun</h2>
                <ul class="mt-4 space-y-2.5 text-sm text-ink-600">
                    <li><a class="hover:text-brand-700" href="{{ route('register') }}">Daftar sebagai donatur</a></li>
                    <li><a class="hover:text-brand-700" href="{{ route('register') }}">Ajukan kampanye</a></li>
                    <li><a class="hover:text-brand-700" href="{{ route('login') }}">Masuk</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-10 flex flex-col gap-3 border-t border-ink-100 pt-6 text-xs text-ink-500 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ date('Y') }} DonasiTrust. Proyek kompetisi SwitchFest 2026.</p>
            <p>Pembayaran berjalan di mode simulasi. Tidak ada uang sungguhan yang berpindah.</p>
        </div>
    </div>
</footer>
