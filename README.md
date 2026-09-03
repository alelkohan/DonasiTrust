# DonasiTrust

Platform donasi dengan pencairan dana bertahap, kuitansi terverifikasi, dan jejak audit ber-rantai-hash.
Dibangun untuk **Web Development Competition SwitchFest 2026** (HMJ TI UIN Walisongo Semarang).

Tema lomba: *NextGen Secure: Building the Future of Trusted Web Ecosystems*.

---

## Masalah yang dijawab

Di kebanyakan platform donasi, jejak uang donatur berhenti begitu pembayaran berhasil. Tidak ada
cara bagi orang luar untuk memeriksa apakah dana benar-benar dipakai sesuai janji.

DonasiTrust memaksakan empat aturan lewat sistem, bukan lewat janji:

1. **Pencairan bertahap.** Target dana wajib dipecah jadi tahapan yang totalnya sama persis dengan
   RAB. Tahap berikutnya terkunci sampai tahap sebelumnya dilaporkan dan diverifikasi admin.
2. **Kuitansi terverifikasi.** Tiap donasi menghasilkan kode HMAC-SHA256 atas nomor transaksi,
   nominal, dan kampanye. Siapa pun bisa mencocokkannya di `/verifikasi` tanpa punya akun.
3. **Jejak audit ber-rantai.** Setiap entri audit menyimpan hash entri sebelumnya. Mengubah satu
   catatan lama membuat seluruh rantai sesudahnya gagal diverifikasi.
4. **Rekening tujuan terkunci.** Dana hanya bisa mengalir ke rekening yang sudah diperiksa admin
   bersama KTP-nya, dan nomornya dibekukan pada tiap pengajuan. Akun pengaju yang dibajak pun
   tidak bisa mengalihkan dana ke rekening lain.
5. **Dua langkah di dua titik yang benar-benar berisiko** — bukan di mana-mana: **mengganti
   rekening tujuan** dan **menyatakan dana sudah ditransfer**. Alasan penempatannya ada di
   bagian *Kenapa gerbangnya cuma di dua tempat* di bawah.

---

## Stack

| Bagian | Pilihan |
|---|---|
| Framework | Laravel 12 |
| Database | MySQL 8 (kompatibel juga dengan SQLite untuk coba cepat) |
| Frontend | Blade + Livewire 3 + Alpine.js + Tailwind CSS 4 |
| Build | Vite |
| Pembayaran | Gateway simulasi bawaan; kerangka Midtrans sudah disiapkan |

---

## Menjalankan di komputer lokal

Prasyarat: PHP 8.2+, Composer, Node.js 20+, dan MySQL (Laragon / XAMPP / Laravel Herd).

```bash
# 1. Dependensi
composer install
npm install

# 2. Konfigurasi
cp .env.example .env
php artisan key:generate

# 3. Buat database bernama "donasitrust" di MySQL, lalu sesuaikan
#    DB_USERNAME / DB_PASSWORD di file .env bila perlu.

# 4. WAJIB: isi secret kuitansi di .env
#    DONASI_RECEIPT_SECRET=<string acak panjang>
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"   # untuk membuat string acak

# 5. Migrasi + data demo
php artisan migrate --seed

# 6. Jalankan (dua terminal)
php artisan serve
npm run dev
```

Buka <http://localhost:8000>.

### Alternatif tanpa MySQL

Ganti di `.env`:

```
DB_CONNECTION=sqlite
```

lalu `touch database/database.sqlite` dan jalankan `php artisan migrate --seed`.

---

## Akun demo

Semua memakai kata sandi `password123`.

| Email | Peran | Kondisi |
|---|---|---|
| `admin@donasitrust.test` | Administrator | Punya antrean review yang belum kosong |
| `pengaju@donasitrust.test` | Pengaju kampanye | Sudah terverifikasi, punya 4 kampanye di berbagai status |
| `pengaju2@donasitrust.test` | Pengaju kampanye | Menunggu verifikasi identitas |
| `donatur@donasitrust.test` | Donatur | Punya riwayat donasi |

### Kode dua langkah untuk akun demo

Akun `admin` dan `pengaju` sudah punya verifikasi dua langkah aktif, karena keduanya menyentuh
uang. Masukkan kunci berikut ke aplikasi authenticator (Google Authenticator, Aegis, atau
pengelola kata sandi) sebagai **entri manual**:

```
JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP
```

Kunci ini sengaja dibuat sama dan diumumkan supaya alur pencairan bisa langsung dicoba tanpa
mendaftar dulu — di produksi tentu tidak boleh begitu. Kalau tidak sempat memasang aplikasi,
tiap akun demo juga punya kode pemulihan sekali pakai: `DEMO-0001` dan `DEMO-0002`.

---

## Alur demo yang disarankan (untuk presentasi 5 menit)

