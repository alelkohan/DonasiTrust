<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class CampaignReviewController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');

        $campaigns = Campaign::with('user:id,name,organization')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('date'), function ($q) use ($request) {
                $q->whereDate('submitted_at', $request->string('date'));
            })
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.campaigns.index', compact('campaigns'));
    }

    public function show(Campaign $campaign)
    {
        $campaign->load(['user', 'items', 'milestones', 'reviewer:id,name']);

        return view('admin.campaigns.show', compact('campaign'));
    }

    public function approve(Request $request, Campaign $campaign, AuditLogger $audit)
    {
        $this->authorize('review', $campaign);

        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        $campaign->update([
            'status' => Campaign::STATUS_APPROVED,
            'review_note' => $data['note'] ?? null,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        $audit->record('campaign.approved', $campaign, [
            'judul' => $campaign->title,
            'catatan' => $data['note'] ?? null,
        ]);

        return redirect()->route('admin.kampanye.index')
            ->with('status', 'Kampanye "'.$campaign->title.'" disetujui dan tayang.');
    }

    public function reject(Request $request, Campaign $campaign, AuditLogger $audit)
    {
        $this->authorize('review', $campaign);

        $data = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ], [], ['note' => 'alasan penolakan']);

        $campaign->update([
            'status' => Campaign::STATUS_REJECTED,
            'review_note' => $data['note'],
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        $audit->record('campaign.rejected', $campaign, [
            'judul' => $campaign->title,
            'alasan' => $data['note'],
        ]);

        return redirect()->route('admin.kampanye.index')
            ->with('status', 'Kampanye ditolak dengan catatan untuk pengaju.');
    }

    public function destroy(Campaign $campaign, AuditLogger $audit)
    {
        $this->authorize('delete', $campaign);
        
        $title = $campaign->title;
        $campaign->delete();

        $audit->record('campaign.deleted', null, ['judul' => $title]);

        return redirect()->route('admin.kampanye.index')
            ->with('status', 'Kampanye "'.$title.'" berhasil dihapus.');
    }
}
