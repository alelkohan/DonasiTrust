<?php

namespace App\Jobs;

use App\Mail\ExpenseReportVerifiedMail;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\ExpenseReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Kirim email broadcast ke seluruh donatur kampanye saat LPJ diverifikasi.
 */
class SendExpenseReportBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ExpenseReport $expenseReport
    ) {}

    public function handle(): void
    {
        $campaign = $this->expenseReport->campaign;

        if (! $campaign) {
            return;
        }

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
                            ->queue(new ExpenseReportVerifiedMail($campaign, $this->expenseReport));
                    }
                }
            });
    }
}
