<?php

namespace App\Http\Controllers\Pengaju;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Milestone;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $campaigns = Campaign::where('user_id', $request->user()->id)
            ->withCount('donations')
            ->latest()
            ->paginate(10);

        return view('pengaju.campaigns.index', compact('campaigns'));
    }

    public function create(Request $request)
    {
        if (! $request->user()->canSubmitCampaign()) {
            return redirect()->route('verifikasi.identitas')
                ->with('warning', 'Verifikasi identitas Anda dulu sebelum membuat kampanye.');
        }

        return view('pengaju.campaigns.form', [
            'campaign' => new Campaign(['status' => Campaign::STATUS_DRAFT]),
            'categories' => Campaign::CATEGORIES,
        ]);
    }

    public function store(Request $request, AuditLogger $audit)
    {
        abort_unless($request->user()->canSubmitCampaign(), 403);

        $data = $this->validated($request);

        $campaign = DB::transaction(function () use ($request, $data, $audit) {
            $campaign = Campaign::create([
                'user_id' => $request->user()->id,
                'title' => $data['title'],
                'slug' => Campaign::makeUniqueSlug($data['title']),
                'category' => $data['category'],
                'summary' => $data['summary'],
                'description' => $data['description'],
                'target_amount' => $data['target_amount'],
                'deadline' => $data['deadline'] ?? null,
                'cover_path' => $this->storeCover($request),
                'status' => Campaign::STATUS_DRAFT,
            ]);

            $this->syncItemsAndMilestones($campaign, $data);
            $audit->record('campaign.created', $campaign, ['judul' => $campaign->title]);

            return $campaign;
        });

        return redirect()->route('pengaju.kampanye.edit', $campaign)
            ->with('status', 'Draf kampanye tersimpan. Periksa lagi sebelum diajukan.');
    }

    public function edit(Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $campaign->load(['items', 'milestones']);

        return view('pengaju.campaigns.form', [
            'campaign' => $campaign,
            'categories' => Campaign::CATEGORIES,
        ]);
    }

    public function update(Request $request, Campaign $campaign, AuditLogger $audit)
    {
        $this->authorize('update', $campaign);

        $data = $this->validated($request);

        DB::transaction(function () use ($request, $campaign, $data, $audit) {
            $campaign->update([
                'title' => $data['title'],
                'slug' => Campaign::makeUniqueSlug($data['title'], $campaign->id),
                'category' => $data['category'],
                'summary' => $data['summary'],
                'description' => $data['description'],
                'target_amount' => $data['target_amount'],
                'deadline' => $data['deadline'] ?? null,
                'cover_path' => $this->storeCover($request) ?? $campaign->cover_path,
            ]);

            $this->syncItemsAndMilestones($campaign, $data);
            $audit->record('campaign.updated', $campaign, ['judul' => $campaign->title]);
        });

        return back()->with('status', 'Perubahan tersimpan.');
    }

    public function submit(Campaign $campaign, AuditLogger $audit)
    {
        $this->authorize('submit', $campaign);

        if ($campaign->milestones()->count() === 0) {
            return back()->withErrors(['milestones' => 'Tambahkan minimal satu tahap pencairan.']);
        }

        $campaign->update([
            'status' => Campaign::STATUS_PENDING,
            'submitted_at' => now(),
            'review_note' => null,
        ]);

        $audit->record('campaign.submitted', $campaign, ['judul' => $campaign->title]);

        return redirect()->route('pengaju.kampanye.index')
            ->with('status', 'Kampanye diajukan. Admin akan meninjau dalam 1x24 jam.');
    }

    public function destroy(Campaign $campaign)
    {
        $this->authorize('delete', $campaign);

        $campaign->delete();

        return redirect()->route('pengaju.kampanye.index')->with('status', 'Kampanye dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', 'in:'.implode(',', array_keys(Campaign::CATEGORIES))],
            'summary' => ['required', 'string', 'max:300'],
            'description' => ['required', 'string', 'max:20000'],
            'target_amount' => ['required', 'integer', 'min:100000'],
            'deadline' => ['nullable', 'date', 'after:today'],
            'cover' => ['nullable', 'image', 'max:'.config('donasi.max_upload_kb')],

            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:150'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit' => ['required', 'string', 'max:30'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],

            'milestones' => ['required', 'array', 'min:1'],
            'milestones.*.title' => ['required', 'string', 'max:150'],
            'milestones.*.description' => ['nullable', 'string', 'max:1000'],
            'milestones.*.amount' => ['required', 'integer', 'min:1'],
        ], [], [
            'title' => 'judul',
            'summary' => 'ringkasan',
            'description' => 'deskripsi',
            'target_amount' => 'target dana',
            'items' => 'rincian anggaran',
            'milestones' => 'tahap pencairan',
        ]);

        $itemTotal = collect($data['items'])->sum(fn ($i) => $i['quantity'] * $i['unit_price']);
        $milestoneTotal = collect($data['milestones'])->sum('amount');

        // Aturan ini yang membuat angka di halaman transparansi bisa dipercaya:
        // target dana harus sama dengan jumlah RAB dan jumlah tahap pencairan.
        if ($itemTotal !== (int) $data['target_amount']) {
            throw ValidationException::withMessages([
                'items' => 'Total RAB (Rp'.number_format($itemTotal, 0, ',', '.')
                    .') harus sama dengan target dana (Rp'
                    .number_format($data['target_amount'], 0, ',', '.').').',
            ]);
        }

        if ($milestoneTotal !== (int) $data['target_amount']) {
            throw ValidationException::withMessages([
                'milestones' => 'Total tahap pencairan (Rp'.number_format($milestoneTotal, 0, ',', '.')
                    .') harus sama dengan target dana.',
            ]);
        }

        $this->periksaDistribusiTahapan($data);

        return $data;
    }

    /**
     * Cegah "pencairan bertahap" yang hanya bertahap di atas kertas.
     *
     * Tanpa aturan ini, satu tahap bisa menyerap 90% dana dan seluruh
     * perlindungannya jadi tinggal nama.
     *
     * Batasnya sengaja longgar (70%), bukan ketat, karena ada proyek yang
     * memang berat di depan — pengeboran sumur, misalnya, menghabiskan
     * sebagian besar biaya sebelum apa pun bisa dilaporkan. Kampanye di atas
     * 40% tetap diizinkan tapi ditandai ke publik lewat Campaign::isFrontLoaded().
     */
    private function periksaDistribusiTahapan(array $data): void
    {
        $target = (int) $data['target_amount'];
        $tahapan = array_values($data['milestones']);

        $minimalDuaTahap = (int) config('donasi.milestone_min_two_above');

        if ($target >= $minimalDuaTahap && count($tahapan) < 2) {
            throw ValidationException::withMessages([
                'milestones' => 'Kampanye dengan target di atas '
                    .rupiah($minimalDuaTahap).' wajib dipecah minimal dua tahap pencairan.',
            ]);
        }

        if (count($tahapan) < 2) {
            return;
        }

        $batas = (float) config('donasi.milestone_first_max_share');
        $porsi = (int) $tahapan[0]['amount'] / max(1, $target);

        if ($porsi > $batas) {
            throw ValidationException::withMessages([
                'milestones' => sprintf(
                    'Tahap pertama menyerap %.1f%% dari target dana. Maksimal %d%% — '
                    .'pecah lebih merata supaya pencairan bertahap benar-benar berfungsi.',
                    $porsi * 100, $batas * 100
                ),
            ]);
        }
    }

    private function storeCover(Request $request): ?string
    {
        if (! $request->hasFile('cover')) {
            return null;
        }

        return $request->file('cover')->store('sampul', 'public');
    }

    private function syncItemsAndMilestones(Campaign $campaign, array $data): void
    {
        $campaign->items()->delete();

        foreach (array_values($data['items']) as $index => $item) {
            $campaign->items()->create([
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'unit' => $item['unit'],
                'unit_price' => $item['unit_price'],
                'subtotal' => $item['quantity'] * $item['unit_price'],
                'sort_order' => $index,
            ]);
        }

        // Milestone yang sudah berjalan tidak boleh dihapus diam-diam.
        $locked = $campaign->milestones()->where('status', '!=', Milestone::STATUS_LOCKED)->exists();

        if ($locked) {
            return;
        }

        $campaign->milestones()->delete();

        foreach (array_values($data['milestones']) as $index => $milestone) {
            $campaign->milestones()->create([
                'sequence' => $index + 1,
                'title' => $milestone['title'],
                'description' => $milestone['description'] ?? null,
                'amount' => $milestone['amount'],
                'status' => Milestone::STATUS_LOCKED,
            ]);
        }
    }
}
