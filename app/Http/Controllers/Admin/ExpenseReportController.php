<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpenseReport;
use App\Models\Milestone;
use App\Services\AuditLogger;
use App\Services\DonationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseReportController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $expenses = ExpenseReport::with(['campaign.user', 'milestone', 'author'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhereHas('campaign', fn ($q) => $q->where('title', 'like', "%{$search}%"))
                      ->orWhereHas('author', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('date'), function ($q) use ($request) {
                $q->whereDate('created_at', $request->string('date'));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.expenses.index', compact('expenses'));
    }

    public function show(ExpenseReport $expense)
    {
        $expense->load(['campaign', 'milestone', 'author']);
        return view('admin.expenses.show', compact('expense'));
    }

    public function verify(ExpenseReport $expense, AuditLogger $audit, DonationService $donationService)
    {
        if ($expense->status !== ExpenseReport::STATUS_PENDING) {
            return back()->with('error', 'Status tidak valid.');
        }

        DB::transaction(function () use ($expense, $audit, $donationService) {
            $expense->update([
                'status' => ExpenseReport::STATUS_VERIFIED,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_note' => 'Nota diverifikasi sah.',
            ]);

            $audit->record('expense.verified', $expense, [
                'judul' => $expense->title,
            ]);

            // Cek apakah milestone bisa ditandai selesai (dilaporkan)
            // Misalnya jika total LPJ >= pencairan, tandai reported.
            $milestone = $expense->milestone;
            if ($milestone) {
                $totalReported = $milestone->expenseReports()->where('status', ExpenseReport::STATUS_VERIFIED)->sum('amount');
                if ($totalReported >= $milestone->amount) {
                    $milestone->update(['status' => Milestone::STATUS_REPORTED]);
                    // Buka milestone berikutnya
                    $donationService->unlockMilestones($expense->campaign);
                }
            }
        });

        return back()->with('status', 'LPJ berhasil diverifikasi.');
    }

    public function reject(Request $request, ExpenseReport $expense, AuditLogger $audit)
    {
        if ($expense->status !== ExpenseReport::STATUS_PENDING) {
            return back()->with('error', 'Status tidak valid.');
        }

        $data = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($expense, $data, $audit) {
            $expense->update([
                'status' => ExpenseReport::STATUS_REJECTED,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_note' => $data['reason'],
            ]);

            $audit->record('expense.rejected', $expense, [
                'judul' => $expense->title,
                'alasan' => $data['reason'],
            ]);
        });

        return back()->with('status', 'LPJ ditolak.');
    }
}
