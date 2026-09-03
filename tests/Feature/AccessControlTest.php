<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Disbursement;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_donatur_tidak_bisa_membuka_area_admin(): void
    {
        $donatur = User::factory()->create(['role' => User::ROLE_DONATUR]);

        $this->actingAs($donatur)->get('/admin')->assertForbidden();
    }

    public function test_pengaju_tidak_bisa_membuka_area_admin(): void
    {
        $pengaju = User::factory()->create(['role' => User::ROLE_PENGAJU]);

        $this->actingAs($pengaju)->get('/admin')->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_halaman_masuk(): void
    {
        $this->get('/dashboard')->assertRedirect('/masuk');
    }

    public function test_pengaju_belum_terverifikasi_tidak_bisa_membuat_kampanye(): void
    {
        $pengaju = User::factory()->create([
            'role' => User::ROLE_PENGAJU,
            'verification_status' => User::VERIFICATION_UNVERIFIED,
        ]);

        $this->actingAs($pengaju)
            ->get('/pengaju/kampanye/baru')
            ->assertRedirect(route('verifikasi.identitas'));
    }

    public function test_dokumen_identitas_tidak_bisa_dibuka_pengguna_lain(): void
    {
        $pemilik = User::factory()->create(['identity_document_path' => 'identitas/1/ktp.jpg']);
        $orangLain = User::factory()->create();

        $this->actingAs($orangLain)
            ->get(route('berkas.identitas', $pemilik))
            ->assertForbidden();
    }

    public function test_pengaju_bisa_melihat_bukti_transfer_pencairannya_sendiri(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('pencairan/bukti.jpg', 'gambar dummy');

        $pengaju = User::factory()->pengaju()->create();
        $campaign = Campaign::create([
            'user_id' => $pengaju->id,
            'title' => 'Kampanye Uji',
            'slug' => 'kampanye-uji',
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
            'status' => Milestone::STATUS_DISBURSED,
        ]);

        $disbursement = Disbursement::create([
            'reference' => 'PC-2026-00001',
            'campaign_id' => $campaign->id,
            'milestone_id' => $milestone->id,
            'requested_by' => $pengaju->id,
            'amount' => 1_000_000,
            'purpose' => 'Beli perlengkapan',
            'supporting_document_path' => 'pencairan/bukti.jpg',
            'status' => Disbursement::STATUS_RELEASED,
        ]);

        $this->actingAs($pengaju)
            ->get(route('berkas.pencairan', $disbursement))
            ->assertOk();
    }

    public function test_pengguna_lain_tidak_bisa_melihat_bukti_transfer_pencairan(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('pencairan/bukti.jpg', 'gambar dummy');

        $pengaju = User::factory()->pengaju()->create();
        $orangLain = User::factory()->create();

        $campaign = Campaign::create([
            'user_id' => $pengaju->id,
            'title' => 'Kampanye Uji 2',
            'slug' => 'kampanye-uji-2',
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
            'status' => Milestone::STATUS_DISBURSED,
        ]);

        $disbursement = Disbursement::create([
            'reference' => 'PC-2026-00002',
            'campaign_id' => $campaign->id,
            'milestone_id' => $milestone->id,
            'requested_by' => $pengaju->id,
            'amount' => 1_000_000,
            'purpose' => 'Beli perlengkapan',
            'supporting_document_path' => 'pencairan/bukti.jpg',
            'status' => Disbursement::STATUS_RELEASED,
        ]);

        $this->actingAs($orangLain)
            ->get(route('berkas.pencairan', $disbursement))
            ->assertForbidden();
    }
}
