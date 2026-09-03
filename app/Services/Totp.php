<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * TOTP (RFC 6238) — kode enam digit yang berganti tiap 30 detik, persis seperti
 * yang ditampilkan Google Authenticator, Aegis, atau 1Password.
 *
 * Ditulis sendiri, tanpa pustaka luar, karena algoritmenya memang pendek:
 * HMAC-SHA1 atas nomor selang waktu, lalu satu langkah "dynamic truncation"
 * untuk memeras 160 bit hash jadi enam digit. Yang di bawah ini adalah
 * RFC 4226 §5.3 (HOTP) ditambah RFC 6238 §4 (memakai waktu sebagai counter).
 *
 * KENAPA SHA-1, bukan SHA-256:
 * Kelemahan SHA-1 yang terkenal adalah tabrakan (collision). HMAC tidak
 * bergantung pada ketahanan tabrakan — ia bergantung pada sifat PRF, dan
 * HMAC-SHA1 masih aman untuk itu. Sementara hampir semua aplikasi authenticator
 * di ponsel hanya mendukung SHA-1. Menggantinya membuat kode gagal dibaca
 * di banyak aplikasi tanpa menambah keamanan yang berarti di sini.
 */
class Totp
{
    /** Panjang satu selang waktu, dalam detik. */
    public const PERIOD = 30;

    /** Panjang kode yang ditampilkan. */
    public const DIGITS = 6;

    /** Alfabet Base32 RFC 4648 — yang dipakai semua aplikasi authenticator. */
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Kunci rahasia baru, 20 byte acak (160 bit) sesuai anjuran RFC 4226 §4.
     * Dikembalikan dalam Base32 karena itu format yang dibaca aplikasi
     * authenticator, baik lewat QR maupun ketikan manual.
     */
    public function generateSecret(int $bytes = 20): string
    {
        return $this->base32Encode(random_bytes($bytes));
    }

    /**
     * Nomor selang waktu saat ini: jumlah periode 30 detik sejak epoch Unix.
     * Inilah "counter" yang dipakai bersama oleh server dan ponsel — keduanya
     * tidak perlu berkomunikasi, cukup jam yang kira-kira sama.
     *
     * Waktunya dibaca lewat Carbon, bukan time(), supaya pengujian bisa
     * membekukan jam dan menguji perilaku "sekali pakai" secara pasti.
     */
    public function timestepAt(?int $timestamp = null): int
    {
        return intdiv($timestamp ?? Carbon::now()->getTimestamp(), self::PERIOD);
    }

    /** Kode yang berlaku pada satu selang waktu tertentu. */
    public function codeAt(string $secret, int $timestep): string
    {
        $key = $this->base32Decode($secret);

        if ($key === '') {
            throw new InvalidArgumentException('Kunci TOTP kosong atau bukan Base32 yang sah.');
        }

        // Counter 8 byte, big-endian (RFC 4226 §5.1).
        $hash = hash_hmac('sha1', pack('J', $timestep), $key, true);

        // Dynamic truncation (RFC 4226 §5.3): 4 bit terakhir hash menunjuk
        // offset tempat 4 byte hasil diambil. Bit tertinggi dibuang supaya
        // hasilnya tidak pernah terbaca sebagai bilangan negatif.
        $offset = ord($hash[19]) & 0x0F;

        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad(
            (string) ($binary % (10 ** self::DIGITS)),
            self::DIGITS,
            '0',
            STR_PAD_LEFT,
        );
    }

    /**
     * Periksa satu kode. Mengembalikan NOMOR SELANG WAKTU yang cocok, atau null
     * bila tidak ada yang cocok.
     *
     * Nomor itu dikembalikan — bukan sekadar true — karena pemanggil perlu
     * menyimpannya untuk mencegah kode yang sama dipakai dua kali (lihat
     * TotpGuard). Tanpa itu, kode yang tercuri masih bisa dipakai ulang selama
     * jendela 30 detiknya belum lewat.
     *
     * $window = 1 berarti kode dari satu periode sebelum dan sesudah ikut
     * diterima, memberi toleransi jam ponsel yang meleset sampai ±30 detik.
     */
    public function verify(string $secret, string $code, int $window = 1, ?int $timestamp = null): ?int
    {
        $code = preg_replace('/\D/', '', $code);

        if (strlen((string) $code) !== self::DIGITS) {
            return null;
        }

        $current = $this->timestepAt($timestamp);
        $match = null;

        for ($drift = -$window; $drift <= $window; $drift++) {
            $step = $current + $drift;

            if ($step < 0) {
                continue;
            }

            // hash_equals: waktu bandingnya tidak bergantung isi kode.
            // Perulangan sengaja TIDAK dihentikan saat sudah cocok, supaya
            // jumlah perbandingan selalu sama berapa pun kode yang dikirim —
            // percuma memakai hash_equals kalau lama proses keseluruhannya
            // masih membocorkan seberapa dekat tebakan penyerang.
            if (hash_equals($this->codeAt($secret, $step), $code)) {
                $match = $step;
            }
        }

        return $match;
    }

    /**
     * URI otpauth:// — isi kode QR yang dipindai aplikasi authenticator.
     * Formatnya standar Key Uri Format milik Google Authenticator.
     */
    public function provisioningUri(string $secret, string $account, ?string $issuer = null): string
    {
        $issuer ??= (string) config('app.name', 'DonasiTrust');

        // Penerbit ditulis dua kali (di label dan di query) memang disengaja:
        // aplikasi lama membaca label, aplikasi baru membaca parameter issuer.
        $label = rawurlencode($issuer).':'.rawurlencode($account);

        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ], '', '&', PHP_QUERY_RFC3986);

        return 'otpauth://totp/'.$label.'?'.$query;
    }

    /** Kunci dipecah per empat huruf supaya tidak salah ketik saat entri manual. */
    public function formatForManualEntry(string $secret): string
    {
        return trim(chunk_split($secret, 4, ' '));
    }

    public function base32Encode(string $binary): string
    {
        if ($binary === '') {
            return '';
        }

        $bits = '';

        foreach (str_split($binary) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        // Sisa bit di ujung dipadatkan dengan nol sampai genap lima bit.
        $chunks = str_split($bits, 5);
        $last = array_key_last($chunks);
        $chunks[$last] = str_pad($chunks[$last], 5, '0', STR_PAD_RIGHT);

        $encoded = '';

        foreach ($chunks as $chunk) {
            $encoded .= self::ALPHABET[bindec($chunk)];
        }

        return $encoded;
    }

    public function base32Decode(string $base32): string
    {
        // Spasi pemisah, tanda '=' padding, dan huruf kecil semuanya dimaafkan:
        // orang mengetik ulang kunci ini dengan tangan.
        $base32 = strtoupper((string) preg_replace('/[^A-Za-z2-7]/', '', $base32));

        if ($base32 === '') {
            return '';
        }

        $bits = '';

        foreach (str_split($base32) as $char) {
            $bits .= str_pad(decbin(strpos(self::ALPHABET, $char)), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';

        foreach (str_split($bits, 8) as $chunk) {
            // Potongan terakhir yang kurang dari 8 bit adalah sisa padding,
            // bukan byte — dibuang, bukan diisi nol.
            if (strlen($chunk) < 8) {
                break;
            }

            $bytes .= chr(bindec($chunk));
        }

        return $bytes;
    }
}