1. **Beranda → detail kampanye.** Tunjukkan RAB terbuka dan tahapan pencairan.
2. **Donasi** dengan nominal apa saja → halaman pembayaran → tombol *Simulasikan pembayaran berhasil*.
3. **Kuitansi terbit** dengan kode verifikasi. Salin kodenya.
4. **Buka `/verifikasi`** di jendela penyamaran (tanpa login), tempel nomor transaksi + kode →
   sistem mengonfirmasi keaslian. Ubah satu karakter kode → ditolak.
5. **Buka `/transparansi`.** Angka sudah bertambah, dan status rantai audit dihitung ulang
   saat halaman dimuat.
6. **Login sebagai admin** → antrean review → setujui satu kampanye → kembali ke jejak audit,
   tunjukkan entri baru dengan `previous_hash` yang menyambung.
7. **Tunjukkan gerbang dua langkahnya.** Sebagai admin, buka satu pencairan berstatus *Disetujui*
   → isi bukti transfer → masukkan kode yang **salah**: dana tidak bergerak, dan percobaan gagalnya
   muncul di jejak audit. Ulangi dengan kode yang benar dari authenticator → baru dana dilepas.
   Lanjut ke pencairan **kedua**: kodenya tidak diminta lagi karena jendela 15 menit masih terbuka,
   dan jejak auditnya menandai bedanya (`kode` vs `jendela_15_menit`).
8. **Tunjukkan penempatannya yang sengaja tidak merata.** Sebagai pengaju, ajukan pencairan —
   tidak ada kode sama sekali, karena rekening tujuannya memang sudah terkunci. Lalu buka
   *Verifikasi identitas* dan coba **ganti nomor rekening**: di situ kodenya diminta. Inilah
   jawaban atas "kenapa 2FA-nya tidak di mana-mana?" — lihat bagian *Kenapa gerbangnya cuma di
   dua tempat*.

---

## Struktur yang perlu diketahui

```
app/
├── Http/Controllers/       Publik, Auth, Pengaju/, Admin/
├── Livewire/DonationForm   Form donasi interaktif
├── Models/                 User, Campaign, CampaignItem, Milestone,
│                           Donation, Disbursement, ExpenseReport, AuditLog
├── Notifications/
│   └── PayoutAccountChanged  Peringatan surel saat rekening pencairan diubah
├── Policies/               Izin per-kampanye
├── Services/
│   ├── AuditLogger         Penulis & pemverifikasi rantai hash
│   ├── DonationService     Pembuatan donasi + pelunasan idempotent
│   ├── ReceiptVerifier     HMAC-SHA256 kuitansi
│   ├── Totp                Algoritma TOTP RFC 6238 (tanpa pustaka luar)
│   ├── TotpGuard           Gerbang dua langkah: sekali pakai, batas laju,
│   │                       jendela sudo 15 menit, audit
│   └── *PaymentGateway     Kontrak gateway + implementasi mock/Midtrans
└── Support/Rupiah          Format & parse rupiah
```

---

## Kenapa gerbangnya cuma di dua tempat

Platform donasi nyata (Kitabisa, GoFundMe) **tidak** mewajibkan Google Authenticator ke penggalang
dana, dan itu bukan kelalaian. Pengaju donasi sering pengurus masjid, keluarga pasien, atau relawan
daerah — memaksakan aplikasi authenticator ke mereka berarti kehilangan pengguna, dan ponsel yang
hilang berarti dana kampanye terkunci selamanya.

Maka pertanyaannya bukan *"seberapa banyak 2FA bisa dipasang"*, melainkan *"di mana ia benar-benar
mengubah hasil"*. Jawabannya kami turunkan dari alur uangnya sendiri:

| Aksi | Perlindungan | Alasan |
|---|---|---|
| Donatur berdonasi | Diserahkan ke payment gateway | Autentikasi ada di sisi bank/e-wallet |
| Pengaju **mengajukan** pencairan | Tidak ada kode | Rekening tujuan sudah terkunci — akun yang dibajak hanya bisa mengalirkan dana ke rekening pemilik aslinya. Kode di sini = friction besar, keamanan nyaris nol |
| Pengaju **mengganti rekening** | **TOTP wajib** (bila diaktifkan) + notifikasi surel | Inilah satu-satunya jalan dana bisa diarahkan ke pihak lain. Aksinya sekali seumur akun, jadi frictionnya terbayar |
| Admin **melepas dana** | **TOTP wajib** + jendela 15 menit | Titik tak-bisa-ditarik-kembali. Jendela dipakai supaya antrean pencairan tidak perlu diketik satu per satu |

Serangan yang sesungguhnya pada model "rekening terkunci" bukan *"bajak akun lalu cairkan"* — itu
buntu. Melainkan: **bajak akun → ganti nomor rekening → tunggu admin meloloskan → baru cairkan.**
Karena itu gerbangnya ada di penggantian rekening, bukan di tiap pengajuan.

Bagi pengaju, dua langkah bersifat **opsional**. Yang mengaktifkannya mendapat lencana di halaman
kampanyenya — *"Rekening pencairan dikunci verifikasi dua langkah"* — sehingga pilihan itu berubah
jadi sinyal kepercayaan yang bisa dilihat calon donatur, bukan sekadar pengaturan tersembunyi.

