<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class UserVerificationController extends Controller
{
    public function index(Request $request)
    {
        $users = User::when($request->filled('status'),
            fn ($q) => $q->where('verification_status', $request->string('status')),
            fn ($q) => $q->where('verification_status', User::VERIFICATION_PENDING))
            ->where('role', '!=', User::ROLE_ADMIN)
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->loadCount('campaigns');

        return view('admin.users.show', compact('user'));
    }

    public function decide(Request $request, User $user, AuditLogger $audit)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:verified,rejected'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($data['decision'] === 'rejected' && empty($data['note'])) {
            return back()->withErrors(['note' => 'Alasan penolakan wajib diisi.']);
        }

        $updateData = [
            'verification_note' => $data['note'] ?? null,
            'verified_by' => $request->user()->id,
        ];

        if ($data['decision'] === 'verified') {
            $updateData['verification_status'] = User::VERIFICATION_VERIFIED;
            $updateData['verified_at'] = now();

            // Akun Terpadu: Jika pengguna saat ini terdaftar sebagai donatur,
            // otomatis tingkatkan peran menjadi pengaju agar bisa langsung membuat kampanye.
            if ($user->isDonatur()) {
                $updateData['role'] = User::ROLE_PENGAJU;
            }

            // Jika ada pengajuan rekening baru yang sedang ditinjau, setujui dan jadikan rekening aktif
            if ($user->hasPendingPayoutAccount()) {
                $updateData['bank_name'] = $user->pending_bank_name;
                $updateData['bank_account_number'] = $user->pending_bank_account_number;
                $updateData['bank_account_holder'] = $user->pending_bank_account_holder;
                $updateData['pending_bank_name'] = null;
                $updateData['pending_bank_account_number'] = null;
                $updateData['pending_bank_account_holder'] = null;
            }
        } else {
            // Keputusan ditolak:
            // Jika pengguna sudah memiliki rekening aktif sebelumnya (pernah terverifikasi),
            // batalkan pengajuan rekening baru dan kembalikan ke rekening sebelumnya serta status kembali verified.
            if ($user->hasPendingPayoutAccount() && $user->hasPayoutAccount()) {
                $updateData['pending_bank_name'] = null;
                $updateData['pending_bank_account_number'] = null;
                $updateData['pending_bank_account_holder'] = null;
                $updateData['verification_status'] = User::VERIFICATION_VERIFIED;
            } else {
                // Pengajuan verifikasi identitas pertama kali yang ditolak
                $updateData['verification_status'] = User::VERIFICATION_REJECTED;
                $updateData['verified_at'] = null;
            }
        }

        $user->update($updateData);

        $audit->record(
            $data['decision'] === 'verified' ? 'user.verified' : 'user.rejected',
            $user,
            ['nama' => $user->name, 'catatan' => $data['note'] ?? null]
        );

        return redirect()->route('admin.pengguna.index')
            ->with('status', 'Keputusan verifikasi tersimpan.');
    }
}
