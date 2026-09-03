<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Services\ReceiptVerifier;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function show(Donation $donation, ReceiptVerifier $verifier)
    {
        $donation->load('campaign');

        return view('public.receipt', [
            'donation' => $donation,
            'shortCode' => $verifier->shortCode($donation),
            'fullCode' => $verifier->for($donation),
        ]);
    }

    /** Form verifikasi mandiri untuk siapa pun, tanpa perlu akun. */
    public function form()
    {
        return view('public.verify');
    }

    public function check(Request $request, ReceiptVerifier $verifier)
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:40'],
            'code' => ['required', 'string', 'max:64'],
        ], [], [
            'reference' => 'nomor transaksi',
            'code' => 'kode verifikasi',
        ]);

        $donation = Donation::with('campaign')
            ->where('reference', strtoupper(trim($data['reference'])))
            ->first();

        $expected = $donation ? $verifier->for($donation) : null;
        $submitted = strtolower(trim($data['code']));

        // Terima kode pendek (16 karakter pertama) maupun kode penuh.
        $valid = $donation && $expected && (
            hash_equals($expected, $submitted)
            || hash_equals(substr($expected, 0, 16), substr($submitted, 0, 16))
        ) && strlen($submitted) >= 16;

        return view('public.verify', [
            'result' => [
                'valid' => (bool) $valid,
                'donation' => $valid ? $donation : null,
                'reference' => $data['reference'],
            ],
        ]);
    }
}
