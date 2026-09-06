<?php

namespace App\Http\Controllers;

use App\Models\EmailOtp;
use App\Models\User;
use App\Notifications\PayoutAccountChanged;
use App\Services\AuditLogger;
use App\Services\OtpService;
use App\Services\TotpGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request, AuditLogger $audit)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'organization' => ['nullable', 'string', 'max:150'],
        ]);

        $user->update($data);

        return back()->with('status', 'Profil diperbarui.');
    }

    public function updatePassword(Request $request, OtpService $otp)
    {
        $hasPassword = (bool) $request->user()->password;

        $request->validate([
            'current_password' => [$hasPassword ? 'required' : 'nullable', $hasPassword ? 'current_password' : 'nullable'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'otp_code' => ['required', 'string'],
        ], [
            'otp_code.required' => 'Kode verifikasi email wajib diisi.',
        ]);

        $otp->assertValid($request->user(), EmailOtp::PURPOSE_PASSWORD_CHANGE, $request->input('otp_code'));

        $request->user()->update(['password' => $request->input('password')]);

        return back()->with('status', 'Kata sandi diperbarui.');
    }

    /** Unggah dokumen identitas untuk diverifikasi manual oleh admin. */
    public function submitVerification(Request $request, AuditLogger $audit, TotpGuard $totp, OtpService $otp)
    {
        $user = $request->user();

        $punyaIdentitasTersimpan = ! empty($user->identity_document_path) && ! empty($user->identity_number_hash);

        $data = $request->validate([
            'organization' => ['nullable', 'string', 'max:150'],
            'identity_number' => [$punyaIdentitasTersimpan ? 'nullable' : 'required', 'digits:16'],
            'identity_document' => [
                $punyaIdentitasTersimpan ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png,pdf',
                'max:'.config('donasi.max_upload_kb'),
            ],
            // Rekening tujuan pencairan diperiksa admin bersamaan dengan KTP.
            // Pengaju TIDAK bisa menentukan rekening tujuan saat mengajukan
            // pencairan — dana hanya bisa mengalir ke rekening yang sudah
            // diverifikasi di sini.
            'bank_name' => ['required', 'string', 'max:60'],
            'bank_account_number' => ['required', 'string', 'max:40', 'regex:/^[0-9 -]+$/'],
            'bank_account_holder' => ['required', 'string', 'max:120'],
        ], [
            'bank_account_number.regex' => 'Nomor rekening hanya boleh berisi angka.',
        ], [
            'identity_number' => 'nomor identitas',
            'identity_document' => 'dokumen identitas',
            'bank_name' => 'nama bank',
            'bank_account_number' => 'nomor rekening',
            'bank_account_holder' => 'nama pemilik rekening',
        ]);

        // Nomor rekening dinormalkan lebih dulu supaya "3374-0100" dan
        // "33740100" tidak terbaca sebagai perubahan.
        $rekeningBaru = preg_replace('/[^0-9]/', '', $data['bank_account_number']);

        $gantiRekening = $user->hasPayoutAccount() && (
            $rekeningBaru !== (string) $user->bank_account_number
            || $data['bank_name'] !== $user->bank_name
            || $data['bank_account_holder'] !== $user->bank_account_holder
        );

        /*
         * INI titik serang yang sesungguhnya pada model "rekening terkunci".
         *
         * Mencairkan dana ke rekening orang lain memang mustahil — tujuannya
         * disalin dari profil terverifikasi. Maka penyerang tidak menyerang
         * pencairannya, melainkan HALAMAN INI: bajak akun, ganti nomor rekening
         * ke miliknya, lampirkan KTP palsu, tunggu admin meloloskan, baru
         * cairkan. Satu-satunya penghalang sesudah itu cuma ketelitian admin.
         *
         * Maka faktor kedua diminta di sini, bukan di tiap pengajuan pencairan.
         * Mengganti rekening adalah aksi sekali seumur akun, jadi frictionnya
         * terbayar — persis pola bank dan e-wallet, yang meminta OTP saat
         * MENAMBAH rekening tujuan, bukan tiap kali menarik dana.
         *
         * Pendaftaran rekening PERTAMA tidak diminta kode: belum ada apa pun
         * yang bisa dicuri, dan memintanya di situ malah mengunci pengguna baru
         * di luar alur verifikasinya sendiri.
         */
        if ($gantiRekening) {
            if ($request->filled('otp_code')) {
                $otp->assertValid($user, EmailOtp::PURPOSE_BANK_CHANGE, $request->input('otp_code'));
            } elseif ($user->hasTwoFactorEnabled() && $request->filled('totp_code')) {
                $totp->assertValid($user, $request->input('totp_code'), 'profile.payout_account_changed');
            } else {
                throw ValidationException::withMessages([
                    'otp_code' => 'Kode verifikasi email wajib diisi untuk mengubah rekening tujuan pencairan.',
                ]);
            }
        }

        // Periksa duplikat NIK jika NIK baru diisi
        $nikHash = $user->identity_number_hash;
        $nikLast4 = $user->identity_number_last4;
        if (! empty($data['identity_number'])) {
            $nikHash = User::hashIdentityNumber($data['identity_number']);
            $nikLast4 = substr($data['identity_number'], -4);

            $dipakaiAkunLain = User::where('identity_number_hash', $nikHash)
                ->whereKeyNot($user->id)
                ->exists();

            if ($dipakaiAkunLain) {
                throw ValidationException::withMessages([
                    'identity_number' => 'Nomor identitas ini sudah terdaftar pada akun lain. '
                        .'Satu KTP hanya bisa dipakai untuk satu akun pengaju.',
                ]);
            }
        }

        // Simpan dokumen baru jika diunggah
        $path = $user->identity_document_path;
        if ($request->hasFile('identity_document')) {
            $pathBaru = $request->file('identity_document')
                ->store('identitas/'.$user->id, 'local');

            if ($path && Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }
            $path = $pathBaru;
        }

        $updateData = [
            'organization' => $data['organization'] ?? $user->organization,
            'identity_document_path' => $path,
            'identity_number_last4' => $nikLast4,
            'identity_number_hash' => $nikHash,
            'verification_status' => User::VERIFICATION_PENDING,
            'verification_note' => null,
        ];

        if ($gantiRekening) {
            // Simpan rekening baru ke kolom antrean review (pending).
            // Rekening aktif yang lama tetap tersimpan sebagai cadangan dan
            // akan dipulihkan otomatis jika pengajuan perubahan ditolak admin.
            $updateData['pending_bank_name'] = $data['bank_name'];
            $updateData['pending_bank_account_number'] = $rekeningBaru;
            $updateData['pending_bank_account_holder'] = $data['bank_account_holder'];
        } else {
            // Pendaftaran rekening pertama kali
            $updateData['bank_name'] = $data['bank_name'];
            $updateData['bank_account_number'] = $rekeningBaru;
            $updateData['bank_account_holder'] = $data['bank_account_holder'];
            $updateData['pending_bank_name'] = null;
            $updateData['pending_bank_account_number'] = null;
            $updateData['pending_bank_account_holder'] = null;
        }

        $user->update($updateData);

        $audit->record('user.verification_submitted', $user, [
            'nama' => $user->name,
            'ganti_rekening' => $gantiRekening,
        ]);

        if ($gantiRekening) {
            $audit->record('user.payout_account_changed', $user, [
                'rekening_baru' => $user->fresh()->maskedPendingPayoutAccount(),
                'dua_langkah' => $user->hasTwoFactorEnabled(),
            ]);

            // Pemberitahuan ke pemilik akun. Kalau perubahan ini BUKAN dia yang
            // melakukan, inilah satu-satunya kesempatan dia tahu sebelum admin
            // meloloskannya. Dikirim setelah data tersimpan supaya isinya
            // mencerminkan keadaan yang sebenarnya.
            $user->notify(new PayoutAccountChanged($user->fresh()->maskedPendingPayoutAccount()));
        }

        return back()->with('status', 'Dokumen terkirim. Admin akan meninjau dalam 1x24 jam.');
    }
}
