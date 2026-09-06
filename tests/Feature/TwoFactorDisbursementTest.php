<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\Disbursement;
use App\Models\Milestone;
use App\Models\User;
use App\Notifications\PayoutAccountChanged;
use App\Services\Totp;
use App\Services\TotpGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Verifikasi dua langkah, ditempatkan di titik yang benar-benar berisiko.
 *
 * Peta keputusannya — dan test di bawah ini menjaga ketiganya:
 *
 * 1. MENGAJUKAN pencairan TIDAK bergerbang. Rekening tujuan sudah terkunci ke
 *    profil terverifikasi, jadi akun yang dibajak pun tidak bisa mengalihkan
 *    dana. Menambah kode di situ = friction besar, keamanan nyaris nol.
 * 2. MENGGANTI rekening tujuan bergerbang. Inilah satu-satunya jalan dana bisa
 *    diarahkan ke pihak lain, dan aksinya sekali seumur akun.
 * 3. MELEPAS dana bergerbang, dengan jendela 15 menit supaya admin tidak perlu
 *    mengetik kode untuk tiap baris antrean.
 */
class TwoFactorDisbursementTest extends TestCase
{
    use RefreshDatabase;

    private Totp $totp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->totp = app(Totp::class);
    }

    // --- 1. Pengajuan pencairan: membutuhkan OTP email --------------------

    public function test_pengajuan_pencairan_membutuhkan_otp_email_tetapi_bukan_authenticator(): void
    {
        [$pengaju, $campaign, $milestone] = $this->kampanyeSiapCair();
        $this->buatOtp($pengaju, \App\Models\EmailOtp::PURPOSE_DISBURSEMENT_REQUEST, '123456');

        $this->actingAs($pengaju)
            ->post($this->rutePengajuan($campaign, $milestone), [
                'purpose' => 'Beli material',
                'otp_code' => '123456',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Disbursement::count());
        $this->assertSame(Milestone::STATUS_REQUESTED, $milestone->fresh()->status);
    }

    public function test_pengajuan_mengunci_rekening_tujuan_dari_profil_terverifikasi(): void
    {
        [$pengaju, $campaign, $milestone] = $this->kampanyeSiapCair();
        $this->buatOtp($pengaju, \App\Models\EmailOtp::PURPOSE_DISBURSEMENT_REQUEST, '123456');

        $this->actingAs($pengaju)
            ->post($this->rutePengajuan($campaign, $milestone), [
                'purpose' => 'Beli material',
                'otp_code' => '123456',
            ])
            ->assertSessionHasNoErrors();

        $disbursement = Disbursement::sole();

        $this->assertSame('BRI', $disbursement->payee_bank_name);
        $this->assertSame('337401004821530', $disbursement->payee_account_number);
        $this->assertSame('AHMAD FAUZI', $disbursement->payee_account_holder);
    }

    public function test_rekening_tujuan_tidak_bisa_ditentukan_dari_request(): void
    {
        [$pengaju, $campaign, $milestone] = $this->kampanyeSiapCair();
        $this->buatOtp($pengaju, \App\Models\EmailOtp::PURPOSE_DISBURSEMENT_REQUEST, '123456');

        $this->actingAs($pengaju)
            ->post($this->rutePengajuan($campaign, $milestone), [
                'purpose' => 'Beli material',
                'otp_code' => '123456',
                // Inilah sebabnya pengajuan tidak perlu digerbangi: parameter
                // titipan pun tidak bisa mengalihkan tujuan dananya.
                'payee_account_number' => '6660000000',
                'payee_bank_name' => 'BANK PENYERANG',
                'payee_account_holder' => 'ORANG LAIN',
            ])
            ->assertSessionHasNoErrors();

        $disbursement = Disbursement::sole();

        $this->assertSame('337401004821530', $disbursement->payee_account_number);
        $this->assertSame('BRI', $disbursement->payee_bank_name);
    }

    // --- 2. Penggantian rekening: DI SINI gerbangnya -------------------------

    public function test_ganti_rekening_ditolak_tanpa_kode(): void
    {
        Storage::fake('local');
        $pengaju = $this->pengajuTerverifikasi();
        $this->aktifkanDuaLangkah($pengaju);

        $this->actingAs($pengaju)
            ->post(route('verifikasi.identitas.store'), $this->payloadVerifikasi([
                'bank_account_number' => '6660000000',
            ]))
            ->assertSessionHasErrors('otp_code');

        // Rekening lama harus utuh.
        $this->assertSame('337401004821530', $pengaju->fresh()->bank_account_number);
    }

    public function test_ganti_rekening_berhasil_dengan_kode_yang_sah(): void
    {
        Storage::fake('local');
        $pengaju = $this->pengajuTerverifikasi();
        $secret = $this->aktifkanDuaLangkah($pengaju);

        $this->actingAs($pengaju)
            ->post(route('verifikasi.identitas.store'), $this->payloadVerifikasi([
                'bank_account_number' => '6660000000',
                'totp_code' => $this->kodeSekarang($secret),
            ]))
            ->assertSessionHasNoErrors();

        $segar = $pengaju->fresh();

        $this->assertSame('6660000000', $segar->pending_bank_account_number);
        $this->assertSame('337401004821530', $segar->bank_account_number);
        // Ganti rekening selalu mengembalikan status ke menunggu peninjauan.
        $this->assertSame(User::VERIFICATION_PENDING, $segar->verification_status);
    }

    public function test_pendaftaran_rekening_pertama_tidak_meminta_kode(): void
    {
        Storage::fake('local');

        // Belum punya rekening apa pun: tidak ada yang bisa dicuri, dan meminta
        // kode di sini justru mengunci pengguna baru di luar alurnya sendiri.
        $pengaju = User::factory()->pengaju()->create([
            'verification_status' => User::VERIFICATION_UNVERIFIED,
        ]);
        $this->aktifkanDuaLangkah($pengaju);

        $this->actingAs($pengaju)
            ->post(route('verifikasi.identitas.store'), $this->payloadVerifikasi())
            ->assertSessionHasNoErrors();

        $this->assertSame('337401004821530', $pengaju->fresh()->bank_account_number);
    }

    public function test_kirim_ulang_verifikasi_tanpa_mengubah_rekening_tidak_meminta_kode(): void
    {
        Storage::fake('local');
        $pengaju = $this->pengajuTerverifikasi();
        $this->aktifkanDuaLangkah($pengaju);

        // Kasus nyata: KTP ditolak admin karena buram, pengaju mengunggah ulang
        // dengan rekening yang sama persis. Tidak ada risiko baru di sini.
        $this->actingAs($pengaju)
            ->post(route('verifikasi.identitas.store'), $this->payloadVerifikasi([
                // Ditulis dengan tanda hubung: normalisasi harus mengenalinya
                // sebagai rekening yang sama, bukan perubahan.
                'bank_account_number' => '3374-0100-4821-530',
            ]))
            ->assertSessionHasNoErrors();
    }

    public function test_pemilik_akun_diberi_tahu_saat_rekening_diubah(): void
    {
        Storage::fake('local');
        Notification::fake();

        $pengaju = $this->pengajuTerverifikasi();
        $secret = $this->aktifkanDuaLangkah($pengaju);

        $this->actingAs($pengaju)
            ->post(route('verifikasi.identitas.store'), $this->payloadVerifikasi([
                'bank_account_number' => '6660000000',
                'totp_code' => $this->kodeSekarang($secret),
            ]))
            ->assertSessionHasNoErrors();

        // Kalau perubahan ini bukan pemiliknya yang melakukan, surat inilah
        // satu-satunya kesempatan dia tahu sebelum admin meloloskan.
        Notification::assertSentTo($pengaju, PayoutAccountChanged::class);
    }

    public function test_penggantian_rekening_tercatat_di_jejak_audit(): void
    {
        Storage::fake('local');
        $pengaju = $this->pengajuTerverifikasi();
        $secret = $this->aktifkanDuaLangkah($pengaju);

        $this->actingAs($pengaju)
            ->post(route('verifikasi.identitas.store'), $this->payloadVerifikasi([
                'bank_account_number' => '6660000000',
                'totp_code' => $this->kodeSekarang($secret),
            ]));

        $entri = AuditLog::where('action', 'user.payout_account_changed')->sole();

        $this->assertTrue($entri->metadata['dua_langkah']);
        // Nomor lengkap tidak boleh bocor ke jejak audit yang dibaca admin lain.
        $this->assertStringNotContainsString('6660000000', json_encode($entri->metadata));
        $this->assertStringContainsString('0000', $entri->metadata['rekening_baru']);
    }

    // --- 3. Pelepasan dana oleh admin ---------------------------------------

    public function test_pelepasan_dana_ditolak_bila_admin_belum_punya_dua_langkah(): void
    {
        Storage::fake(config('filesystems.default'));

        [$admin, $disbursement] = $this->pencairanSiapDilepas();

        $this->actingAs($admin)
            ->post(route('admin.pencairan.release', $disbursement), [
                'proof' => UploadedFile::fake()->image('bukti.jpg'),
            ])
            ->assertSessionHasErrors('totp_code');

        $this->assertSame(Disbursement::STATUS_APPROVED, $disbursement->fresh()->status);
    }

    public function test_pelepasan_dana_dengan_kode_salah_tidak_menyimpan_bukti_transfer(): void
    {
        $disk = config('filesystems.default');
        Storage::fake($disk);

        [$admin, $disbursement] = $this->pencairanSiapDilepas();
        $secret = $this->aktifkanDuaLangkah($admin);

        $this->actingAs($admin)
            ->post(route('admin.pencairan.release', $disbursement), [
                'proof' => UploadedFile::fake()->image('bukti.jpg'),
                'totp_code' => $this->kodeSalah($secret),
            ])
            ->assertSessionHasErrors('totp_code');

        $this->assertSame(Disbursement::STATUS_APPROVED, $disbursement->fresh()->status);
        $this->assertEmpty(Storage::disk($disk)->files('pencairan'),
            'Kode yang gagal tidak boleh meninggalkan bukti transfer yatim di disk.');
    }

    public function test_pelepasan_dana_berhasil_dengan_kode_yang_sah(): void
    {
        Storage::fake(config('filesystems.default'));

        [$admin, $disbursement] = $this->pencairanSiapDilepas();
        $secret = $this->aktifkanDuaLangkah($admin);

        $this->actingAs($admin)
            ->post(route('admin.pencairan.release', $disbursement), [
                'proof' => UploadedFile::fake()->image('bukti.jpg'),
                'totp_code' => $this->kodeSekarang($secret),
            ])
            ->assertSessionHasNoErrors();

        $segar = $disbursement->fresh();

        $this->assertSame(Disbursement::STATUS_RELEASED, $segar->status);
        $this->assertSame(Milestone::STATUS_DISBURSED, $segar->milestone->status);
        $this->assertSame('kode', AuditLog::where('action', 'disbursement.released')->sole()->metadata['dua_langkah']);
    }

    // --- Jendela sudo 15 menit ----------------------------------------------

    public function test_satu_kode_membuka_jendela_untuk_pencairan_berikutnya(): void
    {
        Storage::fake(config('filesystems.default'));

        [$admin, $pertama, $campaign] = $this->pencairanSiapDilepas(kembalikanKampanye: true);
        $kedua = $this->buatPencairanDisetujui($campaign, 2);
        $secret = $this->aktifkanDuaLangkah($admin);

        $this->actingAs($admin)->post(route('admin.pencairan.release', $pertama), [
            'proof' => UploadedFile::fake()->image('bukti1.jpg'),
            'totp_code' => $this->kodeSekarang($secret),
        ])->assertSessionHasNoErrors();

        // Pencairan kedua: TANPA kode sama sekali.
        $this->actingAs($admin)->post(route('admin.pencairan.release', $kedua), [
            'proof' => UploadedFile::fake()->image('bukti2.jpg'),
        ])->assertSessionHasNoErrors();

        $this->assertSame(Disbursement::STATUS_RELEASED, $kedua->fresh()->status);

        // Cara otorisasinya dibedakan, supaya pemeriksa tahu mana yang diketik
        // langsung dan mana yang menumpang jendela.
        $cara = AuditLog::where('action', 'disbursement.released')
            ->orderBy('id')->get()->map(fn ($e) => $e->metadata['dua_langkah'])->all();

        $this->assertSame(['kode', 'jendela_15_menit'], $cara);
    }

    public function test_jendela_kedaluwarsa_setelah_batas_waktunya(): void
    {
        Storage::fake(config('filesystems.default'));

        [$admin, $pertama, $campaign] = $this->pencairanSiapDilepas(kembalikanKampanye: true);
        $kedua = $this->buatPencairanDisetujui($campaign, 2);
        $secret = $this->aktifkanDuaLangkah($admin);

        $this->actingAs($admin)->post(route('admin.pencairan.release', $pertama), [
            'proof' => UploadedFile::fake()->image('bukti1.jpg'),
            'totp_code' => $this->kodeSekarang($secret),
        ])->assertSessionHasNoErrors();

        $this->travel(TotpGuard::SUDO_WINDOW_MINUTES + 1)->minutes();

        $this->actingAs($admin)->post(route('admin.pencairan.release', $kedua), [
            'proof' => UploadedFile::fake()->image('bukti2.jpg'),
        ])->assertSessionHasErrors('totp_code');

        $this->assertSame(Disbursement::STATUS_APPROVED, $kedua->fresh()->status);
    }

    public function test_jendela_tidak_berlaku_untuk_mematikan_dua_langkah(): void
    {
        Storage::fake(config('filesystems.default'));

        [$admin, $disbursement] = $this->pencairanSiapDilepas();
        $secret = $this->aktifkanDuaLangkah($admin, password: 'password123');

        $this->actingAs($admin)->post(route('admin.pencairan.release', $disbursement), [
            'proof' => UploadedFile::fake()->image('bukti.jpg'),
            'totp_code' => $this->kodeSekarang($secret),
        ])->assertSessionHasNoErrors();

        // Jendela terbuka, tapi mematikan alarm adalah justru yang paling ingin
        // dilakukan penyerang duluan — kode segar tetap wajib.
        $this->actingAs($admin)
            ->delete(route('keamanan.matikan'), ['current_password' => 'password123'])
            ->assertSessionHasErrors('totp_code');

        $this->assertTrue($admin->fresh()->hasTwoFactorEnabled());
    }

    public function test_jendela_tertutup_saat_dua_langkah_dimatikan(): void
    {
        Storage::fake(config('filesystems.default'));

        [$admin, $pertama, $campaign] = $this->pencairanSiapDilepas(kembalikanKampanye: true);
        $kedua = $this->buatPencairanDisetujui($campaign, 2);
        $secret = $this->aktifkanDuaLangkah($admin, password: 'password123');

        $this->actingAs($admin)->post(route('admin.pencairan.release', $pertama), [
            'proof' => UploadedFile::fake()->image('bukti1.jpg'),
            'totp_code' => $this->kodeSekarang($secret),
        ])->assertSessionHasNoErrors();

        // Kode berikutnya harus dari selang waktu yang berbeda: yang barusan
        // sudah hangus.
        $this->travel(60)->seconds();

        $this->actingAs($admin)->delete(route('keamanan.matikan'), [
            'current_password' => 'password123',
            'totp_code' => $this->kodeSekarang($secret),
        ])->assertSessionHasNoErrors();

        // Dua langkah dicabut -> sisa jendelanya tidak boleh tetap berlaku.
        $this->actingAs($admin)->post(route('admin.pencairan.release', $kedua), [
            'proof' => UploadedFile::fake()->image('bukti2.jpg'),
        ])->assertSessionHasErrors('totp_code');

        $this->assertSame(Disbursement::STATUS_APPROVED, $kedua->fresh()->status);
    }

    // --- Sifat kode: sekali pakai, dibatasi, bisa dipulihkan ------------------

    public function test_kode_yang_sama_tidak_bisa_dipakai_dua_kali(): void
    {
        $this->travelTo('2026-09-03 10:00:00');
        Storage::fake(config('filesystems.default'));

        [$admin, $pertama, $campaign] = $this->pencairanSiapDilepas(kembalikanKampanye: true);
        $kedua = $this->buatPencairanDisetujui($campaign, 2);
        $secret = $this->aktifkanDuaLangkah($admin);
        $kode = $this->kodeSekarang($secret);

        $this->actingAs($admin)->post(route('admin.pencairan.release', $pertama), [
            'proof' => UploadedFile::fake()->image('bukti1.jpg'),
            'totp_code' => $kode,
        ])->assertSessionHasNoErrors();

        // Jendela ditutup paksa supaya yang diuji benar-benar kodenya, bukan
        // jendela yang kebetulan masih terbuka.
        $this->app->make(TotpGuard::class)->closeWindow();

        $this->actingAs($admin)->post(route('admin.pencairan.release', $kedua), [
            'proof' => UploadedFile::fake()->image('bukti2.jpg'),
            'totp_code' => $kode,
        ])->assertSessionHasErrors('totp_code');

        $this->assertSame(Disbursement::STATUS_APPROVED, $kedua->fresh()->status);
    }

    public function test_kode_pemulihan_berlaku_sekali(): void
    {
        Storage::fake(config('filesystems.default'));

        [$admin, $pertama, $campaign] = $this->pencairanSiapDilepas(kembalikanKampanye: true);
        $kedua = $this->buatPencairanDisetujui($campaign, 2);
        $this->aktifkanDuaLangkah($admin, recoveryCodes: ['ABCD-1234', 'EFGH-5678']);

        $this->actingAs($admin)->post(route('admin.pencairan.release', $pertama), [
            'proof' => UploadedFile::fake()->image('bukti1.jpg'),
            'totp_code' => 'ABCD-1234',
        ])->assertSessionHasNoErrors();

        $this->assertSame(['EFGH-5678'], $admin->fresh()->totp_recovery_codes);

        $this->app->make(TotpGuard::class)->closeWindow();

        $this->actingAs($admin)->post(route('admin.pencairan.release', $kedua), [
            'proof' => UploadedFile::fake()->image('bukti2.jpg'),
            'totp_code' => 'ABCD-1234',
        ])->assertSessionHasErrors('totp_code');

        $this->assertSame(Disbursement::STATUS_APPROVED, $kedua->fresh()->status);
    }

    public function test_percobaan_kode_dibatasi_lajunya(): void
    {
        Storage::fake(config('filesystems.default'));

        [$admin, $disbursement] = $this->pencairanSiapDilepas();
        $secret = $this->aktifkanDuaLangkah($admin);
        $salah = $this->kodeSalah($secret);

        for ($i = 0; $i < TotpGuard::MAX_ATTEMPTS; $i++) {
            $this->actingAs($admin)->post(route('admin.pencairan.release', $disbursement), [
                'proof' => UploadedFile::fake()->image('bukti.jpg'),
                'totp_code' => $salah,
            ])->assertSessionHasErrors('totp_code');
        }

        // Jatah habis: kode BENAR pun ditahan. Tanpa ini, enam digit hanya
        // sejuta kemungkinan untuk ditebak.
        $this->actingAs($admin)->post(route('admin.pencairan.release', $disbursement), [
            'proof' => UploadedFile::fake()->image('bukti.jpg'),
            'totp_code' => $this->kodeSekarang($secret),
        ])->assertSessionHasErrors('totp_code');

        $this->assertSame(Disbursement::STATUS_APPROVED, $disbursement->fresh()->status);
    }

    public function test_percobaan_gagal_tercatat_di_jejak_audit(): void
    {
        Storage::fake(config('filesystems.default'));

        [$admin, $disbursement] = $this->pencairanSiapDilepas();
        $secret = $this->aktifkanDuaLangkah($admin);

        $this->actingAs($admin)->post(route('admin.pencairan.release', $disbursement), [
            'proof' => UploadedFile::fake()->image('bukti.jpg'),
            'totp_code' => $this->kodeSalah($secret),
        ]);

        $entri = AuditLog::where('action', 'totp.failed')->sole();

        $this->assertSame('disbursement.release', $entri->metadata['untuk']);
        // Kode yang dicoba TIDAK boleh ikut tercatat di mana pun.
        $this->assertStringNotContainsString($this->kodeSalah($secret), json_encode($entri->metadata));
    }

    // --- Pendaftaran & pencabutan --------------------------------------------

    public function test_pendaftaran_menyimpan_kunci_hanya_setelah_kode_terbukti_cocok(): void
    {
        $user = User::factory()->pengaju()->create();

        $this->actingAs($user)->get(route('keamanan.index'))->assertOk();
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());

        $secret = session('totp.pending_secret');
        $this->assertNotEmpty($secret);

        $this->actingAs($user)
            ->post(route('keamanan.aktifkan'), ['totp_code' => $this->kodeSalah($secret)])
            ->assertSessionHasErrors('totp_code');

        $this->assertFalse($user->fresh()->hasTwoFactorEnabled(),
            'Kunci tidak boleh aktif sebelum aplikasi authenticator terbukti membacanya.');

        $this->actingAs($user)
            ->post(route('keamanan.aktifkan'), ['totp_code' => $this->kodeSekarang($secret)])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('recovery_codes');

        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
        $this->assertCount(8, $user->fresh()->totp_recovery_codes);
        $this->assertSame(1, AuditLog::where('action', 'totp.enabled')->count());
    }

    public function test_mematikan_dua_langkah_butuh_kata_sandi_dan_kode(): void
    {
        $user = User::factory()->pengaju()->create(['password' => 'password123']);
        $secret = $this->aktifkanDuaLangkah($user);

        $this->actingAs($user)->delete(route('keamanan.matikan'), [
            'current_password' => 'password123',
            'totp_code' => $this->kodeSalah($secret),
        ])->assertSessionHasErrors('totp_code');

        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());

        $this->actingAs($user)->delete(route('keamanan.matikan'), [
            'current_password' => 'salah-sekali',
            'totp_code' => $this->kodeSekarang($secret),
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());

        $this->actingAs($user)->delete(route('keamanan.matikan'), [
            'current_password' => 'password123',
            'totp_code' => $this->kodeSekarang($secret),
        ])->assertSessionHasNoErrors();

        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
        $this->assertSame(1, AuditLog::where('action', 'totp.disabled')->count());
    }

    public function test_kunci_totp_tidak_ikut_saat_model_diserialisasi(): void
    {
        $user = User::factory()->pengaju()->create();
        $this->aktifkanDuaLangkah($user);

        $array = $user->fresh()->toArray();

        $this->assertArrayNotHasKey('totp_secret', $array);
        $this->assertArrayNotHasKey('totp_recovery_codes', $array);
    }

    public function test_kunci_totp_tersimpan_terenkripsi_di_basis_data(): void
    {
        $user = User::factory()->pengaju()->create();
        $secret = $this->aktifkanDuaLangkah($user);

        $mentah = \DB::table('users')->where('id', $user->id)->value('totp_secret');

        $this->assertNotSame($secret, $mentah);
        $this->assertStringNotContainsString($secret, (string) $mentah,
            'Dump basis data yang bocor tidak boleh memuat kunci TOTP apa adanya.');
    }

    // --- Sinyal ke publik & kelengkapan form ---------------------------------

    public function test_lencana_dua_langkah_tampil_di_halaman_kampanye_publik(): void
    {
        [$pengaju, $campaign] = $this->kampanyeSiapCair();

        // Belum aktif: tidak ada klaim apa pun yang boleh ditampilkan.
        $this->get(route('kampanye.show', $campaign))
            ->assertOk()
            ->assertDontSee('dikunci verifikasi dua langkah');

        $this->aktifkanDuaLangkah($pengaju);

        // Opt-in yang diambil pengaju berubah jadi sinyal yang bisa dilihat
        // calon donatur — bukan sekadar pengaturan tersembunyi.
        $this->get(route('kampanye.show', $campaign))
            ->assertOk()
            ->assertSee('dikunci verifikasi dua langkah');
    }

    public function test_pengaju_terverifikasi_tetap_bisa_membuka_form_ganti_rekening(): void
    {
        $pengaju = $this->pengajuTerverifikasi();
        $this->aktifkanDuaLangkah($pengaju);

        // Sempat tidak begitu: halaman ini hanya menampilkan kartu "sudah
        // terverifikasi" tanpa form apa pun, sehingga gerbang TOTP di
        // penggantian rekening tidak bisa dicapai oleh satu-satunya kelompok
        // yang justru punya rekening untuk diganti.
        $this->actingAs($pengaju)->get(route('verifikasi.identitas'))
            ->assertOk()
            ->assertSee('Ubah rekening pencairan')
            ->assertSee('name="bank_account_number"', false)
            ->assertSee('name="totp_code"', false);
    }

    public function test_kedua_form_pelepasan_dana_menyediakan_kolom_kode(): void
    {
        [$admin, $disbursement] = $this->pencairanSiapDilepas();
        $this->aktifkanDuaLangkah($admin);

        // Pelepasan dana punya DUA form — inline di halaman daftar dan di
        // halaman detail. Yang di daftar pernah terlewat, sehingga admin dapat
        // pesan "masukkan kode" tanpa ada tempat mengetiknya.
        $this->actingAs($admin)->get(route('admin.pencairan.index'))
            ->assertOk()->assertSee('name="totp_code"', false);

        $this->actingAs($admin)->get(route('admin.pencairan.show', $disbursement))
            ->assertOk()->assertSee('name="totp_code"', false);
    }

    // --- Perkakas -------------------------------------------------------------

    /** @param  array<int, string>|null  $recoveryCodes */
    private function aktifkanDuaLangkah(User $user, ?array $recoveryCodes = null, ?string $password = null): string
    {
        $secret = $this->totp->generateSecret();

        $user->forceFill(array_filter([
            'totp_secret' => $secret,
            'totp_confirmed_at' => now(),
            'totp_recovery_codes' => $recoveryCodes ?? ['ZZZZ-9999'],
            'password' => $password,
        ]))->save();

        return $secret;
    }

    private function kodeSekarang(string $secret): string
    {
        return $this->totp->codeAt($secret, $this->totp->timestepAt());
    }

    /** Kode yang dijamin berbeda dari kode yang sedang berlaku. */
    private function kodeSalah(string $secret): string
    {
        return $this->kodeSekarang($secret) === '000000' ? '111111' : '000000';
    }

    private function rutePengajuan(Campaign $campaign, Milestone $milestone): string
    {
        return route('pengaju.kampanye.disbursement.request', [$campaign, $milestone]);
    }

    /** @return array<string, mixed> */
    private function payloadVerifikasi(array $ubah = []): array
    {
        return array_merge([
            'identity_number' => '3324061503894821',
            'identity_document' => UploadedFile::fake()->image('ktp.jpg'),
            'bank_name' => 'BRI',
            'bank_account_number' => '337401004821530',
            'bank_account_holder' => 'AHMAD FAUZI',
        ], $ubah);
    }

    private function pengajuTerverifikasi(): User
    {
        return User::factory()->pengaju()->create([
            'bank_name' => 'BRI',
            'bank_account_number' => '337401004821530',
            'bank_account_holder' => 'AHMAD FAUZI',
        ]);
    }

    /** @return array{0: User, 1: Campaign, 2: Milestone} */
    private function kampanyeSiapCair(): array
    {
        $pengaju = $this->pengajuTerverifikasi();

        $campaign = Campaign::create([
            'user_id' => $pengaju->id,
            'title' => 'Sumur Bor Dusun Ngroto',
            'slug' => 'sumur-bor-'.Str::random(10),
            'category' => 'infrastruktur',
            'summary' => 'ringkas',
            'description' => 'panjang',
            'target_amount' => 1_000_000,
            'collected_amount' => 1_000_000,
            'status' => Campaign::STATUS_APPROVED,
        ]);

        $milestone = $campaign->milestones()->create([
            'sequence' => 1,
            'title' => 'Tahap 1',
            'amount' => 600_000,
            'status' => Milestone::STATUS_AVAILABLE,
        ]);

        return [$pengaju, $campaign, $milestone];
    }

    /** @return array{0: User, 1: Disbursement, 2: Campaign} */
    private function pencairanSiapDilepas(bool $kembalikanKampanye = false): array
    {
        [, $campaign] = $this->kampanyeSiapCair();

        return [
            User::factory()->admin()->create(),
            $this->buatPencairanDisetujui($campaign, 1),
            $campaign,
        ];
    }

    private function buatPencairanDisetujui(Campaign $campaign, int $sequence): Disbursement
    {
        $milestone = $campaign->milestones()->firstOrCreate(
            ['sequence' => $sequence],
            ['title' => 'Tahap '.$sequence, 'amount' => 400_000],
        );

        $milestone->update(['status' => Milestone::STATUS_APPROVED]);

        return Disbursement::create([
            'reference' => 'PC-2026-'.Str::padLeft((string) $milestone->id, 5, '0'),
            'campaign_id' => $campaign->id,
            'milestone_id' => $milestone->id,
            'requested_by' => $campaign->user_id,
            'amount' => $milestone->amount,
            'purpose' => 'Beli material',
            'payee_bank_name' => 'BRI',
            'payee_account_number' => '337401004821530',
            'payee_account_holder' => 'AHMAD FAUZI',
            'status' => Disbursement::STATUS_APPROVED,
        ]);
    }

    private function buatOtp(User $user, string $purpose, string $code = '123456'): void
    {
        \App\Models\EmailOtp::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'purpose' => $purpose,
            'code_hash' => \Illuminate\Support\Facades\Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5),
        ]);
    }
}
