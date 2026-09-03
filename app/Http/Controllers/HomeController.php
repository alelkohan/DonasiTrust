<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Donation;

class HomeController extends Controller
{
    public function __invoke()
    {
        $campaigns = Campaign::published()
            ->with('user:id,name,organization')
            ->orderByDesc('collected_amount')
            ->take(6)
            ->get();

        $stats = [
            'terkumpul' => (int) Donation::where('status', Donation::STATUS_PAID)->sum('amount'),
            'donatur' => (int) Donation::where('status', Donation::STATUS_PAID)->count(),
            'kampanye' => Campaign::published()->count(),
            'tercairkan' => (int) Campaign::published()->sum('disbursed_amount'),
        ];

        return view('public.home', compact('campaigns', 'stats'));
    }
}
