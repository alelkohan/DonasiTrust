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
                $query = Campaign::published()
                    ->with(['user:id,name,organization,verification_status', 'paidDonations:id,campaign_id'])
                    ->orderByDesc('id');

                if (! empty($kategori) && $kategori !== 'semua') {
                    $query->where('category', $kategori);
                }

                $campaigns = $query->take(16)->get();
                $html = view('partials.campaign-grid', compact('campaigns'))->render();

                return response()->json([
                    'kategori' => $kategori,
                    'count' => $campaigns->count(),
                    'html' => $html,
                ]);
            }
        }

        // Standard Page Load
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

        if ($request->filled('kategori')) {
            $query->where('category', $request->input('kategori'));
        }

        $campaigns = $query->take(16)->get();

        // Featured Banners for Hero Carousel (Only campaigns with valid cover photos)
        $heroCampaigns = Campaign::published()
            ->whereNotNull('cover_path')
            ->where('cover_path', '!=', '')
            ->with(['user:id,name,organization,verification_status', 'paidDonations:id,campaign_id'])
            ->orderByDesc('collected_amount')
            ->take(8)
            ->get();

        $stats = [
            'terkumpul' => (int) Donation::where('status', Donation::STATUS_PAID)->sum('amount'),
            'donatur' => (int) Donation::where('status', Donation::STATUS_PAID)->count(),
            'kampanye' => Campaign::published()->count(),
            'tercairkan' => (int) Campaign::published()->sum('disbursed_amount'),
        ];

        $categories = Campaign::CATEGORIES;

        return view('public.home', compact('campaigns', 'heroCampaigns', 'stats', 'categories'));
    }
}
