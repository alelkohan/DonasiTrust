{{--
    Pemilihan nominal ditangani sepenuhnya di sisi peramban (Alpine), bukan
    lewat Livewire.

    Sebelumnya input nominal memakai wire:model.live, jadi setiap ketikan
    memicu permintaan ke server — dan karena Alpine ikut memformat ulang isi
    input di saat yang sama, kursor melompat ke ujung kanan saat mengetik di
    tengah angka. Sekarang nilainya baru dikirim ke server saat form disubmit.
--}}<div class="dt-card p-5 sm:p-6">

    @if (session('status'))
        <div class="mb-4 rounded-2xl border border-[#99ff04]/30 bg-[#99ff04]/10 p-4 text-xs font-bold text-[#99ff04] flex items-center gap-2.5 shadow-md">
            <svg class="h-4 w-4 shrink-0 text-[#99ff04]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if ($this->pendingDonation)
        <h2 class="text-lg font-bold text-white">Selesaikan Donasi Anda</h2>
        <p class="mt-1 text-xs text-slate-400">
            Anda memiliki transaksi donasi yang sedang berlangsung untuk kampanye ini.
        </p>

        {{-- Kartu Transaksi Pending Aktif (Form Baru Terkunci) --}}
        <div class="mt-4 rounded-3xl border border-amber-400/30 bg-[#231f36] p-5 shadow-2xl relative overflow-hidden">
            <div class="flex items-start gap-3.5">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-400/20 text-amber-300 shadow-inner">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="rounded bg-amber-400/20 border border-amber-400/40 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-amber-300">
                            Pembayaran Belum Selesai
                        </span>
                    </div>
                    <p class="mt-1 font-mono text-xs text-slate-300">
                        Nomor: <strong class="text-white">{{ $this->pendingDonation->reference }}</strong>
                    </p>
                    <p class="mt-2 text-3xl font-black text-[#99ff04] tracking-tight tabular-nums">
                        {{ rupiah($this->pendingDonation->amount) }}
                    </p>
                    <p class="mt-2 text-xs leading-relaxed text-slate-400">
                        Anda tidak dapat menambah transaksi donasi baru sebelum menyelesaikan atau membatalkan transaksi aktif ini.
                    </p>

                    <div class="mt-5 flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                        <a href="{{ route('donasi.checkout', $this->pendingDonation) }}"
                           class="inline-flex items-center justify-center gap-2 rounded-full bg-[#99ff04] py-3 px-5 text-xs font-black text-black hover:bg-[#84e000] transition-transform active:scale-95 shadow-lg shadow-[#99ff04]/20 cursor-pointer">
                            Lanjutkan Pembayaran &rarr;
                        </a>
                        <button type="button" @click="$dispatch('buka-batal-donasi')"
                                class="inline-flex items-center justify-center gap-1.5 rounded-full border border-rose-500/40 bg-rose-500/10 py-3 px-4 text-xs font-bold text-rose-400 hover:bg-rose-500/20 hover:text-rose-300 transition-colors cursor-pointer">
                            Batalkan Pembayaran
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @elseif ($campaign->isTargetReached())
        <div class="rounded-3xl border border-[#99ff04]/30 bg-[#99ff04]/10 p-6 text-center shadow-xl">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#99ff04] text-black mb-3">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 class="text-lg font-black text-white">Target Donasi 100% Terpenuhi!</h3>
            <p class="mt-1.5 text-xs text-slate-300 leading-relaxed max-w-md mx-auto">
                Alhamdulillah, target dana untuk kampanye ini telah terkumpul sepenuhnya. Terima kasih yang mendalam kepada seluruh donatur atas kedermawanannya.
            </p>
            <div class="mt-4 pt-4 border-t border-white/10 flex justify-center gap-3">
                <a href="{{ route('kampanye.index') }}" class="rounded-full bg-[#99ff04] px-5 py-2.5 text-xs font-black text-black hover:bg-[#84e000] transition-colors shadow-lg">
                    Bantu Kampanye Lainnya &rarr;
                </a>
            </div>
        </div>
    @else
        <h2 class="text-lg font-black text-white">Donasi sekarang</h2>
        <p class="mt-1 text-xs sm:text-sm text-slate-300">
            Setiap donasi menerbitkan kuitansi digital dengan kode yang bisa diverifikasi publik.
        </p>

        <form @submit.prevent="valid() && (bukaModalKonfirmasi = true)" class="mt-5 space-y-4"
              x-data="formDonasi({
                  nominal: {{ (int) $this->amountValue }},
                  cepat: {{ Illuminate\Support\Js::from($quickAmounts) }},
                  min: {{ (int) config('donasi.min_donation') }},
                  max: {{ (int) config('donasi.max_donation') }},
                  sisaTarget: {{ (int) $campaign->remainingTarget() }},
              })">

            <div>
                <span class="dt-label">Pilih nominal</span>
                <div class="grid grid-cols-3 gap-2.5">
                    <template x-for="angka in cepat" :key="angka">
                        <button type="button" @click="pilihCepat(angka)"
                                :class="nominal === angka && !manual
                                    ? 'bg-[#99ff04] text-black border-[#99ff04] font-black shadow-md shadow-[#99ff04]/25'
                                    : 'bg-[#231f36] text-slate-300 border-white/10 hover:border-white/30 font-bold'"
                                class="rounded-2xl border px-2 py-3 text-sm tabular-nums transition-all cursor-pointer select-none"
                                x-text="ringkas(angka)"></button>
                    </template>

                    <button type="button" @click="bukaManual()"
                            :class="manual
                                ? 'bg-[#99ff04] text-black border-[#99ff04] font-black shadow-md shadow-[#99ff04]/25'
                                : 'bg-[#231f36] text-slate-300 border-white/10 hover:border-white/30 font-bold'"
                            class="rounded-2xl border px-2 py-3 text-sm transition-all cursor-pointer select-none">
                        Lainnya
                    </button>
                </div>
            </div>

            <div x-show="manual" x-cloak x-collapse>
                <label for="amount" class="dt-label">Isi nominal sendiri</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 grid w-11 place-items-center text-sm font-black text-slate-400" aria-hidden="true">Rp</span>
                    {{-- wire:model tanpa .live: tidak ada permintaan per ketikan --}}
                    <input id="amount" type="text" inputmode="numeric" x-ref="inputNominal"
                           wire:model="amount"
                           @input="ketik($event)"
                           placeholder="0" autocomplete="off"
                           class="dt-input pl-11 text-lg font-bold tabular-nums"
                           aria-describedby="amount-hint">
                </div>

                <p id="amount-hint" class="dt-hint" x-show="!pesanNominal">
                    Nominal bebas (mulai {{ rupiah(config('donasi.min_donation')) }} hingga {{ rupiah_ringkas(config('donasi.max_donation')) }}).
                </p>
                <p class="dt-error" x-show="pesanNominal" x-cloak x-text="pesanNominal"></p>

                @error('amount') <p class="dt-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="donorName" class="dt-label">Nama {{ auth()->check() ? '' : '(opsional)' }}</label>
                <input id="donorName" type="text" wire:model="donorName" class="dt-input" placeholder="Nama Anda">
                @error('donorName') <p class="dt-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="donorEmail" class="dt-label">Email {{ auth()->check() ? '' : '(opsional)' }}</label>
                <input id="donorEmail" type="email" wire:model="donorEmail" class="dt-input" placeholder="email@contoh.com">
                <p class="dt-hint">Kuitansi digital dikirim ke alamat ini jika diisi.</p>
                @error('donorEmail') <p class="dt-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="note" class="dt-label">Pesan dukungan (opsional)</label>
                <textarea id="note" rows="2" wire:model="note" class="dt-input" maxlength="500"
                          placeholder="Semoga bermanfaat."></textarea>
                @error('note') <p class="dt-error">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-start gap-2.5 text-xs sm:text-sm font-semibold text-slate-300 select-none cursor-pointer">
                <input type="checkbox" wire:model.live="isAnonymous"
                       class="mt-0.5 h-4 w-4 rounded border-white/20 bg-[#231f36] text-[#99ff04] focus:ring-1 focus:ring-[#99ff04] accent-[#99ff04]">
                <span>
                    Sembunyikan nama saya
                    @if ($isAnonymous)
                        <span class="block text-xs text-slate-400 font-normal">Akan tampil sebagai &ldquo;Hamba Allah&rdquo; di daftar donatur.</span>
                    @endif
                </span>
            </label>

            {{-- Ringkasan muncul seketika, tanpa menunggu server --}}
            <div x-show="valid()" x-cloak x-collapse
                 class="rounded-2xl border border-white/10 bg-[#231f36]/70 p-4 text-sm shadow-md backdrop-blur-md">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Total donasi</span>
                    <span class="text-xl font-black text-white tabular-nums" x-text="penuh(nominal)"></span>
                </div>
                <div x-show="nominal > sisaTarget && sisaTarget > 0" x-cloak class="mt-3 rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-amber-300 leading-relaxed">
                    <strong class="font-extrabold text-amber-200">Catatan Transparansi:</strong> Nominal donasi Anda melebihi sisa kebutuhan target (<span x-text="penuh(sisaTarget)"></span>). Kelebihan dana akan tetap dicatat secara transparan di ledger untuk cadangan operasional/lanjutan.
                </div>
                <p class="mt-2 text-[11px] font-medium text-slate-400">
                    Kuitansi diverifikasi dengan HMAC SHA-256 dan dicatat di ledger publik.
                </p>
            </div>

            <button type="button"
                    @click="valid() && (bukaModalKonfirmasi = true)"
                    class="dt-btn-primary w-full py-3 text-base cursor-pointer"
                    :disabled="!valid()">
                <span x-show="!valid()">Pilih nominal dulu</span>
                <span x-show="valid()" x-cloak>Lanjut ke pembayaran &middot; <span x-text="penuh(nominal)"></span></span>
            </button>

            <p class="text-center text-xs text-slate-400">
                Mode simulasi &mdash; tidak ada uang sungguhan yang berpindah.
            </p>

            {{-- MODAL KONFIRMASI DONASI (Mobile: Bottom Sheet Drawer, Desktop: Centered Glassmorphism Modal) --}}
            <div x-show="bukaModalKonfirmasi" x-cloak
                 class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
                 role="dialog" aria-modal="true"
                 @keydown.escape.window="bukaModalKonfirmasi = false">

                {{-- Backdrop blur & darken --}}
                <div x-show="bukaModalKonfirmasi"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-black/80 backdrop-blur-md"
                     @click="bukaModalKonfirmasi = false"
                     aria-hidden="true"></div>

                {{-- Modal Box --}}
                <div x-show="bukaModalKonfirmasi"
                     x-transition:enter="transition ease-out duration-300 transform"
                     x-transition:enter-start="translate-y-full sm:translate-y-0 sm:scale-95 sm:opacity-0"
                     x-transition:enter-end="translate-y-0 sm:scale-100 sm:opacity-100"
                     x-transition:leave="transition ease-in duration-200 transform"
                     x-transition:leave-start="translate-y-0 sm:scale-100 sm:opacity-100"
                     x-transition:leave-end="translate-y-full sm:translate-y-0 sm:scale-95 sm:opacity-0"
                     class="relative w-full sm:max-w-md rounded-t-[2.5rem] sm:rounded-3xl border border-white/15 bg-[#1b182a]/95 backdrop-blur-xl p-6 sm:p-8 shadow-2xl z-10 max-h-[90vh] overflow-y-auto"
                     @click.stop>

                    {{-- Drag handle untuk Mobile Bottom Sheet Drawer --}}
                    <div class="mx-auto mb-4 h-1.5 w-12 rounded-full bg-white/25 sm:hidden"></div>

                    <div class="flex items-center gap-3.5">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#99ff04]/20 text-[#99ff04] shadow-inner">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-black text-white">Konfirmasi Donasi</h3>
                            <p class="text-xs text-slate-400">Pastikan rincian donasi Anda sudah sesuai.</p>
                        </div>
                    </div>

                    <div class="my-5 rounded-2xl border border-white/10 bg-[#231f36]/80 p-4 space-y-2.5 text-xs text-left">
                        <div class="flex justify-between items-center text-slate-400">
                            <span>Kampanye</span>
                            <span class="truncate max-w-[180px] font-medium text-white">{{ $campaign->title }}</span>
                        </div>
                        <div class="flex justify-between items-center text-slate-400">
                            <span>Atas Nama</span>
                            <span class="font-bold text-white" x-text="$wire.isAnonymous ? 'Hamba Allah (Anonim)' : ($wire.donorName ? $wire.donorName.trim() || 'Donatur' : 'Donatur')"></span>
                        </div>
                        <div class="flex justify-between items-center text-slate-400">
                            <span>Email Kuitansi</span>
                            <span class="font-medium text-white truncate max-w-[180px]" x-text="$wire.donorEmail ? $wire.donorEmail.trim() || 'Tidak dicantumkan' : 'Tidak dicantumkan'"></span>
                        </div>
                        <div x-show="$wire.note && $wire.note.trim()" class="flex justify-between items-start text-slate-400">
                            <span class="shrink-0">Pesan</span>
                            <span class="font-medium text-slate-200 text-right max-w-[200px] italic" x-text="$wire.note"></span>
                        </div>
                        <div class="border-t border-white/10 pt-2.5 flex justify-between items-center">
                            <span class="font-bold text-slate-300">Total Donasi</span>
                            <span class="text-xl font-black text-[#99ff04]" x-text="penuh(nominal)"></span>
                        </div>
                    </div>

                    <div class="space-y-2.5">
                        <button type="button"
                                wire:click="submit"
                                wire:loading.attr="disabled"
                                class="flex w-full items-center justify-center gap-2 rounded-full bg-[#99ff04] py-3.5 px-6 text-sm font-black text-black hover:bg-[#84e000] shadow-xl shadow-[#99ff04]/20 transition-transform active:scale-95 disabled:opacity-50 cursor-pointer">
                            <span wire:loading.remove wire:target="submit" class="flex items-center gap-2">
                                Lanjut ke Pembayaran &rarr;
                            </span>
                            <span wire:loading wire:target="submit" class="flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity="0.25"/>
                                    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                                </svg>
                                Memproses&hellip;
                            </span>
                        </button>
                        <button type="button" @click="bukaModalKonfirmasi = false"
                                class="w-full py-2.5 text-xs font-bold text-slate-400 hover:text-white transition-colors cursor-pointer">
                            Ubah Rincian Donasi
                        </button>
                    </div>
                </div>
            </div>
        </form>
    @endif
