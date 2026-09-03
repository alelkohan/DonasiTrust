<?php

namespace App\Services;

use App\Models\Donation;

/**
 * Gateway simulasi untuk pengembangan & demo.
 *
 * Sengaja dibuat menyerupai alur QRIS asli: buat charge -> tampilkan QR ->
 * gateway mengirim webhook -> status donasi berubah. Saat siap pindah ke
 * Midtrans/Xendit, cukup ganti implementasi PaymentGateway di config.
 */
class MockPaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'mock';
    }

    public function createCharge(Donation $donation): array
    {
        return [
            'redirect_url' => null,
            'qr_payload' => 'DONASITRUST-SIMULASI|'.$donation->reference.'|'.$donation->amount,
            'gateway_reference' => 'MOCK-'.strtoupper(bin2hex(random_bytes(6))),
            'expires_at' => now()->addHours(2)->toIso8601String(),
        ];
    }

    public function verifyWebhook(array $payload): bool
    {
        // Gateway simulasi memakai token bersama dari .env supaya endpoint
        // webhook tetap tidak bisa dipanggil sembarang orang saat demo.
        $expected = (string) config('donasi.mock_webhook_token');

        return $expected !== '' && hash_equals($expected, (string) ($payload['token'] ?? ''));
    }
}
