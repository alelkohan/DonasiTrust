<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use Illuminate\Http\Request;

class DonaturDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $donations = Donation::with('campaign:id,title,slug')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        $totalDonated = (int) Donation::where('user_id', $request->user()->id)
            ->where('status', Donation::STATUS_PAID)
            ->sum('amount');

        return view('donatur.dashboard', compact('donations', 'totalDonated'));
    }
}
