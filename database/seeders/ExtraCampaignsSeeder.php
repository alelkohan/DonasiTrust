<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ReceiptVerifier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExtraCampaignsSeeder extends Seeder
{
    public function run(): void
    {
        $audit = app(AuditLogger::class);
        $receipts = app(ReceiptVerifier::class);
        $admin = User::where('role', User::ROLE_ADMIN)->first() ?? User::first();

        // 1. Create realistic pengaju organizations
        $pengajuData = [
            [
                'name' => 'Dr. Aris Setiawan',
                'email' => 'medika@donasitrust.test',
                'organization' => 'Yayasan Medika Peduli Indonesia',
                'bank' => 'Bank Mandiri',
                'acc' => '1370019283741',
            ],
            [
                'name' => 'Dewi Rahmawati',
                'email' => 'anakbangsa@donasitrust.test',
                'organization' => 'Lembaga Perlindungan & Edukasi Anak',
                'bank' => 'BCA',
                'acc' => '8820491823',
            ],
            [
                'name' => 'Bambang Triyono',
                'email' => 'tanggapbencana@donasitrust.test',
                'organization' => 'Aksi Cepat Relawan Tanggap Bencana',
                'bank' => 'BRI',
                'acc' => '019201029384501',
            ],
            [
                'name' => 'Hj. Ratna Sari',
                'email' => 'kasihibu@donasitrust.test',
                'organization' => 'Yayasan Panti Asuhan Kasih Ibu',
                'bank' => 'BNI',
                'acc' => '0982345112',
            ],
            [
                'name' => 'Ir. Eko Prasetyo',
                'email' => 'semestahijau@donasitrust.test',
                'organization' => 'Komunitas Konservasi Semesta Hijau',
                'bank' => 'BSI',
                'acc' => '7123984712',
            ],
            [
                'name' => 'H. M. Yusuf',
                'email' => 'airterang@donasitrust.test',
                'organization' => 'Yayasan Wakaf Air Bersih Nusantara',
                'bank' => 'Bank Mandiri',
                'acc' => '1420088761234',
            ],
        ];

        $pengajuUsers = [];
        foreach ($pengajuData as $p) {
            $user = User::firstOrCreate(
                ['email' => $p['email']],
                [
                    'name' => $p['name'],
                    'password' => bcrypt('password123'),
                    'role' => User::ROLE_PENGAJU,
                    'phone' => '08'.rand(111111111, 999999999),
                    'organization' => $p['organization'],
                    'identity_number_last4' => (string) rand(1000, 9999),
                    'identity_number_hash' => User::hashIdentityNumber((string) rand(3100000000000000, 3500000000000000)),
                    'bank_name' => $p['bank'],
                    'bank_account_number' => $p['acc'],
                    'bank_account_holder' => strtoupper($p['name']),
                    'verification_status' => User::VERIFICATION_VERIFIED,
                    'verified_at' => now()->subDays(rand(10, 60)),
                    'verified_by' => $admin?->id,
                    'email_verified_at' => now(),
                ]
            );
            $pengajuUsers[] = $user;
        }

        // Donors pool for creating realistic donation history
        $donorNames = [
            'Rizky Pratama', 'Siti Aminah', 'Hendra Wijaya', 'Hj. Aminah Nur', 'Anugerah Perkasa',
            'Dina Mariana', 'Fajar Ramadhan', 'PT Karya Utama', 'Hamba Allah', 'Komunitas Motor Peduli',
            'Alumni SMAN 1', 'Dedi Kurniawan', 'Nadia Putri', 'Keluarga Perkasa', 'Dr. Tri Atmojo'
        ];

        // 2. Define 20 detailed campaigns
        $campaignList = [
            [
                'title' => 'Bantu Operasi Jantung Balita Hafiz (2 Tahun)',
                'category' => 'kesehatan',
                'summary' => 'Hafiz menderita kelainan jantung bawaan (VSD) dan membutuhkan tindakan operasi penutupan secepatnya.',
                'description' => "Balita Hafiz berusia 2 tahun didiagnosis menderita Defek Septum Ventrikel (VSD) sejak usia 6 bulan. Ayahnya bekerja sebagai buruh harian lepas dan ibunya ibu rumah tangga.\n\nBiaya operasi dan perawatan pasca operasi di rumah sakit rujukan memerlukan pendampingan alat khusus yang tidak seluruhnya ditanggung BPJS. Kami menggalang dana untuk menutup biaya obat pendamping, alat medis, dan operasional keluarga selama pengobatan.",
                'target' => 45_000_000,
                'cover' => 'https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?auto=format&fit=crop&w=800&q=80',
                'items' => [
                    ['Alat bantu pernapasan portable', 1, 'unit', 12_000_000],
                    ['Obat khusus pendamping operasi', 1, 'paket', 18_000_000],
                    ['Akomodasi & operasional rawat inap 3 minggu', 21, 'hari', 714_285],
                ],
                'milestones' => [
                    ['Persiapan & Tindakan Operasi Utama', 'Pembelian alat pendamping dan obat pra-operasi', 30_000_000],
                    ['Perawatan ICU & Pemulihan Pasca Operasi', 'Biaya obat pemulihan dan rawat inap', 15_000_000],
                ],
            ],
            [
                'title' => 'Tanggap Darurat Dapur Umum Banjir Bandang Demak',
                'category' => 'bencana',
                'summary' => 'Menyediakan 1.500 porsi makanan hangat per hari untuk warga terdampak banjir di 3 desa terisolasi.',
                'description' => "Banjir bandang merendam ratusan rumah di Demak dengan ketinggian air mencapai 1,5 meter. Banyak warga mengungsi di tanggul tanpa fasilitas memasak.\n\nTim relawan mendirikan Dapur Umum Darurat untuk memasak 1.500 porsi makanan sehat tiga kali sehari selama 7 hari masa darurat, serta mendistribusikan air bersih dan selimut.",
                'target' => 35_000_000,
                'cover' => 'https://images.unsplash.com/photo-1547683905-f686c993aae5?auto=format&fit=crop&w=800&q=80',
                'items' => [
                    ['Bahan makanan pokok (Beras, telur, minyak, sayur)', 7, 'hari', 3_500_000],
                    ['Air minum kemasan galon & botol', 200, 'dus', 35_000],
                    ['Perlengkapan sanitasi & kebersihan dapur', 1, 'paket', 3_500_000],
                ],
                'milestones' => [
                    ['Dapur Umum Tahap 1 (Hari 1-4)', 'Belanja sembako massal dan logistik posko awal', 20_000_000],
                    ['Dapur Umum Tahap 2 & Kit Pemulihan', 'Operasional dapur lanjutan dan distribusi paket kebersihan', 15_000_000],
                ],
            ],
            [
                'title' => 'Renovasi Asrama & MCK Panti Asuhan Kasih Ibu',
                'category' => 'sosial',
                'summary' => 'Fasilitas kamar mandi panti rusak berat dan kran air mati, melayani 42 anak yatim piatu.',
                'description' => "Panti Asuhan Kasih Ibu menampung 42 anak yatim dan dhuafa. Saat ini, 2 dari 3 kamar mandi tidak dapat digunakan karena pipa bocor dan lantai amblas.\n\nProgram ini bertujuan merehabilitasi total fasilitas MCK, mengganti penampungan air, dan memperbaiki atap asrama putri yang bocor saat hujan deras.",
                'target' => 28_000_000,
                'cover' => 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=800&q=80',
                'items' => [
                    ['Keramik, semen, sanitari & kloset', 1, 'paket', 12_000_000],
                    ['Pipa PVC, tandon 1000L & pompa air', 1, 'set', 6_500_000],
                    ['Tukang bangunan (2 orang, 15 hari)', 30, 'hari-orang', 316_666],
                ],
                'milestones' => [
                    ['Rehabilitasi Fisik MCK & Sanitasi', 'Pembongkaran, perbaikan pipa, dan pembetonan ulang', 18_000_000],
                    ['Finishing & Pemasangan Sanitari', 'Pemasangan keramik, kloset, tandon air, dan pengecatan', 10_000_000],
                ],
            ],
            [
                'title' => 'Penanaman 2.000 Mangrove Pesisir Abrasi Brebes',
                'category' => 'lingkungan',
                'summary' => 'Mencegah abrasi parah yang menenggelamkan pemukiman pesisir Brebes melalui penanaman bibit mangrove.',
                'description' => "Garis pantai di Brebes terus terkikis abrasi hingga 5 meter setiap tahunnya. Beberapa rumah warga telah terendam air laut saat pasang tinggi.\n\nGerakan ini akan menanam 2.000 bibit mangrove jenis Rhizophora mucronata lengkap dengan ajir bambu pelindung dan perawatan intensif selama 6 bulan oleh kelompok tani tambak lokal.",
                'target' => 25_000_000,
                'cover' => 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=800&q=80',
                'items' => [
                    ['Bibit Mangrove Rhizophora', 2000, 'batang', 7_500],
                    ['Ajir bambu pelindung & tali pelindung', 2000, 'set', 3_500],
                    ['Honor perawatan kelompok tani (6 bulan)', 6, 'bulan', 500_000],
                ],
                'milestones' => [
                    ['Pengadaan Bibit & Pembibitan di Lokasi', 'Pembelian bibit mangrove dan ajir pelindung', 15_000_000],
                    ['Penanaman Masal & Pemeliharaan 6 Bulan', 'Aksi penanaman serta monitoring pertumbuhan bibit', 10_000_000],
                ],
            ],
            [
                'title' => 'Pembangunan Sumur Dalam & Pipanisasi Desa Kekeringan',
                'category' => 'infrastruktur',
                'summary' => 'Sumur bor kedalaman 80m dan jaringan pipa untuk 120 KK di Dusun Gunungkidul yang krisis air.',
                'description' => "Musim kemarau membuat sumber air warga Dusun Watugajah mengering. Warga harus membeli air tangki seharga Rp150.000 per tangki yang hanya bertahan 5 hari.\n\nKami berencana mengebor sumur dalam kedalaman 80 meter, membangun bak penampung utama 5.000 liter, dan menyalurkan pipa ke 4 titik kran umum desa.",
                'target' => 55_000_000,
                'cover' => 'https://images.unsplash.com/photo-1541888946425-d0fbb186a5b3?auto=format&fit=crop&w=800&q=80',
                'items' => [
                    ['Pengeboran sumur dalam 80 meter', 80, 'meter', 450_000],
                    ['Pompa submersible industri & panel surya', 1, 'unit', 11_000_000],
                    ['Tandon air 5.000L & konstruksi menara', 1, 'set', 8_000_000],
                    ['Pipanisasi HDPE 1,5 inch & kran umum', 500, 'meter', 0],
                ],
                'milestones' => [
                    ['Pengeboran & Tes Uji Debit Air', 'Proses pengeboran 80 meter dan uji kelayakan air', 30_000_000],
                    ['Konstruksi Menara Tandon & Pipanisasi', 'Pembangunan bak penampung dan penyambungan pipa warga', 25_000_000],
                ],
            ],
            [
                'title' => 'Beasiswa Transportasi & Alat Tulis 50 Anak Nelayan',
                'category' => 'pendidikan',
                'summary' => 'Bantuan perlengkapan sekolah dan sepeda untuk anak nelayan kurang mampu di pesisir Cilacap.',
                'description' => "Banyak anak nelayan di Desa Kampung Laut harus menempuh jalan kaki sejauh 4 km untuk bersekolah. Tak jarang mereka memilih putus sekolah karena melaut bersama orang tua.\n\nProgram ini menyediakan 50 paket perlengkapan sekolah (seragam, tas, sepatu, buku) serta 15 unit sepeda gayung untuk siswa yang tinggal paling jauh.",
                'target' => 30_000_000,
                'cover' => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=800&q=80',
                'items' => [
                    ['Paket seragam, tas, sepatu & alat tulis', 50, 'paket', 400_000],
                    ['Sepeda sekolah tahan korosi', 15, 'unit', 666_666],
                ],
                'milestones' => [
                    ['Pengadaan Paket Perlengkapan Sekolah', 'Pembelian 50 paket tas, sepatu, dan seragam', 18_000_000],
                    ['Pembelian Sepeda & Penyaluran', 'Pengadaan 15 sepeda dan penyaluran langsung', 12_000_000],
                ],
            ],
            [
                'title' => 'Operasi Katarak Gratis untuk 30 Lansia Dhuafa',
                'category' => 'kesehatan',
                'summary' => 'Mengembalikan penglihatan 30 lansia dhuafa agar dapat beraktivitas dan beribadah secara mandiri.',
                'description' => "Katarak menjadi penyebab utama kebutaan pada lansia di pedesaan. Banyak dari mereka hidup sebatang kara dan tidak memiliki akses atau biaya pendampingan medis.\n\nBekerja sama dengan tim dokter spesialis mata, kami menyelenggarakan bakti sosial operasi katarak metode Phacoemulsification gratis untuk 30 lansia yang membutuhkan.",
                'target' => 42_000_000,
                'cover' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=800&q=80',
                'items' => [
                    ['Paket lensa intraokular & medis per pasien', 30, 'orang', 1_100_000],
                    ['Obat Tetes mata & kaca mata pasca operasi', 30, 'paket', 150_000],
                    ['Sewa ruang OK & tim medis spesialis', 1, 'paket', 4_500_000],
                ],
                'milestones' => [
                    ['Skrining Medis & Pengadaan Lensa', 'Pemeriksaan mata calon pasien dan pembelian lensa khusus', 22_000_000],
                    ['Pelaksanaan Operasi & Kontrol Pemulihan', 'Tindakan operasi dan pemeriksaan ulang pasca operasi', 20_000_000],
                ],
            ],
            [
                'title' => 'Pengadaan Laboratorium Komputer Komunitas Pelosok',
                'category' => 'pendidikan',
                'summary' => '10 komputer bekas terintegrasi untuk pelatihan literasi digital anak desa di lereng Gunung Merbabu.',
                'description' => "Siswa di SMP Satu Atap Selo tidak pernah menyentuh komputer secara langsung. Saat ANBK (Asesmen Nasional), mereka harus menumpang di sekolah kota yang berjarak 20 km.\n\nKami menggalang dana untuk membeli 10 unit PC reconditioned layak pakai, meja laptop, serta instalasi jaringan internet satelit untuk laboratorium belajar desa.",
                'target' => 38_000_000,
                'cover' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=800&q=80',
                'items' => [
                    ['PC Core i5 RAM 8GB + Monitor 19 inch', 10, 'unit', 3_000_000],
                    ['Networking (Router, Switch Hub, Kabel LAN)', 1, 'set', 3_000_000],
                    ['Meja lab & kursi siswa (10 set)', 10, 'set', 500_000],
                ],
                'milestones' => [
                    ['Pembelian Unit PC & Pengiriman', 'Pengadaan 10 unit komputer teruji dan periferal', 30_000_000],
                    ['Instalasi Jaringan & Pelatihan Guru', 'Setting lab komputer dan workshop literasi digital dasar', 8_000_000],
                ],
            ],
            [
                'title' => 'Ambulans Gratis untuk Warga Desa Terpencil Boyolali',
                'category' => 'kesehatan',
                'summary' => 'Pengadaan mobil armada ambulans darurat gratis melayani ibu hamil & pasien kritis di pegunungan.',
                'description' => "Jarak desa ke RSUD terdekat mencapai 35 km dengan jalanan berlekuk-lekuk. Seringkali warga terpaksa membawa pasien kritis atau ibu hendak melahirkan menggunakan mobil bak terbuka.\n\nDonasi ini dipakai untuk memodifikasi satu unit minibus menjadi mobil ambulans standar darurat lengkap dengan tabung oksigen, tandu gantung, dan sirine emergency.",
                'target' => 85_000_000,
                'cover' => 'https://images.unsplash.com/photo-1587745416684-47953f16f02f?auto=format&fit=crop&w=800&q=80',
                'items' => [
                    ['DP & Pelunasan Minibus Karoseri Ambulans', 1, 'unit', 70_000_000],
                    ['Peralatan Medis (Stretcher, Oksigen, P3K)', 1, 'set', 10_000_000],
                    ['Branding & Perizinan Operasional', 1, 'paket', 5_000_000],
                ],
                'milestones' => [
                    ['Pengadaan Unit Kendaraan Utama', 'Pembelian unit mobil dasar dan administrasi perizinan', 55_000_000],
                    ['Karoseri Spesifikasi Medis & Alkes', 'Modifikasi interior ambulans dan pemasangan alat kesehatan', 30_000_000],
                ],
            ],
            [
                'title' => 'Revitalisasi Perpustakaan Keliling Motor Roda Tiga',
                'category' => 'pendidikan',
                'summary' => 'Menjangkau 15 sekolah dasar di pedalaman dengan membawa 1.000 buku bacaan anak berkualitas.',
                'description' => "Minat baca anak-anak pedesaan sangat tinggi, namun perpustakaan sekolah mereka hanya berisi buku pelajaran tua. Pustaka Keliling kami telah beroperasi 3 tahun dengan motor bebek biasa.\n\nKami ingin meng-upgrade kendaraan menjadi motor roda tiga box karoseri agar mampu membawa lebih banyak buku cerita, meja lipat baca, dan proyektor edukasi.",
                'target' => 22_000_000,
                'cover' => 'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?auto=format&fit=crop&w=800&q=80',
                'items' => [
                    ['Motor Roda Tiga Box Karoseri Perpustakaan', 1, 'unit', 16_000_000],
                    ['Buku bacaan bergambar anak (200 judul)', 200, 'eksemplar', 25_000],
                    ['Mini Proyektor & Layar tancap edukasi', 1, 'set', 1_000_000],
                ],
                'milestones' => [
                    ['Pengadaan Motor Roda Tiga Box', 'Pembelian dan karoseri tempat buku motor roda tiga', 16_000_000],
                    ['Pembelian Buku Bacaan & Launching Rute', 'Belanja buku bergambar dan peresmian rute baru', 6_000_000],
                ],
            ],
            [
                'title' => 'Bantuan Sembako Lansia Dhuafa & Janda Sebatang Kara',
                'category' => 'sosial',
                'summary' => 'Paket pangan bulanan untuk 100 lansia yang tidak lagi memiliki penghasilan dan penopang keluarga.',
                'description' => "Di wilayah binaan kami, tercatat 100 lansia berusia di atas 65 tahun yang hidup sebatang kara. Banyak dari mereka yang mengandalkan belas kasihan tetangga sekitar.\n\nProgram ini menyalurkan beras 10kg, minyak goreng, telur, susu lansia, dan suplemen vitamin setiap bulan selama 3 bulan berturut-turut.",
                'target' => 27_000_000,
                'cover' => 'https://images.unsplash.com/photo-1593113598332-cd288d649433?auto=format&fit=crop&w=800&q=80',
                'items' => [
                    ['Paket Sembako Lengkap Lansia', 300, 'paket', 90_000],
                ],
                'milestones' => [
                    ['Penyaluran Sembako Bulan Ke-1 & Ke-2', 'Pembelian beras, telur, suplemen dan pengantaran door to door', 18_000_000],
                    ['Penyaluran Sembako Bulan Ke-3', 'Penyaluran tahap akhir dan evaluasi kondisi kesehatan lansia', 9_000_000],
                ],
            ],
            [
                'title' => 'Rehabilitasi Habitat Satwa Owa Jawa Terancam Punah',
                'category' => 'lingkungan',
                'summary' => 'Penanaman pohon pakan Owa Jawa dan pembuatan koridor kanopi di Hutan Lindung Petungkriyono.',
                'description' => "Owa Jawa (Hylobates moloch) adalah primata endemik Jawa yang statusnya Terancam Punah (Endangered). Fragmentasi hutan membuat populasi mereka terisolasi dan kekurangan sumber makanan.\n\nKami melakukan penanaman 1.000 bibit pohon buah hutan (Ficus, Rasamala) serta memasang koridor tambang kanopi buatan untuk menghubungkan fragmentasi area jelajah Owa.",
                'target' => 34_000_000,
                'cover' => 'https://images.unsplash.com/photo-1534567153574-2b12153a87f0?auto=format&fit=crop&w=800&q=80',
                'items' => [
                    ['Bibit pohon buah hutan endemik', 1000, 'batang', 15_000],
                    ['Jembatan kanopi tambang kelapa', 10, 'titik', 1_200_000],
                    ['Patroli perlindungan & monitoring GPS', 6, 'bulan', 1_166_666],
                ],
                'milestones' => [
                    ['Pengadaan Bibit Buah & Pembuatan Kanopi', 'Pembelian 1.000 bibit pakan dan konstruksi jembatan kanopi', 22_000_000],
                    ['Monitoring Populasi & Patroli Hutan', 'Patroli cegah perburuan dan monitoring kamera jebak', 12_000_000],
                ],
            ],
            [
                'title' => 'Restorasi Terumbu Karang Pantai Karimunjawa',
                'category' => 'lingkungan',
                'summary' => 'Transplantasi 500 fragmen terumbu karang meja pada media bio-rock untuk memulihkan ekosistem laut.',
                'description' => "Kerusakan terumbu karang akibat peningkatan suhu laut dan penangkapan ikan tak ramah lingkungan mengancam keanekaragaman hayati Karimunjawa.\n\nProgram konservasi bahari ini melakukan adopsi karang dengan metode struktur spider/reef star sebanyak 50 struktur yang ditanami 500 fragmen karang karang cabang (Acropora).",
                'target' => 31_000_000,
                'cover' => 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&w=800&q=80',
                'items' => [
                    ['Struktur Rangka Spider Karang Coating', 50, 'unit', 350_000],
                    ['Bibit fragmen karang Acropora', 500, 'fragmen', 20_000],
                    ['Operasional tim penyelam & peralatan', 1, 'paket', 3_500_000],
                ],
                'milestones' => [
                    ['Pembuatan Rangka & Penyelaman Transplantasi', 'Pembuatan 50 struktur besi spider dan transplantasi bibit', 20_000_000],
                    ['Monitoring Pertumbuhan & Perawatan 6 Bulan', 'Pembersihan alga penutup karang dan pencatatan laju tumbuh', 11_000_000],
                ],
            ],
            [
                'title' => 'Listrik Tenaga Surya untuk Komunitas Adat Kampung Pelosok',
                'category' => 'infrastruktur',
                'summary' => 'Instalasi 25 paket SHS (Solar Home System) di pemukiman warga yang belum tersentuh PLN.',
                'description' => "Dusun Seringin belum memiliki akses jaringan PLN karena lokasinya yang berada di dalam hutan lindung. Malam hari warga hanya mengandalkan lampu tempel minyak tanah yang berisiko kebakaran.\n\nKami akan memasang 25 unit sistem panel surya mandiri (Panel 100Wp, Baterai Lithium, Controller, & 4 Lampu LED) di tiap rumah warga.",
                'target' => 62_000_000,
                'cover' => 'https://images.unsplash.com/photo-1509391365360-2e959784a276?auto=format&fit=crop&w=800&q=80',
                'items' => [
                    ['Paket Solar Home System 100Wp Complete', 25, 'unit', 2_200_000],
                    ['Kabel instalasi & lampu LED hemat energi', 25, 'set', 200_000],
                    ['Pengiriman logistik medan berat & instalator', 1, 'paket', 2_000_000],
                ],
                'milestones' => [
                    ['Pengadaan Komponen Solar Panel & Baterai', 'Pembelian 25 set panel surya, baterai lithium, & controller', 45_000_000],
                    ['Mobilisasi Logistik & Pemasangan', 'Pengiriman ke lokasi dan instalasi lampu rumah warga', 17_000_000],
                ],
            ],
            [
                'title' => 'Pemberdayaan Ekonomi UMKM Kerajinan Bambu Ibu-Ibu Desa',
                'category' => 'sosial',
                'summary' => 'Pelatihan & bantuan alat pertukangan bambu modern untuk kelompok usaha wanita tani.',
                'description' => "Potensi bambu di Desa Sambamba sangat melimpah, namun ibu-ibu hanya menjualnya dalam bentuk besek murah dengan keuntungan amat kecil.\n\nProgram ini membekali 25 ibu rumah tangga dengan mesin pasah bambu listrik, mesin potong, serta pelatihan pembuatan produk kerajinan bernilai tinggi seperti sedotan bambu dan tempat lampu ekspor.",
                'target' => 26_000_000,
                'cover' => 'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?auto=format&fit=crop&w=800&q=80',
                'items' => [
                    ['Mesin pasah & irat bambu listrik', 5, 'unit', 3_000_000],
                    ['Set peralatan ukir & finishing vernis', 25, 'set', 240_000],
                    ['Pelatihan desainer pengrajin (3 hari)', 1, 'paket', 5_000_000],
                ],
                'milestones' => [
                    ['Pengadaan Mesin & Alat Pertukangan Bambu', 'Pembelian mesin pasah listrik dan alat finishing', 16_000_000],
                    ['Workshop Pelatihan & Pendampingan Pemasaran', 'Pelatihan produk ekspor dan pembuatan katalog digital', 10_000_000],
                ],
            ],
            [
                'title' => 'Posyandu Bergerak & Penanganan Gizi Buruk Balita',
                'category' => 'kesehatan',
                'summary' => 'Pemberian makanan tambahan (PMT) berbahan lokal dan suplemen nutrisi untuk 60 balita stunting.',
                'description' => "Angka stunting di Kecamatan Kedungbandung mencapai 24%. Terbatasnya pengetahuan gizi orang tua dan akses bahan pangan berkualitas menjadi penyebab utama.\n\nKami menginisiasi Posyandu Bergerak yang mendatangi 5 dusun setiap minggu untuk melakukan penimbangan, konseling gizi, serta pemberian paket PMT telur, ikan, & biskuit kaya nutrisi selama 90 hari.",
                'target' => 29_000_000,
                'cover' => 'https://images.unsplash.com/photo-1576765608535-5f04d1e3f289?auto=format&fit=crop&w=800&q=80',
                'items' => [
                    ['Paket PMT Nutrisi Balita (90 Hari)', 60, 'anak', 400_000],
                    ['Timbangan digital & alat ukur tinggi badan', 5, 'set', 800_000],
                    ['Honor pendampingan ahli gizi & kader', 3, 'bulan', 333_333],
                ],
                'milestones' => [
                    ['Pengadaan Paket PMT Tahap 1 (Bulan 1-2)', 'Pembelian bahan makanan bergizi dan pendistribusian harian', 18_000_000],
                    ['Pengadaan Paket PMT Tahap 2 & Evaluasi', 'Pemberian PMT bulan ke-3 dan evaluasi kurva tumbuh balita', 11_000_000],
                ],
            ],
            [
                'title' => 'Bantuan Alat Bantu Dengar & Terapi Wicara Anak Tuli',
                'category' => 'kesehatan',
                'summary' => 'Pengadaan 15 unit Alat Bantu Dengar (ABD) digital serta sesi terapi wicara untuk anak gangguan pendengaran.',
                'description' => "Anak-anak dengan gangguan pendengaran konduktif di SLB Negeri mengalami hambatan belajar karena tidak mampu membeli Alat Bantu Dengar yang harganya mahal.\n\nKampanye ini menggalang dana untuk melakukan pemeriksaan audiometri lengkap, pengadaan 15 ABD digital sesuai frekuensi ambang dengar anak, dan 10 kali sesi terapi wicara.",
                'target' => 40_000_000,
                'cover' => 'https://images.unsplash.com/photo-1516549655169-df83a0774514?auto=format&fit=crop&w=800&q=80',
                'items' => [
                    ['Alat Bantu Dengar Digital Programmable', 15, 'unit', 2_200_000],
                    ['Tes Audiometri & Pembuatan Cetakan Telinga', 15, 'anak', 300_000],
                    ['Sesi Terapi Wicara Intensif (10x pertemuan)', 1, 'paket', 2_500_000],
                ],
                'milestones' => [
                    ['Skrining Audiometri & Fitment ABD', 'Pemeriksaan laboratorium pendengaran dan pemesanan ABD', 25_000_000],
                    ['Pemasangan ABD & Sesi Terapi Wicara', 'Penyerahan alat bantu dengar dan pelaksanaan sesi terapi', 15_000_000],
                ],
            ],
            [
                'title' => 'Pembangunan Jembatan Gantung Perintis Antar Desa',
                'category' => 'infrastruktur',
                'summary' => 'Menghubungkan dua desa terisolasi agar 140 anak sekolah tidak lagi menyeberangi sungai deras.',
                'description' => "Setiap musim hujan tiba, Sungai Comal meluap dan memutuskan akses penyeberangan bambu buatan warga. Siswa SD harus meliburkan diri karena tidak ada akses jalan alternatif lain.\n\nDonasi ini dipakai untuk membeli tiang pancang baja, seling kabel baja tahan tarik 16mm, dan papan kayu jati untuk membangun Jembatan Gantung Perintis sepanjang 35 meter.",
                'target' => 95_000_000,
                'cover' => 'https://images.unsplash.com/photo-1477959858617-67f30ac4ce78?auto=format&fit=crop&w=800&q=80',
                'items' => [
                    ['Kabel Seling Baja 16mm & Klem Aksesoris', 100, 'meter', 350_000],
                    ['Tiang Pancang Pondasi Beton Bertulang', 1, 'paket', 35_000_000],
                    ['Gelagar Kayu Jati & Papan Landasan 35m', 1, 'set', 25_000_000],
                ],
                'milestones' => [
                    ['Pekerjaan Pondasi Beton & Abutmen Jembatan', 'Penggalian dan pengecoran tiang pancang beton di dua sisi sungai', 50_000_000],
                    ['Penarikan Seling Utama & Pemasangan Gelagar', 'Pemasangan kabel baja utama, papan landasan, dan pagar pengaman', 45_000_000],
                ],
            ],
            [
                'title' => 'Beasiswa Santri Hafiz Al-Qur\'an Prasejahtera',
                'category' => 'pendidikan',
                'summary' => 'Menanggung biaya pendidikan & asrama 30 santri yatim penghafal Al-Qur\'an selama 1 tahun.',
                'description' => "Banyak santri berprestasi yang memiliki cita-cita menjadi Hafiz 30 Juz terancam terhenti pendidikannya karena keterbatasan ekonomi keluarga yatim/dhuafa.\n\nProgram Beasiswa Tahfiz ini mencakup biaya kitab, seragam, biaya makan di asrama, serta insentif untuk ustadz pembimbing hafalan.",
                'target' => 36_000_000,
                'cover' => 'https://images.unsplash.com/photo-1585036156171-384164a8c675?auto=format&fit=crop&w=800&q=80',
                'items' => [
                    ['Bantuan Biaya Hidup & Makan Santri (30 Anak)', 30, 'santri', 800_000],
                    ['Kitab Al-Qur\'an Hafalan & Buku Catatan', 30, 'set', 200_000],
                    ['Insentif Musyrif/Ustadz Pembimbing', 12, 'bulan', 500_000],
                ],
                'milestones' => [
                    ['Penyaluran Beasiswa Semester 1', 'Pemenuhan kebutuhan belajar dan akomodasi santri semester awal', 18_000_000],
                    ['Penyaluran Beasiswa Semester 2', 'Penyaluran lanjutan setelah ujian imtihan hafalan juz', 18_000_000],
                ],
            ],
            [
                'title' => 'Sanitasi Sehat & Fasilitas Toilet Halaman Sekolah Dasar',
                'category' => 'infrastruktur',
                'summary' => 'Pembangunan 4 bilik toilet bersih dan tempat cuci tangan mengalir untuk 180 siswa SD.',
                'description' => "SD Negeri 3 Plalangan hanya memiliki 1 unit toilet yang kondisinya sangat memprihatinkan tanpa penerangan dan pintu yang rusak. Siswa kerap terpaksa buang air di semak-semak belakang sekolah.\n\nKami berencana membangun 4 unit bilik toilet terpisah putra/putri lengkap dengan wastafel cuci tangan air mengalir dan septic tank bio-filter.",
                'target' => 32_000_000,
                'cover' => 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=800&q=80',
                'items' => [
                    ['Batu bata, semen, keramik & bahan bangunan', 1, 'paket', 16_000_000],
                    ['Septic Tank Biofilter 1.500 Liter', 1, 'unit', 6_000_000],
                    ['Wastafel cuci tangan & kran otomatis', 4, 'set', 1_000_000],
                    ['Upah tukang bangunan', 1, 'paket', 6_000_000],
                ],
                'milestones' => [
                    ['Pembangunan Konstruksi Bilik Toilet & Septic Tank', 'Penggalian septic tank biofilter dan penataan dinding bata', 20_000_000],
                    ['Pemasangan Keramik, Wastafel & Pintu', 'Finishing interior, instalasi air bersih, dan pengecatan', 12_000_000],
                ],
            ],
        ];

        // 3. Seed campaigns into DB
        foreach ($campaignList as $index => $c) {
            $pengaju = $pengajuUsers[$index % count($pengajuUsers)];
            $collected = rand((int)($c['target'] * 0.25), (int)($c['target'] * 0.85));

            $campaign = Campaign::create([
                'user_id' => $pengaju->id,
                'title' => $c['title'],
                'slug' => Str::slug($c['title']),
                'category' => $c['category'],
                'cover_path' => $c['cover'],
                'summary' => $c['summary'],
                'description' => $c['description'],
                'target_amount' => $c['target'],
                'collected_amount' => 0, // Will recalculate from donations
                'disbursed_amount' => 0,
                'deadline' => now()->addDays(rand(14, 90)),
                'status' => Campaign::STATUS_APPROVED,
                'submitted_at' => now()->subDays(rand(10, 30)),
                'reviewed_at' => now()->subDays(rand(5, 9)),
                'reviewed_by' => $admin?->id,
            ]);

            // RAB Items
            foreach ($c['items'] as $i => [$name, $qty, $unit, $price]) {
                $subtotal = $qty > 0 ? $qty * $price : $price;
                $campaign->items()->create([
                    'name' => $name,
                    'quantity' => $qty,
                    'unit' => $unit,
                    'unit_price' => $price,
                    'subtotal' => $subtotal,
                    'sort_order' => $i,
                ]);
            }

            // Milestones
            foreach ($c['milestones'] as $i => [$mTitle, $mDesc, $mAmount]) {
                $campaign->milestones()->create([
                    'sequence' => $i + 1,
                    'title' => $mTitle,
                    'description' => $mDesc,
                    'amount' => $mAmount,
                    'status' => 'locked',
                ]);
            }

            // Create 3 - 7 realistic paid donations per campaign
            $donationCount = rand(3, 7);
            $remainingTarget = $collected;
            for ($d = 0; $d < $donationCount; $d++) {
                $dAmount = ($d === $donationCount - 1)
                    ? max(50000, $remainingTarget)
                    : rand(100000, max(200000, (int)($remainingTarget / 2)));
                $remainingTarget -= $dAmount;

                $donorName = $donorNames[array_rand($donorNames)];
                $isAnon = rand(0, 4) === 0;

                $donation = Donation::create([
                    'reference' => 'sementara-'.Str::random(12),
                    'campaign_id' => $campaign->id,
                    'user_id' => null,
                    'donor_name' => $isAnon ? null : $donorName,
                    'donor_email' => Str::slug($donorName).rand(10,99).'@gmail.com',
                    'is_anonymous' => $isAnon,
                    'message' => rand(0, 1) ? 'Semoga berkah dan bermanfaat untuk sesama.' : null,
                    'amount' => $dAmount,
                    'status' => Donation::STATUS_PAID,
                    'payment_channel' => rand(0, 1) ? 'qris' : 'bank_transfer',
                    'gateway' => 'mock',
                    'gateway_reference' => 'MOCK-'.Str::upper(Str::random(12)),
                    'paid_at' => now()->subDays(rand(1, 15)),
                    'verification_code' => str_repeat('0', 64),
                ]);

                $donation->reference = $donation->buildReference();
                $donation->verification_code = $receipts->for($donation);
                $donation->created_at = $donation->paid_at;
                $donation->save();
            }

            $campaign->recalculateTotals();

            // Audit log entry
            $audit->record('campaign.approved', $campaign, ['judul' => $campaign->title], $admin);
        }

        $this->command?->info('Berhasil menambahkan 20 kampanye baru dengan relasi pengaju!');
    }
}
