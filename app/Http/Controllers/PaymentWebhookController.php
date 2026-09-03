<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Services\DonationService;
use App\Services\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $driver,
        PaymentGateway $gateway,
        DonationService $donations,
    ): JsonResponse {
        // Endpoint hanya melayani gateway yang sedang aktif; permintaan ke
        // driver lain ditolak tanpa menyentuh basis data.
        if ($driver !== $gateway->name()) {
            return response()->json(['message' => 'unknown gateway'], 404);
        }

        $payload = $request->all();

        if (! $gateway->verifyWebhook($payload)) {
            // Jangan bocorkan alasan penolakan ke pemanggil.
            return response()->json(['message' => 'invalid signature'], 401);
        }

        $reference = (string) ($payload['order_id'] ?? $payload['reference'] ?? '');
        $donation = Donation::where('reference', $reference)->first();

        if (! $donation) {
            return response()->json(['message' => 'not found'], 404);
        }

        $status = (string) ($payload['transaction_status'] ?? $payload['status'] ?? '');

        if (in_array($status, ['settlement', 'capture', 'paid', 'PAID'], true)) {
            $donations->markPaid($donation, ['webhook' => $payload]);
        } elseif (in_array($status, ['expire', 'expired'], true)) {
            $donation->update(['status' => Donation::STATUS_EXPIRED]);
        } elseif (in_array($status, ['deny', 'cancel', 'failure', 'failed'], true)) {
            $donation->update(['status' => Donation::STATUS_FAILED]);
        }

        return response()->json(['message' => 'ok']);
    }
}
