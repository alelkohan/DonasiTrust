<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Donation;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        // Check if AJAX request for real-time search or category switching
        if ($request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest' || $request->has('ajax')) {
            $ajaxType = $request->input('ajax');

            if ($ajaxType === 'search') {
                $q = trim($request->input('q', ''));
                if (empty($q)) {
                    return response()->json([
                        'is_search' => false,
                        'html' => '',
                    ]);
                }

                $searchResults = Campaign::published()
                    ->with(['user:id,name,organization,verification_status', 'paidDonations:id,campaign_id'])
                    ->where(function ($sub) use ($q) {
                        $sub->where('title', 'like', "%{$q}%")
                            ->orWhere('summary', 'like', "%{$q}%")
                            ->orWhereHas('user', function ($u) use ($q) {
                                $u->where('name', 'like', "%{$q}%")
                                  ->orWhere('organization', 'like', "%{$q}%");
                            });
                    })
                    ->orderByDesc('id')
                    ->take(6)
                    ->get();

                $html = view('partials.search-results-grid', ['searchResults' => $searchResults, 'query' => $q])->render();

                return response()->json([
                    'is_search' => true,
                    'count' => $searchResults->count(),
                    'html' => $html,
                ]);
            }

            if ($ajaxType === 'category') {
                $kategori = $request->input('kategori');

                if (empty($kategori) || $kategori === 'semua') {
                    $top5 = Campaign::published()
                        ->with(['user:id,name,organization,verification_status', 'paidDonations:id,campaign_id'])
                        ->withCount(['donations as paid_donations_count' => function ($q) {
                            $q->where('status', Donation::STATUS_PAID);
                        }])
                        ->orderByDesc('paid_donations_count')
                        ->orderByDesc('collected_amount')
                        ->take(5)
                        ->get();

                    $top5Ids = $top5->pluck('id');

                    $latest5 = Campaign::published()
                        ->with(['user:id,name,organization,verification_status', 'paidDonations:id,campaign_id'])
                        ->whereNotIn('id', $top5Ids)
                        ->orderByDesc('id')
                        ->take(5)
                        ->get();

                    $campaigns = $top5->concat($latest5);
                } else {
                    $campaigns = Campaign::published()
                        ->with(['user:id,name,organization,verification_status', 'paidDonations:id,campaign_id'])
                        ->where('category', $kategori)
                        ->orderByDesc('id')
                        ->take(10)
                        ->get();
                }

                $html = view('partials.campaign-grid', compact('campaigns'))->render();

                return response()->json([
                    'kategori' => $kategori,
                    'count' => $campaigns->count(),
                    'html' => $html,
                ]);
            }
        }

        // Standard Page Load
        $popularCampaigns = Campaign::published()
            ->with(['user:id,name,organization,verification_status', 'paidDonations:id,campaign_id'])
            ->withCount(['donations as paid_donations_count' => function ($q) {
                $q->where('status', Donation::STATUS_PAID);
            }])
            ->orderByDesc('paid_donations_count')
            ->orderByDesc('collected_amount')
            ->take(5)
            ->get();

        $top5Ids = $popularCampaigns->pluck('id');

        $latestCampaigns = Campaign::published()
            ->with(['user:id,name,organization,verification_status', 'paidDonations:id,campaign_id'])
            ->whereNotIn('id', $top5Ids)
            ->orderByDesc('id')
            ->take(5)
            ->get();

        $campaigns = $popularCampaigns->concat($latestCampaigns);

        if ($request->filled('q') || ($request->filled('kategori') && $request->input('kategori') !== 'semua')) {
            $query = Campaign::published()
                ->with(['user:id,name,organization,verification_status', 'paidDonations:id,campaign_id'])
                ->orderByDesc('id');

            if ($request->filled('q')) {
                $q = $request->input('q');
                $query->where(function ($sub) use ($q) {
                    $sub->where('title', 'like', "%{$q}%")
                        ->orWhere('summary', 'like', "%{$q}%")
                        ->orWhereHas('user', function ($u) use ($q) {
                            $u->where('name', 'like', "%{$q}%")
                              ->orWhere('organization', 'like', "%{$q}%");
                        });
                });
            }

            if ($request->filled('kategori') && $request->input('kategori') !== 'semua') {
                $query->where('category', $request->input('kategori'));
            }

            $campaigns = $query->take(10)->get();
        }

        // Featured Banners for Hero Carousel & Slider
        $heroCampaigns = Campaign::published()
            ->with(['user:id,name,organization,verification_status', 'paidDonations:id,campaign_id'])
            ->orderByDesc('collected_amount')
            ->take(12)
            ->get();

        $stats = [
            'terkumpul' => (int) Donation::where('status', Donation::STATUS_PAID)->sum('amount'),
            'donatur' => (int) Donation::where('status', Donation::STATUS_PAID)->count(),
            'kampanye' => Campaign::published()->count(),
            'tercairkan' => (int) Campaign::published()->sum('disbursed_amount'),
        ];

        $categories = Campaign::CATEGORIES;

        return view('public.home', compact('popularCampaigns', 'latestCampaigns', 'campaigns', 'heroCampaigns', 'stats', 'categories'));
    }
}
