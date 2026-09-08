<?php

namespace Tests\Feature;

use App\Livewire\DonationForm;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GuestDonationTest extends TestCase
{
    use RefreshDatabase;

    private function createCampaign(): Campaign
    {
        $pengaju = User::factory()->create(['role' => User::ROLE_PENGAJU]);

        $campaign = Campaign::create([
            'user_id' => $pengaju->id,
            'title' => 'Bantu Korban Banjir',
            'slug' => 'bantu-korban-banjir',
            'category' => 'bencana',
            'summary' => 'Bantuan sembako dan logistik darurat.',
            'description' => 'Deskripsi kampanye bencana alam.',
            'target_amount' => 50_000_000,
            'status' => Campaign::STATUS_APPROVED,
        ]);

        $campaign->milestones()->create([
            'sequence' => 1,
            'title' => 'Tahap 1',
            'amount' => 50_000_000,
        ]);

        return $campaign;
    }

    public function test_tamu_bisa_donasi_tanpa_login_lewat_livewire(): void
    {
        $campaign = $this->createCampaign();

        // Tamu (guest, belum login) mengisi form donasi
        Livewire::test(DonationForm::class, ['campaign' => $campaign])
            ->set('amount', '100.000')
            ->set('donorName', 'Donatur Baik')
            ->set('donorEmail', 'donatur@contoh.test')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect();

        $donation = Donation::first();
        $this->assertNotNull($donation);
        $this->assertSame(100_000, $donation->amount);
        $this->assertSame('Donatur Baik', $donation->donor_name);
        $this->assertSame('donatur@contoh.test', $donation->donor_email);
        $this->assertNull($donation->user_id);
    }

    public function test_tamu_bisa_donasi_tanpa_email_dan_tanpa_nama(): void
    {
        $campaign = $this->createCampaign();

        Livewire::test(DonationForm::class, ['campaign' => $campaign])
            ->set('amount', '50.000')
            ->set('donorName', '')
            ->set('donorEmail', '')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect();

        $donation = Donation::first();
        $this->assertNotNull($donation);
        $this->assertSame(50_000, $donation->amount);
        $this->assertNull($donation->user_id);
        $this->assertSame('Donatur', $donation->displayName());
    }

    public function test_tamu_bisa_donasi_lewat_endpoint_controller(): void
    {
        $campaign = $this->createCampaign();

        $response = $this->post(route('donasi.store', $campaign), [
            'amount' => 75_000,
            'donor_name' => 'Kawan Peduli',
        ]);

        $donation = Donation::first();
        $this->assertNotNull($donation);
        $response->assertRedirect(route('donasi.checkout', $donation));
        $this->assertNull($donation->user_id);
    }

    public function test_tamu_melihat_banner_lanjutkan_pembayaran_setelah_membuat_donasi(): void
    {
        $campaign = $this->createCampaign();

        // 1. Tamu submit donasi lewat Livewire
        $livewire = Livewire::test(DonationForm::class, ['campaign' => $campaign])
            ->set('amount', '35.000')
            ->set('donorName', 'Donatur Setia')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect();

        $donation = Donation::first();
        $this->assertNotNull($donation);

        // 2. Kunjungi kembali halaman kampanye dengan sesi yang tersimpan
        $response = $this->withSession(['pending_donation_'.$campaign->id => $donation->reference])
            ->get(route('kampanye.show', $campaign));

        $response->assertOk();
        $response->assertSee('Pembayaran Belum Selesai');
        $response->assertSee($donation->reference);
        $response->assertSee('Lanjutkan Pembayaran');

        // 3. Komponen Livewire mengunci form dan menampilkan pending donation
        session(['pending_donation_'.$campaign->id => $donation->reference]);
        $lwTest = Livewire::test(DonationForm::class, ['campaign' => $campaign]);
        
        $lwTest->assertSee('Pembayaran Belum Selesai');
        $lwTest->assertSee($donation->reference);
        $lwTest->assertSee('Batalkan Pembayaran');

        // 4. Tidak bisa submit donasi baru saat masih ada transaksi pending
        $lwTest->call('submit');
        $lwTest->assertHasErrors(['amount']);

        // 5. Batalkan pembayaran membatalkan transaksi dan membuka kembali form donasi
        $lwTest->call('cancelPendingDonation');
        $this->assertNull($lwTest->get('pendingDonation'));
        $this->assertSame(Donation::STATUS_CANCELLED, $donation->fresh()->status);
    }

    public function test_halaman_checkout_midtrans_memuat_skrip_embed_dan_link_fallback(): void
    {
        config(['donasi.gateway' => 'midtrans']);

        $campaign = $this->createCampaign();
        $donation = Donation::create([
            'campaign_id' => $campaign->id,
            'amount' => 50_000,
            'status' => Donation::STATUS_PENDING,
            'payment_channel' => 'qris',
            'gateway' => 'midtrans',
            'reference' => 'DT-TEST-SNAP-123',
            'verification_code' => str_repeat('a', 64),
            'gateway_payload' => [
                'snap_token' => 'mock-token-xyz-123',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/mock-token-xyz-123',
                'qr_payload' => 'mock-qr-string',
            ],
        ]);

        $response = $this->get(route('donasi.checkout', $donation));

        $response->assertOk();
        $response->assertSee('snap-embed-container');
        $response->assertSee('window.snap.embed');
        $response->assertSee('mock-token-xyz-123');
        $response->assertSee('Buka Halaman Pembayaran Midtrans');
        $response->assertSee('https://app.sandbox.midtrans.com/snap/v4/redirection/mock-token-xyz-123');
        $response->assertSee('Menutup halaman ini tidak membatalkan transaksi');
    }

    public function test_modal_konfirmasi_dan_batal_terpasang_dengan_benar(): void
    {
        $campaign = $this->createCampaign();
        $donation = Donation::create([
            'campaign_id' => $campaign->id,
            'amount' => 150_000,
            'status' => Donation::STATUS_PENDING,
            'payment_channel' => 'qris',
            'gateway' => 'mock',
            'reference' => 'DT-MODAL-TEST-999',
            'verification_code' => str_repeat('b', 64),
        ]);

        // 1. Kunjungi kampanye dengan sesi pending donation
        $response = $this->withSession(['pending_donation_'.$campaign->id => $donation->reference])
            ->get(route('kampanye.show', $campaign));

        $response->assertOk();
        // Lanjutkan pembayaran langsung berupa tautan (tanpa modal)
        $response->assertSee(route('donasi.checkout', $donation));
        // Modal pembatalan tersedia
        $response->assertSee('@buka-batal-donasi.window="bukaBatal()"', false);
        $response->assertSee('Batalkan Pembayaran?');
        $response->assertSee(route('donasi.cancel', $donation));

        // 2. Form donasi baru memuat modal konfirmasi sebelum lanjut bayar
        session()->forget('pending_donation_'.$campaign->id);
        $lw = Livewire::test(DonationForm::class, ['campaign' => $campaign]);
        $lw->assertSee('bukaModalKonfirmasi');
        $lw->assertSee('Konfirmasi Donasi');
        $lw->assertSee('Pastikan rincian donasi Anda sudah sesuai.');

        // 3. Test pembatalan lewat endpoint POST
        $cancelResponse = $this->withSession(['pending_donation_'.$campaign->id => $donation->reference])
            ->post(route('donasi.cancel', $donation));

        $cancelResponse->assertRedirect(route('kampanye.show', $campaign));
        $cancelResponse->assertSessionMissing('pending_donation_'.$campaign->id);
        $this->assertSame(Donation::STATUS_CANCELLED, $donation->fresh()->status);
    }
}