</div>

@push('head')
<script>
    document.addEventListener('alpine:init', () => {
    Alpine.data('formDonasi', (awal) => ({
        nominal: awal.nominal || 0,
        cepat: awal.cepat,
        min: awal.min,
        max: awal.max,
        sisaTarget: awal.sisaTarget || 0,
        bukaModalKonfirmasi: false,
        // true bila pengguna memakai kolom isian sendiri, bukan tombol cepat
        manual: awal.nominal > 0 && ! awal.cepat.includes(awal.nominal),

        init() {
            if (this.manual) {
                this.$refs.inputNominal.value = this.penuhTanpaRp(this.nominal);
            }
        },

        pilihCepat(angka) {
            // Menekan tombol yang sama lagi berarti membatalkan pilihan.
            this.nominal = this.nominal === angka && ! this.manual ? 0 : angka;
            this.manual = false;
            this.$refs.inputNominal.value = '';

            // Nilainya HARUS ikut dikirim ke Livewire. Sebelumnya baris ini
            // mengirim string kosong, jadi tombol nominal cepat terlihat
            // terpilih di layar tetapi server menerima amount kosong dan
            // menolak dengan "Pilih atau isi nominal donasi."
            this.sinkron(this.nominal ? this.penuhTanpaRp(this.nominal) : '');
        },

        bukaManual() {
            this.manual = true;
            this.nominal = 0;
            this.$refs.inputNominal.value = '';
            this.sinkron('');
            this.$nextTick(() => this.$refs.inputNominal.focus());
        },

        /**
         * Memformat sambil mengetik TANPA melempar kursor ke ujung: posisi
         * kursor dihitung dari sisi kanan, sehingga titik yang muncul otomatis
         * tidak mengganggu pengetikan di tengah angka.
         */
        ketik(event) {
            const input = event.target;
            const sebelum = input.value;
            const dariKanan = sebelum.length - (input.selectionEnd ?? sebelum.length);

            const angka = sebelum.replace(/\D/g, '').slice(0, 12);
            const terformat = angka ? this.penuhTanpaRp(Number(angka)) : '';

            input.value = terformat;
            const posisi = Math.max(0, terformat.length - dariKanan);
            input.setSelectionRange(posisi, posisi);

            this.nominal = Number(angka) || 0;
            this.sinkron(terformat);
        },

        /* Simpan ke state Livewire tanpa memanggil server; terkirim saat submit. */
        sinkron(nilai) {
            this.$wire.amount = nilai;
        },

        valid() {
            return this.nominal >= this.min && this.nominal <= this.max;
        },

        get pesanNominal() {
            if (this.nominal === 0) return '';
            if (this.nominal < this.min) return `Minimal ${this.penuh(this.min)}.`;
            if (this.nominal > this.max) return `Maksimal ${this.penuh(this.max)}.`;
            return '';
        },

        penuhTanpaRp(n) {
            return new Intl.NumberFormat('id-ID').format(n);
        },

        penuh(n) {
            return 'Rp' + this.penuhTanpaRp(n);
        },

        ringkas(n) {
            if (n >= 1_000_000) return 'Rp' + (n / 1_000_000).toString().replace('.', ',') + ' jt';
            if (n >= 1_000) return 'Rp' + Math.round(n / 1_000) + ' rb';
            return 'Rp' + n;
        },
    }));
    });
</script>
@endpush
