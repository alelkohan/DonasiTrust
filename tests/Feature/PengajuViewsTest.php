<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengajuViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengaju_bisa_membuka_seluruh_halaman_pengaju(): void
    {
        $pengaju = User::factory()->pengaju()->create([
            'verification_status' => User::VERIFICATION_VERIFIED,
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Jane Doe',
        ]);

        $draftCampaign = Campaign::create([
            'user_id' => $pengaju->id,
            'title' => 'Kampanye Draf Pengaju',
            'slug' => 'kampanye-draf-pengaju',
            'category' => 'sosial',
            'summary' => 'ringkasan kampanye draf',
            'description' => 'deskripsi kampanye draf lengkap',
            'target_amount' => 5_000_000,
            'status' => Campaign::STATUS_DRAFT,
        ]);

        $approvedCampaign = Campaign::create([
            'user_id' => $pengaju->id,
            'title' => 'Kampanye Disetujui Pengaju',
            'slug' => 'kampanye-disetujui-pengaju',
            'category' => 'sosial',
            'summary' => 'ringkasan kampanye disetujui',
            'description' => 'deskripsi kampanye disetujui lengkap',
            'target_amount' => 5_000_000,
            'status' => Campaign::STATUS_APPROVED,
        ]);

        $approvedCampaign->milestones()->create([
            'sequence' => 1,
            'title' => 'Tahap 1',
            'amount' => 5_000_000,
            'status' => Milestone::STATUS_AVAILABLE,
            'description' => 'Pembelian tahap awal',
        ]);

        $routes = [
            route('pengaju.dashboard'),
            route('pengaju.kampanye.index'),
            route('pengaju.kampanye.create'),
            route('pengaju.kampanye.edit', $draftCampaign),
            route('pengaju.kampanye.milestones', $approvedCampaign),
            route('profil.edit'),
            route('verifikasi.identitas'),
        ];

        foreach ($routes as $url) {
            $response = $this->actingAs($pengaju)->get($url);
            $response->assertOk();

            // Pastikan tidak ada lagi styling usang light-mode di template pengaju
            $response->assertDontSee('text-ink-900', false);
            $response->assertDontSee('bg-brand-50', false);
            $response->assertDontSee('border-ink-200', false);
        }
    }

    public function test_membuat_kampanye_baru_mengarahkan_ke_index_kampanye_dengan_notifikasi_sukses(): void
    {
        $pengaju = User::factory()->pengaju()->create([
            'verification_status' => User::VERIFICATION_VERIFIED,
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Jane Doe',
        ]);

        $response = $this->actingAs($pengaju)->post(route('pengaju.kampanye.store'), [
            'title' => 'Kampanye Baru Pengujian',
            'category' => 'sosial',
            'summary' => 'Ringkasan pengujian kampanye baru',
            'description' => 'Deskripsi rinci pengujian kampanye baru',
            'target_amount' => 5_000_000,
            'items' => [
                ['name' => 'Item 1', 'quantity' => 1, 'unit' => 'paket', 'unit_price' => 5_000_000],
            ],
            'milestones' => [
                ['title' => 'Tahap 1', 'description' => 'Realisasi item 1', 'amount' => 5_000_000],
            ],
        ]);

        $response->assertRedirect(route('pengaju.kampanye.index'));
        $response->assertSessionHas('status', 'Kampanye baru berhasil dibuat sebagai draf!');
        $this->assertDatabaseHas('campaigns', ['title' => 'Kampanye Baru Pengujian', 'status' => Campaign::STATUS_DRAFT]);
    }

    public function test_membuat_kampanye_baru_via_ajax_mengembalikan_json_redirect_ke_index(): void
    {
        $pengaju = User::factory()->pengaju()->create([
            'verification_status' => User::VERIFICATION_VERIFIED,
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Jane Doe',
        ]);

        $response = $this->actingAs($pengaju)->postJson(route('pengaju.kampanye.store'), [
            'title' => 'Kampanye Baru AJAX',
            'category' => 'sosial',
            'summary' => 'Ringkasan AJAX',
            'description' => 'Deskripsi AJAX',
            'target_amount' => 5_000_000,
            'items' => [
                ['name' => 'Item 1', 'quantity' => 1, 'unit' => 'paket', 'unit_price' => 5_000_000],
            ],
            'milestones' => [
                ['title' => 'Tahap 1', 'description' => 'Realisasi tahap 1', 'amount' => 5_000_000],
            ],
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'redirect' => route('pengaju.kampanye.index'),
        ]);
        $response->assertSessionHas('status', 'Kampanye baru berhasil dibuat sebagai draf!');
    }

    public function test_update_kampanye_via_ajax_mengembalikan_json_sukses_tanpa_redirect(): void
    {
        $pengaju = User::factory()->pengaju()->create([
            'verification_status' => User::VERIFICATION_VERIFIED,
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Jane Doe',
        ]);

        $campaign = Campaign::create([
            'user_id' => $pengaju->id,
            'title' => 'Kampanye Awal',
            'slug' => 'kampanye-awal',
            'category' => 'sosial',
            'summary' => 'Ringkasan awal',
            'description' => 'Deskripsi awal',
            'target_amount' => 5_000_000,
            'status' => Campaign::STATUS_DRAFT,
        ]);

        $response = $this->actingAs($pengaju)->putJson(route('pengaju.kampanye.update', $campaign), [
            'title' => 'Kampanye Diperbarui',
            'category' => 'sosial',
            'summary' => 'Ringkasan terupdate',
            'description' => 'Deskripsi terupdate',
            'target_amount' => 5_000_000,
            'items' => [
                ['name' => 'Item Baru', 'quantity' => 1, 'unit' => 'paket', 'unit_price' => 5_000_000],
            ],
            'milestones' => [
                ['title' => 'Tahap Baru', 'description' => 'Realisasi tahap baru', 'amount' => 5_000_000],
            ],
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Perubahan draf kampanye berhasil disimpan.',
        ]);
        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id, 'title' => 'Kampanye Diperbarui']);
    }

    public function test_ajukan_review_kampanye_mengubah_status_menjadi_pending_dan_mengembalikan_json_redirect(): void
    {
        $pengaju = User::factory()->pengaju()->create([
            'verification_status' => User::VERIFICATION_VERIFIED,
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Jane Doe',
        ]);

        $campaign = Campaign::create([
            'user_id' => $pengaju->id,
            'title' => 'Kampanye Siap Diajukan',
            'slug' => 'kampanye-siap-diajukan',
            'category' => 'sosial',
            'summary' => 'Ringkasan siap ajukan',
            'description' => 'Deskripsi siap ajukan',
            'target_amount' => 5_000_000,
            'status' => Campaign::STATUS_DRAFT,
        ]);

        $campaign->milestones()->create([
            'sequence' => 1,
            'title' => 'Tahap 1',
            'amount' => 5_000_000,
            'status' => Milestone::STATUS_LOCKED,
            'description' => 'Realisasi tahap 1',
        ]);

        $response = $this->actingAs($pengaju)->postJson(route('pengaju.kampanye.submit', $campaign));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'redirect' => route('pengaju.kampanye.index'),
        ]);

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'status' => Campaign::STATUS_PENDING,
        ]);
    }

    public function test_kampanye_yang_sudah_diajukan_atau_aktif_tidak_bisa_diubah_lagi(): void
    {
        $pengaju = User::factory()->pengaju()->create([
            'verification_status' => User::VERIFICATION_VERIFIED,
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Jane Doe',
        ]);

        $pendingCampaign = Campaign::create([
            'user_id' => $pengaju->id,
            'title' => 'Kampanye Pending',
            'slug' => 'kampanye-pending',
            'category' => 'sosial',
            'summary' => 'Ringkasan pending',
            'description' => 'Deskripsi pending',
            'target_amount' => 5_000_000,
            'status' => Campaign::STATUS_PENDING,
        ]);

        $this->assertFalse($pendingCampaign->isEditable());

        // Mencoba membuka halaman edit harus dilarang (403 Forbidden)
        $responseEdit = $this->actingAs($pengaju)->get(route('pengaju.kampanye.edit', $pendingCampaign));
        $responseEdit->assertForbidden();

        // Mencoba update harus dilarang (403 Forbidden)
        $responseUpdate = $this->actingAs($pengaju)->putJson(route('pengaju.kampanye.update', $pendingCampaign), [
            'title' => 'Ubah Paksa',
            'category' => 'sosial',
            'summary' => 'Ringkasan diubah',
            'description' => 'Deskripsi diubah',
            'target_amount' => 5_000_000,
            'items' => [
                ['name' => 'Item', 'quantity' => 1, 'unit' => 'paket', 'unit_price' => 5_000_000],
            ],
            'milestones' => [
                ['title' => 'Tahap 1', 'description' => 'Deskripsi', 'amount' => 5_000_000],
            ],
        ]);
        $responseUpdate->assertForbidden();
    }

    public function test_hapus_kampanye_via_ajax_mengembalikan_json_sukses_dan_menghapus_data(): void
    {
        $pengaju = User::factory()->pengaju()->create([
            'verification_status' => User::VERIFICATION_VERIFIED,
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Jane Doe',
        ]);

        $campaign = Campaign::create([
            'user_id' => $pengaju->id,
            'title' => 'Kampanye Dihapus',
            'slug' => 'kampanye-dihapus',
            'category' => 'sosial',
            'summary' => 'Ringkasan hapus',
            'description' => 'Deskripsi hapus',
            'target_amount' => 5_000_000,
            'status' => Campaign::STATUS_DRAFT,
        ]);

        $response = $this->actingAs($pengaju)->deleteJson(route('pengaju.kampanye.destroy', $campaign));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'redirect' => route('pengaju.kampanye.index'),
        ]);

        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
    }
}
