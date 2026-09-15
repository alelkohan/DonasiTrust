<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Milestone;
use App\Models\User;
use App\Services\CampaignAiAuditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignAiAuditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_auditor_mendeteksi_anomali_markup_harga_dan_alokasi_honor_tinggi(): void
    {
        $pengaju = User::factory()->pengaju()->create([
            'verification_status' => User::VERIFICATION_VERIFIED,
        ]);

        $campaign = Campaign::create([
            'user_id' => $pengaju->id,
            'title' => 'Renovasi Posko Bencana Gempa',
            'slug' => 'renovasi-posko-bencana-gempa',
            'category' => 'bencana',
            'summary' => 'Renovasi darurat posko penampungan',
            'description' => 'Posko membutuhkan renovasi secepatnya.',
            'target_amount' => 10_000_000,
            'status' => Campaign::STATUS_DRAFT,
        ]);

        // Item dengan semen sangat mahal (mark-up) dan honor panitia 40%
        $campaign->items()->createMany([
            ['name' => 'Semen Portland 50kg', 'quantity' => 10, 'unit' => 'sak', 'unit_price' => 450_000, 'subtotal' => 4_500_000, 'sort_order' => 0],
            ['name' => 'Honor Panitia Pelaksana', 'quantity' => 1, 'unit' => 'paket', 'unit_price' => 4_000_000, 'subtotal' => 4_000_000, 'sort_order' => 1],
            ['name' => 'Paket Logistik', 'quantity' => 1, 'unit' => 'paket', 'unit_price' => 1_500_000, 'subtotal' => 1_500_000, 'sort_order' => 2],
        ]);

        $campaign->milestones()->createMany([
            ['sequence' => 1, 'title' => 'Tahap 1', 'amount' => 5_000_000, 'status' => Milestone::STATUS_LOCKED],
            ['sequence' => 2, 'title' => 'Tahap 2', 'amount' => 5_000_000, 'status' => Milestone::STATUS_LOCKED],
        ]);

        $auditor = app(CampaignAiAuditor::class);
        $result = $auditor->analyze($campaign);

        $this->assertEquals('high', $result['risk_level']);
        $this->assertGreaterThanOrEqual(70, $result['risk_score']);
        $this->assertNotEmpty($result['flags']);
        $this->assertNotEmpty($result['admin_recommendations']);

        $campaign->refresh();
        $this->assertTrue($campaign->hasAiAnalysis());
        $this->assertEquals('high', $campaign->ai_risk_level);
        $this->assertEquals('danger', $campaign->aiRiskTone());
        $this->assertNotNull($campaign->ai_analyzed_at);
        $this->assertGreaterThan(0, $campaign->aiFlagsCount());
    }

    public function test_ai_auditor_menilai_kampanye_wajar_sebagai_risiko_rendah(): void
    {
        $pengaju = User::factory()->pengaju()->create([
            'verification_status' => User::VERIFICATION_VERIFIED,
        ]);

        $campaign = Campaign::create([
            'user_id' => $pengaju->id,
            'title' => 'Bantuan Perlengkapan Sekolah Anak Yatim',
            'slug' => 'bantuan-perlengkapan-sekolah-anak-yatim',
            'category' => 'pendidikan',
            'summary' => 'Pengadaan seragam dan buku tulis untuk 50 anak',
            'description' => 'Membantu anak-anak yatim mendapatkan seragam dan alat tulis lengkap.',
            'target_amount' => 5_000_000,
            'status' => Campaign::STATUS_DRAFT,
        ]);

        $campaign->items()->createMany([
            ['name' => 'Seragam Sekolah Merah Putih', 'quantity' => 50, 'unit' => 'stel', 'unit_price' => 60_000, 'subtotal' => 3_000_000, 'sort_order' => 0],
            ['name' => 'Buku Tulis & Alat Tulis', 'quantity' => 50, 'unit' => 'paket', 'unit_price' => 30_000, 'subtotal' => 1_500_000, 'sort_order' => 1],
            ['name' => 'Tas Sekolah', 'quantity' => 10, 'unit' => 'buah', 'unit_price' => 50_000, 'subtotal' => 500_000, 'sort_order' => 2],
        ]);

        $campaign->milestones()->createMany([
            ['sequence' => 1, 'title' => 'Tahap 1', 'amount' => 2_500_000, 'status' => Milestone::STATUS_LOCKED],
            ['sequence' => 2, 'title' => 'Tahap 2', 'amount' => 2_500_000, 'status' => Milestone::STATUS_LOCKED],
        ]);

        $auditor = app(CampaignAiAuditor::class);
        $result = $auditor->analyze($campaign);

        $this->assertEquals('low', $result['risk_level']);
        $this->assertEquals('success', $campaign->fresh()->aiRiskTone());
        $this->assertNotEmpty($result['positive_aspects']);
    }

    public function test_admin_bisa_menjalankan_audit_ai_ulang_via_endpoint(): void
    {
        $admin = User::factory()->admin()->create();
        $pengaju = User::factory()->pengaju()->create();

        $campaign = Campaign::create([
            'user_id' => $pengaju->id,
            'title' => 'Bantuan Sembako Warga Dhuafa',
            'slug' => 'bantuan-sembako-warga-dhuafa',
            'category' => 'sosial',
            'summary' => 'Penyaluran beras dan paket sembako untuk 100 warga lansia dhuafa.',
            'description' => 'Program bantuan pangan untuk membantu memenuhi kebutuhan pokok keluarga prasejahtera dan lansia dhuafa di wilayah terdampak.',
            'target_amount' => 5_000_000,
            'status' => Campaign::STATUS_PENDING,
        ]);

        $campaign->items()->createMany([
            ['name' => 'Beras 5kg', 'quantity' => 100, 'unit' => 'sak', 'unit_price' => 50_000, 'subtotal' => 5_000_000, 'sort_order' => 0],
        ]);

        $campaign->milestones()->create([
            'sequence' => 1, 'title' => 'Tahap 1', 'amount' => 5_000_000, 'status' => Milestone::STATUS_LOCKED
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.kampanye.audit-ai', $campaign));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'risk_level' => 'low',
        ]);

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'ai_risk_level' => 'low',
        ]);
    }

    public function test_bukan_admin_tidak_bisa_mengakses_endpoint_audit_ai(): void
    {
        $pengaju = User::factory()->pengaju()->create();

        $campaign = Campaign::create([
            'user_id' => $pengaju->id,
            'title' => 'Kampanye Uji Otorisasi',
            'slug' => 'kampanye-uji-otorisasi',
            'category' => 'sosial',
            'summary' => 'Ringkasan uji otorisasi',
            'description' => 'Deskripsi uji otorisasi lengkap untuk verifikasi hak akses endpoint audit.',
            'target_amount' => 5_000_000,
            'status' => Campaign::STATUS_PENDING,
        ]);

        $response = $this->actingAs($pengaju)->postJson(route('admin.kampanye.audit-ai', $campaign));
        $response->assertForbidden();
    }

    public function test_pengajuan_kampanye_oleh_pengaju_otomatis_memicu_audit_ai(): void
    {
        $pengaju = User::factory()->pengaju()->create([
            'verification_status' => User::VERIFICATION_VERIFIED,
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Jane Doe',
        ]);

        $campaign = Campaign::create([
            'user_id' => $pengaju->id,
            'title' => 'Penyaluran Paket Sembako Yatim',
            'slug' => 'penyaluran-paket-sembako-yatim',
            'category' => 'sosial',
            'summary' => 'Paket sembako bergizi untuk panti asuhan',
            'description' => 'Bantuan pemenuhan nutrisi dan kebutuhan pokok harian bagi anak-anak di panti asuhan.',
            'target_amount' => 5_000_000,
            'status' => Campaign::STATUS_DRAFT,
        ]);

        $campaign->items()->createMany([
            ['name' => 'Sembako', 'quantity' => 50, 'unit' => 'paket', 'unit_price' => 100_000, 'subtotal' => 5_000_000, 'sort_order' => 0],
        ]);

        $campaign->milestones()->create([
            'sequence' => 1, 'title' => 'Tahap 1', 'amount' => 5_000_000, 'status' => Milestone::STATUS_LOCKED
        ]);

        $response = $this->actingAs($pengaju)->postJson(route('pengaju.kampanye.submit', $campaign));

        $response->assertOk();

        $campaign->refresh();
        $this->assertEquals(Campaign::STATUS_PENDING, $campaign->status);
        $this->assertTrue($campaign->hasAiAnalysis());
        $this->assertNotNull($campaign->ai_risk_level);
        $this->assertNotNull($campaign->ai_analyzed_at);
    }

    public function test_ai_auditor_mendeteksi_teks_dummy_dan_item_fiktif_sebagai_risiko_tinggi(): void
    {
        $pengaju = User::factory()->pengaju()->create([
            'verification_status' => User::VERIFICATION_VERIFIED,
        ]);

        // Kampanye dengan teks asal-asalan / dummy
        $campaign = Campaign::create([
            'user_id' => $pengaju->id,
            'title' => 'sef',
            'slug' => 'sef',
            'category' => 'sosial',
            'summary' => 'sef',
            'description' => 'awd',
            'target_amount' => 100_000,
            'status' => Campaign::STATUS_DRAFT,
        ]);

        $campaign->items()->create([
            'name' => 'awd',
            'quantity' => 1,
            'unit' => 'unit',
            'unit_price' => 100_000,
            'subtotal' => 100_000,
            'sort_order' => 0,
        ]);

        $campaign->milestones()->create([
            'sequence' => 1, 'title' => 'Tahap 1', 'amount' => 100_000, 'status' => Milestone::STATUS_LOCKED,
        ]);

        $auditor = app(CampaignAiAuditor::class);
        $result = $auditor->analyze($campaign);

        $this->assertEquals('high', $result['risk_level']);
        $this->assertGreaterThanOrEqual(75, $result['risk_score']);

        $flagTypes = array_column($result['flags'], 'type');
        $this->assertContains('inadequate_context', $flagTypes);
        $this->assertContains('fictitious_item', $flagTypes);
    }
}
