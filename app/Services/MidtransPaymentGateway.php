<?php

namespace App\Services;

use App\Models\Donation;

/**
 * Kerangka integrasi Midtrans (Snap / QRIS).
 *
 * BELUM DIIMPLEMENTASIKAN. Sengaja dibiarkan melempar exception daripada
 * mengembalikan data palsu, supaya tidak ada yang mengira pembayaran asli
 * sudah jalan. Untuk mengaktifkan: pasang `midtrans/midtrans-php`, isi
 * MIDTRANS_SERVER_KEY, lalu lengkapi metode di bawah.
 */
class MidtransPaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'midtrans';
    }

    public function createCharge(Donation $donation): array
    {
        throw new \RuntimeException(
            'Integrasi Midtrans belum diimplementasikan. Set PAYMENT_GATEWAY=mock di .env.'
        );
    }

    public function verifyWebhook(array $payload): bool
    {
        // Midtrans mengirim signature_key = sha512(order_id + status_code + gross_amount + server_key)
        $serverKey = (string) config('donasi.midtrans.server_key');

        if ($serverKey === '') {
            return false;
        }

        $expected = hash('sha512',
            ($payload['order_id'] ?? '').
            ($payload['status_code'] ?? '').
            ($payload['gross_amount'] ?? '').
            $serverKey
        );

        return hash_equals($expected, (string) ($payload['signature_key'] ?? ''));
    }
}
