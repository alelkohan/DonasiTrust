<?php

namespace App\Services;

use App\Models\Donation;

interface PaymentGateway
{
    /**
     * Buat sesi pembayaran dan kembalikan data yang dibutuhkan halaman checkout.
     *
     * @return array{redirect_url: ?string, qr_payload: ?string, gateway_reference: string, expires_at: string}
     */
    public function createCharge(Donation $donation): array;

    /** Nama gateway untuk disimpan di kolom donations.gateway. */
    public function name(): string;

    /** Validasi keaslian notifikasi webhook. */
    public function verifyWebhook(array $payload): bool;
}
