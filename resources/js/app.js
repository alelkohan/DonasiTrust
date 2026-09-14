import './bootstrap';

import QRCode from 'qrcode';
import AOS from 'aos';
import 'aos/dist/aos.css';

const initAos = () => {
    AOS.init({
        duration: 750,
        easing: 'ease-out-cubic',
        once: true,
        offset: 60,
    });
};

document.addEventListener('DOMContentLoaded', initAos);
document.addEventListener('livewire:navigated', () => {
    setTimeout(() => {
        AOS.refresh();
    }, 100);
});

/*
| Alpine SENGAJA tidak diimpor di sini.
|
| @livewireScripts sudah membawa Alpine lengkap dengan plugin collapse,
| persist, intersect, mask, dan anchor. Kalau berkas ini ikut mengimpor
| alpinejs dari npm lalu memanggil Alpine.start(), ada DUA Alpine yang
| berjalan di satu halaman: keduanya memindai DOM yang sama, dan komponen
| yang didaftarkan di salah satunya tidak dikenali oleh yang lain
| ("formDonasi is not defined"). Itulah sebabnya tombol nominal cepat pada
| form donasi tidak muncul dan tulisan di tombol kirim hilang.
|
| Komponen Alpine didaftarkan lewat <script> di @push('head') pada masing-
| masing tampilan, memakai alpine:init — skrip head dieksekusi saat halaman
| diurai, jadi selalu lebih dulu daripada livewire.js di akhir <body>.
*/

/*
|------------------------------------------------------------------------------
| Status memuat pada tombol kirim
|------------------------------------------------------------------------------
| Sebelum ini, menekan "Setujui" tidak memunculkan tanda apa pun sampai halaman
| berganti. Di koneksi lambat pengguna menekannya dua kali — dan untuk aksi
| seperti menyetujui pencairan, klik ganda bukan cuma masalah rasa.
|
| Dipasang sekali di sini, berlaku untuk seluruh form biasa di aplikasi.
| Form Livewire dilewati karena sudah punya wire:loading sendiri.
*/
const SPINNER = `<svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity="0.25"/>
    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
</svg>`;

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)) return;

    // Livewire mengurus status memuatnya sendiri; jangan diganggu.
    if (form.hasAttribute('wire:submit') || form.hasAttribute('data-no-loading')) return;

    // Pengiriman kedua saat yang pertama masih berjalan: tahan.
    if (form.dataset.submitting === '1') {
        event.preventDefault();
        return;
    }

    const button = event.submitter || form.querySelector('button[type="submit"]');

    if (!button) return;

    form.dataset.submitting = '1';

    // Dinonaktifkan SETELAH peristiwa submit selesai diproses, supaya
    // name/value tombolnya tetap ikut terkirim ke server.
    setTimeout(() => {
        button.dataset.originalHtml = button.innerHTML;
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.innerHTML = `${SPINNER}<span>${button.textContent.trim() || 'Memproses'}&hellip;</span>`;
    }, 0);
});

// Kalau pengguna menekan tombol "kembali", browser bisa memulihkan halaman dari
// cache dengan tombol masih dalam keadaan nonaktif. Kembalikan seperti semula.
window.addEventListener('pageshow', () => {
    document.querySelectorAll('form[data-submitting="1"]').forEach((form) => {
        form.dataset.submitting = '0';
    });

    document.querySelectorAll('button[data-original-html]').forEach((button) => {
        button.innerHTML = button.dataset.originalHtml;
        button.disabled = false;
        button.removeAttribute('aria-busy');
        delete button.dataset.originalHtml;
    });
});

/*
|------------------------------------------------------------------------------
| Input rupiah berformat
|------------------------------------------------------------------------------
| Tandai input dengan data-rupiah. Saat diketik tampil 1.500.000 supaya mudah
| dibaca dan salah satu digit langsung kelihatan; tepat sebelum dikirim,
| pemisah ribuannya dilucuti lagi jadi angka polos untuk server.
*/
const formatRupiah = (value) => {
    const digits = String(value).replace(/\D/g, '');

    return digits ? new Intl.NumberFormat('id-ID').format(Number(digits)) : '';
};

document.addEventListener('input', (event) => {
    const input = event.target;

    if (!(input instanceof HTMLInputElement) || !input.hasAttribute('data-rupiah')) return;

    const sebelumnya = input.value;
    const kursorDariBelakang = sebelumnya.length - (input.selectionEnd ?? sebelumnya.length);

    input.value = formatRupiah(sebelumnya);

    // Pertahankan posisi kursor relatif terhadap ujung kanan, supaya titik yang
    // muncul otomatis tidak melempar kursor ke belakang saat mengetik di tengah.
    const posisi = Math.max(0, input.value.length - kursorDariBelakang);
    input.setSelectionRange(posisi, posisi);
});

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)) return;

    form.querySelectorAll('input[data-rupiah]').forEach((input) => {
        input.value = String(input.value).replace(/\D/g, '');
    });
}, true); // fase capture: harus jalan sebelum Livewire/handler lain membaca nilainya

