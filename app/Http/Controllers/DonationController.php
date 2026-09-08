<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Donation;
use App\Services\DonationService;
use App\Services\PaymentGateway;
use Illuminate\Http\Request;

class DonationController extends Controller
{
    public function store(Request $request, Campaign $campaign, DonationService $donations)
    {
        abort_unless($campaign->isPublished(), 404);

        $data = $request->validate([
            'amount' => [
                'required', 'integer',
                'min:'.config('donasi.min_donation'),
                'max:'.config('donasi.max_donation'),
            ],
            'donor_name' => ['nullable', 'string', 'max:120'],
            'donor_email' => ['nullable', 'email', 'max:255'],
            'message' => ['nullable', 'string', 'max:500'],
            'is_anonymous' => ['nullable', 'boolean'],
        ], [], [
            'amount' => 'nominal donasi',
            'donor_name' => 'nama',
            'donor_email' => 'email',
        ]);

        $pendingReference = session('pending_donation_'.$campaign->id);
        if ($pendingReference) {
            $existing = Donation::where('reference', $pendingReference)
                ->where('campaign_id', $campaign->id)
                ->where('status', Donation::STATUS_PENDING)
                ->first();

            if ($existing && ! $existing->isExpired()) {
                return redirect()->route('donasi.checkout', $existing)
                    ->with('error', 'Selesaikan atau batalkan transaksi donasi Anda sebelumnya terlebih dahulu.');
            }
        }

        $donation = $donations->create($campaign, $data);

        session()->put('pending_donation_'.$campaign->id, $donation->reference);

        return redirect()->route('donasi.checkout', $donation);
    }

    /** Halaman pembayaran (simulasi QRIS). */
    public function checkout(Donation $donation)
    {
        $donation->load('campaign');

        if ($donation->isPaid()) {
            return redirect()->route('kuitansi.show', $donation);
        }

        // Rapikan status begitu ketahuan lewat batas, supaya daftar donasi
        // donatur tidak terus menampilkannya sebagai "menunggu pembayaran".
        if ($donation->isExpired() && $donation->status === Donation::STATUS_PENDING) {
            $donation->update(['status' => Donation::STATUS_EXPIRED]);
            $donation->refresh();
        }

        if (config('donasi.gateway') === 'midtrans' && (empty($donation->gateway_payload['snap_token']) || ! empty($donation->gateway_payload['error']))) {
            $charge = app(PaymentGateway::class)->createCharge($donation);
            $donation->gateway_payload = $charge;
            $donation->save();
        }

        return view('public.checkout', compact('donation'));
    }

    /** API Status pembayaran untuk polling otomatis (realtime auto-redirect). */
    public function status(Donation $donation, DonationService $donations)
    {
        $donation->load('campaign');

        if (! $donation->isPaid() && config('donasi.gateway') === 'midtrans') {
            try {
                \Midtrans\Config::$serverKey = (string) config('donasi.midtrans.server_key');
                \Midtrans\Config::$isProduction = (bool) config('donasi.midtrans.is_production', false);

                $orderId = $donation->gateway_payload['order_id'] ?? $donation->reference;
                $res = \Midtrans\Transaction::status($orderId);

                $status = is_object($res) ? ($res->transaction_status ?? '') : ($res['transaction_status'] ?? '');
                if (in_array($status, ['settlement', 'capture'], true)) {
                    $donations->markPaid($donation, ['sync' => (array) $res]);
                    $donation->refresh();
                    session()->forget('pending_donation_'.$donation->campaign_id);
                }
            } catch (\Throwable $e) {
                // Abaikan jika belum ada transaksi di Midtrans
            }
        }

        return response()->json([
            'status' => $donation->status,
            'is_paid' => $donation->isPaid(),
            'redirect_url' => route('kuitansi.show', $donation),
        ]);
    }

    /**
     * Tombol "saya sudah bayar" pada gateway simulasi.
     * Hanya tersedia saat gateway = mock, supaya tidak bisa dipakai
     * memalsukan pembayaran di lingkungan produksi.
     */
    public function simulatePayment(Donation $donation, DonationService $donations)
    {
        abort_unless(config('donasi.gateway') === 'mock', 404);

        if ($donation->isPaid()) {
            session()->forget('pending_donation_'.$donation->campaign_id);
            return redirect()->route('kuitansi.show', $donation);
        }

        // Halaman checkout menampilkan batas waktu; kalau server tetap menerima
        // pembayaran setelah lewat, batas waktu itu cuma hiasan.
        if ($donation->isExpired()) {
            $donation->update(['status' => Donation::STATUS_EXPIRED]);
            session()->forget('pending_donation_'.$donation->campaign_id);

            return back()->with('error',
                'Sesi pembayaran ini sudah lewat batas waktu. Silakan buat donasi baru.');
        }

        $donations->markPaid($donation, ['simulated_at' => now()->toIso8601String()]);
        session()->forget('pending_donation_'.$donation->campaign_id);

        return redirect()->route('kuitansi.show', $donation->fresh())
            ->with('status', 'Pembayaran diterima. Kuitansi digital Anda sudah terbit.');
    }

    /**
     * Batalkan transaksi donasi pending.
     */
    public function cancel(Donation $donation)
    {
        if ($donation->status === Donation::STATUS_PENDING) {
            $donation->update(['status' => Donation::STATUS_CANCELLED]);
            session()->forget('pending_donation_'.$donation->campaign_id);
        }

        return redirect()->route('kampanye.show', $donation->campaign)
            ->with('status', 'Transaksi pembayaran berhasil dibatalkan. Anda dapat membuat donasi baru sekarang.');
    }
}