---

## Catatan keamanan (dan batasnya)

Yang **sudah** dilakukan:

- Kata sandi di-hash bcrypt otomatis lewat cast `hashed` pada model `User`.
- Semua query lewat Eloquent/query builder → parameter ter-binding, aman dari SQL injection.
- Otorisasi per-peran (middleware `role`) dan per-objek (`CampaignPolicy`).
- Dokumen identitas disimpan di disk privat (`storage/app/private`), hanya bisa dibuka lewat route
  yang memeriksa izin di tiap permintaan. Tidak ada URL publik yang bisa ditebak.
- NIK lengkap **tidak** disimpan — hanya 4 digit terakhir.
- Rate limiting pada login, registrasi, donasi, verifikasi kuitansi, dan unggah identitas.
- **Verifikasi dua langkah (TOTP, RFC 6238)**, ditulis sendiri tanpa pustaka luar dan diuji
  terhadap keenam vektor uji resmi RFC 6238 Lampiran B. Kunci disimpan terenkripsi (`APP_KEY`),
  satu kode hanya berlaku **sekali pakai**, percobaan salah dibatasi 5 kali per 5 menit, dan
  berhasil maupun gagal keduanya masuk jejak audit ber-rantai. Kode pemulihan sekali pakai
  disediakan supaya ponsel yang hilang tidak mengunci dana selamanya.
- Rekening tujuan pencairan **tidak bisa ditentukan dari form pengajuan** — disalin dari profil
  yang sudah diverifikasi admin, dan dibekukan pada pengajuan itu.
- **Menotifikasi pemilik akun** lewat surel setiap kali rekening pencairan diubah. Kalau bukan
  dia yang melakukannya, itu kesempatannya tahu sebelum admin meloloskan.
- Endpoint webhook memverifikasi signature dan bersifat idempotent (pembayaran tidak dihitung dua kali).
- HTTPS dipaksakan saat `APP_ENV=production`.

Yang **jujur belum/tidak** dijamin:

- **Kode kuitansi adalah MAC, bukan tanda tangan digital.** Ia membuktikan kuitansi diterbitkan
  server ini, bukan identitas penandatangan. Tanda tangan sungguhan butuh pasangan kunci (ECDSA/RSA).
- **Rantai hash bersifat *tamper-evident*, bukan *tamper-proof*.** Pihak dengan akses tulis penuh ke
  basis data masih bisa menghitung ulang seluruh rantai. Jaminan lebih kuat butuh publikasi hash
  berkala ke pihak luar.
- **Verifikasi identitas dan nota dilakukan manusia.** Tidak ada akses ke API Dukcapil, dan tidak ada
  perangkat lunak yang bisa memastikan keaslian nota fisik yang difoto.
- **Dua langkah membuktikan SIAPA yang menekan tombol, bukan bahwa uangnya sampai.** Transfer
  masih dilakukan admin secara manual di luar sistem; yang dipegang sistem hanya foto bukti
  transfer. Untuk benar-benar memastikan dana mendarat di rekening yang tercatat, dibutuhkan
  integrasi API disbursement bank (mis. Xendit/Flip) — itu langkah berikutnya, bukan yang ini.
- **Dua langkah bagi pengaju bersifat opsional.** Konsekuensinya jujur: akun pengaju yang belum
  mengaktifkannya tetap bisa berganti rekening hanya dengan sesi login, dan yang menahannya
  tinggal peninjauan admin plus notifikasi surel. Itu pilihan sadar — memaksakannya ke pengguna
  non-teknis menimbulkan masalah yang lebih besar daripada yang diselesaikan.
- **Pembayaran masih simulasi.** `PAYMENT_GATEWAY=mock`. Kerangka Midtrans ada di
  `app/Services/MidtransPaymentGateway.php` tapi sengaja melempar exception daripada berpura-pura jalan.

---

## Belum dibangun (rencana lanjutan)

Sesuai urutan build yang disepakati, bagian berikut belum masuk rilis ini:

- Kuitansi PDF dan QR code (butuh `barryvdh/laravel-dompdf` + `simplesoftwareio/simple-qrcode`)
- Transfer otomatis lewat API disbursement bank, menggantikan transfer manual + foto bukti
- Integrasi Midtrans/Xendit sandbox sungguhan
- Dua langkah saat **login** — sekarang kodenya baru diminta pada aksi berisiko, bukan di pintu masuk
- OTP WhatsApp sebagai alternatif bagi pengaju non-teknis. Perlu dicatat jujur: WA/SMS **lebih
  lemah** daripada TOTP (rentan SIM swap dan pembajakan akun WA), jadi posisinya pelengkap
  kenyamanan — bukan peningkatan keamanan
- Passkey/WebAuthn, yang justru **lebih kuat** dari TOTP sekaligus lebih mudah dipakai

---

## Perkakas

```bash
php tools/check-blade.php resources/views   # cek cepat sintaks template tanpa menjalankan aplikasi
php artisan test                            # test suite
```
