<?php

namespace App\Http\Controllers\Pengaju;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Disbursement;
use App\Models\EmailOtp;
use App\Models\ExpenseReport;
use App\Models\Milestone;
use App\Services\AuditLogger;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MilestoneController extends Controller
{
    public function index(Campaign $campaign)
    {
        $this->authorize('manageFunds', $campaign);
        $campaign->load('milestones.disbursements', 'milestones.expenseReports', 'items');

        // Milestone::guidance() membaca kampanyenya. Tanpa ini setiap tahap
        // memicu query sendiri hanya untuk mengambil kampanye yang sudah ada
        // di tangan.
        $campaign->milestones->each->setRelation('campaign', $campaign);

        return view('pengaju.campaigns.milestones', compact('campaign'));
    }

    /**
     * Pengajuan pencairan TIDAK bergerbang dua langkah, dan itu disengaja.
     *
     * Yang menahan pencurian di sini bukan kode authenticator, melainkan
     * rekening tujuan yang terkunci ke profil terverifikasi: akun pengaju yang
     * dibajak sekalipun hanya bisa mengalirkan dana ke rekening pemilik aslinya
     * — penyerang tidak mendapat apa pun. Menambah kode di sini berarti menambah
     * friction besar bagi pengguna non-teknis (pengurus masjid, keluarga pasien)
     * demi keamanan yang nyaris nol.
     *
     * Gerbangnya dipindahkan ke tempat yang benar-benar berisiko: MENGGANTI
     * rekening tujuan (ProfileController::submitVerification) dan MELEPAS dana
     * (Admin\DisbursementController::release).
     */
    public function requestDisbursement(
        Request $request,
        Campaign $campaign,
        Milestone $milestone,
        AuditLogger $audit,
        OtpService $otp,
    ) {
        $this->authorize('manageFunds', $campaign);

        if (! $request->user()->isVerified()) {
            return back()->with('error', 'Akun Anda belum terverifikasi atau sedang menunggu peninjauan ulang.');
        }

        if ($milestone->campaign_id !== $campaign->id) {
            abort(404);
        }

        if ($milestone->status !== Milestone::STATUS_AVAILABLE) {
            return back()->with('error', 'Tahapan ini belum bisa dicairkan.');
        }

        $pemilik = $campaign->user;

        // Rekening tujuan tidak boleh ditentukan di sini — hanya boleh diambil
        // dari profil yang sudah diperiksa admin bersama KTP-nya. Kalau
        // verifikasinya sedang tertunda (mis. pengaju baru saja mengganti
        // nomor rekening), pencairan ditahan sampai admin memeriksa ulang.
        if (! $pemilik->hasPayoutAccount()) {
            return back()->with('error', 'Rekening tujuan belum terdaftar. Lengkapi dulu lewat '
                .'halaman verifikasi identitas.');
        }

        if (! $pemilik->isVerified()) {
            return back()->with('error', 'Rekening tujuan sedang menunggu verifikasi ulang admin. '
                .'Pencairan bisa diajukan setelah verifikasi selesai.');
        }

        $data = $request->validate([
            'purpose' => 'required|string|max:1000',
            'otp_code' => 'required|string',
        ], [
            'otp_code.required' => 'Kode verifikasi email wajib diisi untuk mengajukan pencairan.',
        ]);

        $otp->assertValid($request->user(), EmailOtp::PURPOSE_DISBURSEMENT_REQUEST, $data['otp_code']);

        DB::transaction(function () use ($campaign, $milestone, $data, $pemilik, $audit) {
            $disbursement = Disbursement::create([
                'reference' => 'sementara-' . Str::random(12),
                'campaign_id' => $campaign->id,
                'milestone_id' => $milestone->id,
                'requested_by' => auth()->id(),
                'amount' => $milestone->amount,
                'purpose' => $data['purpose'],
                // Disalin, bukan direlasikan: catatan "ke mana dana dikirim"
                // harus tetap utuh walau profil pengaju berubah setelahnya.
                'payee_bank_name' => $pemilik->bank_name,
                'payee_account_number' => $pemilik->bank_account_number,
                'payee_account_holder' => $pemilik->bank_account_holder,
                'status' => Disbursement::STATUS_PENDING,
            ]);

            $disbursement->reference = $disbursement->buildReference();
            $disbursement->save();

            $milestone->update(['status' => Milestone::STATUS_REQUESTED]);

            $audit->record('disbursement.requested', $disbursement, [
                'reference' => $disbursement->reference,
                'amount' => $disbursement->amount,
                'rekening_tujuan' => $disbursement->maskedPayee(),
            ]);
        });

        return back()->with('status', 'Pencairan berhasil diajukan. Menunggu review admin.');
    }

    public function submitExpenseReport(Request $request, Campaign $campaign, Milestone $milestone, AuditLogger $audit)
    {
        $this->authorize('manageFunds', $campaign);

        if ($milestone->campaign_id !== $campaign->id) {
            abort(404);
        }

        if ($milestone->status !== Milestone::STATUS_DISBURSED) {
            return back()->with('error', 'Dana tahap ini belum cair atau sudah dilaporkan.');
        }

        // Sisa nominal tahap ini yang belum dilaporkan. LPJ tidak boleh
        // melebihi dana yang benar-benar dicairkan untuk tahap tersebut.
        $sudahDilaporkan = (int) $milestone->expenseReports()
            ->whereIn('status', [ExpenseReport::STATUS_PENDING, ExpenseReport::STATUS_VERIFIED])
            ->sum('amount');

        $sisa = max(0, $milestone->amount - $sudahDilaporkan);

        if ($sisa === 0) {
            return back()->with('error', 'Seluruh dana tahap ini sudah dilaporkan.');
        }

        $data = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'required|string|max:1000',
            'amount' => 'required|integer|min:1|max:'.$sisa,
            'spent_on' => 'required|date|before_or_equal:today',
            'receipt' => 'required|image|max:' . config('donasi.max_upload_kb'),
            // Item RAB WAJIB milik kampanye ini. Tanpa pembatasan ini, pengaju
            // kampanye A bisa melampirkan pengeluarannya ke item RAB kampanye B
            // dan angkanya ikut muncul di tabel realisasi publik kampanye B.
            'campaign_item_id' => [
                'nullable',
                Rule::exists('campaign_items', 'id')->where('campaign_id', $campaign->id),
            ],
        ], [
            'amount.max' => 'Nominal melebihi sisa dana tahap ini (maksimal Rp'
                .number_format($sisa, 0, ',', '.').').',
        ]);

        $receiptPath = $request->file('receipt')->store('lpj');

        DB::transaction(function () use ($campaign, $milestone, $data, $receiptPath, $audit) {
            $expense = ExpenseReport::create([
                'campaign_id' => $campaign->id,
                'milestone_id' => $milestone->id,
                'campaign_item_id' => $data['campaign_item_id'] ?? null,
                'created_by' => auth()->id(),
                'title' => $data['title'],
                'description' => $data['description'],
                'amount' => $data['amount'],
                'spent_on' => $data['spent_on'],
                'receipt_path' => $receiptPath,
                'status' => ExpenseReport::STATUS_PENDING,
            ]);

            $audit->record('expense.reported', $expense, [
                'judul' => $expense->title,
                'amount' => $expense->amount,
            ]);
        });

        return back()->with('status', 'Laporan pertanggungjawaban berhasil dikirim. Menunggu verifikasi admin.');
    }
}
