<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jejak audit ber-rantai-hash (hash chain).
     *
     * current_hash = sha256(previous_hash + payload_kanonik)
     *
     * Mengubah satu baris lama akan memutus rantai pada SEMUA baris sesudahnya,
     * jadi manipulasi diam-diam bisa terdeteksi (tamper-evident).
     * Catatan jujur: ini tidak MENCEGAH perubahan oleh pihak dengan akses DB
     * penuh yang menghitung ulang seluruh rantai; ia hanya membuat perubahan
     * parsial terdeteksi. Jaminan lebih kuat butuh publikasi hash ke pihak luar.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_label')->nullable();
            $table->string('action', 80)->index();
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();

            $table->char('previous_hash', 64)->nullable();
            $table->char('current_hash', 64)->unique();

            $table->timestamp('created_at')->nullable();

            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
