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
        $status = $request->query('status', 'pending');

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

    public function show(Campaign $campaign, \App\Services\CampaignAiAuditor $auditor)
    {
        $campaign->load(['user', 'items', 'milestones', 'reviewer:id,name']);

        // Jika kampanye belum diaudit AI dan berstatus pending/draf/aktif, jalankan audit otomatis
        if (! $campaign->hasAiAnalysis() && $campaign->items->isNotEmpty()) {
            $auditor->analyze($campaign);
        }

        return view('admin.campaigns.show', compact('campaign'));
    }

    public function auditAi(Request $request, Campaign $campaign, \App\Services\CampaignAiAuditor $auditor)
    {
        $this->authorize('view', $campaign);

        $analysis = $auditor->analyze($campaign);

        if ($request->expectsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'message' => 'Analisis kelayakan anggaran oleh AI berhasil diperbarui.',
                'analysis' => $analysis,
                'risk_level' => $campaign->ai_risk_level,
                'risk_label' => $campaign->aiRiskLabel(),
                'risk_tone' => $campaign->aiRiskTone(),
            ]);
        }

        return back()->with('status', 'Analisis kelayakan anggaran oleh AI berhasil diperbarui.');
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

        $message = 'Kampanye "'.$campaign->title.'" disetujui dan tayang.';
        if ($request->expectsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            $request->session()->flash('status', $message);
            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => route('admin.kampanye.index', ['status' => 'pending']),
            ]);
        }

        return redirect()->route('admin.kampanye.index', ['status' => 'pending'])
            ->with('status', $message);
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

        $message = 'Kampanye ditolak dengan catatan untuk pengaju.';
        if ($request->expectsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            $request->session()->flash('status', $message);
            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => route('admin.kampanye.index', ['status' => 'pending']),
            ]);
        }

        return redirect()->route('admin.kampanye.index', ['status' => 'pending'])
            ->with('status', $message);
    }

    public function destroy(Campaign $campaign, AuditLogger $audit)
    {
        $this->authorize('delete', $campaign);
        
        $title = $campaign->title;
        $campaign->delete();

        $audit->record('campaign.deleted', null, ['judul' => $title]);

        $message = 'Kampanye "'.$title.'" berhasil dihapus.';
        if (request()->expectsJson() || request()->ajax() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
            request()->session()->flash('status', $message);
            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => route('admin.kampanye.index', ['status' => 'pending']),
            ]);
        }

        return redirect()->route('admin.kampanye.index', ['status' => 'pending'])
            ->with('status', $message);
    }
}
