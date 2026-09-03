<?php

namespace App\Services;

use App\Models\Donation;

/**
 * Kode verifikasi kuitansi.
 *
 * PENTING (dan sering salah disebut): ini HMAC-SHA256, yaitu Message
 * Authentication Code — bukan tanda tangan digital. Ia membuktikan kuitansi
 * diterbitkan oleh server yang memegang secret, bukan identitas seseorang.
 * Tanda tangan digital yang sesungguhnya butuh pasangan kunci (mis. ECDSA)
 * agar pihak ketiga bisa memverifikasi tanpa memegang secret.
 */
class ReceiptVerifier
{
    public function secret(): string
    {
        $secret = (string) config('donasi.receipt_secret');

        if ($secret === '') {
            // Jangan diam-diam pakai string kosong: itu membuat kode bisa dipalsukan.
            throw new \RuntimeException('DONASI_RECEIPT_SECRET belum diisi di file .env.');
        }

        return $secret;
    }

    public function for(Donation $donation): string
    {
        $payload = implode('|', [
            $donation->reference,
            (string) $donation->amount,
            (string) $donation->campaign_id,
        ]);

        return hash_hmac('sha256', $payload, $this->secret());
    }

    public function matches(Donation $donation, string $code): bool
    {
        return hash_equals($this->for($donation), strtolower(trim($code)));
    }

    /** Potongan pendek untuk ditampilkan di kuitansi. */
    public function shortCode(Donation $donation): string
    {
        return strtoupper(substr($this->for($donation), 0, 16));
    }
}
