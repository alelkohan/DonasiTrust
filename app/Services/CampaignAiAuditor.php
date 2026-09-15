<?php

namespace App\Services;

use App\Models\Campaign;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CampaignAiAuditor
{
    /**
     * Jalankan audit analisis kelayakan anggaran dan deteksi anomali pada kampanye.
     */
    public function analyze(Campaign $campaign): array
    {
        $campaign->loadMissing(['items', 'milestones', 'user']);

        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model', 'gemini-1.5-flash');

        $analysis = null;

        if (! empty($apiKey)) {
            try {
                $analysis = $this->callGeminiApi($campaign, $apiKey, $model);
            } catch (Throwable $e) {
                Log::warning('AI Audit via Gemini API failed, falling back to heuristic: '.$e->getMessage(), [
                    'campaign_id' => $campaign->id,
                ]);
            }
        }

        if (! $analysis || ! isset($analysis['risk_level'])) {
            $analysis = $this->heuristicAudit($campaign);
        }

        $campaign->update([
            'ai_analysis' => $analysis,
            'ai_risk_level' => $analysis['risk_level'] ?? 'low',
            'ai_analyzed_at' => now(),
        ]);

        return $analysis;
    }

    /**
     * Kirim data RAB dan cerita kampanye ke Google Gemini API.
     */
    private function callGeminiApi(Campaign $campaign, string $apiKey, string $model): ?array
    {
        $prompt = $this->buildPrompt($campaign);

        $response = Http::timeout(20)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'responseMimeType' => 'application/json',
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('Gemini API returned error: '.$response->status().' - '.$response->body());
            return null;
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (! $text) {
            return null;
        }

        $parsed = json_decode($text, true);

        if (! is_array($parsed) || ! isset($parsed['risk_level'])) {
            // Coba ekstrak JSON jika model menyisipkan markdown code block
            if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
                $parsed = json_decode($matches[0], true);
            }
        }

        return is_array($parsed) && isset($parsed['risk_level']) ? $this->sanitizeAnalysisResult($parsed) : null;
    }

    /**
     * Susun prompt kontekstual audit kelayakan anggaran donasi di Indonesia.
     */
    private function buildPrompt(Campaign $campaign): string
    {
        $itemsData = $campaign->items->map(function ($i) use ($campaign) {
            $pct = $campaign->target_amount > 0 ? round(($i->subtotal / $campaign->target_amount) * 100, 1) : 0;
            return "- Item: {$i->name} | Qty: {$i->quantity} {$i->unit} | Harga Satuan: Rp".number_format($i->unit_price, 0, ',', '.')." | Subtotal: Rp".number_format($i->subtotal, 0, ',', '.')." ({$pct}% dari target)";
        })->implode("\n");

        $milestonesData = $campaign->milestones->map(function ($m) {
            return "- Tahap {$m->sequence}: {$m->title} (Rp".number_format($m->amount, 0, ',', '.').")".($m->description ? " - {$m->description}" : '');
        })->implode("\n");

        return <<<PROMPT
Anda adalah "AI Budget Auditor" profesional untuk platform transparansi donasi terpercaya di Indonesia (DonasiTrust).
Tugas Anda adalah menganalisis kewajaran, proporsionalitas, dan indikasi penggelembungan (mark-up) pada Rencana Anggaran Biaya (RAB) kampanye penggalangan dana berikut.

DATA KAMPANYE:
- Judul: {$campaign->title}
- Kategori: {$campaign->categoryLabel()}
- Ringkasan: {$campaign->summary}
- Target Dana: Rp{$campaign->target_amount}
- Deskripsi Lengkap:
{$campaign->description}

RINCIAN ANGGARAN (RAB):
{$itemsData}

TAHAPAN PENCAIRAN:
{$milestonesData}

PANDUAN EVALUASI (WAJIB DIPERIKSA):
1. Evaluasi kewajaran harga satuan barang/jasa berdasarkan standar harga pasar wajar di Indonesia (IDR).
2. Periksa proporsionalitas alokasi: apakah biaya operasional/honor/konsumsi wajar terhadap misi bantuan langsung (biaya honor/operasional panitia tidak boleh mendominasi >30%).
3. DETEKSI TEKS ACAK / GIBBERISH / DUMMY: Jika judul, cerita, atau nama item RAB berupa ketikan asal/dummy (contoh: "awd", "sef", "asdf", "test", "xxx"), WAJIB tandai sebagai "high" risk dan berikan skor risiko tinggi (80-95).
4. PERIKSA KELAYAKAN CERITA & KONTEKS: Jika cerita terlalu singkat (< 15 kata) atau tidak memuat latar belakang penerima manfaat, WAJIB tandai sebagai "high" / "medium" risk.
5. PERIKSA KEJELASAN ITEM RAB (ANTI-ITEM FIKTIF): Setiap pos anggaran harus merepresentasikan barang/jasa riil. Tolak item fiktif/tidak jelas (seperti "awd", "barang", "tes").

HASILKAN RESPON HANYA DALAM FORMAT JSON BERIKUT (VALID JSON):
{
  "risk_level": "low" | "medium" | "high",
  "risk_score": 0 sampai 100 (0 = sangat wajar/aman, 100 = indikasi penggelembungan atau data fiktif sangat tinggi),
  "summary": "Ringkasan ringkas (2-3 kalimat) mengenai hasil evaluasi kelayakan anggaran dan kejelasan program",
  "flags": [
    {
      "item_name": "Nama item dari RAB atau aspek anggaran",
      "type": "potential_markup" | "disproportionate_allocation" | "irrelevant_item" | "vague_description" | "fictitious_item" | "inadequate_context",
      "severity": "low" | "medium" | "high",
      "reason": "Penjelasan mengapa item ini dicurigai atau perlu diklarifikasi",
      "suggested_action": "Saran verifikasi untuk admin"
    }
  ],
  "positive_aspects": [
    "Poin positif kewajaran anggaran, misalnya rincian spesifik atau alokasi bantuan langsung dominan"
  ],
  "admin_recommendations": [
    "Poin pertanyaan atau dokumen pendukung yang disarankan untuk diminta admin ke pengaju"
  ]
}
PROMPT;
    }

    /**
     * Mesin Audit Heuristik Cerdas (Fallback saat offline / API key belum disetel).
     */
    public function heuristicAudit(Campaign $campaign): array
    {
        $flags = [];
        $positiveAspects = [];
        $adminRecommendations = [];
        $target = max(1, $campaign->target_amount);

        // 1. Deteksi Kualitas Cerita & Teks Dummy / Gibberish pada Kampanye
        $storyClean = trim(strip_tags($campaign->description ?? ''));
        $summaryClean = trim($campaign->summary ?? '');
        $titleClean = trim($campaign->title ?? '');

        if ($this->isGibberishOrDummy($storyClean) || $this->isGibberishOrDummy($titleClean) || str_word_count($storyClean) < 5 || strlen($storyClean) < 20) {
            $flags[] = [
                'item_name' => 'Narasi & Cerita Kampanye',
                'type' => 'inadequate_context',
                'severity' => 'high',
                'reason' => 'Deskripsi cerita kampanye terlalu singkat atau terindikasi teks acak/dummy (belum memuat latar belakang masalah dan sasaran penerima manfaat yang jelas).',
                'suggested_action' => 'Minta pengaju melengkapi latar belakang cerita, urgensi bantuan, dan data penerima manfaat secara spesifik.',
            ];
            $adminRecommendations[] = 'Minta pengaju melengkapi deskripsi cerita dan latar belakang kampanye sebelum dapat disetujui.';
        }

        // 2. Periksa alokasi honor / operasional / konsumsi
        $operationalKeywords = ['honor', 'gaji', 'upah panitia', 'konsumsi', 'makan panitia', 'koordinasi', 'operasional panitia', 'admin fee', 'uang saku', 'sewa kendaraan panitia'];
        $operationalTotal = 0;
        $operationalItems = [];

        foreach ($campaign->items as $item) {
            $nameLower = strtolower($item->name);
            foreach ($operationalKeywords as $kw) {
                if (str_contains($nameLower, $kw)) {
                    $operationalTotal += $item->subtotal;
                    $operationalItems[] = $item;
                    break;
                }
            }
        }

        $operationalShare = ($operationalTotal / $target) * 100;

        if ($operationalShare > 30) {
            $flags[] = [
                'item_name' => 'Alokasi Biaya Operasional / Honor',
                'type' => 'disproportionate_allocation',
                'severity' => $operationalShare > 50 ? 'high' : 'medium',
                'reason' => sprintf('Total biaya operasional dan honor panitia mencapai %.1f%% (%s) dari target dana, tergolong tinggi untuk kampanye sosial.', $operationalShare, rupiah($operationalTotal)),
                'suggested_action' => 'Minta pengaju merasionalisasi biaya operasional agar proporsi bantuan langsung lebih dominan.',
            ];
            $adminRecommendations[] = 'Minta rincian pembagian tugas dan justifikasi nominal honor panitia pelaksana.';
        } elseif ($operationalTotal > 0) {
            $positiveAspects[] = sprintf('Porsi operasional terkendali dengan baik (%.1f%% dari total dana).', $operationalShare);
        }

        // 3. Periksa item RAB (Kejelasan barang riil, mark-up harga pasar, dan pos samar)
        $commonBenchmark = [
            'semen' => ['max' => 150000, 'norm' => 'Rp60.000 - Rp85.000 per sak'],
            'beras' => ['max' => 35000, 'norm' => 'Rp14.000 - Rp18.000 per kg', 'unit_hint' => ['kg', 'kilo', 'kilogram']],
            'mie instan' => ['max' => 250000, 'norm' => 'Rp110.000 - Rp140.000 per dus'],
            'air mineral' => ['max' => 95000, 'norm' => 'Rp30.000 - Rp50.000 per dus'],
            'minyak goreng' => ['max' => 45000, 'norm' => 'Rp17.000 - Rp22.000 per liter', 'unit_hint' => ['liter', 'l', 'lt']],
            'batu bata' => ['max' => 3000, 'norm' => 'Rp700 - Rp1.200 per buah'],
            'genteng' => ['max' => 12000, 'norm' => 'Rp2.500 - Rp5.000 per buah'],
            'pasir' => ['max' => 1200000, 'norm' => 'Rp300.000 - Rp600.000 per pick-up / truk kecil'],
        ];

        foreach ($campaign->items as $item) {
            $nameTrim = trim($item->name);
            $nameLower = strtolower($nameTrim);
            $unitLower = strtolower($item->unit);

            // A. Deteksi nama item fiktif / teks acak (contoh: 'awd', 'sef', 'asdf')
            if (strlen($nameTrim) < 4 || $this->isGibberishOrDummy($nameTrim)) {
                $flags[] = [
                    'item_name' => $item->name,
                    'type' => 'fictitious_item',
                    'severity' => 'high',
                    'reason' => sprintf("Nama pos anggaran '%s' terindikasi teks acak/fiktif dan tidak mendeskripsikan barang atau jasa riil.", $item->name),
                    'suggested_action' => 'Minta pengaju mengganti pos ini dengan nama barang/jasa riil yang dapat diverifikasi.',
                ];
                $adminRecommendations[] = sprintf("Wajibkan pengaju memperbaiki pos anggaran '%s' menjadi kebutuhan riil.", $item->name);
                continue;
            }

            // B. Cek item samar / tidak jelas bernilai besar
            $vagueKeywords = ['lain-lain', 'biaya tak terduga', 'biaya tambahan', 'kegiatan', 'keperluan lain'];
            foreach ($vagueKeywords as $vague) {
                if (str_contains($nameLower, $vague) && $item->subtotal > ($target * 0.15)) {
                    $flags[] = [
                        'item_name' => $item->name,
                        'type' => 'vague_description',
                        'severity' => 'medium',
                        'reason' => 'Pos anggaran berlabel samar dengan nilai cukup besar (>15% dari total target).',
                        'suggested_action' => 'Minta pengaju memecah pos ini menjadi kebutuhan riil yang terukur.',
                    ];
                    $adminRecommendations[] = "Minta rincian spesifik untuk pos '{$item->name}'.";
                }
            }

            // C. Cek mark-up harga acuan pasar
            foreach ($commonBenchmark as $commodity => $rule) {
                if (str_contains($nameLower, $commodity)) {
                    if (isset($rule['unit_hint']) && ! in_array($unitLower, $rule['unit_hint'], true) && ! str_contains($nameLower, '1kg') && ! str_contains($nameLower, '1 kg')) {
                        continue;
                    }

                    if ($item->unit_price > $rule['max']) {
                        $flags[] = [
                            'item_name' => $item->name,
                            'type' => 'potential_markup',
                            'severity' => 'high',
                            'reason' => sprintf('Harga satuan diajukan (%s) melebihi batas wajar acuan pasar (%s).', rupiah($item->unit_price), $rule['norm']),
                            'suggested_action' => 'Minta pengaju melampirkan proforma invoice atau survei harga dari toko penyedia.',
                        ];
                        $adminRecommendations[] = "Minta kuitansi penawaran harga resmi untuk item '{$item->name}'.";
                    }
                }
            }

            // D. Cek item tunggal yang menyerap > 60% anggaran
            if ($campaign->items->count() > 1 && $item->subtotal > ($target * 0.60)) {
                $flags[] = [
                    'item_name' => $item->name,
                    'type' => 'disproportionate_allocation',
                    'severity' => 'low',
                    'reason' => sprintf('Item ini menyerap porsi mayoritas (%.1f%%) dari seluruh target kampanye.', ($item->subtotal / $target) * 100),
                    'suggested_action' => 'Pastikan dokumen dan urgensi item ini benar-benar terverifikasi.',
                ];
            }
        }

        // 4. Positive aspects
        if (empty($flags)) {
            $positiveAspects[] = 'Tidak ditemukan indikasi penggelembungan harga mencolok pada rincian RAB.';
            if ($campaign->items->count() >= 3) {
                $positiveAspects[] = 'RAB tersusun terperinci dengan beberapa pos alokasi yang jelas.';
            }
            if ($campaign->milestones->count() >= 2) {
                $positiveAspects[] = 'Skema pencairan terbagi ke dalam beberapa tahapan terkontrol.';
            }
        }

        // 5. Hitung Level Risiko dan Skor
        $highFlags = count(array_filter($flags, fn ($f) => $f['severity'] === 'high'));
        $medFlags = count(array_filter($flags, fn ($f) => $f['severity'] === 'medium'));
        $lowFlags = count(array_filter($flags, fn ($f) => $f['severity'] === 'low'));

        if ($highFlags > 0 || $medFlags >= 2) {
            $riskLevel = 'high';
            $riskScore = min(95, 70 + ($highFlags * 12) + ($medFlags * 5));
            $summary = 'Terdeteksi indikasi anomali, teks fiktif/tidak jelas, atau ketidakwajaran harga satuan yang perlu ditinjau cermat oleh administrator sebelum disetujui.';
        } elseif ($medFlags > 0 || $lowFlags >= 2) {
            $riskLevel = 'medium';
            $riskScore = min(68, 45 + ($medFlags * 10) + ($lowFlags * 5));
            $summary = 'Secara umum rencana anggaran terstruktur, namun terdapat komponen yang disarankan untuk diklarifikasi ke pengaju.';
        } else {
            $riskLevel = 'low';
            $riskScore = max(10, 15 + ($lowFlags * 5));
            $summary = 'Rencana Anggaran Biaya (RAB) dinilai wajar, proporsional, dan sejalan dengan sasaran kampanye.';
        }

        if (empty($adminRecommendations)) {
            $adminRecommendations[] = 'Anggaran siap disetujui jika berkas identitas dan izin kampanye telah lengkap.';
        }

        return [
            'risk_level' => $riskLevel,
            'risk_score' => $riskScore,
            'summary' => $summary,
            'flags' => $flags,
            'positive_aspects' => array_values(array_unique($positiveAspects)),
            'admin_recommendations' => array_values(array_unique($adminRecommendations)),
            'mode' => 'heuristic_engine',
        ];
    }

    /**
     * Deteksi teks acak / dummy / keyboard mash / teks tanpa arti.
     */
    public function isGibberishOrDummy(string $text): bool
    {
        $clean = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $text)));
        if (strlen($clean) === 0) {
            return true;
        }

        // 1. Daftar kata dummy / tes umum
        $dummyWords = [
            'awd', 'sef', 'asdf', 'asdfgh', 'asdfghjkl', 'test', 'tes', 'testing',
            'dummy', 'xxx', 'xxxx', 'aaa', 'aaaa', 'bbb', 'ccc', 'zzz', 'qwe',
            'qwerty', 'halo', 'coba', 'cobaan', 'kampanye', 'contoh', 'sample',
            'foo', 'bar', 'baz', 'asdasd', 'wew', 'bla', 'blabla', 'tes123', 'admin',
        ];

        if (in_array($clean, $dummyWords, true)) {
            return true;
        }

        // 2. Karakter berulang identik (contoh: "aaaa", "zzzz", "1111")
        if (preg_match('/^(.)\1{2,}$/', $clean)) {
            return true;
        }

        // 3. Teks pendek tanpa huruf vokal (contoh: "awd", "sdf", "zxc", "ghj", "klm", "bdf")
        // Catatan: Jika panjang >= 3 dan tidak mengandung vokal sama sekali (a, i, u, e, o)
        if (strlen($clean) >= 3 && ! preg_match('/[aiueo]/i', $clean)) {
            return true;
        }

        return false;
    }

    /**
     * Sanitasi dan standardisasi struktur array hasil audit AI.
     */
    private function sanitizeAnalysisResult(array $data): array
    {
        $riskLevel = strtolower($data['risk_level'] ?? 'low');
        if (! in_array($riskLevel, ['low', 'medium', 'high'], true)) {
            $riskLevel = 'medium';
        }

        return [
            'risk_level' => $riskLevel,
            'risk_score' => (int) ($data['risk_score'] ?? match ($riskLevel) {
                'high' => 80,
                'medium' => 50,
                default => 20,
            }),
            'summary' => (string) ($data['summary'] ?? 'Analisis kelayakan anggaran selesai dilakukan.'),
            'flags' => is_array($data['flags'] ?? null) ? array_values($data['flags']) : [],
            'positive_aspects' => is_array($data['positive_aspects'] ?? null) ? array_values($data['positive_aspects']) : [],
            'admin_recommendations' => is_array($data['admin_recommendations'] ?? null) ? array_values($data['admin_recommendations']) : [],
            'mode' => 'gemini_ai',
        ];
    }
}
