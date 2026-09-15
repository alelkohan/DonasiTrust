# DonasiTrust

Platform donasi transparan dengan pencairan dana bertahap, kuitansi terverifikasi HMAC-SHA256, jejak audit ber-rantai-hash, dan sistem audit anggaran berbasis AI (Google Gemini).
Dibangun untuk **Web Development Competition SwitchFest 2026** (HMJ TI UIN Walisongo Semarang).

Tema lomba: *NextGen Secure: Building the Future of Trusted Web Ecosystems*.

---

## Masalah yang Dijawab

Di kebanyakan platform donasi konvensional, jejak uang donatur terputus begitu pembayaran selesai. Masyarakat luar dan donatur tidak memiliki instrumen untuk memeriksa apakah dana benar-benar dipakai sesuai janji atau dimanipulasi di tengah jalan.

DonasiTrust memaksakan enam aturan integritas lewat sistem, bukan sekadar janji:

1. **Pencairan bertahap (*Milestone-based*).** Target donasi wajib dipecah menjadi tahapan yang totalnya sama persis dengan Rincian Anggaran Biaya (RAB). Tahap berikutnya terkunci rapat sampai tahap sebelumnya selesai dibelanjakan, dilaporkan dengan bukti nota sah (LPJ), dan disetujui admin.
2. **AI Budget & Anti-Fraud Auditor (Google Gemini).** Setiap pengajuan kampanye dianalisis secara otomatis oleh AI untuk mendeteksi anomali markup harga RAB, penulisan deskripsi *dummy/gibberish*, cerita yang tidak memuat konteks urgensi, serta item anggaran fiktif.
3. **Kuitansi terverifikasi publik.** Setiap donasi menghasilkan kode kriptografis HMAC-SHA256 atas nomor transaksi, nominal, dan ID kampanye. Siapa pun dapat menguji keasliannya di `/verifikasi` tanpa harus mendaftar atau login.
4. **Jejak audit ber-rantai hash (*Tamper-evident Audit Trail*).** Setiap catatan audit menyimpan hash entri sebelumnya layaknya mini-blockchain. Mengubah satu catatan lama di database secara ilegal membuat seluruh rantai sesudahnya gagal diverifikasi di `/transparansi`.
5. **Rekening tujuan terkunci.** Dana donasi hanya bisa mengalir ke rekening bank yang telah diperiksa admin bersama dokumen identitas KTP. Rekening disalin dan dibekukan per pengajuan, sehingga akun pengaju yang dibajak sekalipun tidak bisa mengalihkan dana ke pihak lain.
6. **Perlindungan OTP Email di titik-titik berisiko.** Verifikasi dua langkah (OTP Email 6 digit acak) ditempatkan secara terukur pada 3 aksi paling rawan: **mengganti rekening pencairan**, **mengajukan pencairan tahap**, dan **melepas dana transfer**.

---

## Stack Teknologi

| Komponen | Pilihan Teknologi |
|---|---|
| Framework | Laravel 13 (PHP 8.3+) |
| Database | MySQL 8 (Kompatibel juga dengan SQLite untuk pengujian cepat) |
| Frontend | Blade + Livewire 3 + Alpine.js + Tailwind CSS 4 |
| Mesin AI | Google Gemini API (1.5 Flash) + Heuristic Fallback Engine |
| Autentikasi | Laravel Session Auth + Google OAuth 2.0 |
| PWA & Mobile | Service Worker + Web App Manifest + Native Install Prompt |
| Pembayaran | Gateway simulasi internal + Dynamic QR Code Canvas (Siap integrasi Midtrans) |
| Build Tool | Vite |

---

## Menjalankan di Komputer Lokal

Prasyarat: PHP 8.3+, Composer, Node.js 20+, dan MySQL (Laragon / XAMPP / Laravel Herd).

