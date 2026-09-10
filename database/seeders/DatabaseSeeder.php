<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Disbursement;
use App\Models\Donation;
use App\Models\ExpenseReport;
use App\Models\Milestone;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ReceiptVerifier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Data seeder lengkap 32+ kampanye untuk presentasi dan pengujian DonasiTrust.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $audit = app(AuditLogger::class);
        $receipts = app(ReceiptVerifier::class);

        // --- Akun demo Utama -------------------------------------------------------
        $admin = User::create([
            'name' => 'Admin DonasiTrust',
            'email' => 'jokibuat121@gmail.com',
            'password' => 'password123',
            'role' => User::ROLE_ADMIN,
            'verification_status' => User::VERIFICATION_VERIFIED,
            'verified_at' => now(),
            'email_verified_at' => now(),
        ]);

        $pengaju = User::create([
            'name' => 'Ahmad Fauzi',
            'email' => 'pengaju@donasitrust.test',
            'password' => 'password123',
            'role' => User::ROLE_PENGAJU,
            'phone' => '081234567890',
            'organization' => 'Yayasan Peduli Sesama Semarang',
            'identity_number_last4' => '4821',
            'identity_number_hash' => User::hashIdentityNumber('3324061503894821'),
            'bank_name' => 'BRI',
            'bank_account_number' => '337401004821530',
            'bank_account_holder' => 'AHMAD FAUZI',
            'verification_status' => User::VERIFICATION_VERIFIED,
            'verified_at' => now()->subDays(20),
            'verified_by' => $admin->id,
            'email_verified_at' => now(),
        ]);

        $pengaju2 = User::create([
            'name' => 'dr. Bambang Hermanto',
            'email' => 'bambang@medikanusantara.org',
            'password' => 'password123',
            'role' => User::ROLE_PENGAJU,
            'phone' => '081399887766',
            'organization' => 'Yayasan Medika Nusantara Kemanusiaan',
            'identity_number_last4' => '8812',
            'identity_number_hash' => User::hashIdentityNumber('3324061503898812'),
            'bank_name' => 'Mandiri',
            'bank_account_number' => '1370009876543',
            'bank_account_holder' => 'YAYASAN MEDIKA NUSANTARA',
            'verification_status' => User::VERIFICATION_VERIFIED,
            'verified_at' => now()->subDays(15),
            'verified_by' => $admin->id,
            'email_verified_at' => now(),
        ]);

        $pengaju3 = User::create([
            'name' => 'Budi Raharjo',
            'email' => 'budi@sahabatalam.org',
            'password' => 'password123',
            'role' => User::ROLE_PENGAJU,
            'phone' => '082144556677',
            'organization' => 'Komunitas Sahabat Alam Jawa Tengah',
            'identity_number_last4' => '5543',
            'identity_number_hash' => User::hashIdentityNumber('3324061503895543'),
            'bank_name' => 'BCA',
            'bank_account_number' => '0841239981',
            'bank_account_holder' => 'BUDI RAHARJO',
            'verification_status' => User::VERIFICATION_VERIFIED,
            'verified_at' => now()->subDays(10),
            'verified_by' => $admin->id,
            'email_verified_at' => now(),
        ]);

        $pengajuBaru = User::create([
            'name' => 'Siti Nurhaliza',
            'email' => 'pengaju2@donasitrust.test',
            'password' => 'password123',
            'role' => User::ROLE_PENGAJU,
            'organization' => 'Komunitas Sungai Bersih Kendal',
            'identity_number_last4' => '1907',
            'identity_number_hash' => User::hashIdentityNumber('3324065502021907'),
            'bank_name' => 'BCA',
            'bank_account_number' => '0391847190',
            'bank_account_holder' => 'SITI NURHALIZA',
            'verification_status' => User::VERIFICATION_PENDING,
            'email_verified_at' => now(),
        ]);

        $donatur = User::create([
            'name' => 'Budi Santoso',
            'email' => 'donatur@donasitrust.test',
            'password' => 'password123',
            'role' => User::ROLE_DONATUR,
            'phone' => '089876543210',
            'email_verified_at' => now(),
        ]);

        $audit->record('user.registered', $pengaju, ['role' => 'pengaju'], $pengaju);
        $audit->record('user.verified', $pengaju, ['nama' => $pengaju->name], $admin);
        $audit->record('user.verification_submitted', $pengajuBaru, ['nama' => $pengajuBaru->name], $pengajuBaru);

        // --- Daftar 32 Kampanye --------------------------------------------------------
        $definitions = [
            // 1. Madrasah Al-Hikmah
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju,
                'title' => 'Perbaikan Atap Madrasah Al-Hikmah Kendal',
                'category' => 'pendidikan',
                'summary' => 'Atap tiga ruang kelas bocor parah. 87 santri terpaksa belajar bergantian di aula saat hujan.',
                'description' => "Madrasah Al-Hikmah berdiri sejak 1998 dan melayani 87 santri dari keluarga prasejahtera di Kecamatan Kaliwungu, Kendal.\n\nSejak musim hujan Desember lalu, tiga dari lima ruang kelas mengalami kebocoran. Rangka kayu di dua ruang sudah lapuk dan berisiko roboh. Pengurus sudah menambal sementara dengan terpal, tapi itu tidak bertahan lebih dari dua minggu.\n\nDana yang kami ajukan dipakai untuk mengganti rangka dan genteng tiga ruang kelas, bukan renovasi total. Kami memecahnya jadi tiga tahap agar donatur bisa melihat hasil setiap tahap sebelum tahap berikutnya cair.",
                'target' => 48_500_000,
                'deadline' => now()->addDays(45),
                'items' => [
                    ['Genteng beton', 1200, 'buah', 12_000],
                    ['Kayu kaso 5x7 meranti', 180, 'batang', 85_000],
                    ['Reng kayu', 220, 'batang', 32_000],
                    ['Upah tukang (3 orang, 12 hari)', 36, 'hari-orang', 150_000],
                    ['Paku, sekrup, plafon, dan material pendukung', 1, 'paket', 6_360_000],
                ],
                'milestones' => [
                    ['Pembelian material tahap 1 (ruang kelas A)', 'Genteng dan kayu untuk satu ruang kelas, plus mobilisasi tukang.', 18_000_000],
                    ['Pengerjaan ruang kelas B dan C', 'Material sisa dan upah tukang untuk dua ruang berikutnya.', 20_500_000],
                    ['Finishing dan pembersihan', 'Pengecatan plafon, pembersihan puing, dan dokumentasi akhir.', 10_000_000],
                ],
                'donations' => [
                    ['Budi Santoso', 500_000, false, 'Semoga cepat selesai sebelum musim hujan berikutnya.', 26],
                    ['PT Sinar Rejeki Abadi', 15_000_000, false, 'Dana CSR triwulan III.', 24],
                    [null, 250_000, true, null, 22],
                    ['Rina Wijaya', 1_000_000, false, 'Untuk adik-adik di Kaliwungu.', 20],
                    ['Alumni MA Al-Hikmah angkatan 2010', 8_000_000, false, 'Patungan satu angkatan.', 18],
                    ['Hendra Kusuma', 150_000, false, null, 16],
                    [null, 2_000_000, true, 'Semoga berkah.', 13],
                    ['Masjid Baiturrahman Kaliwungu', 6_500_000, false, 'Hasil kotak amal Jumat.', 11],
                    ['Dewi Lestari', 300_000, false, 'Ikut bantu semampunya.', 8],
                    ['Komunitas Sepeda Kendal', 4_450_000, false, 'Hasil gowes amal.', 6],
                    ['Agus Salim', 750_000, false, null, 3],
                    [null, 100_000, true, null, 1],
                ],
                'disbursement' => [
                    'milestone' => 1,
                    'amount' => 18_000_000,
                    'purpose' => 'Pembelian genteng, kayu kaso, dan reng untuk ruang kelas A, plus upah tukang minggu pertama dan sewa scaffolding.',
                    'days_ago' => 10,
                ],
                'expenses' => [
                    ['Genteng beton 400 buah (ruang kelas A)', 0, 4_800_000, 9, 'Dibeli di TB Sumber Rejeki Kaliwungu. Harga Rp12.000/buah sesuai RAB.', 'nota-genteng.png'],
                    ['Kayu kaso dan reng ruang kelas A', 1, 7_450_000, 9, 'Kayu meranti 60 batang dan reng 75 batang. Nota terlampir.', 'nota-kayu.png'],
                    ['Upah tukang minggu pertama', 3, 3_600_000, 5, '3 tukang x 8 hari x Rp150.000. Daftar hadir ditandatangani ketua RT.', 'nota-upah.png'],
                    ['Sewa scaffolding dan mobilisasi material', 4, 2_150_000, 8, 'Sewa 10 hari plus ongkos angkut dua rit pikap.', 'nota-scaffolding.png'],
                ],
            ],

            // 2. Air Bersih Dusun Ngroto
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju,
                'title' => 'Air Bersih untuk Dusun Ngroto, Grobogan',
                'category' => 'infrastruktur',
                'summary' => 'Warga berjalan 2,5 km tiap pagi untuk mengambil air. Kami ingin membangun satu sumur bor komunal.',
                'description' => "Dusun Ngroto dihuni 64 kepala keluarga. Sumber air terdekat berjarak 2,5 km, dan pada musim kemarau debitnya turun drastis.\n\nSurvei geolistrik yang kami lakukan bulan lalu menemukan titik dengan potensi air pada kedalaman 60 meter. Anggaran di bawah adalah hasil penawaran dari dua kontraktor pengeboran lokal — kami memilih yang lebih murah dengan spesifikasi setara.\n\nSetelah selesai, sumur dikelola kelompok warga dengan iuran perawatan Rp5.000 per KK per bulan.",
                'target' => 32_000_000,
                'deadline' => now()->addDays(28),
                'items' => [
                    ['Jasa pengeboran 60 meter', 60, 'meter', 350_000],
                    ['Pipa PVC dan casing', 1, 'paket', 4_800_000],
                    ['Pompa submersible + panel', 1, 'unit', 5_200_000],
                    ['Tandon 2200 liter + rangka', 1, 'unit', 1_000_000],
                ],
                'milestones' => [
                    ['Pengeboran dan pemasangan casing', 'Tahap paling berisiko — dicairkan lebih dulu.', 21_000_000],
                    ['Instalasi pompa dan tandon', 'Setelah air keluar dan debit terverifikasi.', 11_000_000],
                ],
                'donations' => [
                    ['Kantor Notaris Yulianto', 5_000_000, false, 'Semoga bermanfaat untuk warga Ngroto.', 9],
                    ['Budi Santoso', 250_000, false, null, 7],
                    [null, 1_500_000, true, null, 4],
                    ['Maya Sari', 400_000, false, 'Air itu hak dasar.', 2],
                ],
            ],

            // 3. Beasiswa Transportasi 30 Siswa
            [
                'status' => Campaign::STATUS_PENDING,
                'user' => $pengaju,
                'title' => 'Beasiswa Transportasi 30 Siswa SMK Pesisir',
                'category' => 'pendidikan',
                'summary' => 'Ongkos angkot Rp12.000/hari membuat 30 siswa kelas 12 terancam putus sekolah menjelang ujian.',
                'description' => "Data dari BK SMK Negeri 1 Rembang: 30 siswa kelas 12 tercatat absen lebih dari 20% semester ini. Wawancara wali kelas menemukan penyebab utama yang sama — biaya transportasi.\n\nProgram ini menanggung ongkos harian selama satu semester penuh, disalurkan lewat sekolah dengan absensi sebagai bukti penyaluran.",
                'target' => 21_600_000,
                'deadline' => now()->addDays(60),
                'items' => [
                    ['Ongkos transportasi harian', 1800, 'hari-siswa', 12_000],
                ],
                'milestones' => [
                    ['Penyaluran bulan 1-3', 'Disalurkan lewat bendahara sekolah, dilaporkan dengan absensi.', 10_800_000],
                    ['Penyaluran bulan 4-6', 'Cair setelah laporan tahap pertama diverifikasi.', 10_800_000],
                ],
                'donations' => [],
            ],

            // 4. Peralatan Olahraga Karang Taruna
            [
                'status' => Campaign::STATUS_REJECTED,
                'user' => $pengaju,
                'title' => 'Pengadaan Peralatan Olahraga Karang Taruna',
                'category' => 'sosial',
                'summary' => 'Bola, net, dan seragam untuk kegiatan rutin karang taruna desa.',
                'description' => "Karang taruna desa membutuhkan peralatan olahraga untuk kegiatan mingguan pemuda.",
                'target' => 8_000_000,
                'deadline' => now()->addDays(30),
                'review_note' => 'RAB terlalu umum. Mohon rinci merek/spesifikasi tiap item dan lampirkan minimal dua penawaran harga pembanding, agar donatur bisa menilai kewajarannya.',
                'items' => [
                    ['Bola sepak', 10, 'buah', 245_000],
                    ['Net voli dan tiang', 2, 'set', 1_200_000],
                    ['Seragam tim', 30, 'set', 105_000],
                ],
                'milestones' => [
                    ['Pembelian seluruh peralatan', null, 8_000_000],
                ],
                'donations' => [],
            ],

            // 5. Operasi Katarak Gratis
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju2,
                'title' => 'Operasi Katarak Gratis 50 Lansia Dhuafa Brebes',
                'category' => 'kesehatan',
                'summary' => 'Bantu 50 lansia di pedalaman Brebes mendapatkan penglihatan mereka kembali lewat operasi katarak gratis.',
                'description' => "Berdasarkan skrinning medis tim relawan kesehatan, 50 lansia di Brebes mengalami penurunan penglihatan drastis akibat katarak stadium lanjut.\n\nBanyak dari mereka hidup sebatang kara dan tidak lagi bisa beraktivitas. Program ini bekerja sama dengan dokter spesialis mata dan rumah sakit daerah untuk melaksanakan bakti sosial operasi katarak masal.",
                'target' => 65_000_000,
                'deadline' => now()->addDays(40),
                'items' => [
                    ['Paket Lensa IOL dan Medis Katarak', 50, 'paket', 900_000],
                    ['Sewa Ruang Operasi & Sterilisasi', 50, 'pasien', 300_000],
                    ['Obat Tetes & Obat Pasca Operasi', 50, 'paket', 100_000],
                ],
                'milestones' => [
                    ['Pembelian paket lensa IOL & obat-obatan', 'Persiapan alat medis sebelum tindakan operasi.', 40_000_000],
                    ['Pelaksanaan operasi & pemeriksaan pasca op', 'Tindakan medis dan kontrol evaluasi minggu ke-1.', 25_000_000],
                ],
                'donations' => [
                    ['Keluarga H. Soetrisno', 10_000_000, false, 'Niat sedekah jariyah untuk almarhumah ibu.', 12],
                    ['Siti Aminah', 500_000, false, 'Semoga bapak ibu penerima bisa melihat kembali.', 10],
                    [null, 2_500_000, true, 'Semoga berkah untuk sesama.', 5],
                    ['Indra Kusuma', 1_000_000, false, null, 2],
                ],
            ],

            // 6. Tanggap Bencana Banjir Bandang Demak
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju2,
                'title' => 'Bantuan Tanggap Bencana Banjir Bandang Demak',
                'category' => 'bencana',
                'summary' => 'Banjir bandang menerjang 4 desa di Demak. 1.200 warga mengungsi butuh selimut, makanan siap saji, dan higienitas kit.',
                'description' => "Luapan sungai akibat curah hujan ekstrem merendam pemukiman warga hingga ketinggian 1,5 meter.\n\nKebutuhan paling mendesak saat ini di posko pengungsian adalah makanan bergizi siap saji, perlengkapan bayi (pampers/minyak kayu putih), dan selimut hangat.",
                'target' => 50_000_000,
                'deadline' => now()->addDays(15),
                'items' => [
                    ['Nasi Kotak & Makanan Siap Saji (1.000 porsi)', 1000, 'porsi', 25_000],
                    ['Selimut Tebal & Matras Pengungsi', 200, 'paket', 75_000],
                    ['Hygiene Kit & Perlengkapan Bayi', 100, 'paket', 100_000],
                ],
                'milestones' => [
                    ['Pembelian makanan dan kebutuhan darurat hari 1-3', 'Penyaluran logistik mendesak ke posko pengungsian.', 30_000_000],
                    ['Pemulihan pasca banjir & sanitasi lingkungan', 'Pembagian peralatan kebersihan dan obat-obatan.', 20_000_000],
                ],
                'donations' => [
                    ['Budi Santoso', 1_000_000, false, 'Semoga saudara kita di Demak diberikan ketabahan.', 7],
                    ['Hamba Allah', 5_000_000, true, 'Doa kami menyertai para korban.', 4],
                    ['Komunitas Kuliner Semarang', 12_000_000, false, 'Bantuan konsumsi dari kawan-kawan pengusaha makanan.', 1],
                ],
            ],

            // 7. Penerangan Jalan Tenaga Surya Pelosok
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju3,
                'title' => 'Penerangan Jalan Tenaga Surya Dusun Pelosok Gunungkidul',
                'category' => 'infrastruktur',
                'summary' => 'Jalan desa sepanjang 3 km gelap gulita dan rawan kecelakaan. Mari bantu pasang 15 unit PJUTS mandiri.',
                'description' => "Dusun Tepus Gunungkidul berada di perbukitan tanpa akses lampu jalan dari PLN. Anak-anak yang pulang mengaji malam hari terpaksa melintasi jalan gelap bertebing berbahaya.\n\nKami akan memasang 15 titik lampu solar panel hemat energi yang akan menyala otomatis tiap malam.",
                'target' => 37_500_000,
                'deadline' => now()->addDays(50),
                'items' => [
                    ['Lampu PJUTS Solar Cell 100W All-in-One', 15, 'unit', 1_800_000],
                    ['Tiang Galvanis 6 Meter + Pondasi', 15, 'tiang', 600_000],
                    ['Jasa Pemasangan & Mobilisasi Medan Perbukitan', 1, 'paket', 1_500_000],
                ],
                'milestones' => [
                    ['Pengadaan unit lampu PJUTS & tiang galvanis', 'Pemesanan dan pengiriman barang ke lokasi proyek.', 25_000_000],
                    ['Pengecoran pondasi & instalasi lampu', 'Pemasangan tiang oleh relawan dan teknisi.', 12_500_000],
                ],
                'donations' => [
                    ['Komunitas Offroad Jogja', 7_500_000, false, 'Siap bantu mobilisasi dan dana pemasangan tiang.', 14],
                    ['Eko Prasetyo', 500_000, false, null, 11],
                    [null, 3_000_000, true, 'Semoga menjadi penerang jalan di akhirat.', 6],
                ],
            ],

            // 8. Penanaman 10.000 Pohon Mangrove Demak
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju3,
                'title' => 'Penanaman 10.000 Pohon Mangrove Pesisir Sayung Demak',
                'category' => 'lingkungan',
                'summary' => 'Abrasi air laut telah menenggelamkan puluhan rumah di Sayung. Mari cegah kerusakan lebih parah dengan menanam mangrove.',
                'description' => "Desa Bedono Sayung Demak terus tergerus abrasi laut Jawa. Penanaman bibit mangrove jenis Rhizophora sp terbukti paling efektif menahan hempasan gelombang laut dan memulihkan ekosistem pesisir.",
                'target' => 40_000_000,
                'deadline' => now()->addDays(60),
                'items' => [
                    ['Bibit Mangrove Rhizophora (10.000 bibit)', 10000, 'batang', 3_000],
                    ['Ajir Bambu Penyangga & Tali Belat', 10000, 'unit', 800],
                    ['Operasional Relawan & Perawatan 6 Bulan', 1, 'paket', 2_000_000],
                ],
                'milestones' => [
                    ['Pembelian bibit & pembuatan bronjong penyangga', 'Pengadaan material awal dan pembibitan.', 25_000_000],
                    ['Penanaman serentak & monitoring tumbuh 6 bulan', 'Aksi tanam bersama masyarakat nelayan setempat.', 15_000_000],
                ],
                'donations' => [
                    ['Mahasiswa Pencinta Alam Undip', 2_000_000, false, 'Semangat jaga pesisir Jawa Tengah!', 15],
                    ['PT Hijau Lestari Indonesia', 15_000_000, false, 'Program CSR Lingkungan 2026.', 8],
                ],
            ],

            // 9. Rumah Singgah Pasien Kanker Anak
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju2,
                'title' => 'Rumah Singgah Pasien Kanker Anak Semarang',
                'category' => 'kemanusiaan',
                'summary' => 'Bantu sewa rumah dan operasional untuk tempat tinggal gratis anak-anak penderita kanker selama berobat di RSUP Kariadi.',
                'description' => "Banyak pasien kanker anak dari keluarga tidak mampu di luar kota Semarang harus menjalani kemoterapi rutin berbulan-bulan. Sewa rumah singgah ini menyediakan tempat tinggal, makanan bergizi, dan ambulan pengantar gratis.",
                'target' => 72_000_000,
                'deadline' => now()->addDays(90),
                'items' => [
                    ['Sewa Rumah 1 Tahun ( dekat RSUP Kariadi)', 1, 'tahun', 36_000_000],
                    ['Konsumsi Nutrisi & Susu Pasien Kanker', 12, 'bulan', 2_000_000],
                    ['Bahan Bakar & Perawatan Ambulan Singgah', 12, 'bulan', 1_000_000],
                ],
                'milestones' => [
                    ['Pembayaran sewa rumah & kelengkapan tempat tidur', 'Pelunasan sewa rumah 1 tahun.', 40_000_000],
                    ['Operasional konsumsi & ambulan 6 bulan pertama', 'Biaya makan bergizi dan transportasi anak-anak.', 32_000_000],
                ],
                'donations' => [
                    ['Komunitas Ibu Peduli Kanker', 8_000_000, false, 'Peluk hangat untuk anak-anak hebat.', 20],
                    ['Donatur Anonim', 10_000_000, true, 'Semoga lekas sembuh adik-adik.', 10],
                    ['Rahmat Hidayat', 1_500_000, false, null, 3],
                ],
            ],

            // 10. Motor Perpustakaan Keliling Anak Pesisir
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju,
                'title' => 'Pengadaan Motor Perpustakaan Keliling Anak Pesisir Cilacap',
                'category' => 'pendidikan',
                'summary' => 'Meningkatkan minat baca anak-anak di dusun nelayan terpencil yang tak memiliki akses perpustakaan.',
                'description' => "Dusun nelayan Kampung Laut Cilacap sulit dijangkau mobil. Pengadaan motor roda tiga modifikasi perpustakaan akan membawa ratusan buku bacaan edukatif langsung ke perkampungan anak-anak setiap sore.",
                'target' => 28_000_000,
                'deadline' => now()->addDays(35),
                'items' => [
                    ['Motor Roda Tiga Karoseri Box Buku', 1, 'unit', 21_000_000],
                    ['Buku Bacaan Anak, Cerita & Edukasi (300 buku)', 300, 'eksemplar', 20_000],
                    ['Karpet Lipat & Pengeras Suara Edukasi', 1, 'set', 1_000_000],
                ],
                'milestones' => [
                    ['Pembelian unit motor roda tiga & karoseri box', 'Pembelian armada pengangkut buku.', 22_000_000],
                    ['Pengadaan buku & perlengkapan lapak baca', 'Pembelian buku dan operasional launching.', 6_000_000],
                ],
                'donations' => [
                    ['Toko Buku Saraswati', 3_000_000, false, 'Bantu lewat donasi buku dan dana tunai.', 12],
                    ['Fitriani', 300_000, false, 'Maju terus literasi anak Indonesia.', 5],
                ],
            ],

            // 11. Ambulans Gratis Desa Terpencil Merbabu
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju2,
                'title' => 'Ambulans Gratis Desa Terpencil Lereng Merbabu',
                'category' => 'kesehatan',
                'summary' => 'Ibu hamil dan warga sakit sering terlambat dirujuk karena tak ada kendaraan darurat. Mari hadirkan 1 unit armada ambulans desa.',
                'description' => "Di lereng Gunung Merbabu, jarak dari dusun ke Puskesmas terdekat mencapai 18 km jalan menanjak. Pengadaan mobil ambulans bekas layak pakai ini akan digratiskan 100% untuk emergency warga.",
                'target' => 95_000_000,
                'deadline' => now()->addDays(75),
                'items' => [
                    ['Unit Mobil Minibus Bekas Layak Jalan', 1, 'unit', 75_000_000],
                    ['Modifikasi Karoseri Ambulan & Sirine', 1, 'paket', 12_000_000],
                    ['Alat Medis Darurat (Tandu, Tabung O2, P3K)', 1, 'set', 8_000_000],
                ],
                'milestones' => [
                    ['Pembelian armada mobil bekas', 'Pembelian unit mobil dasar.', 75_000_000],
                    ['Karoseri & kelengkapan medis ambulan', 'Pemasangan sirine, tandu, dan alat medis emergency.', 20_000_000],
                ],
                'donations' => [
                    ['Paguyuban Merbabu Asri', 15_000_000, false, 'Gotong royong warga desa & perantau.', 25],
                    ['Hamba Allah', 20_000_000, true, 'Semoga bermanfaat untuk penyelamatan nyawa.', 14],
                    ['Dr. Supriyanto Sp.OG', 5_000_000, false, 'Semoga tidak ada lagi ibu hamil yang terlambat ditangani.', 8],
                ],
            ],

            // 12. Jembatan Gantung Swadaya Dusun Kedungombo
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju,
                'title' => 'Jembatan Gantung Swadaya Dusun Kedungombo',
                'category' => 'infrastruktur',
                'summary' => 'Anak-anak sekolah terpaksa menyeberangi sungai deras dengan rakit bambu lapuk. Mari bangun jembatan gantung baja aman.',
                'description' => "Jembatan bambu lama telah hanyut tersapu banjir bulan lalu. 45 anak sekolah tiap pagi harus menaruh nyawa menyeberang sungai. Pembangunan jembatan gantung kabel baja ini dirancang oleh insinyur relawan.",
                'target' => 85_000_000,
                'deadline' => now()->addDays(60),
                'items' => [
                    ['Kabel Sling Baja 22mm & Angkur', 4, 'roll', 7_500_000],
                    ['Batu, Semen, Besi Beton Pondasi Menara', 1, 'paket', 25_000_000],
                    ['Bordes Plat Besi & Pagar Pengaman Wiremesh', 1, 'paket', 20_000_000],
                    ['Upah Tukang Las & Mobilisasi Alat', 1, 'paket', 10_000_000],
                ],
                'milestones' => [
                    ['Pengecoran pondasi & pengecoran menara penyangga', 'Pekerjaan sipil pondasi kedua belah tepi sungai.', 45_000_000],
                    ['Penarikan sling baja & pemasangan lantai jembatan', 'Pemasangan kabel sling, lantai bordes, dan tes beban.', 40_000_000],
                ],
                'donations' => [
                    ['Alumni Teknik Sipil Diponegoro', 25_000_000, false, 'Bantuan supervisi teknik & material baja.', 18],
                    ['H. Anang Hermansyah', 10_000_000, false, 'Untuk keselamatan anak-anak sekolah.', 12],
                    [null, 2_000_000, true, null, 4],
                ],
            ],

            // 13. Dapur Ummat Makanan Bergizi Lansia
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju,
                'title' => 'Dapur Ummat Makanan Bergizi 100 Lansia Sebatang Kara',
                'category' => 'sosial',
                'summary' => 'Menyediakan 100 paket makanan siap santap sehat dan nutrisi harian untuk lansia terlantar di Semarang.',
                'description' => "Banyak lansia terlantar di kawasan pinggiran kota yang tidak sanggup memasak sendiri dan sering menahan lapar. Dapur Ummat memasakkan makanan segar bernutrisi setiap hari dan diantar relawan.",
                'target' => 30_000_000,
                'deadline' => now()->addDays(30),
                'items' => [
                    ['Bahan Makanan Beras, Lauk, Sayur & Buah (3.000 porsi)', 3000, 'porsi', 9_000],
                    ['Kotak Ramah Lingkungan & Kemasan', 3000, 'pcs', 1_000],
                    ['Gas Elpiji & Biaya Operasional Masak', 1, 'paket', 0],
                ],
                'milestones' => [
                    ['Operasional Dapur Ummat Bulan Ke-1', 'Pengadaan bahan pokok dan penyaluran 1.500 porsi.', 15_000_000],
                    ['Operasional Dapur Ummat Bulan Ke-2', 'Penyaluran 1.500 porsi bulan kedua.', 15_000_000],
                ],
                'donations' => [
                    ['Warung Makan Berkah', 3_000_000, false, 'Bantu suplai beras & tenaga masak.', 9],
                    ['Siti Zulaikha', 500_000, false, 'Berkah untuk para sesepuh.', 3],
                ],
            ],

            // 14. Bantuan Operasi Bibir Sumbing Balita
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju2,
                'title' => 'Bantuan Operasi Bibir Sumbing 15 Balita Banyumas',
                'category' => 'kesehatan',
                'summary' => 'Kembalikan senyum indah 15 balita dari keluarga prasejahtera dengan tindakan operasi rekonstruksi bibir & lelangit.',
                'description' => "Bibir sumbing membuat balita kesulitan menyusu dan berisiko gizi buruk. Tim dokter bedah plastik relawan siap melakukan operasi gratis, bantuan donasi dibutuhkan untuk biaya laboratorium dan rawat inap.",
                'target' => 45_000_000,
                'deadline' => now()->addDays(45),
                'items' => [
                    ['Skrinning Lab & Swab Pre-Op', 15, 'pasien', 400_000],
                    ['Sewa Kamar Rawat Inap 2 Hari', 15, 'pasien', 1_000_000],
                    ['Obat Khusus Pasca Operasi & Nutrisi', 15, 'pasien', 1_600_000],
                ],
                'milestones' => [
                    ['Pemeriksaan laboratorium & tindakan medis 15 pasien', 'Tindakan operasi batch 1 dan 2.', 30_000_000],
                    ['Rawat inap & perawatan terapi bicara', 'Pemulihan pasca op dan evaluasi tumbuh kembang.', 15_000_000],
                ],
                'donations' => [
                    ['Komunitas Senyum Anak Nusantara', 10_000_000, false, 'Senyum mereka masa depan bangsa.', 16],
                    ['Anisa Rahma', 1_000_000, false, null, 7],
                ],
            ],

            // 15. Saluran Irigasi Sawah Petani Sukomaju
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju3,
                'title' => 'Rehabilitasi Saluran Irigasi Sawah 40 Hektar Sukomaju',
                'category' => 'infrastruktur',
                'summary' => 'Saluran irigasi tanah longsor menutup aliran air ke 40 hektar sawah petani. Mari bantu permanenkan dengan plengsengan batu.',
                'description' => "Musim tanam terancam gagal panen karena tanggul saluran air jebol dihantam erosi. Pembangunan plengsengan batu tebal sepanjang 150 meter akan mengamankan pasokan air sawah 60 petani lokal.",
                'target' => 54_000_000,
                'deadline' => now()->addDays(40),
                'items' => [
                    ['Batu Kali & Pasir Pasang', 1, 'paket', 22_000_000],
                    ['Semen Gresik 40kg', 300, 'sak', 70_000],
                    ['Upah Kelompok Tani (Gotong Royong)', 1, 'paket', 11_000_000],
                ],
                'milestones' => [
                    ['Material batu, pasir, dan semen', 'Pengiriman material ke pangkal saluran irigasi.', 35_000_000],
                    ['Pemasangan plengsengan batu & plesteran', 'Pekerjaan fisik bersama kelompok tani.', 19_000_000],
                ],
                'donations' => [
                    ['Gabungan Kelompok Tani Sukomaju', 5_000_000, false, 'Swadaya dari kas kelompok tani.', 14],
                    ['Ir. Bambang Triyono', 2_000_000, false, 'Petani adalah pahlawan pangan.', 9],
                ],
            ],

            // 16. Beasiswa Koding Anak Yatim
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju,
                'title' => 'Pelatihan Komputer & Koding 40 Anak Yatim Panti',
                'category' => 'pendidikan',
                'summary' => 'Memberikan keahlian digital, pemrograman web dasar, dan 10 unit laptop bekas layak untuk masa depan anak yatim.',
                'description' => "Dunia kerja saat ini membutuhkan keterampilan digital. Program ini melatih 40 remaja panti asuhan membuat website dan desain grafis selama 3 bulan intensif.",
                'target' => 36_000_000,
                'deadline' => now()->addDays(50),
                'items' => [
                    ['Laptop Bekas Core i5 Layak Pakai (10 unit)', 10, 'unit', 2_800_000],
                    ['Honor Pengajar & Modul Pelatihan 3 Bulan', 3, 'bulan', 2_000_000],
                    ['Koneksi Internet & Sertifikasi', 1, 'paket', 2_000_000],
                ],
                'milestones' => [
                    ['Pengadaan 10 unit laptop & modul koding', 'Pembelian laptop bekas berkualitas & setting lab kom.', 28_000_000],
                    ['Pelatihan intensif 3 bulan & sertifikasi akhir', 'Biaya instruktur dan ujian kelulusan.', 8_000_000],
                ],
                'donations' => [
                    ['Komunitas Developer Semarang (SemarangDev)', 12_000_000, false, 'Patungan komunitas programmer untuk adik-adik panti.', 11],
                    ['Rian Ekky', 1_000_000, false, null, 4],
                ],
            ],

            // 17. Alat Bantu Dengar Anak Tuli
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju2,
                'title' => 'Pengadaan Alat Bantu Dengar 20 Anak Tuli Prasejahtera',
                'category' => 'kemanusiaan',
                'summary' => 'Bantu 20 anak tuli mendengar indahnya dunia dan suara orang tua mereka untuk pertama kalinya.',
                'description' => "Gangguan pendengaran sejak lahir menghambat perkembangan bicara anak. Pengadaan alat bantu dengar (ABD) digital dan terapi wicara awal membuka kesempatan mereka bersekolah di sekolah umum.",
                'target' => 42_000_000,
                'deadline' => now()->addDays(40),
                'items' => [
                    ['Alat Bantu Dengar Digital BTE (20 pasang)', 20, 'unit', 1_800_000],
                    ['Pemeriksaan Audiometri & Cetak Earmold', 20, 'pasien', 300_000],
                ],
                'milestones' => [
                    ['Tes audiometri & pemesanan unit ABD', 'Pemeriksaan dokter THT dan pembuatan cetakan telinga.', 20_000_000],
                    ['Fitting alat bantu dengar & pelatihan awal wicara', 'Pemasangan alat dan bimbingan orang tua.', 22_000_000],
                ],
                'donations' => [
                    ['Yayasan Teman Dengar Indonesia', 10_000_000, false, 'Dukungan penuh untuk inklusivitas anak.', 13],
                    ['Hamba Allah', 5_000_000, true, 'Semoga menjadi amal jariah.', 6],
                ],
            ],

            // 18. Renovasi Panti Asuhan Wonosobo
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju,
                'title' => 'Renovasi Kamar Mandi & Kamar Tidur Panti Asuhan Wonosobo',
                'category' => 'sosial',
                'summary' => 'Kamar mandi panti asuhan yang dihuni 35 anak dalam kondisi rusak berat dan berjamur. Mari hadirkan tempat tinggal bersih.',
                'description' => "Panti Asuhan Kasih Harapan Wonosobo menampung 35 anak yatim piatu. Kondisi sanitasi kamar mandi saat ini sangat memprihatinkan dengan lantai retak dan pintu lapuk.",
                'target' => 33_000_000,
                'deadline' => now()->addDays(45),
                'items' => [
                    ['Keramik Lantai & Dinding Anti Slip', 120, 'dus', 75_000],
                    ['Kloset Duduk & Jongkok Saniter', 4, 'unit', 1_200_000],
                    ['Pintu PVC, Pipa & Aksesoris Mandi', 1, 'paket', 4_200_000],
                    ['Kasur Busa & Sprei Baru (20 bed)', 20, 'unit', 750_000],
                ],
                'milestones' => [
                    ['Renovasi fisik 4 kamar mandi panti', 'Pembelian sanitari dan pengerjaan lantai/pintu.', 18_000_000],
                    ['Pengadaan kasur tidur layak anak panti', 'Pembelian 20 set kasur busa baru.', 15_000_000],
                ],
                'donations' => [
                    ['Donatur Wonosobo Peduli', 8_000_000, false, 'Bantu adik-adik panti agar hidup lebih sehat.', 15],
                    ['Tri Wahyuni', 500_000, false, null, 8],
                ],
            ],

            // 19. Perahu Motor Listrik Nelayan Depok
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju3,
                'title' => 'Konversi Perahu Motor Listrik Ramah Lingkungan Nelayan Kecil',
                'category' => 'lingkungan',
                'summary' => 'Bantu 10 nelayan tradisional menghemat biaya BBM solar hingga 80% dengan konversi mesin tempel listrik berbasis baterai.',
                'description' => "Harga solar yang tinggi mencekik penghasilan nelayan kecil Pantai Depok. Konversi ke mesin listrik tenaga surya membuat operasional melaut jauh lebih hemat dan bebas polusi minyak.",
                'target' => 60_000_000,
                'deadline' => now()->addDays(60),
                'items' => [
                    ['Kit Motor Tempel Listrik 5 HP (10 set)', 10, 'set', 4_500_000],
                    ['Baterai Lithium LiFePO4 Marine 48V', 10, 'unit', 1_500_000],
                ],
                'milestones' => [
                    ['Pengadaan 10 kit motor listrik & baterai marine', 'Pembelian perangkat konversi mesin nelayan.', 45_000_000],
                    ['Instalasi & uji coba melaut bersama nelayan', 'Pemasangan teknis dan pendampingan operasional.', 15_000_000],
                ],
                'donations' => [
                    ['Koperasi Nelayan Bahari Mandiri', 10_000_000, false, 'Dukungan modal awal dari koperasi.', 17],
                    ['Budi Santoso', 1_000_000, false, 'Inovasi hijau yang sangat bagus!', 10],
                ],
            ],

            // 20. Restorasi Terumbu Karang Karimunjawa
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju3,
                'title' => 'Restorasi Terumbu Karang & Media Biorock Karimunjawa',
                'category' => 'lingkungan',
                'summary' => 'Memulihkan 500 meter persegi terumbu karang yang rusak akibat jangkar kapal dan pemanasan suhu laut.',
                'description' => "Kerusakan terumbu karang mengancam keberlanjutan biota laut Karimunjawa. Metode Biorock menggunakan arus listrik tegangan rendah terbukti mempercepat pertumbuhan karang hingga 5x lebih cepat.",
                'target' => 48_000_000,
                'deadline' => now()->addDays(70),
                'items' => [
                    ['Struktur Kerangka Besi Biorock (10 unit)', 10, 'unit', 2_500_000],
                    ['Kabel Laut & Power Supply Tenaga Surya', 1, 'set', 13_000_000],
                    ['Operasional Penyelam & Fragmen Karang', 1, 'paket', 10_000_000],
                ],
                'milestones' => [
                    ['Fabrikasi struktur besi Biorock & panel surya', 'Pembuatan rangka besi di darat.', 28_000_000],
                    ['Penurunan struktur laut & penempelan bibit karang', 'Aksi penyelaman dan transplantasi karang.', 20_000_000],
                ],
                'donations' => [
                    ['Club Penyelam Semarang (Semarang Scuba)', 8_000_000, false, 'Siap bantu relawan penyelam & donasi.', 19],
                    [null, 5_000_000, true, 'Jaga lautan Indonesia.', 12],
                ],
            ],

            // 21. Bantuan Erupsi Gunung Slamet
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju2,
                'title' => 'Bantuan Masker Medis & Logistik Erupsi Gunung Slamet',
                'category' => 'bencana',
                'summary' => 'Hujan abu vulkanik melanda 5 desa lereng Slamet. 3.000 warga butuh masker N95, pembersih mata, dan air bersih.',
                'description' => "Abu vulkanik yang tebal berisiko tinggi menyebabkan ISPA pada anak-anak dan lansia. Pembagian masker standar medis N95 dan obat tetes mata sangat krusial saat ini.",
                'target' => 35_000_000,
                'deadline' => now()->addDays(20),
                'items' => [
                    ['Masker Respirator N95 (5.000 pcs)', 5000, 'pcs', 4_000],
                    ['Obat Tetes Mata & Obat ISPA', 500, 'paket', 20_000],
                    ['Air Mineral Botol Dus (300 dus)', 300, 'dus', 16_666],
                ],
                'milestones' => [
                    ['Pengiriman 5.000 masker N95 & obat-obatan', 'Logistik kesehatan darurat ke posko bencana.', 25_000_000],
                    ['Penyaluran air bersih tangki ke pemukiman', 'Suplai air bersih untuk warga terdampak abu.', 10_000_000],
                ],
                'donations' => [
                    ['Ikatan Dokter Indonesia (IDI) Cabang Purwokerto', 10_000_000, false, 'Bantuan obat dan tenaga medis.', 5],
                    ['Budi Santoso', 500_000, false, null, 2],
                ],
            ],

            // 22. Pengadaan Posyandu Kit Digital
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju,
                'title' => 'Pengadaan Posyandu Kit & Timbangan Digital 12 Desa Stunting',
                'category' => 'kesehatan',
                'summary' => 'Pencegahan stunting dini dengan melengkapi 12 Posyandu alat ukur tinggi badan akurat (Infantometer) dan timbangan digital.',
                'description' => "Alat ukur manual di Posyandu pedesaan seringkali kurang presisi sehingga deteksi stunting terlambat. Peralatan antropometri standar Kemenkes ini akan memantau 600 balita secara akurat.",
                'target' => 36_000_000,
                'deadline' => now()->addDays(40),
                'items' => [
                    ['Paket Anthropometri Kit Stunting (12 set)', 12, 'set', 2_800_000],
                    ['Pita LILA & Modul Edukasi Gizi Ibu Hamil', 12, 'paket', 200_000],
                ],
                'milestones' => [
                    ['Pengadaan 12 set Alat Anthropometri Kit Kemenkes', 'Pembelian alat ukur digital berizin edar.', 30_000_000],
                    ['Pelatihan kader posyandu & sosialisasi gizi', 'Bimbingan teknis penggunaan alat kepada kader.', 6_000_000],
                ],
                'donations' => [
                    ['Komunitas Ibu Menyusui (AIMI Jateng)', 5_000_000, false, 'Cegah stunting demi generasi masa depan.', 10],
                    ['Gita Gutawa', 2_000_000, false, null, 4],
                ],
            ],

            // 23. Pembangunan MCK Komunal Higienis
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju,
                'title' => 'Pembangunan MCK Komunal Higienis Dusun Karanganyar',
                'category' => 'infrastruktur',
                'summary' => '30 keluarga di dusun belum memiliki jamban sehat dan masih BABS di sungai. Mari bangun 1 fasilitas MCK umum yang layak.',
                'description' => "Bongkar kebiasaan buang air besar sembarangan (BABS) di sungai yang menjadi sumber penyakit diare warga. MCK komunal 4 pintu dilengkapi septic tank bio-filter aman lingkungan.",
                'target' => 44_000_000,
                'deadline' => now()->addDays(50),
                'items' => [
                    ['Konstruksi Bangunan 4 Pintu & Keramik', 1, 'paket', 24_000_000],
                    ['Septic Tank Bio-Filter Ramah Lingkungan 2.000L', 1, 'unit', 12_000_000],
                    ['Sumur Timba / Pompa & Tandon Air', 1, 'set', 8_000_000],
                ],
                'milestones' => [
                    ['Pekerjaan fisik MCK & instalasi septic tank biofilter', 'Pembangunan gedung MCK dan sistem sanitasi.', 32_000_000],
                    ['Instalasi perpipaan & tandon air bersih', 'Penyelesaian sanitasi dan serah terima ke warga.', 12_000_000],
                ],
                'donations' => [
                    ['Puskesmas Karanganyar', 4_000_000, false, 'Dukungan program bebas BABS (ODF).', 11],
                    ['Hamba Allah', 3_000_000, true, null, 3],
                ],
            ],

            // 24. Perlengkapan Sekolah Anak Pemulung
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju,
                'title' => 'Paket Perlengkapan Sekolah 200 Anak Pemulung TPA Jatibarang',
                'category' => 'pendidikan',
                'summary' => 'Bantu 200 anak pemulung di kawasan TPA Jatibarang menyambut tahun ajaran baru dengan seragam, tas, dan alat tulis baru.',
                'description' => "Anak-anak pemulung seringkali memakai seragam lusuh bekas tumpukan sampah. Pembagian paket perlengkapan sekolah baru membangkitkan rasa percaya diri mereka untuk semangat belajar.",
                'target' => 40_000_000,
                'deadline' => now()->addDays(30),
                'items' => [
                    ['Paket Seragam Sekolah Lengkap (200 set)', 200, 'set', 120_000],
                    ['Tas Sekolah & Sepatu Hitam (200 set)', 200, 'set', 60_000],
                    ['Alat Tulis & Buku Tulis (200 paket)', 200, 'paket', 20_000],
                ],
                'milestones' => [
                    ['Pembelian seragam & sepatu sekolah 200 anak', 'Pengadaan pakaian dan alas kaki sekolah.', 30_000_000],
                    ['Pembelian tas & alat tulis serta penyerahan acara', 'Pembagian paket belajar di sekolah terbuka TPA.', 10_000_000],
                ],
                'donations' => [
                    ['Komunitas Peduli Anak TPA', 6_000_000, false, 'Senyum anak-anak adalah kebahagiaan kita.', 8],
                    ['Budi Santoso', 500_000, false, null, 2],
                ],
            ],

            // 25. Kebun Gizi Organik Keluarga
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju3,
                'title' => 'Kebun Gizi Organik Keluarga Pra-Sejahtera Desa Boyolali',
                'category' => 'sosial',
                'summary' => 'Memberdayakan 50 ibu rumah tangga untuk menanam sayuran organik mandiri di pekarangan rumah demi ketahanan pangan.',
                'description' => "Pelatihan dan pemberian bibit sayuran (cabe, tomat, bayam, kangkung) dalam polibag serta pupuk organik untuk menekan pengeluaran belanja dapur keluarga miskin.",
                'target' => 20_000_000,
                'deadline' => now()->addDays(40),
                'items' => [
                    ['Paket Bibit Sayuran Organik & Polibag (50 paket)', 50, 'paket', 250_000],
                    ['Pupuk Organik & Alat Siram Mantap', 50, 'paket', 100_000],
                    ['Pelatihan & Pendampingan Penyuluhan Pertanian', 1, 'paket', 2_500_000],
                ],
                'milestones' => [
                    ['Pengadaan bibit, media tanam, dan pupuk organik', 'Pembelian starter kit kebun gizi.', 15_000_000],
                    ['Pelatihan penanaman & pendampingan panen pertama', 'Pelatihan cara tanam dan perawatan tanaman.', 5_000_000],
                ],
                'donations' => [
                    ['Kelompok Wanita Tani (KWT) Melati', 2_500_000, false, 'Semangat mandiri pangan keluarga!', 9],
                ],
            ],

            // 26. Revitalisasi Sanggar Seni Pemuda
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju3,
                'title' => 'Revitalisasi Sanggar Seni Dan Budaya Pemuda Desa Blora',
                'category' => 'sosial',
                'summary' => 'Melestarikan kesenian gamelan dan tari tradisional untuk anak-anak muda desa agar terhindar dari kegiatan negatif.',
                'description' => "Sanggar tari dan gamelan di desa Blora vakum karena alat musik kendang dan rebab rusak. Pengadaan perbaikan gamelan akan menghidupkan kembali tempat latihan pemuda.",
                'target' => 25_000_000,
                'deadline' => now()->addDays(45),
                'items' => [
                    ['Perbaikan & Servis Set Gamelan Jawa', 1, 'set', 15_000_000],
                    ['Pengadaan Kostum Tari Tradisional (10 set)', 10, 'set', 800_000],
                    ['Honor Pelatih Seni 6 Bulan', 6, 'bulan', 333_333],
                ],
                'milestones' => [
                    ['Servis alat gamelan & pembelian kostum tari', 'Perbaikan instrumen musik dan kostum.', 20_000_000],
                    ['Operasional latihan rutin & pementasan budaya desa', 'Pementasan perdana kelulusan santri sanggar.', 5_000_000],
                ],
                'donations' => [
                    ['Dinas Kebudayaan Blora', 5_000_000, false, 'Rawat budaya adiluhung bangsa.', 14],
                ],
            ],

            // 27. Pelatihan Kewirausahaan Ibu Rumah Tangga (Pending)
            [
                'status' => Campaign::STATUS_PENDING,
                'user' => $pengaju,
                'title' => 'Pelatihan Kewirausahaan & Modal Usaha Ibu Rumah Tangga',
                'category' => 'sosial',
                'summary' => 'Memberikan pelatihan pembuatan olahan makanan lokal dan alat produksi untuk 20 ibu korban PHK suami.',
                'description' => "Bantu 20 ibu rumah tangga mandiri secara ekonomi lewat usaha jualan keripik dan kue kering lokal dengan bantuan spinner minyak dan sealer kemasan modern.",
                'target' => 22_000_000,
                'deadline' => now()->addDays(60),
                'items' => [
                    ['Mesin Spinner Peniris Minyak (5 unit)', 5, 'unit', 2_000_000],
                    ['Impulse Sealer & Kemasan Aluminium Foil', 20, 'set', 350_000],
                    ['Bahan Baku Awal produksi (20 paket)', 20, 'paket', 250_000],
                ],
                'milestones' => [
                    ['Pembelian peralatan spinner & sealer kemasan', 'Pengadaan mesin pendukung produksi.', 15_000_000],
                    ['Bahan baku & pendampingan sertifikasi halal', 'Pembelian bahan dan pendaftaran PIRT.', 7_000_000],
                ],
                'donations' => [],
            ],

            // 28. Peralatan Relawan Kebencanaan (Pending)
            [
                'status' => Campaign::STATUS_PENDING,
                'user' => $pengaju2,
                'title' => 'Peralatan Pertolongan Pertama & APD Relawan Kebencanaan',
                'category' => 'bencana',
                'summary' => 'Melengkapi 30 anggota relawan SAR independen dengan rompi pelampung, helm safety, dan chain saw evakuasi pohon.',
                'description' => "Relawan SAR lokal seringkali bertaruh nyawa menyelamatkan korban banjir tanpa APD yang memadai. Pengadaan peralatan keselamatan ini meningkatkan respon cepat kebencanaan.",
                'target' => 27_000_000,
                'deadline' => now()->addDays(50),
                'items' => [
                    ['Rompi Life Jacket Standard SAR (30 unit)', 30, 'unit', 350_000],
                    ['Helm Safety Rescue & Headlamp (30 unit)', 30, 'unit', 250_000],
                    ['Chainsaw Potong Pohon Tumbang (2 unit)', 2, 'unit', 4_500_000],
                ],
                'milestones' => [
                    ['Pengadaan APD perorangan (Life jacket, helm, headlamp)', 'Pembelian peralatan perlindungan diri relawan.', 18_000_000],
                    ['Pengadaan Chainsaw & Tali Carmantel Rescue', 'Pembelian alat evakuasi darurat.', 9_000_000],
                ],
                'donations' => [],
            ],

            // 29. Dapur Umum Tanggap Longsor (Pending)
            [
                'status' => Campaign::STATUS_PENDING,
                'user' => $pengaju2,
                'title' => 'Dapur Umum Tanggap Darurat Tanah Longsor Karanganyar',
                'category' => 'bencana',
                'summary' => 'Suplai makanan hangat dan air bersih untuk 300 pengungsi korban longsor bukit Karanganyar.',
                'description' => "Tanah longsor menimbun 8 rumah dan memaksa 300 warga mengungsi di balai desa. Dapur umum darurat disiagakan untuk menyuplai konsumsi 3 kali sehari.",
                'target' => 30_000_000,
                'deadline' => now()->addDays(25),
                'items' => [
                    ['Bahan Baku Makanan 3 Porsi X 7 Hari', 1, 'paket', 21_000_000],
                    ['Perlengkapan Masak Dapur Lapangan & Tabung Gas', 1, 'set', 9_000_000],
                ],
                'milestones' => [
                    ['Pengadaan bahan baku konsumsi hari 1-7', 'Suplai konsumsi darurat.', 21_000_000],
                    ['Operasional dapur posko & pembersihan', 'Penutupan posko dan evaluasi.', 9_000_000],
                ],
                'donations' => [],
            ],

            // 30. Sumur Resapan Pengendali Banjir
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju3,
                'title' => 'Sumur Resapan Dan Biopori Pengendali Banjir Semarang',
                'category' => 'lingkungan',
                'summary' => 'Membuat 50 lubang resapan biopori dan 5 sumur resapan dalam di pemukiman padat untuk mengurangi genangan air hujan.',
                'description' => "Genangan air hujan sering merendam jalan pemukiman karena tiadanya resapan tanah. Pembuatan sumur resapan mengalirkan air hujan kembali ke dalam tanah secara efektif.",
                'target' => 32_000_000,
                'deadline' => now()->addDays(60),
                'items' => [
                    ['Konstruksi Sumur Resapan Buis Beton (5 unit)', 5, 'unit', 4_800_000],
                    ['Pipa Pipa PVC Perforated & Kerikil Resapan', 1, 'paket', 5_000_000],
                    ['Pembuatan 50 Lubang Biopori & Tutup PVC', 50, 'unit', 60_000],
                ],
                'milestones' => [
                    ['Pengeboran & pemasangan 5 sumur resapan buis beton', 'Pekerjaan sumur resapan dalam.', 24_000_000],
                    ['Pemasangan 50 biopori & sosialisasi komposter', 'Pemasangan biopori pekarangan rumah warga.', 8_000_000],
                ],
                'donations' => [
                    ['Komunitas Semarang Hijau', 5_000_000, false, 'Solusi nyata banjir pemukiman.', 12],
                    ['Dwi Cahyono', 500_000, false, null, 5],
                ],
            ],

            // 31. Solar Panel Listrik Puskesmas Pelosok
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju2,
                'title' => 'Solar Panel Listrik Mandiri Puskesmas Pembantu Pelosok',
                'category' => 'infrastruktur',
                'summary' => 'Seringnya pemadaman listrik mengganggu penyimpanan vaksin dan persalinan malam hari. Mari pasang solar panel 2.000W.',
                'description' => "Listrik di daerah pelosok sering padam hingga 8 jam. Pasokan listrik tenaga surya dengan baterai cadangan memastikan kulkas vaksin dan lampu operasi tetap menyala 24 jam.",
                'target' => 52_000_000,
                'deadline' => now()->addDays(55),
                'items' => [
                    ['Panel Surya Monokristalin 400W (5 keping)', 5, 'unit', 2_500_000],
                    ['Inverter Hybrid 3KW & Solar Charger Controller', 1, 'unit', 14_500_000],
                    ['Baterai LiFePO4 48V 100Ah Deep Cycle', 1, 'unit', 20_000_000],
                    ['Kabel & Rak Mounting Atap', 1, 'set', 5_000_000],
                ],
                'milestones' => [
                    ['Pengadaan komponen solar panel, inverter, & baterai', 'Pembelian perangkat energi terbarukan.', 45_000_000],
                    ['Instalasi perakitan & pengujian sistem backup', 'Pemasangan di puskesmas pembantu.', 7_000_000],
                ],
                'donations' => [
                    ['Perhimpunan Dokter Puskesmas Jateng', 10_000_000, false, 'Sangat krusial untuk keamanan penyimpan vaksin.', 15],
                    [null, 2_000_000, true, null, 8],
                ],
            ],

            // 32. Kursi Roda & Alat Bantu Disabilitas
            [
                'status' => Campaign::STATUS_APPROVED,
                'user' => $pengaju,
                'title' => 'Pengadaan Kursi Roda & Alat Bantu Disabilitas Cilacap',
                'category' => 'kemanusiaan',
                'summary' => 'Membantu 25 warga disabilitas fisik & lansia lumpuh mendapatkan kursi roda standar medis dan kruk penyangga.',
                'description' => "Bantu saudara-saudara kita yang mengalami kelumpuhan agar dapat kembali beraktivitas mandiri dan tidak hanya terbaring di tempat tidur.",
                'target' => 37_500_000,
                'deadline' => now()->addDays(40),
                'items' => [
                    ['Kursi Roda Standard Velg Racing (20 unit)', 20, 'unit', 1_500_000],
                    ['Tongkat Kruk Penyangga Aluminium (10 pasang)', 10, 'pasang', 250_000],
                    ['Tongkat Piramid Kaki 4 (10 unit)', 10, 'unit', 500_000],
                ],
                'milestones' => [
                    ['Pembelian 20 unit kursi roda & tongkat kruk', 'Pengadaan alat bantu mobilitas.', 32_500_000],
                    ['Penyaluran langsung ke rumah-rumah penerima manfaat', 'Penyerahan bantuan dan dokumentasi.', 5_000_000],
                ],
                'donations' => [
                    ['Komunitas Disabilitas Mandiri', 5_000_000, false, 'Semangat untuk teman-teman!', 11],
                    ['Budi Santoso', 1_000_000, false, null, 3],
                ],
            ],
        ];

        foreach ($definitions as $def) {
            $campaign = Campaign::create([
                'user_id' => $def['user']->id,
                'title' => $def['title'],
                'slug' => Str::slug($def['title']),
                'category' => $def['category'],
                'summary' => $def['summary'],
                'description' => $def['description'],
                'target_amount' => $def['target'],
                'deadline' => $def['deadline'],
                'status' => $def['status'],
                'review_note' => $def['review_note'] ?? null,
                'submitted_at' => $def['status'] === Campaign::STATUS_DRAFT ? null : now()->subDays(15),
                'reviewed_at' => in_array($def['status'], [Campaign::STATUS_APPROVED, Campaign::STATUS_REJECTED], true)
                    ? now()->subDays(14) : null,
                'reviewed_by' => in_array($def['status'], [Campaign::STATUS_APPROVED, Campaign::STATUS_REJECTED], true)
                    ? $admin->id : null,
            ]);

            foreach ($def['items'] as $i => [$name, $qty, $unit, $price]) {
                $campaign->items()->create([
                    'name' => $name,
                    'quantity' => $qty,
                    'unit' => $unit,
                    'unit_price' => $price,
                    'subtotal' => $qty * $price,
                    'sort_order' => $i,
                ]);
            }

            foreach ($def['milestones'] as $i => [$title, $description, $amount]) {
                $campaign->milestones()->create([
                    'sequence' => $i + 1,
                    'title' => $title,
                    'description' => $description,
                    'amount' => $amount,
                    'status' => Milestone::STATUS_LOCKED,
                ]);
            }

            $audit->record('campaign.created', $campaign, ['judul' => $campaign->title], $def['user']);

            if ($def['status'] !== Campaign::STATUS_DRAFT) {
                $audit->record('campaign.submitted', $campaign, ['judul' => $campaign->title], $def['user']);
            }

            if ($def['status'] === Campaign::STATUS_APPROVED) {
                $audit->record('campaign.approved', $campaign, ['judul' => $campaign->title], $admin);
            } elseif ($def['status'] === Campaign::STATUS_REJECTED) {
                $audit->record('campaign.rejected', $campaign, [
                    'judul' => $campaign->title,
                    'alasan' => $def['review_note'],
                ], $admin);
            }

            // --- Donasi ------------------------------------------------------
            foreach ($def['donations'] as [$name, $amount, $anonymous, $note, $daysAgo]) {
                $donation = Donation::create([
                    'reference' => 'sementara-'.Str::random(12),
                    'campaign_id' => $campaign->id,
                    'user_id' => $name === 'Budi Santoso' ? $donatur->id : null,
                    'donor_name' => $name,
                    'donor_email' => $name ? Str::slug((string) $name).'@contoh.test' : 'anonim@contoh.test',
                    'is_anonymous' => $anonymous,
                    'message' => $note,
                    'amount' => $amount,
                    'status' => Donation::STATUS_PAID,
                    'payment_channel' => 'qris',
                    'gateway' => 'mock',
                    'gateway_reference' => 'MOCK-'.Str::upper(Str::random(12)),
                    'paid_at' => now()->subDays($daysAgo),
                    'verification_code' => str_repeat('0', 64),
                ]);

                $donation->reference = $donation->buildReference();
                $donation->verification_code = $receipts->for($donation);
                $donation->created_at = now()->subDays($daysAgo);
                $donation->save();

                $audit->record('donation.paid', $donation, [
                    'reference' => $donation->reference,
                    'amount' => $donation->amount,
                    'campaign' => $campaign->title,
                ], null);
            }

            $campaign->recalculateTotals();

            // Buka tahap pertama pada kampanye yang dananya sudah mencukupi.
            $donations = app(\App\Services\DonationService::class);
            $donations->unlockMilestones($campaign->fresh());

            // --- Pencairan tahap 1 (hanya untuk kampanye yang mendefinisikannya) ---
            if (! empty($def['disbursement'])) {
                $spec = $def['disbursement'];
                $milestone = $campaign->milestones()->where('sequence', $spec['milestone'])->first();

                if ($milestone) {
                    $disbursement = Disbursement::create([
                        'reference' => 'sementara-'.Str::random(12),
                        'campaign_id' => $campaign->id,
                        'milestone_id' => $milestone->id,
                        'requested_by' => $def['user']->id,
                        'amount' => $spec['amount'],
                        'purpose' => $spec['purpose'],
                        'payee_bank_name' => $def['user']->bank_name,
                        'payee_account_number' => $def['user']->bank_account_number,
                        'payee_account_holder' => $def['user']->bank_account_holder,
                        'status' => Disbursement::STATUS_RELEASED,
                        'review_note' => 'Rincian kebutuhan sesuai RAB. Disetujui.',
                        'reviewed_by' => $admin->id,
                        'reviewed_at' => now()->subDays($spec['days_ago']),
                        'released_at' => now()->subDays($spec['days_ago']),
                    ]);

                    $disbursement->reference = $disbursement->buildReference();
                    $disbursement->created_at = now()->subDays($spec['days_ago'] + 1);
                    $disbursement->save();

                    $milestone->update(['status' => Milestone::STATUS_DISBURSED]);

                    $audit->record('disbursement.requested', $disbursement, [
                        'reference' => $disbursement->reference,
                        'amount' => $disbursement->amount,
                    ], $def['user']);
                    $audit->record('disbursement.approved', $disbursement, [
                        'reference' => $disbursement->reference,
                    ], $admin);
                    $audit->record('disbursement.released', $disbursement, [
                        'reference' => $disbursement->reference,
                        'amount' => $disbursement->amount,
                    ], $admin);

                    $campaign->recalculateTotals();
                }
            }

            // --- Laporan pertanggungjawaban tahap 1 ---
            if (! empty($def['expenses'])) {
                $milestone = $campaign->milestones()->where('sequence', 1)->first();
                $items = $campaign->items()->get()->values();

                foreach ($def['expenses'] as [$title, $itemIndex, $amount, $daysAgo, $description, $notaFile]) {
                    $notaPath = $this->salinNota($notaFile, $campaign->id);

                    $expense = ExpenseReport::create([
                        'campaign_id' => $campaign->id,
                        'milestone_id' => $milestone?->id,
                        'campaign_item_id' => $items[$itemIndex]->id ?? null,
                        'created_by' => $def['user']->id,
                        'title' => $title,
                        'description' => $description,
                        'amount' => $amount,
                        'spent_on' => now()->subDays($daysAgo)->toDateString(),
                        'receipt_path' => $notaPath,
                        'receipt_hash' => $notaPath
                            ? hash_file('sha256', database_path('seeders/nota/'.$notaFile))
                            : null,
                        'status' => ExpenseReport::STATUS_VERIFIED,
                        'review_note' => 'Nota cocok dengan item RAB.',
                        'reviewed_by' => $admin->id,
                        'reviewed_at' => now()->subDays($daysAgo - 1),
                    ]);

                    $audit->record('expense.reported', $expense, [
                        'judul' => $expense->title,
                        'amount' => $expense->amount,
                    ], $def['user']);
                    $audit->record('expense.verified', $expense, [
                        'judul' => $expense->title,
                    ], $admin);
                }

                $milestone?->update(['status' => Milestone::STATUS_REPORTED]);
                $donations->unlockMilestones($campaign->fresh());
            }
        }

        $this->command?->info('Seeder selesai! 32 Kampanye telah sukses dibuat.');
    }

    private function salinNota(string $namaBerkas, int $campaignId): ?string
    {
        $sumber = database_path('seeders/nota/'.$namaBerkas);

        if (! is_file($sumber)) {
            return null;
        }

        $tujuan = 'lpj/'.$campaignId.'/'.$namaBerkas;
        Storage::disk('local')->put($tujuan, file_get_contents($sumber));

        return $tujuan;
    }
}
