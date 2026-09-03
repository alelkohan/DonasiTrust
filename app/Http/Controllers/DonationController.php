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

        if (! auth()->check() && empty($data['donor_email'])) {
            return back()->withInput()->withErrors([
                'donor_email' => 'Email diperlukan untuk mengirim kuitansi digital.',
            ]);
        }

        $donation = $donations->create($campaign, $data);

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

        return view('public.checkout', compact('donation'));
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
            return redirect()->route('kuitansi.show', $donation);
        }

        // Halaman checkout menampilkan batas waktu; kalau server tetap menerima
        // pembayaran setelah lewat, batas waktu itu cuma hiasan.
        if ($donation->isExpired()) {
            $donation->update(['status' => Donation::STATUS_EXPIRED]);

            return back()->with('error',
                'Sesi pembayaran ini sudah lewat batas waktu. Silakan buat donasi baru.');
        }

        $donations->markPaid($donation, ['simulated_at' => now()->toIso8601String()]);

        return redirect()->route('kuitansi.show', $donation->fresh())
            ->with('status', 'Pembayaran diterima. Kuitansi digital Anda sudah terbit.');
    }
}
