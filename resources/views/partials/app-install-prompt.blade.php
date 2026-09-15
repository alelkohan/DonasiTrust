{{-- Komponen Pasang Aplikasi di HP (Mobile Install Prompt) --}}
<div
    x-data="{
        deferredPrompt: null,
        showPrompt: false,
        showIosGuide: false,
        isIos: false,
        isStandalone: false,

        init() {
            // Cek apakah aplikasi sudah berjalan dalam mode terpasang (standalone)
            this.isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
            if (this.isStandalone) {
                return;
            }

            // Deteksi perangkat iOS
            const ua = window.navigator.userAgent.toLowerCase();
            this.isIos = /iphone|ipad|ipod/.test(ua) && !window.MSStream;

            // Deteksi apakah perangkat mobile / tablet
            const isMobileDevice = /android|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile/i.test(ua) || window.innerWidth <= 768;

            // Cek apakah user pernah menutup prompt dalam 5 hari terakhir
            const lastDismissed = localStorage.getItem('dt_install_dismissed');
            const now = Date.now();
            const fiveDaysInMs = 5 * 24 * 60 * 60 * 1000;

            if (lastDismissed && (now - parseInt(lastDismissed, 10)) < fiveDaysInMs) {
                return;
            }

            // Tangkap event instalasi browser Android / Chromium
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                this.deferredPrompt = e;
                if (isMobileDevice) {
                    setTimeout(() => {
                        this.showPrompt = true;
                    }, 2500);
                }
            });

            // Untuk iOS Safari (yang tidak mendukung beforeinstallprompt), munculkan banner jika di mobile
            if (this.isIos && isMobileDevice && !this.isStandalone) {
                setTimeout(() => {
                    this.showPrompt = true;
                }, 3000);
            }

            // Daftarkan service worker
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            }
        },

        async installApp() {
            if (this.deferredPrompt) {
                this.deferredPrompt.prompt();
                const { outcome } = await this.deferredPrompt.userChoice;
                this.deferredPrompt = null;
                this.showPrompt = false;
                if (outcome === 'accepted') {
                    localStorage.setItem('dt_install_dismissed', Date.now().toString());
                }
            } else if (this.isIos) {
                this.showIosGuide = true;
            } else {
                this.dismiss();
            }
        },

        dismiss() {
            this.showPrompt = false;
            this.showIosGuide = false;
            localStorage.setItem('dt_install_dismissed', Date.now().toString());
        }
    }"
    x-cloak
    class="print:hidden"
