<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Disbursement;
use App\Models\User;
use App\Services\ReceiptVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji empat penutupan celah penipuan yang ditambahkan 26 Agustus 2026.
 */
class AntiFraudTest extends TestCase
{
    use RefreshDatabase;

    // --- Hash NIK: satu identitas, satu akun ---------------------------------

    public function test_hash_nik_konsisten_dan_mengabaikan_format(): void
    {
        $a = User::hashIdentityNumber('3324061503894821');
        $b = User::hashIdentityNumber('3324-0615-0389-4821');

        $this->assertSame($a, $b, 'Tanda hubung tidak boleh mengubah hash.');
        $this->assertSame(64, strlen($a));
    }

    public function test_nik_berbeda_menghasilkan_hash_berbeda(): void
    {
        $this->assertNotSame(
            User::hashIdentityNumber('3324061503894821'),
            User::hashIdentityNumber('3324061503894822'),
        );
    }

    public function test_hash_nik_terpisah_domain_dari_kode_kuitansi(): void
    {
        // Keduanya memakai kunci rahasia yang sama; awalan domain harus membuat
        // hasilnya tetap berbeda supaya satu fitur tidak bisa dipakai menebak
        // nilai fitur lain.
        $nik = '3324061503894821';

        $this->assertNotSame(
            User::hashIdentityNumber($nik),
            hash_hmac('sha256', $nik, (string) config('donasi.receipt_secret')),
        );
    }

    public function test_kolom_hash_nik_unik_di_basis_data(): void
    {
        $hash = User::hashIdentityNumber('3324061503894821');

        User::factory()->create(['identity_number_hash' => $hash]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        User::factory()->create(['identity_number_hash' => $hash]);
    }

    // --- Rekening tujuan pencairan -------------------------------------------

    public function test_rekening_dianggap_lengkap_hanya_bila_ketiganya_terisi(): void
    {
        $user = User::factory()->create([
            'bank_name' => 'BRI',
            'bank_account_number' => '337401004821530',
            'bank_account_holder' => 'AHMAD FAUZI',
        ]);

        $this->assertTrue($user->hasPayoutAccount());

        $user->update(['bank_account_holder' => null]);
        $this->assertFalse($user->fresh()->hasPayoutAccount());
    }

    public function test_rekening_publik_hanya_menampilkan_empat_digit_terakhir(): void
    {
        $user = User::factory()->create([
            'bank_name' => 'BRI',
            'bank_account_number' => '337401004821530',
            'bank_account_holder' => 'AHMAD FAUZI',
        ]);

        $tersamar = $user->maskedPayoutAccount();

        $this->assertStringContainsString('1530', $tersamar);
        $this->assertStringNotContainsString('337401004821530', $tersamar);
        $this->assertStringContainsString('AHMAD FAUZI', $tersamar);
    }

    public function test_nomor_rekening_tidak_ikut_saat_model_diserialisasi(): void
    {
        $user = User::factory()->create(['bank_account_number' => '337401004821530']);

        $this->assertArrayNotHasKey('bank_account_number', $user->toArray());
    }

    public function test_pencairan_menyamarkan_rekening_untuk_publik(): void
    {
        $disbursement = new Disbursement([
            'payee_bank_name' => 'bri',
            'payee_account_number' => '337401004821530',
            'payee_account_holder' => 'AHMAD FAUZI',
        ]);

        $this->assertStringContainsString('BRI', $disbursement->maskedPayee());
        $this->assertStringContainsString('1530', $disbursement->maskedPayee());
        $this->assertStringNotContainsString('33740100482', $disbursement->maskedPayee());
    }

    // --- Distribusi tahap pencairan ------------------------------------------

    public function test_kampanye_besar_wajib_lebih_dari_satu_tahap(): void
    {
        $pengaju = User::factory()->pengaju()->create();

        $this->actingAs($pengaju)
            ->post(route('pengaju.kampanye.store'), $this->payloadKampanye(
                target: 20_000_000,
                milestones: [['title' => 'Sekali cair', 'amount' => 20_000_000]],
            ))
            ->assertSessionHasErrors('milestones');

        $this->assertSame(0, Campaign::count());
    }

    public function test_tahap_pertama_tidak_boleh_menyerap_lebih_dari_batas(): void
    {
        $pengaju = User::factory()->pengaju()->create();

        $this->actingAs($pengaju)
            ->post(route('pengaju.kampanye.store'), $this->payloadKampanye(
                target: 20_000_000,
                milestones: [
                    ['title' => 'Tahap 1', 'amount' => 18_000_000], // 90%
                    ['title' => 'Tahap 2', 'amount' => 2_000_000],
                ],
            ))
            ->assertSessionHasErrors('milestones');

        $this->assertSame(0, Campaign::count());
    }

    public function test_distribusi_yang_wajar_diterima(): void
    {
        $pengaju = User::factory()->pengaju()->create();

        $this->actingAs($pengaju)
            ->post(route('pengaju.kampanye.store'), $this->payloadKampanye(
                target: 20_000_000,
                milestones: [
                    ['title' => 'Tahap 1', 'amount' => 12_000_000], // 60%
                    ['title' => 'Tahap 2', 'amount' => 8_000_000],
                ],
            ))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Campaign::count());
    }

