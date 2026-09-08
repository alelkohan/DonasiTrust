<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Donation;
use Illuminate\Http\Request;

class CampaignBrowseController extends Controller
{
    public function index(Request $request)
    {
        $campaigns = Campaign::published()
            ->with('user:id,name,organization')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                // Binding parameter otomatis oleh query builder — aman dari SQL injection.
                $query->where(fn ($q) => $q->where('title', 'like', $term)
                    ->orWhere('summary', 'like', $term));
            })
            ->when($request->filled('kategori'), fn ($q) => $q->where('category', $request->string('kategori')))
            ->when($request->input('urut') === 'terbaru', fn ($q) => $q->latest('submitted_at'),
                fn ($q) => $q->orderByDesc('collected_amount'))
            ->paginate(9)
            ->withQueryString();

        return view('public.campaigns', [
            'campaigns' => $campaigns,
            'categories' => Campaign::CATEGORIES,
        ]);
    }

    public function show(Campaign $campaign)
    {
        abort_unless($campaign->isPublished(), 404);

        // Kolom pengaju dibatasi: halaman ini publik, jadi NIK dan rekening
        // tidak boleh ikut tertarik.
        $campaign->load(['user:id,name,organization', 'items', 'milestones']);

        $recentDonations = $campaign->paidDonations()
            ->latest('paid_at')
            ->take(8)
            ->get();

        $pendingReference = session('pending_donation_'.$campaign->id);
        $pendingDonation = null;

        if ($pendingReference) {
            $pendingDonation = Donation::where('reference', $pendingReference)
                ->where('campaign_id', $campaign->id)
                ->where('status', Donation::STATUS_PENDING)
                ->first();

            if ($pendingDonation && $pendingDonation->isExpired()) {
                $pendingDonation = null;
                session()->forget('pending_donation_'.$campaign->id);
            }
        }

        if (! $pendingDonation && auth()->check()) {
            $pendingDonation = Donation::where('user_id', auth()->id())
                ->where('campaign_id', $campaign->id)
                ->where('status', Donation::STATUS_PENDING)
                ->latest()
                ->first();

            if ($pendingDonation && $pendingDonation->isExpired()) {
                $pendingDonation = null;
            }
        }

        return view('public.campaign-show', compact('campaign', 'recentDonations', 'pendingDonation'));
    }

    /** Halaman transparansi publik: ledger pemasukan, pencairan, dan LPJ. */
    public function transparency(Campaign $campaign)
    {
        abort_unless($campaign->isPublished(), 404);

        $campaign->load(['items', 'milestones']);

        $disbursements = $campaign->disbursements()
            ->with('milestone:id,title,sequence')
            ->whereIn('status', ['approved', 'released'])
            ->orderBy('created_at')
            ->get();

        $expenses = $campaign->expenseReports()
            ->with('item:id,name')
            ->orderByDesc('spent_on')
            ->get();

        $donationCount = $campaign->paidDonations()->count();

        $largestDonation = (int) $campaign->paidDonations()->max('amount');

        return view('public.transparency', compact(
            'campaign', 'disbursements', 'expenses', 'donationCount', 'largestDonation'
        ));
    }
}
