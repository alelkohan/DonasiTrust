<?php

namespace App\Jobs;

use App\Mail\DisbursementReleasedMail;
use App\Models\Campaign;
use App\Models\Disbursement;
use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Kirim email broadcast ke seluruh donatur kampanye saat pencairan dana terjadi.
 * Menggunakan chunking untuk efisiensi memori & performa background worker.
 */
class SendDisbursementBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Disbursement $disbursement
    ) {}

    public function handle(): void
    {
        $campaign = $this->disbursement->campaign;

        if (! $campaign) {
            return;
        }

        // Ambil email unik seluruh donatur yang donasinya paid
        Donation::query()
            ->where('campaign_id', $campaign->id)
            ->where('status', Donation::STATUS_PAID)
            ->whereNotNull('donor_email')
            ->select('donor_email')
            ->distinct()
            ->chunk(100, function ($donations) use ($campaign) {
                foreach ($donations as $donation) {
                    if (filter_var($donation->donor_email, FILTER_VALIDATE_EMAIL)) {
                        Mail::to($donation->donor_email)
                            ->queue(new DisbursementReleasedMail($campaign, $this->disbursement));
                    }
                }
            });
    }
}
