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
}
