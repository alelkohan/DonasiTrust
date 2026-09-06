<?php

namespace App\Http\Controllers;

use App\Models\EmailOtp;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OtpController extends Controller
{
    public function send(Request $request, OtpService $otpService)
    {
        $data = $request->validate([
            'purpose' => ['required', 'string', Rule::in([
                EmailOtp::PURPOSE_DISBURSEMENT_REQUEST,
                EmailOtp::PURPOSE_BANK_CHANGE,
                EmailOtp::PURPOSE_DISBURSEMENT_RELEASE,
                EmailOtp::PURPOSE_PASSWORD_CHANGE,
            ])],
        ]);

        $user = $request->user();

        // Admin release purpose hanya boleh oleh admin
        if ($data['purpose'] === EmailOtp::PURPOSE_DISBURSEMENT_RELEASE && ! $user->isAdmin()) {
            abort(403);
        }

        $otpService->generateAndSend($user, $data['purpose']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Kode verifikasi telah dikirim ke email ' . $user->email,
            ]);
        }

        return back()->with('status', 'Kode verifikasi telah dikirim ke email ' . $user->email . '. Periksa kotak masuk atau spam.');
    }
}