>
    {{-- Banner Pasang Aplikasi di Bawah Layar Mobile --}}
    <div
        x-show="showPrompt && !showIosGuide"
        x-transition:enter="transition ease-out duration-400 transform"
        x-transition:enter-start="translate-y-full opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-300 transform"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-full opacity-0"
        class="fixed bottom-0 sm:bottom-6 sm:right-6 inset-x-0 sm:inset-x-auto sm:max-w-md z-[70] p-4 sm:p-5 pb-6 sm:pb-5"
    >
        <div class="relative overflow-hidden rounded-3xl border border-white/15 bg-[#181528]/95 p-5 shadow-2xl backdrop-blur-2xl ring-1 ring-white/10">
            {{-- Ambient Glow --}}
            <div aria-hidden="true" class="absolute -top-12 -right-12 h-32 w-32 rounded-full bg-[#99ff04]/15 blur-2xl pointer-events-none"></div>

            {{-- Tombol Tutup X --}}
            <button
                type="button"
                @click="dismiss()"
                class="absolute top-3.5 right-3.5 grid h-7 w-7 place-items-center rounded-full text-slate-400 hover:text-white hover:bg-white/10 transition-colors cursor-pointer"
                aria-label="Tutup"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <div class="flex items-start gap-3.5">
                {{-- Logo App --}}
                <div class="relative shrink-0 mt-0.5">
                    <div class="grid h-12 w-12 place-items-center rounded-2xl bg-[#231f36] border border-white/15 p-1.5 shadow-lg shadow-[#99ff04]/10">
                        <img src="{{ asset('images/logo-no-bg.png') }}" alt="DonasiTrust" class="h-8 w-8 object-contain">
                    </div>
                    <span class="absolute -bottom-1 -right-1 grid h-4 w-4 place-items-center rounded-full bg-[#99ff04] text-[9px] font-black text-black ring-2 ring-[#181528]">
                        ✓
                    </span>
                </div>

                {{-- Deskripsi --}}
                <div class="flex-1 min-w-0 pr-4">
                    <div class="flex items-center gap-2">
                        <span class="rounded bg-[#99ff04]/15 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-[#99ff04] border border-[#99ff04]/30">
                            Aplikasi Resmi
                        </span>
                    </div>
                    <h3 class="mt-1 text-sm font-black text-white leading-tight">
                        Pasang Donasi<span class="text-[#99ff04]">Trust</span> di HP
                    </h3>
                    <p class="mt-1 text-xs font-medium text-slate-300 leading-relaxed">
                        Akses instan lebih cepat, hemat kuota, dan pantau donasi transparan langsung dari layar utama HP Anda.
                    </p>
                </div>
            </div>

            {{-- 3 Keunggulan Mini --}}
            <div class="mt-3.5 flex items-center justify-between border-t border-white/10 pt-3 text-[11px] font-bold text-slate-300">
                <span class="flex items-center gap-1.5">
                    <span class="text-[#99ff04]">⚡</span> Ringan & Cepat
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="text-[#99ff04]">📱</span> Akses 1-Klik
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="text-[#99ff04]">🔒</span> Aman & Sah
                </span>
            </div>

            {{-- Tombol Aksi --}}
            <div class="mt-4 flex flex-col sm:flex-row items-center gap-2.5">
                <button
                    type="button"
                    @click="installApp()"
                    class="w-full flex items-center justify-center gap-2 rounded-2xl bg-[#99ff04] px-4 py-3 text-xs font-black text-black shadow-lg shadow-[#99ff04]/25 hover:bg-[#84e000] active:scale-98 transition-all cursor-pointer"
                >
                    <svg class="h-4 w-4 stroke-black" viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    <span>Pasang Sekarang</span>
                </button>

                <button
                    type="button"
                    @click="dismiss()"
                    class="w-full sm:w-auto py-2 text-center text-xs font-bold text-slate-400 hover:text-white transition-colors cursor-pointer"
                >
                    Nanti Saja
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Panduan Khusus iOS (Safari) --}}
    <div
        x-show="showIosGuide"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[80] flex items-end sm:items-center justify-center bg-black/75 p-4 backdrop-blur-sm"
    >
        <div
            @click.outside="showIosGuide = false"
            class="relative w-full max-w-sm rounded-3xl border border-white/15 bg-[#181528] p-6 shadow-2xl text-white"
        >
            <div class="flex items-center justify-between border-b border-white/10 pb-3.5">
                <div class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo-no-bg.png') }}" alt="DonasiTrust" class="h-6 w-6 object-contain">
                    <h3 class="text-sm font-black">Pasang di iPhone / iPad</h3>
                </div>
                <button
                    type="button"
                    @click="showIosGuide = false"
                    class="rounded-full p-1 text-slate-400 hover:text-white"
                >
                    ✕
                </button>
            </div>

            <div class="mt-4 space-y-3.5 text-xs text-slate-200">
                <div class="flex items-start gap-3 rounded-2xl bg-white/5 p-3 border border-white/10">
                    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-[#99ff04] text-xs font-black text-black">
                        1
                    </span>
                    <p class="leading-relaxed">
                        Ketuk tombol <strong class="text-white">Bagikan (Share)</strong>
                        <svg class="inline h-4 w-4 text-sky-400 -mt-0.5 mx-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                        di bilah bawah browser Safari.
                    </p>
                </div>

                <div class="flex items-start gap-3 rounded-2xl bg-white/5 p-3 border border-white/10">
                    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-[#99ff04] text-xs font-black text-black">
                        2
                    </span>
                    <p class="leading-relaxed">
                        Gulir ke bawah pada menu lalu pilih <strong class="text-[#99ff04]">"Tambahkan ke Layar Utama"</strong> (<em>Add to Home Screen</em>).
                    </p>
                </div>

                <div class="flex items-start gap-3 rounded-2xl bg-white/5 p-3 border border-white/10">
                    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-[#99ff04] text-xs font-black text-black">
                        3
                    </span>
                    <p class="leading-relaxed">
                        Ketuk tombol <strong class="text-white">"Tambah"</strong> di pojok kanan atas. Ikon aplikasi DonasiTrust akan langsung muncul di layar HP Anda!
                    </p>
                </div>
            </div>

            <button
                type="button"
                @click="dismiss()"
                class="mt-5 w-full rounded-2xl bg-[#99ff04] py-2.5 text-xs font-black text-black shadow-md hover:bg-[#84e000] cursor-pointer"
            >
                Saya Mengerti
            </button>
        </div>
    </div>
</div>
