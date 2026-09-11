<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\User;
use App\Services\DonationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengaju_memiliki_menu_riwayat_donasi_dan_bisa_membuka_dashboard(): void
    {
        $pengaju = User::factory()->create([
            'role' => User::ROLE_PENGAJU,
            'verification_status' => User::VERIFICATION_VERIFIED,
        ]);

        $response = $this->actingAs($pengaju)->get(route('donatur.dashboard'));

        $response->assertOk();
        $response->assertSee('Daftar Riwayat Transaksi');
        $response->assertSee('Dasbor Kampanye');
        $response->assertSee('Riwayat donasi');

        // Pastikan menu Riwayat donasi berstatus aktif (memiliki aria-current="page")
        $this->assertMatchesRegularExpression(
            '/href="[^"]*dashboard"[^>]*aria-current="page"[^>]*>\s*<span>Riwayat donasi<\/span>/s',
            $response->getContent()
        );
    }

    public function test_pengaju_melihat_ringkasan_donasi_pribadi_di_kedua_dasbor(): void
    {
        $pengaju = User::factory()->create([
            'role' => User::ROLE_PENGAJU,
            'verification_status' => User::VERIFICATION_VERIFIED,
        ]);

        $campaign = Campaign::create([
            'user_id' => $pengaju->id,
            'title' => 'Bantuan Air Bersih Desa',
            'slug' => 'bantuan-air-bersih-desa',
            'category' => 'sosial',
            'summary' => 'Pembangunan sumur air bersih',
            'description' => 'Deskripsi lengkap proyek air bersih',
            'target_amount' => 10_000_000,
            'status' => Campaign::STATUS_APPROVED,
        ]);

        $campaign->milestones()->create([
            'sequence' => 1,
            'title' => 'Tahap 1',
            'amount' => 10_000_000,
        ]);

        $this->actingAs($pengaju);
        $donation = app(DonationService::class)->create($campaign, [
            'amount' => 75000,
            'donor_name' => $pengaju->name,
            'donor_email' => $pengaju->email,
        ]);
        app(DonationService::class)->markPaid($donation, ['status' => 'settlement']);

        // Di halaman riwayat donasi (/dashboard)
        $this->actingAs($pengaju)
            ->get(route('donatur.dashboard'))
            ->assertOk()
            ->assertSee($donation->fresh()->reference)
            ->assertSee('Rp75.000');

        // Di halaman dasbor kampanye pengaju (/pengaju)
        $this->actingAs($pengaju)
            ->get(route('pengaju.dashboard'))
            ->assertOk()
            ->assertSee('Donasi Pribadi Anda')
            ->assertSee('Rp75.000')
            ->assertSee(route('donatur.dashboard'));
    }

    public function test_admin_setujui_verifikasi_otomatis_upgrade_donatur_menjadi_pengaju(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $donatur = User::factory()->create([
            'role' => User::ROLE_DONATUR,
            'verification_status' => User::VERIFICATION_PENDING,
            'identity_document_path' => 'identitas/dummy-ktp.jpg',
            'bank_name' => 'bca',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Donatur Berbakti',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.pengguna.decide', $donatur), [
            'decision' => 'verified',
            'note' => 'Identitas dan buku rekening valid.',
        ]);

        $response->assertRedirect(route('admin.pengguna.index'));

        $donatur->refresh();
        $this->assertSame(User::ROLE_PENGAJU, $donatur->role);
        $this->assertSame(User::VERIFICATION_VERIFIED, $donatur->verification_status);
        $this->assertTrue($donatur->canSubmitCampaign());

        // Pengaju baru ini sekarang bisa membuka form pembuatan kampanye tanpa 403
        $this->actingAs($donatur)
            ->get(route('pengaju.kampanye.create'))
            ->assertOk()
            ->assertSee('Kampanye baru');
    }

    public function test_donatur_melihat_menu_dasbor_di_sidebar(): void
    {
        $donatur = User::factory()->create([
            'role' => User::ROLE_DONATUR,
            'verification_status' => User::VERIFICATION_UNVERIFIED,
        ]);

        $response = $this->actingAs($donatur)->get(route('donatur.dashboard'));

        $response->assertOk();
        $response->assertSee('Dasbor');

        // Pastikan menu Dasbor berstatus aktif (memiliki aria-current="page")
        $this->assertMatchesRegularExpression(
            '/href="[^"]*dashboard"[^>]*aria-current="page"[^>]*>\s*<span>Dasbor<\/span>/s',
            $response->getContent()
        );
    }
}