```bash
# 1. Unduh dependensi
composer install
npm install

# 2. Konfigurasi Environment
cp .env.example .env
php artisan key:generate

# 3. Buat database bernama "donasitrust" di MySQL, lalu sesuaikan
#    DB_USERNAME / DB_PASSWORD di file .env bila diperlukan.

# 4. WAJIB: Isi secret kuitansi & API Key Gemini di .env
#    DONASI_RECEIPT_SECRET=<string acak panjang>
#    GEMINI_API_KEY=<opsional: api key gemini Anda>
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"   # untuk membuat string acak kuitansi

# 5. Jalankan migrasi dan data demo
php artisan migrate --seed

# 6. Jalankan server (buka dua terminal)
php artisan serve
npm run dev
```

Buka aplikasi di peramban: <http://localhost:8000>.

### Alternatif Tanpa MySQL (SQLite)

Cukup sesuaikan di `.env`:
```env
DB_CONNECTION=sqlite
```
Lalu jalankan `touch database/database.sqlite` dan `php artisan migrate --seed`.

---

## Akun Demo

Semua akun demo menggunakan kata sandi bawaan: `password123`.

| Email | Peran | Kondisi Awal |
|---|---|---|
| `admin@donasitrust.test` | Administrator | Memiliki antrean review kampanye, verifikasi KTP, dan LPJ |
| `pengaju@donasitrust.test` | Pengaju Kampanye | Terverifikasi identitas, memiliki kampanye di berbagai tahapan |
| `pengaju2@donasitrust.test` | Pengaju Kampanye | Menunggu peninjauan identitas |
| `donatur@donasitrust.test` | Donatur | Memiliki riwayat donasi dan kuitansi pembayaran |

> **Catatan Pengujian OTP Email Demo:**
> Pada pengujian lokal dengan driver email `log`, kode verifikasi OTP 6 digit yang dikirim dapat langsung dilihat di antrean log atau di jendela notifikasi aplikasi saat aksi dijalankan.

---

## Alur Demo yang Disarankan (Presentasi 5 Menit)

1. **Beranda → Detail Kampanye**: Tunjukkan transparansi Rincian Anggaran Biaya (RAB) terbuka dan pembagian tahapan pencairan dana (*milestones*).
2. **Donasi & Pembayaran**: Lakukan donasi dengan nominal bebas → halaman pembayaran QR Code interaktif → klik *Simulasikan Pembayaran Berhasil*.
3. **Penerbitan Kuitansi Terverifikasi**: Kuitansi sah diterbitkan dengan kode HMAC-SHA256 unik. Salin kode verifikasinya.
4. **Verifikasi Publik di `/verifikasi`**: Buka menu verifikasi kuitansi (bisa di mode penyamaran/tanpa login) → masukkan nomor transaksi dan kode → sistem mengonfirmasi keaslian. Ubah 1 karakter kode untuk membuktikan sistem anti-pemalsuan.
5. **Ledger Publik & Rantai Audit di `/transparansi`**: Tunjukkan angka donasi yang bertambah seketika dan status keutuhan rantai hash audit trail yang divalidasi secara matematis.
6. **Login Admin & AI Budget Auditor**: Masuk sebagai admin → buka menu review kampanye berstatus *Pending* → tunjukkan kartu **Analisis AI Auditor** yang menguji kewajaran harga RAB, mendeteksi teks asal-asalan, dan mengevaluasi kelayakan proposal.
7. **Modal Konfirmasi & Gerbang OTP Email**:
   - Sebagai admin, setujui kampanye melalui modal konfirmasi kustom.
   - Buka menu pencairan dana → masukkan kode OTP Email untuk melepaskan dana.
   - Sebagai pengaju, tunjukkan perlindungan OTP Email saat mencoba mengubah rekening bank pencairan di halaman verifikasi identitas.

---

## Struktur Direktori Utama

```
app/
├── Http/Controllers/
│   ├── Admin/              Review Kampanye, Pencairan, LPJ, Verifikasi Identitas
│   ├── Auth/               Login, Register, Google OAuth, Ganti Password
│   ├── Pengaju/            Manajemen Kampanye, Pencairan Tahap, Unggah LPJ
│   └── Public              Eksplorasi Kampanye, Donasi, Kuitansi, Transparansi
├── Livewire/               Form Donasi Interaktif & Real-time
├── Models/                 User, Campaign, CampaignItem, Milestone, Donation,
│                           Disbursement, ExpenseReport, AuditLog, EmailOtp
├── Services/
│   ├── AuditLogger         Pencatat & pemverifikasi rantai hash SHA-256
│   ├── CampaignAiAuditor   Mesin AI analisis RAB & deteksi fraud (Google Gemini)
│   ├── DonationService     Pembuat transaksi donasi & pelunasan idempotent
│   ├── OtpService          Pengelola siklus hidup OTP Email (expiry, limit, cooldown)
│   └── ReceiptVerifier     Generator & pemverifikasi HMAC-SHA256 kuitansi
└── Support/                Format Rupiah & Helper Menu
```

