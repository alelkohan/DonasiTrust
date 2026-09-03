<?php

namespace App\Http\Controllers\Pengaju;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Disbursement;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $campaigns = Campaign::where('user_id', $user->id)
            ->withCount('donations')
            ->latest()
            ->get();

        $stats = [
            'kampanye' => $campaigns->count(),
            'aktif' => $campaigns->where('status', Campaign::STATUS_APPROVED)->count(),
            'terkumpul' => (int) $campaigns->sum('collected_amount'),
            'tercairkan' => (int) $campaigns->sum('disbursed_amount'),
        ];

        $pendingDisbursements = Disbursement::whereIn('campaign_id', $campaigns->pluck('id'))
            ->where('status', Disbursement::STATUS_PENDING)
            ->count();

        return view('pengaju.dashboard', compact('campaigns', 'stats', 'pendingDisbursements'));
    }
}
