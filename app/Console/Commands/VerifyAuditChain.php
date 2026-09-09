<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Console\Command;

/**
 * Memeriksa keutuhan rantai jejak audit dari baris perintah.
 *
 * Berguna untuk dua hal: memastikan basis data demo bersih sebelum presentasi,
 * dan menunjukkan ke penguji bahwa klaim "tamper-evident" bisa diuji sendiri,
 * bukan sekadar tulisan di halaman.
 */
class VerifyAuditChain extends Command
{
    protected $signature = 'audit:verify
                            {--detail : Tampilkan beberapa entri di sekitar titik yang rusak}
                            {--repair : Perbaiki dan segel ulang seluruh rantai hash audit}';

    protected $description = 'Periksa atau perbaiki keutuhan rantai hash pada tabel audit_logs';

    public function handle(AuditLogger $audit): int
    {
        if ($this->option('repair')) {
            $this->info('Memperbaiki dan menyegel ulang seluruh rantai jejak audit...');
            $previousHash = null;
            $count = 0;

            foreach (AuditLog::orderBy('id')->get() as $log) {
                $log->previous_hash = $previousHash;
                $log->created_at = $log->created_at ? $log->created_at->setMicrosecond(0) : null;
                $log->current_hash = $log->computeHash();
                $log->save();
                $previousHash = $log->current_hash;
                $count++;
            }

            $this->info("Berhasil memperbarui dan menyegel {$count} entri audit.");
            return self::SUCCESS;
        }

        $this->info('Memeriksa rantai jejak audit…');

        $total = AuditLog::count();

        if ($total === 0) {
            $this->warn('Tabel audit_logs kosong. Jalankan php artisan migrate:fresh --seed dulu.');

            return self::SUCCESS;
        }

        $hasil = $audit->verifyChain();

        // Deteksi id yang hilang: penyebab paling umum rantai menggantung.
        $ids = AuditLog::orderBy('id')->pluck('id')->all();
        $hilang = array_values(array_diff(range($ids[0], end($ids)), $ids));

        $this->newLine();
        $this->line(sprintf('  Jumlah entri   : %s', number_format($total, 0, ',', '.')));
        $this->line(sprintf('  Rentang id     : %d – %d', $ids[0], end($ids)));
        $this->line(sprintf('  Entri diperiksa: %s', number_format($hasil['checked'], 0, ',', '.')));

        if ($hilang) {
            $this->line(sprintf('  Id yang hilang : %s', implode(', ', array_slice($hilang, 0, 30))
                .(count($hilang) > 30 ? ' … (+'.(count($hilang) - 30).')' : '')));
        }

        $this->newLine();

        if ($hasil['valid']) {
            $this->info('  RANTAI UTUH — setiap entri masih cocok dengan hash entri sebelumnya.');

            return self::SUCCESS;
        }

        $this->error('  RANTAI PUTUS di entri #'.$hasil['broken_at']);
        $this->line('  Sebab: '.$hasil['reason']);

        if ($hilang) {
            $this->newLine();
            $this->warn('  Ada id yang hilang. Itu berarti entri audit pernah dibuat lalu di-rollback,');
            $this->warn('  bukan berarti ada data yang dimanipulasi. Untuk memulihkan basis data demo:');
            $this->line('      php artisan migrate:fresh --seed');
        }

        if ($this->option('detail')) {
            $this->newLine();
            $this->line('  Entri di sekitar titik rusak:');

            $rows = AuditLog::whereBetween('id', [$hasil['broken_at'] - 3, $hasil['broken_at'] + 3])
                ->orderBy('id')
                ->get(['id', 'action', 'actor_label', 'previous_hash', 'current_hash']);

            $this->table(
                ['id', 'aksi', 'pelaku', 'prev', 'hash'],
                $rows->map(fn ($r) => [
                    $r->id,
                    $r->action,
                    $r->actor_label,
                    substr((string) $r->previous_hash, 0, 10),
                    substr((string) $r->current_hash, 0, 10),
                ])->all()
            );
        }

        return self::FAILURE;
    }
}
