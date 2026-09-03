<?php

namespace Tests\Unit;

use App\Services\Totp;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Algoritma TOTP diuji langsung terhadap vektor uji resmi RFC 6238 Lampiran B.
 *
 * Ini penting justru karena implementasinya ditulis sendiri: kalau ada satu bit
 * yang salah tempat, kode yang dihasilkan tetap "kelihatan" seperti enam digit
 * yang wajar, tapi tidak akan pernah cocok dengan aplikasi authenticator mana
 * pun. Vektor uji resmi adalah satu-satunya cara membuktikan kecocokannya
 * tanpa memasang aplikasi authenticator sungguhan.
 */
class TotpTest extends TestCase
{
    private Totp $totp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->totp = new Totp;
    }

    /** Kunci contoh pada RFC 6238: ASCII "12345678901234567890". */
    private function secretRfc(): string
    {
        return $this->totp->base32Encode('12345678901234567890');
    }

    public function test_base32_cocok_dengan_rfc_4648(): void
    {
        $this->assertSame(
            'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
            $this->totp->base32Encode('12345678901234567890'),
        );
    }

    public function test_base32_bolak_balik_utuh(): void
    {
        $binary = random_bytes(20);

        $this->assertSame($binary, $this->totp->base32Decode($this->totp->base32Encode($binary)));
    }

    public function test_base32_memaafkan_spasi_dan_huruf_kecil(): void
    {
        // Yang diketik ulang orang dari layar: huruf kecil, dipisah spasi.
        $this->assertSame(
            '12345678901234567890',
            $this->totp->base32Decode('gezd gnbv gy3t qojq gezd gnbv gy3t qojq'),
        );
    }

    #[DataProvider('vektorRfc6238')]
    public function test_kode_cocok_dengan_vektor_uji_rfc_6238(int $waktu, string $harapan): void
    {
        $this->assertSame(
            $harapan,
            $this->totp->codeAt($this->secretRfc(), $this->totp->timestepAt($waktu)),
            "Kode pada detik {$waktu} tidak cocok dengan RFC 6238.",
        );
    }

    /**
     * Enam digit terakhir dari nilai 8 digit pada RFC 6238 Lampiran B (SHA-1).
     *
     * @return array<string, array{int, string}>
     */
    public static function vektorRfc6238(): array
    {
        return [
            '1970-01-01 00:00:59' => [59, '287082'],
            '2005-03-18 01:58:29' => [1111111109, '081804'],
            '2005-03-18 01:58:31' => [1111111111, '050471'],
            '2009-02-13 23:31:30' => [1234567890, '005924'],
            '2033-05-18 03:33:20' => [2000000000, '279037'],
            '2603-10-11 11:33:20' => [20000000000, '353130'],
        ];
    }

    public function test_kode_yang_benar_diterima_dan_mengembalikan_selang_waktunya(): void
    {
        $secret = $this->totp->generateSecret();
        $step = $this->totp->timestepAt();

        $this->assertSame($step, $this->totp->verify($secret, $this->totp->codeAt($secret, $step)));
    }

    public function test_kode_salah_ditolak(): void
    {
        $secret = $this->totp->generateSecret();

        $this->assertNull($this->totp->verify($secret, '000000', timestamp: 1_700_000_000));
        $this->assertNull($this->totp->verify($secret, 'bukan-angka'));
        $this->assertNull($this->totp->verify($secret, '12345'), 'Kode kurang dari enam digit harus ditolak.');
    }

    public function test_toleransi_jam_meleset_satu_periode(): void
    {
        $secret = $this->totp->generateSecret();
        $sekarang = 1_700_000_000;
        $step = $this->totp->timestepAt($sekarang);

        // Ponsel yang jamnya tertinggal / mendahului satu periode tetap diterima.
        $this->assertSame($step - 1, $this->totp->verify($secret, $this->totp->codeAt($secret, $step - 1), timestamp: $sekarang));
        $this->assertSame($step + 1, $this->totp->verify($secret, $this->totp->codeAt($secret, $step + 1), timestamp: $sekarang));

        // Dua periode meleset sudah di luar toleransi.
        $this->assertNull($this->totp->verify($secret, $this->totp->codeAt($secret, $step + 2), timestamp: $sekarang));
    }

    public function test_kunci_berbeda_menghasilkan_kode_berbeda(): void
    {
        $step = $this->totp->timestepAt();

        $this->assertNotSame(
            $this->totp->codeAt($this->totp->generateSecret(), $step),
            $this->totp->codeAt($this->totp->generateSecret(), $step),
        );
    }

    public function test_uri_otpauth_memuat_parameter_yang_dibaca_aplikasi_authenticator(): void
    {
        $uri = $this->totp->provisioningUri('JBSWY3DPEHPK3PXP', 'admin@donasitrust.test', 'DonasiTrust');

        $this->assertStringStartsWith('otpauth://totp/DonasiTrust:admin%40donasitrust.test?', $uri);
        $this->assertStringContainsString('secret=JBSWY3DPEHPK3PXP', $uri);
        $this->assertStringContainsString('issuer=DonasiTrust', $uri);
        $this->assertStringContainsString('algorithm=SHA1', $uri);
        $this->assertStringContainsString('digits=6', $uri);
        $this->assertStringContainsString('period=30', $uri);
    }
}
