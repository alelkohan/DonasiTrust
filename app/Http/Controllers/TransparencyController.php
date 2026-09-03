<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Disbursement;
use App\Models\Donation;
use App\Models\ExpenseReport;
use App\Services\AuditLogger;

class TransparencyController extends Controller
{
    /** Ringkasan seluruh platform + status integritas jejak audit. */
    public function __invoke(AuditLogger $audit)
    {
        $totals = [
            'terkumpul' => (int) Donation::where('status', Donation::STATUS_PAID)->sum('amount'),
            'tercairkan' => (int) Disbursement::where('status', Disbursement::STATUS_RELEASED)->sum('amount'),
            'dilaporkan' => (int) ExpenseReport::where('status', ExpenseReport::STATUS_VERIFIED)->sum('amount'),
            'transaksi' => Donation::where('status', Donation::STATUS_PAID)->count(),
        ];

        $totals['saldo'] = max(0, $totals['terkumpul'] - $totals['tercairkan']);

        $campaigns = Campaign::published()
            ->withCount(['donations as donatur_count' => fn ($q) => $q->where('status', Donation::STATUS_PAID)])
            ->orderByDesc('collected_amount')
            ->get();

        // Grafik 30 hari terakhir, diagregasi di PHP supaya sintaks tanggal
        // tetap sama di MySQL maupun SQLite.
        $daily = Donation::where('status', Donation::STATUS_PAID)
            ->where('paid_at', '>=', now()->subDays(29)->startOfDay())
            ->get(['amount', 'paid_at'])
            ->groupBy(fn ($d) => $d->paid_at->toDateString())
            ->map(fn ($group) => (int) $group->sum('amount'));

        $chart = collect(range(29, 0))->map(function ($offset) use ($daily) {
            $date = now()->subDays($offset)->toDateString();

            $amount = (int) ($daily[$date] ?? 0);

            return [
                'date' => $date,
                'amount' => $amount,
                'label' => now()->subDays($offset)->translatedFormat('d M Y'),
                'formatted' => rupiah($amount),
            ];
        })->values();

        return view('public.transparency-index', [
            'totals' => $totals,
            'campaigns' => $campaigns,
            'chart' => $chart,
            'chainStatus' => $audit->verifyChain(),
        ]);
    }
}
