<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Milestone;
use App\Models\User;
use App\Services\DonationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonationFlowTest extends TestCase
{
    use RefreshDatabase;

    private function campaign(): Campaign
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

        $campaign->milestones()->create(['sequence' => 1, 'title' => 'Tahap 1', 'amount' => 400_000]);
        $campaign->milestones()->create(['sequence' => 2, 'title' => 'Tahap 2', 'amount' => 600_000]);

        return $campaign;
    }

    public function test_donasi_lunas_menambah_total_terkumpul(): void
    {
        $campaign = $this->campaign();
        $service = app(DonationService::class);

        $donation = $service->create($campaign, ['amount' => 500_000, 'donor_email' => 'a@b.test']);
        $this->assertSame(0, $campaign->fresh()->collected_amount);

        $service->markPaid($donation);
        $this->assertSame(500_000, $campaign->fresh()->collected_amount);
    }

    public function test_pelunasan_ganda_tidak_menghitung_dana_dua_kali(): void
    {
        $campaign = $this->campaign();
        $service = app(DonationService::class);

        $donation = $service->create($campaign, ['amount' => 500_000, 'donor_email' => 'a@b.test']);

        $this->assertTrue($service->markPaid($donation));
        $this->assertFalse($service->markPaid($donation->fresh()));
        $this->assertSame(500_000, $campaign->fresh()->collected_amount);
    }

    public function test_tahap_kedua_tetap_terkunci_sampai_tahap_pertama_dilaporkan(): void
    {
        $campaign = $this->campaign();
        $service = app(DonationService::class);

        $donation = $service->create($campaign, ['amount' => 1_000_000, 'donor_email' => 'a@b.test']);
        $service->markPaid($donation);

        $milestones = $campaign->fresh()->milestones()->get();

        $this->assertSame(Milestone::STATUS_AVAILABLE, $milestones[0]->status);
        $this->assertSame(Milestone::STATUS_LOCKED, $milestones[1]->status);
    }

    public function test_nomor_transaksi_selalu_unik(): void
    {
        $campaign = $this->campaign();
        $service = app(DonationService::class);

        $refs = collect(range(1, 5))
            ->map(fn () => $service->create($campaign, ['amount' => 50_000, 'donor_email' => 'a@b.test'])->reference);

        $this->assertCount(5, $refs->unique());
    }

    public function test_webhook_menolak_signature_yang_salah(): void
    {
        $campaign = $this->campaign();
        $donation = app(DonationService::class)
            ->create($campaign, ['amount' => 50_000, 'donor_email' => 'a@b.test']);

        $this->postJson('/webhook/payment/mock', [
            'token' => 'token-palsu',
            'reference' => $donation->reference,
            'status' => 'paid',
        ])->assertStatus(401);

        $this->assertSame(Donation::STATUS_PENDING, $donation->fresh()->status);
    }

    public function test_status_endpoint_mengarahkan_ke_halaman_kuitansi(): void
    {
        $campaign = $this->campaign();
        $donation = app(DonationService::class)
            ->create($campaign, ['amount' => 50_000, 'donor_email' => 'a@b.test']);

        $response = $this->getJson(route('donasi.status', $donation));
        $response->assertOk()
            ->assertJson([
                'status' => Donation::STATUS_PENDING,
                'is_paid' => false,
                'redirect_url' => route('kuitansi.show', $donation),
            ]);
    }

    public function test_kuitansi_dikirim_ke_email_saat_donasi_lunas(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $campaign = $this->campaign();
        $service = app(DonationService::class);

        $donation = $service->create($campaign, ['amount' => 100_000, 'donor_email' => 'donatur@example.com']);
        $service->markPaid($donation);

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\DonationReceiptMail::class, function ($mail) use ($donation) {
            return $mail->hasTo('donatur@example.com') && $mail->donation->id === $donation->id;
        });
    }
}
