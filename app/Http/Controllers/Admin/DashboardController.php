<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Disbursement;
use App\Models\Donation;
use App\Models\ExpenseReport;
use App\Models\User;
use App\Services\AuditLogger;

class DashboardController extends Controller
{
    public function __invoke(AuditLogger $audit)
    {
        $stats = [
            'kampanye_pending' => Campaign::where('status', Campaign::STATUS_PENDING)->count(),
            'user_pending' => User::where('verification_status', User::VERIFICATION_PENDING)->count(),
            'pencairan_pending' => Disbursement::where('status', Disbursement::STATUS_PENDING)->count(),
            'lpj_pending' => ExpenseReport::where('status', ExpenseReport::STATUS_PENDING)->count(),
            'total_terkumpul' => (int) Donation::where('status', Donation::STATUS_PAID)->sum('amount'),
            'total_donatur' => Donation::where('status', Donation::STATUS_PAID)->count(),
            'kampanye_aktif' => Campaign::where('status', Campaign::STATUS_APPROVED)->count(),
            'total_tercairkan' => (int) Disbursement::where('status', Disbursement::STATUS_RELEASED)->sum('amount'),
        ];

        $recentCampaigns = Campaign::with('user:id,name')
            ->where('status', Campaign::STATUS_PENDING)
            ->latest('submitted_at')
            ->take(5)
            ->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'recentCampaigns' => $recentCampaigns,
            'chainStatus' => $audit->verifyChain(),
        ]);
    }
}
