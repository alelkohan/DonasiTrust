{{--
    Pemilihan nominal ditangani sepenuhnya di sisi peramban (Alpine), bukan
    lewat Livewire.

    Sebelumnya input nominal memakai wire:model.live, jadi setiap ketikan
    memicu permintaan ke server — dan karena Alpine ikut memformat ulang isi
    input di saat yang sama, kursor melompat ke ujung kanan saat mengetik di
    tengah angka. Sekarang nilainya baru dikirim ke server saat form disubmit.
--}}
<div class="dt-card p-5 sm:p-6">
    <h2 class="text-lg font-bold text-ink-900">Donasi sekarang</h2>
    <p class="mt-1 text-sm text-ink-600">
        Setiap donasi menerbitkan kuitansi digital dengan kode yang bisa diverifikasi publik.
    </p>

    <form wire:submit="submit" class="mt-5 space-y-4"
          x-data="formDonasi({
              nominal: {{ (int) $this->amountValue }},
              cepat: {{ Illuminate\Support\Js::from($quickAmounts) }},
              min: {{ (int) config('donasi.min_donation') }},
              max: {{ (int) config('donasi.max_donation') }},
          })">

        <div>
            <span class="dt-label">Pilih nominal</span>
            <div class="grid grid-cols-3 gap-2">
                <template x-for="angka in cepat" :key="angka">
                    <button type="button" @click="pilihCepat(angka)"
                            :class="nominal === angka && !manual
                                ? 'border-brand-600 bg-brand-50 text-brand-800'
                                : 'border-ink-200 text-ink-700 hover:border-brand-300 hover:bg-brand-50/50'"
                            class="rounded-xl border px-2 py-2.5 text-sm font-semibold tabular-nums transition-colors"
                            x-text="ringkas(angka)"></button>
                </template>

                <button type="button" @click="bukaManual()"
                        :class="manual
                            ? 'border-brand-600 bg-brand-50 text-brand-800'
                            : 'border-ink-200 text-ink-700 hover:border-brand-300 hover:bg-brand-50/50'"
                        class="rounded-xl border px-2 py-2.5 text-sm font-semibold transition-colors">
                    Lainnya
                </button>
            </div>
        </div>

        <div x-show="manual" x-cloak x-collapse>
            <label for="amount" class="dt-label">Isi nominal sendiri</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 grid w-11 place-items-center text-sm font-semibold text-ink-500" aria-hidden="true">Rp</span>
                {{-- wire:model tanpa .live: tidak ada permintaan per ketikan --}}
                <input id="amount" type="text" inputmode="numeric" x-ref="inputNominal"
                       wire:model="amount"
                       @input="ketik($event)"
                       placeholder="0" autocomplete="off"
                       class="dt-input pl-11 text-lg font-bold tabular-nums"
                       aria-describedby="amount-hint">
            </div>

            <p id="amount-hint" class="dt-hint" x-show="!pesanNominal">
                Minimal {{ rupiah(config('donasi.min_donation')) }}, maksimal {{ rupiah_ringkas(config('donasi.max_donation')) }}.
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
            <label for="donorEmail" class="dt-label">Email</label>
            <input id="donorEmail" type="email" wire:model="donorEmail" class="dt-input" placeholder="email@contoh.com">
            <p class="dt-hint">Kuitansi digital dikirim ke alamat ini.</p>
            @error('donorEmail') <p class="dt-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="note" class="dt-label">Pesan dukungan (opsional)</label>
            <textarea id="note" rows="2" wire:model="note" class="dt-input" maxlength="500"
                      placeholder="Semoga bermanfaat."></textarea>
            @error('note') <p class="dt-error">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-start gap-2.5 text-sm text-ink-700">
            <input type="checkbox" wire:model.live="isAnonymous"
                   class="mt-0.5 h-4 w-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500">
            <span>
                Sembunyikan nama saya
                @if ($isAnonymous)
                    <span class="block text-xs text-ink-500">Akan tampil sebagai &ldquo;Hamba Allah&rdquo; di daftar donatur.</span>
                @endif
            </span>
        </label>

        {{-- Ringkasan muncul seketika, tanpa menunggu server --}}
        <div x-show="valid()" x-cloak x-collapse
             class="rounded-xl border border-brand-200 bg-brand-50/70 p-3.5 text-sm">
            <div class="flex items-baseline justify-between gap-2">
                <span class="text-brand-900">Total dibayar</span>
                <span class="text-lg font-extrabold text-brand-800 tabular-nums" x-text="penuh(nominal)"></span>
            </div>
            <p class="mt-1.5 text-xs leading-relaxed text-brand-900/80">
                Dana masuk ke saldo kampanye dan hanya bisa dicairkan per tahap setelah disetujui admin.
            </p>
        </div>

        <button type="submit" class="dt-btn-primary w-full py-3 text-base"
                wire:loading.attr="disabled" :disabled="!valid()">
            <span wire:loading.remove wire:target="submit">
                <span x-show="!valid()">Pilih nominal dulu</span>
                <span x-show="valid()" x-cloak>Lanjut ke pembayaran &middot; <span x-text="penuh(nominal)"></span></span>
            </span>
            <span wire:loading wire:target="submit" class="inline-flex items-center gap-2">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity="0.25"/>
                    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                </svg>
                Memproses&hellip;
            </span>
        </button>

        <p class="text-center text-xs text-ink-500">
            Mode simulasi &mdash; tidak ada uang sungguhan yang berpindah.
        </p>
    </form>
</div>

{{--
    Didaftarkan lewat alpine:init di @push('head'), sama seperti komponen
    Alpine lain di aplikasi ini — bukan @script. @script terikat pada Alpine
    milik Livewire; selama masih ada Alpine kedua dari bundel Vite, pendaftaran
    lewat @script tidak terlihat oleh Alpine yang menginisialisasi x-data ini.
--}}
@push('head')
<script>
    document.addEventListener('alpine:init', () => {
    Alpine.data('formDonasi', (awal) => ({
        nominal: awal.nominal || 0,
        cepat: awal.cepat,
        min: awal.min,
        max: awal.max,
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
