<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Disbursement;
use App\Models\EmailOtp;
use App\Models\Milestone;
use App\Services\AuditLogger;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DisbursementController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $disbursements = Disbursement::with(['campaign.user', 'milestone', 'requester'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(function ($q) use ($search) {
                    $q->where('reference', 'like', "%{$search}%")
                      ->orWhereHas('campaign', fn ($q) => $q->where('title', 'like', "%{$search}%"))
                      ->orWhereHas('requester', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('date'), function ($q) use ($request) {
                $q->whereDate('created_at', $request->string('date'));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.disbursements.index', compact('disbursements'));
    }

    public function show(Disbursement $disbursement)
    {
        $disbursement->load(['campaign', 'milestone', 'requester']);
        return view('admin.disbursements.show', compact('disbursement'));
    }

    public function approve(Disbursement $disbursement, AuditLogger $audit)
    {
        if ($disbursement->status !== Disbursement::STATUS_PENDING) {
            return back()->with('error', 'Status tidak valid.');
        }

        DB::transaction(function () use ($disbursement, $audit) {
            $disbursement->update([
                'status' => Disbursement::STATUS_APPROVED,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_note' => 'Disetujui untuk dicairkan.',
            ]);

            $milestone = $disbursement->milestone;
            $milestone->update(['status' => Milestone::STATUS_APPROVED]);

            $audit->record('disbursement.approved', $disbursement, [
                'reference' => $disbursement->reference,
            ]);
        });

        return back()->with('status', 'Pencairan disetujui. Silakan transfer secara manual, lalu tekan "Dana Dicairkan".');
    }

    public function reject(Request $request, Disbursement $disbursement, AuditLogger $audit)
    {
        if ($disbursement->status !== Disbursement::STATUS_PENDING) {
            return back()->with('error', 'Status tidak valid.');
        }
        
        $data = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($disbursement, $data, $audit) {
            $disbursement->update([
                'status' => Disbursement::STATUS_REJECTED,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_note' => $data['reason'],
            ]);

            $milestone = $disbursement->milestone;
            $milestone->update(['status' => Milestone::STATUS_AVAILABLE]);

            $audit->record('disbursement.rejected', $disbursement, [
                'reference' => $disbursement->reference,
                'reason' => $data['reason'],
            ]);
        });

        return back()->with('status', 'Pencairan ditolak.');
    }

    public function release(Request $request, Disbursement $disbursement, AuditLogger $audit, OtpService $otp)
    {
        if ($disbursement->status !== Disbursement::STATUS_APPROVED) {
            return back()->with('error', 'Hanya pencairan yang disetujui yang dapat dirilis.');
        }

        $request->validate([
            'proof' => 'required|image|max:' . config('donasi.max_upload_kb'),
            'otp_code' => 'required|string',
        ], [
            'otp_code.required' => 'Kode verifikasi email wajib diisi.',
        ]);

        $otp->assertValid($request->user(), EmailOtp::PURPOSE_DISBURSEMENT_RELEASE, $request->input('otp_code'));
        $metode = 'otp_email';

        $proofPath = $request->file('proof')->store('pencairan');

        DB::transaction(function () use ($disbursement, $proofPath, $audit, $metode) {
            $disbursement->update([
                'status' => Disbursement::STATUS_RELEASED,
                'supporting_document_path' => $proofPath,
                'released_at' => now(),
            ]);

            $milestone = $disbursement->milestone;
            $milestone->update(['status' => Milestone::STATUS_DISBURSED]);
            
            $campaign = $disbursement->campaign;
            $campaign->recalculateTotals();

            $audit->record('disbursement.released', $disbursement, [
                'reference' => $disbursement->reference,
                'amount' => $disbursement->amount,
                'rekening_tujuan' => $disbursement->maskedPayee(),
                'dua_langkah' => $metode,
            ]);
        });

        // Broadcast email ke seluruh donatur kampanye via Queue Worker
        \App\Jobs\SendDisbursementBroadcastJob::dispatch($disbursement);

        return back()->with('status', 'Dana berhasil ditandai telah dicairkan dan notifikasi email dikirim ke donatur.');
    }
}
