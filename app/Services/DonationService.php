<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Milestone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DonationService
{
    public function __construct(
        private PaymentGateway $gateway,
        private ReceiptVerifier $receipts,
        private AuditLogger $audit,
    ) {}

    /** Buat donasi berstatus pending beserta sesi pembayarannya. */
    public function create(Campaign $campaign, array $data): Donation
    {
        $donation = DB::transaction(function () use ($campaign, $data) {
            $donation = new Donation([
                'campaign_id' => $campaign->id,
                'user_id' => auth()->id(),
                'donor_name' => $data['donor_name'] ?? auth()->user()?->name,
                'donor_email' => $data['donor_email'] ?? auth()->user()?->email,
                'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
                'message' => $data['message'] ?? null,
                'amount' => (int) $data['amount'],
                'status' => Donation::STATUS_PENDING,
                'payment_channel' => $data['payment_channel'] ?? 'qris',
                'gateway' => $this->gateway->name(),
                // Nilai sementara yang pasti unik; diganti di bawah setelah id terbit.
                'reference' => 'tmp-'.Str::random(24),
                'verification_code' => str_repeat('0', 64),
            ]);

            $donation->save();

            // Referensi dikunci ke kode acak unik ber-entropi tinggi yang tidak pernah duplikat
            do {
                $ref = $donation->buildReference();
            } while (Donation::where('reference', $ref)->exists());

            $donation->reference = $ref;
            $donation->verification_code = $this->receipts->for($donation);
            $donation->save();

            return $donation;
        });

        $charge = $this->gateway->createCharge($donation);
        $donation->gateway_reference = $charge['gateway_reference'];
        $donation->gateway_payload = $charge;
        $donation->save();

        $this->audit->record('donation.created', $donation, [
            'campaign' => $campaign->title,
            'amount' => $donation->amount,
        ]);

        return $donation;
    }

    /**
     * Tandai donasi lunas. Idempotent: dipanggil dua kali oleh webhook
     * tidak akan menghitung dana dua kali.
     */
    public function markPaid(Donation $donation, array $gatewayPayload = []): bool
    {
        return DB::transaction(function () use ($donation, $gatewayPayload) {
            $fresh = Donation::whereKey($donation->id)->lockForUpdate()->first();

            if (! $fresh || $fresh->status === Donation::STATUS_PAID) {
                return false;
            }

            $fresh->status = Donation::STATUS_PAID;
            $fresh->paid_at = now();

            if ($gatewayPayload) {
                $fresh->gateway_payload = array_merge((array) $fresh->gateway_payload, $gatewayPayload);
            }

            $fresh->save();

            $campaign = Campaign::whereKey($fresh->campaign_id)->lockForUpdate()->first();
            $campaign->recalculateTotals();

            $this->unlockMilestones($campaign);

            $this->audit->record('donation.paid', $fresh, [
                'reference' => $fresh->reference,
                'amount' => $fresh->amount,
                'campaign' => $campaign->title,
            ]);

            $recipientEmail = $fresh->donor_email ?: $fresh->user?->email;

            if (filled($recipientEmail)) {
                try {
                    \Illuminate\Support\Facades\Mail::to($recipientEmail)
                        ->send(new \App\Mail\DonationReceiptMail($fresh));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Gagal mengirim email kuitansi donasi: '.$e->getMessage());
                }
            }

            return true;
        });
    }

    /**
     * Buka milestone berikutnya begitu dana terkumpul mencukupi.
     * Milestone dibuka berurutan: tahap 2 hanya terbuka setelah tahap 1 dilaporkan.
     */
    public function unlockMilestones(Campaign $campaign): void
    {
        $available = $campaign->collected_amount;
        $previousReported = true;

        foreach ($campaign->milestones()->get() as $milestone) {
            if ($milestone->status !== Milestone::STATUS_LOCKED) {
                $previousReported = $milestone->status === Milestone::STATUS_REPORTED;
                $available -= $milestone->amount;

                continue;
            }

            if ($previousReported && $available >= $milestone->amount) {
                $milestone->update(['status' => Milestone::STATUS_AVAILABLE]);
                $available -= $milestone->amount;
                $previousReported = false;
            }

            break;
        }
    }

    /** Proses pending donasi yang disimpan di session saat pengguna belum login. */
    public function processPendingDonation(\App\Models\User $user): ?Donation
    {
        $pending = session()->pull('pending_donation');

        if (! $pending || empty($pending['campaign_id']) || empty($pending['amount'])) {
            return null;
        }

        $campaign = Campaign::find($pending['campaign_id']);
        if (! $campaign) {
            return null;
        }

        return $this->create($campaign, [
            'amount' => $pending['amount'],
            'donor_name' => ($pending['is_anonymous'] ?? false) ? 'Anonim' : ($pending['donor_name'] ?: $user->name),
            'donor_email' => $user->email,
            'message' => $pending['message'] ?? null,
            'is_anonymous' => $pending['is_anonymous'] ?? false,
        ]);
    }
}
