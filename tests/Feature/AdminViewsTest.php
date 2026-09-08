<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Disbursement;
use App\Models\ExpenseReport;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_bisa_membuka_seluruh_halaman_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $pengaju = User::factory()->pengaju()->create([
            'verification_status' => User::VERIFICATION_VERIFIED,
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'John Doe',
        ]);

        $campaign = Campaign::create([
            'user_id' => $pengaju->id,
            'title' => 'Kampanye Uji Admin',
            'slug' => 'kampanye-uji-admin',
            'category' => 'sosial',
            'summary' => 'ringkasan kampanye',
            'description' => 'deskripsi kampanye lengkap',
            'target_amount' => 10_000_000,
            'status' => Campaign::STATUS_PENDING,
        ]);

        $milestone = $campaign->milestones()->create([
            'sequence' => 1,
            'title' => 'Tahap 1',
            'amount' => 10_000_000,
            'status' => Milestone::STATUS_LOCKED,
        ]);

        $disb = Disbursement::create([
            'reference' => 'PC-2026-00001',
            'campaign_id' => $campaign->id,
            'milestone_id' => $milestone->id,
            'requested_by' => $pengaju->id,
            'amount' => 5_000_000,
            'purpose' => 'Pembelian bahan',
            'status' => Disbursement::STATUS_PENDING,
        ]);

        $expense = ExpenseReport::create([
            'campaign_id' => $campaign->id,
            'milestone_id' => $milestone->id,
            'created_by' => $pengaju->id,
            'title' => 'Nota Pembelian',
            'amount' => 2_500_000,
            'spent_on' => now()->subDay(),
            'description' => 'Beli semen',
            'status' => ExpenseReport::STATUS_PENDING,
        ]);

        // 1. Dashboard
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dasbor admin')
            ->assertSee('text-white', false)
            ->assertDontSee('text-ink-900', false);

        // 2. Campaigns Index & Show
        $this->actingAs($admin)->get(route('admin.kampanye.index'))
            ->assertOk()
            ->assertSee('Review kampanye')
            ->assertDontSee('border-ink-200 bg-white text-ink-600', false);

        $this->actingAs($admin)->get(route('admin.kampanye.show', $campaign))
            ->assertOk()
            ->assertSee($campaign->title);

        // 3. Disbursements Index & Show
        $this->actingAs($admin)->get(route('admin.pencairan.index'))
            ->assertOk()
            ->assertSee('Review pencairan dana')
            ->assertDontSee('bg-white shadow-sm', false);

        $this->actingAs($admin)->get(route('admin.pencairan.show', $disb))
            ->assertOk()
            ->assertSee($disb->reference);

        // 4. Expenses Index & Show
        $this->actingAs($admin)->get(route('admin.lpj.index'))
            ->assertOk()
            ->assertSee('Verifikasi laporan pertanggungjawaban')
            ->assertDontSee('bg-white shadow-sm', false);

        $this->actingAs($admin)->get(route('admin.lpj.show', $expense))
            ->assertOk()
            ->assertSee($expense->title);

        // 5. Users Index & Show
        $this->actingAs($admin)->get(route('admin.pengguna.index'))
            ->assertOk()
            ->assertSee('Verifikasi pengguna')
            ->assertDontSee('border-ink-200 bg-white text-ink-600', false);

        $this->actingAs($admin)->get(route('admin.pengguna.show', $pengaju))
            ->assertOk()
            ->assertSee($pengaju->name);

        // 6. Audit Logs
        $this->actingAs($admin)->get(route('admin.audit.index'))
            ->assertOk()
            ->assertSee('Jejak audit');
    }
}