---

## Filosofi Penempatan Gerbang Keamanan (OTP Email)

Platform donasi dunia nyata tidak memaksakan verifikasi berbelit-belit di setiap pintu masuk. Pengaju kampanye sering kali adalah pengurus rumah ibadah, keluarga pasien, atau relawan lapangan yang membutuhkan sistem yang ramah dan mudah digunakan.

Oleh karena itu, kami menempatkan verifikasi keamanan dua langkah di **titik-titik yang benar-benar mengubah alur risiko keuangan**:

| Titik Aksi | Mekanisme Proteksi | Alasan Desain |
|---|---|---|
| Donatur Berdonasi | Dikelola Payment Gateway | Autentikasi berada di sisi perbankan/e-wallet donatur |
| Pengaju Mengajukan Pencairan | **Wajib OTP Email** | Memastikan pengajuan dilakukan secara sadar oleh pemilik sah |
| Pengaju **Mengganti Rekening** | **Wajib OTP Email** + Notifikasi Surel | Satu-satunya celah dana dialihkan ke pihak lain jika akun dibajak |
| Admin **Melepas Dana Transfer** | **Wajib OTP Email** | Titik pelepasan dana yang tidak dapat ditarik kembali |

---

## Catatan Keamanan & Batasan Sistem

Yang **sudah** dijamin dan diterapkan:
- Seluruh kata sandi di-hash aman dengan algoritma *bcrypt*.
- Seluruh query database menggunakan parameter binding Eloquent ORM (kebal terhadap SQL Injection).
- Otorisasi ketat berbasis peran (*Role Middleware*) dan kepemilikan objek (*CampaignPolicy*).
- Dokumen KTP dan nota LPJ disimpan di disk privat terenkripsi (`storage/app/private`), hanya dapat diakses melalui endpoint yang menguji hak akses otorisasi.
- NIK lengkap **tidak pernah disimpan** di database (hanya 4 digit terakhir untuk keperluan verifikasi audit).
- Sistem deteksi fraud cerdas berbasis AI untuk menyaring kampanye bermasalah sebelum tayang ke publik.
- Dukungan PWA untuk pengalaman aplikasi mobile yang cepat dan ringan.

Yang **secara terbuka belum** dijamin:
- **Kode Kuitansi adalah MAC (HMAC-SHA256), bukan Digital Signature Asimetris**: Membuktikan kuitansi diterbitkan secara sah oleh platform DonasiTrust. Tanda tangan digital identitas penuh membutuhkan pasangan kunci publik-privat (RSA/ECDSA).
- **Rantai Hash Audit bersifat *Tamper-evident***: Membuktikan ada atau tidaknya manipulasi data historis.
- **Verifikasi Fisik Lapangan Dilakukan Admin**: Belum terhubung dengan API Dukcapil instansi pemerintah.

---

## Rencana Pengembangan Lanjutan

- Ekspor Kuitansi & Laporan LPJ dalam format PDF resmi (`barryvdh/laravel-dompdf`).
- Integrasi *Disbursement API Gateway* (Xendit/Flip) untuk transfer dana otomatis ke rekening bank pengaju.
- Integrasi Midtrans / Payment Gateway Production Sandbox.
- Pengiriman OTP melalui WhatsApp Business API sebagai alternatif selain surel.
- Autentikasi Biometrik / Passkey (*WebAuthn*) untuk login instan.

---

## Pengujian & Kualitas Kode

```bash
# Menjalankan seluruh test suite otomatis (84 tests, 329 assertions)
php artisan test

# Memeriksa sintaks seluruh template Blade
php tools/check-blade.php resources/views
```
