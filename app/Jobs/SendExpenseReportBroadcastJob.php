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
            ->pluck('donor_email')
            ->unique()
            ->each(function ($email) use ($campaign) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    Mail::to($email)
                        ->queue(new ExpenseReportVerifiedMail($campaign, $this->expenseReport));
                }
            });
    }
}
