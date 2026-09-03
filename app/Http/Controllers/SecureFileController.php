<?php

namespace App\Http\Controllers;

use App\Models\Disbursement;
use App\Models\ExpenseReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Satu-satunya jalan untuk membuka berkas di disk privat.
 * Setiap permintaan diperiksa: siapa yang meminta dan berkas siapa.
 */
class SecureFileController extends Controller
{
    public function identity(Request $request, User $user)
    {
        // Hanya admin, atau pemilik berkas itu sendiri.
        abort_unless($request->user()->isAdmin() || $request->user()->is($user), 403);
        abort_unless($user->identity_document_path, 404);

        return $this->stream($user->identity_document_path);
    }

    public function disbursement(Request $request, Disbursement $disbursement)
    {
        abort_unless(
            $request->user()->isAdmin() || $request->user()->id === $disbursement->requested_by,
            403
        );
        abort_unless($disbursement->supporting_document_path, 404);

        return $this->stream($disbursement->supporting_document_path);
    }

    /** Bukti nota bersifat publik — itu inti transparansinya. */
    public function receipt(ExpenseReport $expense)
    {
        abort_unless($expense->receipt_path, 404);
        abort_unless($expense->campaign->isPublished(), 404);

        return $this->stream($expense->receipt_path);
    }

    private function stream(string $path)
    {
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
