<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Menulis jejak audit ber-rantai-hash.
 *
 * Setiap entri menyimpan hash entri sebelumnya, sehingga mengedit satu baris
 * lama akan membuat seluruh rantai sesudahnya tidak cocok saat diverifikasi.
 *
 * PENTING — kenapa penulisan ditunda sampai commit:
 * Entri audit TIDAK boleh ditulis di dalam transaksi pemanggil. Kalau operasi
 * bisnisnya di-rollback, baris auditnya ikut hilang tapi id AUTOINCREMENT-nya
 * sudah terpakai, sementara entri berikutnya sudah terlanjur menyimpan
 * previous_hash milik baris yang lenyap itu. Rantainya jadi menggantung
 * permanen dan verifikasi melaporkan "putus" padahal tidak ada manipulasi.
 * Itu persis yang terjadi pada basis data demo 25 Agustus 2026 (id 62-73 hilang).
 *
 * Maka: bila sedang ada transaksi aktif, penulisan didaftarkan lewat
 * DB::afterCommit() sehingga hanya berjalan kalau operasinya benar-benar jadi.
 */
class AuditLogger
{
    /**
     * Catat satu peristiwa.
     *
     * Mengembalikan AuditLog bila langsung ditulis, atau null bila penulisannya
     * ditunda sampai transaksi pemanggil commit. Jangan mengandalkan nilai balik
     * ini di dalam blok DB::transaction().
     */
    public function record(
        string $action,
        ?Model $subject = null,
        array $metadata = [],
        ?User $actor = null,
    ): ?AuditLog {
        $actor ??= auth()->user();

        // Semua konteks ditangkap SEKARANG, bukan saat commit — supaya waktu,
        // pelaku, dan IP-nya mencerminkan saat peristiwa terjadi.
        $entry = [
            'user_id' => $actor?->id,
            'actor_label' => $actor?->name ?? 'Sistem',
            'action' => $action,
            'auditable_type' => $subject ? $subject::class : null,
            'auditable_id' => $subject?->getKey(),
            'metadata' => $metadata ?: null,
            'ip_address' => request()->ip(),
            'occurred_at' => now(),
        ];

        // Pengecualian untuk pengujian: RefreshDatabase membungkus tiap test
        // dalam satu transaksi yang memang tidak pernah di-commit, jadi kalau
        // ditunda entri auditnya tidak akan pernah tertulis. Di dalam test
        // seluruh basis data di-rollback bersama, sehingga tidak ada rantai
        // yang bisa menggantung.
        if (DB::transactionLevel() > 0 && ! app()->runningUnitTests()) {
            DB::afterCommit(fn () => $this->write($entry));

            return null;
        }

        return $this->write($entry);
    }

    /** Penulisan sebenarnya, dalam transaksinya sendiri. */
    private function write(array $entry): AuditLog
    {
        return DB::transaction(function () use ($entry) {
            // lockForUpdate mencegah dua permintaan bersamaan membaca "entri
            // terakhir" yang sama dan menghasilkan rantai bercabang.
            $previous = AuditLog::query()->orderByDesc('id')->lockForUpdate()->first();

            $log = new AuditLog([
                'user_id' => $entry['user_id'],
                'actor_label' => $entry['actor_label'],
                'action' => $entry['action'],
                'auditable_type' => $entry['auditable_type'],
                'auditable_id' => $entry['auditable_id'],
                'metadata' => $entry['metadata'],
                'ip_address' => $entry['ip_address'],
                'previous_hash' => $previous?->current_hash,
            ]);

            $log->created_at = $entry['occurred_at'];
            // Hash sementara supaya kolom unik tidak bentrok saat insert pertama.
            $log->current_hash = hash('sha256', uniqid('tmp', true));
            $log->save();

            // Sekarang id sudah ada, hitung hash final atas payload lengkap.
            $log->current_hash = $log->computeHash();
            $log->save();

            return $log;
        });
    }

    /**
     * Telusuri seluruh rantai dan laporkan entri pertama yang rusak.
     *
     * @return array{valid: bool, checked: int, broken_at: ?int, reason: ?string}
     */
    public function verifyChain(): array
    {
        $checked = 0;
        $expectedPrevious = null;
        $broken = null;
        $reason = null;

        AuditLog::query()->orderBy('id')->chunk(200, function ($logs) use (&$checked, &$expectedPrevious, &$broken, &$reason) {
            foreach ($logs as $log) {
                $checked++;

                if ($log->previous_hash !== $expectedPrevious) {
                    $broken = $log->id;
                    $reason = 'Tautan ke entri sebelumnya tidak cocok — ada entri yang hilang atau disisipkan.';

                    return false;
                }

                if (! hash_equals($log->computeHash(), (string) $log->current_hash)) {
                    $broken = $log->id;
                    $reason = 'Isi entri berubah setelah dicatat.';

                    return false;
                }

                $expectedPrevious = $log->current_hash;
            }

            return true;
        });

        return [
            'valid' => $broken === null,
            'checked' => $checked,
            'broken_at' => $broken,
            'reason' => $reason,
        ];
    }
}
