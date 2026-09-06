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
 * Data demo untuk presentasi.
 *
 * Sengaja membuat kampanye dalam BERBAGAI status (draf, menunggu review,
 * tayang, ditolak) supaya seluruh alur bisa didemokan tanpa harus
 * menyiapkan data manual saat presentasi final.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Kunci TOTP akun demo. Hanya untuk data contoh — lihat catatan di bawah,
     * dan jangan pernah memakai kunci yang diketahui publik di produksi.
     */
    public const DEMO_TOTP_SECRET = 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP';

    public function run(): void
    {
        $audit = app(AuditLogger::class);
        $receipts = app(ReceiptVerifier::class);

        // --- Akun demo -------------------------------------------------------
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

        // Dua langkah untuk akun yang menyentuh uang, supaya alur pencairan bisa
        // langsung didemokan. Kuncinya SENGAJA sama dan tertulis di README:
        // penguji tinggal memasukkannya ke aplikasi authenticator, tanpa harus
        // mendaftar dulu. Di dunia nyata kunci ini tentu tidak boleh diketahui
        // siapa pun selain pemiliknya.
        foreach ([$admin, $pengaju] as $pemegangDana) {
            $pemegangDana->forceFill([
                'totp_secret' => self::DEMO_TOTP_SECRET,
                'totp_confirmed_at' => now(),
                'totp_recovery_codes' => ['DEMO-0001', 'DEMO-0002'],
            ])->save();
        }

        $audit->record('user.registered', $pengaju, ['role' => 'pengaju'], $pengaju);
        $audit->record('totp.enabled', $pengaju, ['nama' => $pengaju->name], $pengaju);
        $audit->record('totp.enabled', $admin, ['nama' => $admin->name], $admin);
        $audit->record('user.verified', $pengaju, ['nama' => $pengaju->name], $admin);
        $audit->record('user.verification_submitted', $pengajuBaru, ['nama' => $pengajuBaru->name], $pengajuBaru);

        // --- Kampanye --------------------------------------------------------
        $definitions = [
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
                // Total 39.000.000 — sengaja melewati tahap 1 (18 jt) DAN cukup
                // untuk membuka tahap 2 (20,5 jt) setelah tahap 1 dilaporkan,
                // supaya seluruh mekanisme pencairan bertahap terlihat saat demo.
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

                // Tahap 1 sudah cair dan sudah dilaporkan lengkap dengan nota.
                'disbursement' => [
                    'milestone' => 1,
                    'amount' => 18_000_000,
                    'purpose' => 'Pembelian genteng, kayu kaso, dan reng untuk ruang kelas A, '
                        .'plus upah tukang minggu pertama dan sewa scaffolding.',
                    'days_ago' => 10,
                ],

                // Jumlahnya persis 18.000.000 = nominal tahap 1.
                'expenses' => [
                    ['Genteng beton 400 buah (ruang kelas A)', 0, 4_800_000, 9,
                        'Dibeli di TB Sumber Rejeki Kaliwungu. Harga Rp12.000/buah sesuai RAB.',
                        'nota-genteng.png'],
                    ['Kayu kaso dan reng ruang kelas A', 1, 7_450_000, 9,
                        'Kayu meranti 60 batang dan reng 75 batang. Nota terlampir.',
                        'nota-kayu.png'],
                    ['Upah tukang minggu pertama', 3, 3_600_000, 5,
                        '3 tukang x 8 hari x Rp150.000. Daftar hadir ditandatangani ketua RT.',
                        'nota-upah.png'],
                    ['Sewa scaffolding dan mobilisasi material', 4, 2_150_000, 8,
                        'Sewa 10 hari plus ongkos angkut dua rit pikap.',
                        'nota-scaffolding.png'],
                ],
            ],
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

            // --- Laporan pertanggungjawaban tahap 1 ---
            if (! empty($def['expenses'])) {
                $milestone = $campaign->milestones()->where('sequence', 1)->first();
                $items = $campaign->items()->get()->values();

                foreach ($def['expenses'] as [$title, $itemIndex, $amount, $daysAgo, $description, $notaFile]) {
                    // Salin gambar nota contoh ke disk privat, supaya halaman
                    // transparansi publik benar-benar punya bukti untuk dibuka.
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

                // Tahap 1 selesai dilaporkan -> tahap 2 boleh terbuka.
                $milestone?->update(['status' => Milestone::STATUS_REPORTED]);
                $donations->unlockMilestones($campaign->fresh());
            }
        }

        $this->command?->info('Seeder selesai. Login: jokibuat121@gmail.com / password123');
        $this->command?->info('Kunci TOTP demo (admin & pengaju): '.self::DEMO_TOTP_SECRET);
        $this->command?->comment('Masukkan kunci itu ke aplikasi authenticator sebagai entri manual '
            .'untuk mencoba alur pencairan.');
    }

    /**
     * Salin satu berkas nota contoh dari database/seeders/nota ke disk privat.
     * Mengembalikan path relatif untuk disimpan di kolom receipt_path.
     */
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
