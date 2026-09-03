<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\User;
use App\Services\ReceiptVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function donation(int $amount = 100_000): Donation
    {
        $user = User::factory()->create(['role' => User::ROLE_PENGAJU]);

        $campaign = Campaign::create([
            'user_id' => $user->id,
            'title' => 'Kampanye Uji',
            'slug' => 'kampanye-uji',
            'category' => 'sosial',
            'summary' => 'ringkas',
            'description' => 'panjang',
            'target_amount' => 1_000_000,
            'status' => Campaign::STATUS_APPROVED,
        ]);

        $donation = Donation::create([
            'reference' => 'DT-2026-000001',
            'campaign_id' => $campaign->id,
            'amount' => $amount,
            'status' => Donation::STATUS_PAID,
            'verification_code' => str_repeat('0', 64),
        ]);

        $donation->update(['verification_code' => app(ReceiptVerifier::class)->for($donation)]);

        return $donation->fresh();
    }

    public function test_kode_yang_benar_diterima(): void
    {
        $donation = $this->donation();
        $code = app(ReceiptVerifier::class)->shortCode($donation);

        $this->post('/verifikasi', [
            'reference' => $donation->reference,
            'code' => $code,
        ])->assertOk()->assertSee('Kuitansi asli dan terdaftar');
    }

    public function test_kode_yang_diubah_satu_karakter_ditolak(): void
    {
        $donation = $this->donation();
        $code = app(ReceiptVerifier::class)->shortCode($donation);
        $rusak = ($code[0] === 'A' ? 'B' : 'A').substr($code, 1);

        $this->post('/verifikasi', [
            'reference' => $donation->reference,
            'code' => $rusak,
        ])->assertOk()->assertSee('Kuitansi tidak cocok');
    }

    public function test_kode_terikat_pada_nominal_sehingga_nominal_yang_diubah_membatalkan_kuitansi(): void
    {
        $donation = $this->donation(100_000);
        $verifier = app(ReceiptVerifier::class);
        $kodeLama = $verifier->for($donation);

        // Seseorang mengubah nominal langsung di basis data.
        $donation->update(['amount' => 900_000]);

        $this->assertFalse($verifier->matches($donation->fresh(), $kodeLama));
    }
}
