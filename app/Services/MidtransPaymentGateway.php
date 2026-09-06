<?php

namespace App\Services;

use App\Models\Donation;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\CoreApi;
use Midtrans\Snap;

/**
 * Integrasi Midtrans Snap & Core API QRIS (Sandbox & Produksi).
 */
class MidtransPaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'midtrans';
    }

    private function setupConfig(): void
    {
        $serverKey = (string) config('donasi.midtrans.server_key');

        Config::$serverKey = $serverKey;
        Config::$isProduction = (bool) config('donasi.midtrans.is_production', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function createCharge(Donation $donation): array
    {
        // Jika donasi sudah memiliki snap_token yang valid tanpa error, gunakan yang tersimpan
        if (! empty($donation->gateway_payload['snap_token']) && empty($donation->gateway_payload['error'])) {
            return $donation->gateway_payload;
        }

        $this->setupConfig();

        $orderId = $donation->reference;

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $donation->amount,
            ],
            'customer_details' => [
                'first_name' => $donation->donor_name ?: 'Donatur',
                'email' => $donation->donor_email ?: 'donatur@donasitrust.test',
            ],
            'item_details' => [
                [
                    'id' => 'CAMP-'.$donation->campaign_id,
                    'price' => (int) $donation->amount,
                    'quantity' => 1,
                    'name' => Str::limit($donation->campaign->title ?? 'Donasi Kampanye', 50),
                ],
            ],
        ];

        $snapToken = null;
        $redirectUrl = null;
        $qrString = null;
        $error = null;

        try {
            $tx = Snap::createTransaction($params);
            $snapToken = $tx->token ?? null;
            $redirectUrl = $tx->redirect_url ?? null;
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();

            // Retry dengan unique timestamp suffix jika order_id sudah pernah dicoba sebelumnya
            try {
                $orderId = $donation->reference.'-v'.time();
                $params['transaction_details']['order_id'] = $orderId;
                $tx = Snap::createTransaction($params);
                $snapToken = $tx->token ?? null;
                $redirectUrl = $tx->redirect_url ?? null;
                $errorMsg = null;
            } catch (\Exception $ex) {
                $errorMsg = $ex->getMessage();
            }

            $error = $errorMsg;
        }

        // Dapatkan string QRIS asli (000201...) khusus untuk Midtrans QRIS Simulator di mode Sandbox
        if (! config('donasi.midtrans.is_production')) {
            try {
                $coreParams = [
                    'payment_type' => 'qris',
                    'transaction_details' => [
                        'order_id' => $orderId.'-qris',
                        'gross_amount' => (int) $donation->amount,
                    ],
                    'qris' => [
                        'acquirer' => 'gopay',
                    ],
                ];
                $coreRes = CoreApi::charge($coreParams);
                if (is_object($coreRes)) {
                    $qrString = $coreRes->qr_string ?? null;
                }
            } catch (\Exception $ex) {
                // Abaikan error Core API fallback
            }
        }

        return [
            'gateway_reference' => $donation->reference,
            'order_id' => $orderId,
            'snap_token' => $snapToken,
            'redirect_url' => $redirectUrl,
            'qr_string' => $qrString,
            'qr_payload' => $qrString ?: $donation->reference,
            'error' => $error,
            'expires_at' => now()->addHours(24)->toIso8601String(),
        ];
    }

    public function verifyWebhook(array $payload): bool
    {
        $serverKey = (string) config('donasi.midtrans.server_key');

        if ($serverKey === '') {
            return false;
        }

        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');

        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        return hash_equals($expected, (string) ($payload['signature_key'] ?? ''));
    }
}
