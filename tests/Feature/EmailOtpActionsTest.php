<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Disbursement;
use App\Models\EmailOtp;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmailOtpActionsTest extends TestCase
{
    use RefreshDatabase;

    private function bikinOtp(User $user, string $purpose, string $code = '123456'): void
    {
        EmailOtp::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5),
        ]);
    }

    private function kampanyeSiapCair(): array
    {
        Storage::fake('local');

        $pengaju = User::factory()->create([
            'role' => User::ROLE_PENGAJU,
            'bank_name' => 'BRI',
            'bank_account_number' => '337401004821530',
            'bank_account_holder' => 'AHMAD FAUZI',
            'verification_status' => User::VERIFICATION_VERIFIED,
            'identity_document_path' => 'ktp/test.jpg',
            'identity_number_last4' => '1234',
            'identity_number_hash' => User::hashIdentityNumber('3374012345671234'),
        ]);

        $campaign = Campaign::create([
            'user_id' => $pengaju->id,
            'title' => 'Sumur Bor Dusun Ngroto',
            'slug' => 'sumur-bor-test-'.\Illuminate\Support\Str::random(10),
            'category' => 'infrastruktur',
            'summary' => 'ringkas',
            'description' => 'panjang',
            'target_amount' => 10_000_000,
            'collected_amount' => 10_000_000,
            'status' => Campaign::STATUS_APPROVED,
        ]);

        $milestone = $campaign->milestones()->create([
            'sequence' => 1,
            'title' => 'Tahap 1',
            'amount' => 5_000_000,
            'status' => Milestone::STATUS_AVAILABLE,
        ]);

        return [$pengaju, $campaign, $milestone];
    }

    public function test_ganti_rekening_berhasil_dengan_otp_email_dan_status_menjadi_pending(): void
    {
        Storage::fake('local');

        $pengaju = User::factory()->create([
            'role' => User::ROLE_PENGAJU,
            'identity_document_path' => 'identitas/99/ktp.jpg',
            'identity_number_last4' => '4821',
            'identity_number_hash' => User::hashIdentityNumber('3324061503894821'),
            'bank_name' => 'BRI',
            'bank_account_number' => '337401004821530',
            'bank_account_holder' => 'AHMAD FAUZI',
            'verification_status' => User::VERIFICATION_VERIFIED,
        ]);

        $this->bikinOtp($pengaju, EmailOtp::PURPOSE_BANK_CHANGE, '654321');

        $response = $this->actingAs($pengaju)->post(route('verifikasi.identitas.store'), [
            'organization' => 'Yayasan Peduli',
            'bank_name' => 'BCA',
            'bank_account_number' => '8880001234',
            'bank_account_holder' => 'AHMAD FAUZI',
            'otp_code' => '654321',
        ]);

        $response->assertSessionHasNoErrors();

        $pengaju->refresh();
        // Rekening baru masuk ke antrean pending
        $this->assertSame('BCA', $pengaju->pending_bank_name);
        $this->assertSame('8880001234', $pengaju->pending_bank_account_number);
        // Rekening aktif yang lama tetap utuh
        $this->assertSame('BRI', $pengaju->bank_name);
        $this->assertSame('337401004821530', $pengaju->bank_account_number);
        // KTP dan NIK lama tetap dipertahankan
        $this->assertSame('identitas/99/ktp.jpg', $pengaju->identity_document_path);
        $this->assertSame('4821', $pengaju->identity_number_last4);
        // Status kembali menjadi pending untuk diperiksa ulang admin
        $this->assertSame(User::VERIFICATION_PENDING, $pengaju->verification_status);
    }

    public function test_ganti_rekening_ditolak_jika_otp_email_salah(): void
    {
        Storage::fake('local');

        $pengaju = User::factory()->create([
            'role' => User::ROLE_PENGAJU,
            'bank_name' => 'BRI',
            'bank_account_number' => '337401004821530',
            'bank_account_holder' => 'AHMAD FAUZI',
            'verification_status' => User::VERIFICATION_VERIFIED,
        ]);

        $this->bikinOtp($pengaju, EmailOtp::PURPOSE_BANK_CHANGE, '654321');

        $response = $this->actingAs($pengaju)->post(route('verifikasi.identitas.store'), [
            'organization' => 'Yayasan Peduli',
            'identity_number' => '3374012345678901',
            'identity_document' => UploadedFile::fake()->create('ktp.jpg', 200, 'image/jpeg'),
            'bank_name' => 'BCA',
            'bank_account_number' => '8880001234',
            'bank_account_holder' => 'AHMAD FAUZI',
            'otp_code' => '000000',
        ]);

        $response->assertSessionHasErrors('otp_code');
        $this->assertSame('BRI', $pengaju->fresh()->bank_name);
    }

    public function test_pengajuan_pencairan_berhasil_dengan_otp_email(): void
    {
        [$pengaju, $campaign, $milestone] = $this->kampanyeSiapCair();

        $this->bikinOtp($pengaju, EmailOtp::PURPOSE_DISBURSEMENT_REQUEST, '112233');

        $response = $this->actingAs($pengaju)->post(
            route('pengaju.kampanye.disbursement.request', [$campaign, $milestone]),
            [
                'purpose' => 'Beli material atap',
                'otp_code' => '112233',
            ]
        );

        $response->assertSessionHasNoErrors();
        $this->assertSame(1, Disbursement::count());
        $this->assertSame(Milestone::STATUS_REQUESTED, $milestone->fresh()->status);
    }

    public function test_pengajuan_pencairan_gagal_tanpa_otp_email(): void
    {
        [$pengaju, $campaign, $milestone] = $this->kampanyeSiapCair();

        $response = $this->actingAs($pengaju)->post(
            route('pengaju.kampanye.disbursement.request', [$campaign, $milestone]),
            [
                'purpose' => 'Beli material atap',
            ]
        );

        $response->assertSessionHasErrors('otp_code');
        $this->assertSame(0, Disbursement::count());
    }

    public function test_admin_rilis_dana_berhasil_dengan_otp_email(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$pengaju, $campaign, $milestone] = $this->kampanyeSiapCair();

        $disbursement = Disbursement::create([
            'reference' => 'PC-2026-TEST01',
            'campaign_id' => $campaign->id,
            'milestone_id' => $milestone->id,
            'requested_by' => $pengaju->id,
            'amount' => 5_000_000,
            'purpose' => 'Beli material atap',
            'payee_bank_name' => 'BRI',
            'payee_account_number' => '337401004821530',
            'payee_account_holder' => 'AHMAD FAUZI',
            'status' => Disbursement::STATUS_APPROVED,
        ]);

        $this->bikinOtp($admin, EmailOtp::PURPOSE_DISBURSEMENT_RELEASE, '998877');

        $response = $this->actingAs($admin)->post(
            route('admin.pencairan.release', $disbursement),
            [
                'proof' => UploadedFile::fake()->create('struk.jpg', 300, 'image/jpeg'),
                'otp_code' => '998877',
            ]
        );

        $response->assertSessionHasNoErrors();
        $this->assertSame(Disbursement::STATUS_RELEASED, $disbursement->fresh()->status);
        $this->assertSame(Milestone::STATUS_DISBURSED, $milestone->fresh()->status);
    }

    public function test_admin_rilis_dana_gagal_tanpa_otp_email(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$pengaju, $campaign, $milestone] = $this->kampanyeSiapCair();

        $disbursement = Disbursement::create([
            'reference' => 'PC-2026-TEST02',
            'campaign_id' => $campaign->id,
            'milestone_id' => $milestone->id,
            'requested_by' => $pengaju->id,
            'amount' => 5_000_000,
            'purpose' => 'Beli material atap',
            'payee_bank_name' => 'BRI',
            'payee_account_number' => '337401004821530',
            'payee_account_holder' => 'AHMAD FAUZI',
            'status' => Disbursement::STATUS_APPROVED,
        ]);

        $response = $this->actingAs($admin)->post(
            route('admin.pencairan.release', $disbursement),
            [
                'proof' => UploadedFile::fake()->create('struk.jpg', 300, 'image/jpeg'),
            ]
        );

        $response->assertSessionHasErrors('otp_code');
        $this->assertSame(Disbursement::STATUS_APPROVED, $disbursement->fresh()->status);
    }

    public function test_admin_rilis_dana_gagal_dengan_otp_salah(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$pengaju, $campaign, $milestone] = $this->kampanyeSiapCair();

        $disbursement = Disbursement::create([
            'reference' => 'PC-2026-TEST03',
            'campaign_id' => $campaign->id,
            'milestone_id' => $milestone->id,
            'requested_by' => $pengaju->id,
            'amount' => 5_000_000,
            'purpose' => 'Beli material atap',
            'payee_bank_name' => 'BRI',
            'payee_account_number' => '337401004821530',
            'payee_account_holder' => 'AHMAD FAUZI',
            'status' => Disbursement::STATUS_APPROVED,
        ]);

        $this->bikinOtp($admin, EmailOtp::PURPOSE_DISBURSEMENT_RELEASE, '998877');

        $response = $this->actingAs($admin)->post(
            route('admin.pencairan.release', $disbursement),
            [
                'proof' => UploadedFile::fake()->create('struk.jpg', 300, 'image/jpeg'),
                'otp_code' => '111111',
            ]
        );

        $response->assertSessionHasErrors('otp_code');
        $this->assertSame(Disbursement::STATUS_APPROVED, $disbursement->fresh()->status);
    }

    public function test_ganti_password_berhasil_dengan_otp_email(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('passwordLama123'),
        ]);

        $this->bikinOtp($user, EmailOtp::PURPOSE_PASSWORD_CHANGE, '445566');

        $response = $this->actingAs($user)->put(route('profil.password'), [
            'current_password' => 'passwordLama123',
            'password' => 'passwordBaru123',
            'password_confirmation' => 'passwordBaru123',
            'otp_code' => '445566',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('passwordBaru123', $user->fresh()->password));
    }

    public function test_ganti_password_gagal_tanpa_otp_email(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('passwordLama123'),
        ]);

        $response = $this->actingAs($user)->put(route('profil.password'), [
            'current_password' => 'passwordLama123',
            'password' => 'passwordBaru123',
            'password_confirmation' => 'passwordBaru123',
        ]);

        $response->assertSessionHasErrors('otp_code');
        $this->assertTrue(Hash::check('passwordLama123', $user->fresh()->password));
    }

    public function test_admin_setuju_ganti_rekening_mengaktifkan_rekening_baru(): void
    {
        $admin = User::factory()->admin()->create();
        $pengaju = User::factory()->create([
            'role' => User::ROLE_PENGAJU,
            'bank_name' => 'BRI',
            'bank_account_number' => '337401004821530',
            'bank_account_holder' => 'AHMAD FAUZI',
            'pending_bank_name' => 'BCA',
            'pending_bank_account_number' => '8880001234',
            'pending_bank_account_holder' => 'AHMAD FAUZI',
            'verification_status' => User::VERIFICATION_PENDING,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.pengguna.decide', $pengaju), [
            'decision' => 'verified',
        ]);

        $response->assertRedirect(route('admin.pengguna.index'));
        $pengaju->refresh();
        $this->assertSame('BCA', $pengaju->bank_name);
        $this->assertSame('8880001234', $pengaju->bank_account_number);
        $this->assertNull($pengaju->pending_bank_name);
        $this->assertNull($pengaju->pending_bank_account_number);
        $this->assertSame(User::VERIFICATION_VERIFIED, $pengaju->verification_status);
    }

    public function test_admin_tolak_ganti_rekening_kembali_ke_rekening_lama(): void
    {
        $admin = User::factory()->admin()->create();
        $pengaju = User::factory()->create([
            'role' => User::ROLE_PENGAJU,
            'bank_name' => 'BRI',
            'bank_account_number' => '337401004821530',
            'bank_account_holder' => 'AHMAD FAUZI',
            'pending_bank_name' => 'BCA',
            'pending_bank_account_number' => '8880001234',
            'pending_bank_account_holder' => 'PENIPU LAIN',
            'verification_status' => User::VERIFICATION_PENDING,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.pengguna.decide', $pengaju), [
            'decision' => 'rejected',
            'note' => 'Nama pemilik rekening baru tidak sesuai dengan KTP.',
        ]);

        $response->assertRedirect(route('admin.pengguna.index'));
        $pengaju->refresh();
        // Rekening lama tetap dipakai
        $this->assertSame('BRI', $pengaju->bank_name);
        $this->assertSame('337401004821530', $pengaju->bank_account_number);
        $this->assertSame('AHMAD FAUZI', $pengaju->bank_account_holder);
        // Antrean rekening baru dibersihkan
        $this->assertNull($pengaju->pending_bank_name);
        $this->assertNull($pengaju->pending_bank_account_number);
        $this->assertNull($pengaju->pending_bank_account_holder);
        // Status kembali verified menggunakan rekening lama
        $this->assertSame(User::VERIFICATION_VERIFIED, $pengaju->verification_status);
        $this->assertSame('Nama pemilik rekening baru tidak sesuai dengan KTP.', $pengaju->verification_note);
    }
}