// Format nilai awal yang sudah terisi (misalnya setelah validasi gagal).
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[data-rupiah]').forEach((input) => {
        input.value = formatRupiah(input.value);
    });
});

/*
|------------------------------------------------------------------------------
| Kode QR pada halaman pembayaran
|------------------------------------------------------------------------------
| Menggambar isi atribut data-qr ke <canvas>. Dipakai di halaman checkout
| menggantikan kotak putus-putus kosong yang sebelumnya cuma bertuliskan
| "kode QR muncul di sini" — untuk demo, kotak kosong terbaca sebagai fitur
| yang belum jadi.
|
| Isinya kode transaksi simulasi, bukan QRIS sungguhan. Labelnya di halaman
| itu menyatakan hal ini secara terbuka.
*/
const gambarQr = () => {
    document.querySelectorAll('canvas[data-qr]').forEach((canvas) => {
        if (canvas.dataset.qrRendered === '1') return;

        QRCode.toCanvas(canvas, canvas.dataset.qr, {
            width: 176,
            margin: 0,
            errorCorrectionLevel: 'M',
            color: { dark: '#0f172a', light: '#ffffff' },
        }, (error) => {
            if (error) {
                console.error('[DonasiTrust] gagal menggambar QR:', error);
                return;
            }

            canvas.dataset.qrRendered = '1';
        });
    });
};

document.addEventListener('DOMContentLoaded', gambarQr);
document.addEventListener('livewire:navigated', gambarQr);

/*
|------------------------------------------------------------------------------
| Pendaftaran Komponen Alpine.js
|------------------------------------------------------------------------------
| Didaftarkan secara global agar selalu siap ketika navigasi SPA (wire:navigate)
| berpindah antar halaman tanpa hard reload.
*/
const registerAlpineComponents = () => {
    if (!window.Alpine) return;

    if (!window.Alpine.data('formKampanye')) {
        window.Alpine.data('formKampanye', (awal = {}) => ({
            items: awal?.items || [],
            milestones: awal?.milestones || [],
            isSubmitting: false,
            errorMessage: '',
            errorList: [],
            successMessage: '',

            get target() {
                return this.totalItems;
            },

            get totalItems() {
                return (this.items || []).reduce((n, i) => n + (Number(i.quantity) || 0) * (Number(i.unit_price) || 0), 0);
            },

            get totalMilestones() {
                return (this.milestones || []).reduce((n, m) => n + (Number(m.amount) || 0), 0);
            },

            format(value) {
                return 'Rp' + new Intl.NumberFormat('id-ID').format(Math.round(value || 0));
            },

            async submitForm(event) {
                if (this.isSubmitting) return;

                this.errorMessage = '';
                this.errorList = [];
                this.successMessage = '';

                const form = event.target;
                const formData = new FormData(form);

                this.isSubmitting = true;

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: formData
                    });

                    const data = await response.json().catch(() => ({}));

                    if (response.ok && data.success) {
                        if (data.redirect) {
                            if (window.Livewire && typeof window.Livewire.navigate === 'function') {
                                window.Livewire.navigate(data.redirect);
                            } else {
                                window.location.href = data.redirect;
                            }
                            return;
                        }
                        this.successMessage = data.message || 'Perubahan draf kampanye berhasil disimpan.';
                        this.isSubmitting = false;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    } else {
                        this.isSubmitting = false;
                        if (data.errors) {
                            this.errorMessage = data.message || 'Terdapat kesalahan pengisian formulir:';
                            this.errorList = Object.values(data.errors).flat();
                        } else {
                            this.errorMessage = data.message || 'Terjadi kesalahan saat menyimpan data kampanye.';
                        }
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                } catch (err) {
                    this.isSubmitting = false;
                    this.errorMessage = 'Terjadi gangguan koneksi internet. Silakan coba lagi.';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }
        }));
    }

    if (!window.Alpine.data('hitungMundur')) {
        window.Alpine.data('hitungMundur', ({ sampai, habis }) => ({
            habis,
            sisa: '',
            timer: null,

            init() {
                this.hitung();
                this.timer = setInterval(() => this.hitung(), 1000);
            },

            destroy() {
                clearInterval(this.timer);
            },

            hitung() {
                const selisih = new Date(sampai).getTime() - Date.now();

                if (selisih <= 0) {
                    this.habis = true;
                    this.sisa = '00:00:00';
                    clearInterval(this.timer);
                    return;
                }

                const total = Math.floor(selisih / 1000);
                const jam = String(Math.floor(total / 3600)).padStart(2, '0');
                const menit = String(Math.floor((total % 3600) / 60)).padStart(2, '0');
                const detik = String(total % 60).padStart(2, '0');

                this.sisa = `${jam}:${menit}:${detik}`;
            },
        }));
    }
};

document.addEventListener('alpine:init', registerAlpineComponents);
document.addEventListener('livewire:navigated', registerAlpineComponents);
document.addEventListener('DOMContentLoaded', registerAlpineComponents);
registerAlpineComponents();