    public function test_kampanye_berat_di_depan_ditandai_ke_publik(): void
    {
        $pengaju = User::factory()->pengaju()->create();

        $campaign = Campaign::create([
            'user_id' => $pengaju->id,
            'title' => 'Sumur Bor Dusun Ngroto',
            'slug' => 'sumur-bor',
            'category' => 'infrastruktur',
            'summary' => 'ringkas',
            'description' => 'panjang',
            'target_amount' => 32_000_000,
            'status' => Campaign::STATUS_APPROVED,
        ]);

        $campaign->milestones()->create(['sequence' => 1, 'title' => 'Pengeboran', 'amount' => 21_000_000]);
        $campaign->milestones()->create(['sequence' => 2, 'title' => 'Instalasi', 'amount' => 11_000_000]);

        // 21/32 = 65,6% — di bawah batas tolak (70%) tapi di atas ambang tanda (40%).
        $this->assertEqualsWithDelta(65.6, $campaign->firstMilestoneShare(), 0.1);
        $this->assertTrue($campaign->isFrontLoaded());
    }

    // --- Rekening tidak boleh dipakai selagi verifikasi tertunda -------------

    public function test_pencairan_ditolak_bila_verifikasi_pemilik_sedang_tertunda(): void
    {
        $pengaju = User::factory()->pengaju()->create([
            'bank_name' => 'BRI',
            'bank_account_number' => '337401004821530',
            'bank_account_holder' => 'AHMAD FAUZI',
        ]);

        $campaign = Campaign::create([
            'user_id' => $pengaju->id,
            'title' => 'Kampanye Uji',
            'slug' => 'kampanye-uji-verifikasi',
            'category' => 'sosial',
            'summary' => 'ringkas',
            'description' => 'panjang',
            'target_amount' => 1_000_000,
            'status' => Campaign::STATUS_APPROVED,
        ]);

        $milestone = $campaign->milestones()->create([
            'sequence' => 1,
            'title' => 'Tahap 1',
            'amount' => 1_000_000,
            'status' => \App\Models\Milestone::STATUS_AVAILABLE,
        ]);

        // Pengaju mengganti rekening -> status verifikasi kembali tertunda.
        $pengaju->update([
            'bank_account_number' => '9999999999',
            'verification_status' => User::VERIFICATION_PENDING,
        ]);

        $this->actingAs($pengaju)
            ->post(route('pengaju.kampanye.disbursement.request', [$campaign, $milestone]),
                ['purpose' => 'Beli material'])
            ->assertSessionHas('error');

        $this->assertSame(0, Disbursement::count());
    }

    /** @param  array<int, array{title: string, amount: int}>  $milestones */
    private function payloadKampanye(int $target, array $milestones): array
    {
        return [
            'title' => 'Kampanye Uji',
            'category' => 'sosial',
            'summary' => 'Ringkasan singkat kampanye uji.',
            'description' => 'Cerita lengkap kampanye uji.',
            'target_amount' => $target,
            'items' => [
                ['name' => 'Item tunggal', 'quantity' => 1, 'unit' => 'paket', 'unit_price' => $target],
            ],
            'milestones' => array_map(fn ($m) => [
                'title' => $m['title'],
                'description' => null,
                'amount' => $m['amount'],
            ], $milestones),
        ];
    }
}
