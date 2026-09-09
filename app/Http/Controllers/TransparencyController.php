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
            ->with([
                'milestones',
                'disbursements' => fn ($q) => $q->with('milestone:id,title,sequence')->whereIn('status', ['approved', 'released'])->orderBy('created_at'),
                'expenseReports' => fn ($q) => $q->with('item:id,name')->orderByDesc('spent_on'),
            ])
            ->orderByDesc('collected_amount')
            ->paginate(5);

        $campaignsData = collect($campaigns->items())->mapWithKeys(function ($c) {
            return [
                $c->id => [
                    'id' => $c->id,
                    'title' => $c->title,
                    'category_label' => $c->categoryLabel(),
                    'collected_amount' => $c->collected_amount,
                    'collected_formatted' => rupiah($c->collected_amount),
                    'disbursed_amount' => $c->disbursed_amount,
                    'disbursed_formatted' => rupiah($c->disbursed_amount),
                    'remaining_balance' => $c->remainingBalance(),
                    'remaining_formatted' => rupiah($c->remainingBalance()),
                    'donatur_count' => $c->donatur_count,
                    'show_url' => route('kampanye.show', $c),
                    'transparency_url' => route('kampanye.transparansi', $c),
                    'milestones' => $c->milestones->map(fn ($m) => [
                        'sequence' => $m->sequence,
                        'title' => $m->title,
                        'amount_formatted' => rupiah($m->amount),
                        'status_label' => $m->statusLabel(),
                        'is_done' => in_array($m->status, ['disbursed', 'reported'], true),
                        'is_active' => in_array($m->status, ['available', 'requested', 'approved'], true),
                    ])->values()->all(),
                    'disbursements' => $c->disbursements->map(fn ($d) => [
                        'sequence' => $d->milestone?->sequence ?? '—',
                        'reference' => $d->reference,
                        'purpose' => $d->purpose,
                        'masked_payee' => $d->maskedPayee() ?? '—',
                        'amount_formatted' => rupiah($d->amount),
                        'status_label' => $d->statusLabel(),
                    ])->values()->all(),
                    'expense_reports' => $c->expenseReports->map(fn ($e) => [
                        'title' => $e->title,
                        'spent_on_formatted' => $e->spent_on ? $e->spent_on->translatedFormat('d F Y') : '—',
                        'amount_formatted' => rupiah($e->amount),
                        'receipt_url' => $e->receipt_path ? route('berkas.lpj', $e) : null,
                    ])->values()->all(),
                ],
            ];
        })->all();

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
            'campaignsData' => $campaignsData,
            'chart' => $chart,
            'chainStatus' => $audit->verifyChain(),
        ]);
    }
}
