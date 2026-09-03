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

        $user->update([
            'verification_status' => $data['decision'],
            'verification_note' => $data['note'] ?? null,
            'verified_at' => $data['decision'] === 'verified' ? now() : null,
            'verified_by' => $request->user()->id,
        ]);

        $audit->record(
            $data['decision'] === 'verified' ? 'user.verified' : 'user.rejected',
            $user,
            ['nama' => $user->name, 'catatan' => $data['note'] ?? null]
        );

        return redirect()->route('admin.pengguna.index')
            ->with('status', 'Keputusan verifikasi tersimpan.');
    }
}
