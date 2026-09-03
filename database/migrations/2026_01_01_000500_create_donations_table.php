<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();

            // Nomor transaksi publik, mis. DT-2026-000042
            $table->string('reference', 40)->unique();

            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            // Null = donasi tanpa akun (tetap diizinkan sesuai alur kerja).
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('donor_name')->nullable();
            $table->string('donor_email')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->text('message')->nullable();

            $table->unsignedBigInteger('amount');

            // pending | paid | failed | expired
            $table->string('status', 20)->default('pending')->index();

            $table->string('payment_channel', 30)->default('qris');
            $table->string('gateway', 30)->default('mock');
            $table->string('gateway_reference')->nullable();
            $table->json('gateway_payload')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Kode verifikasi kuitansi = HMAC-SHA256(referensi|jumlah|kampanye, APP secret).
            // Ini MAC, bukan tanda tangan digital: membuktikan kuitansi diterbitkan
            // oleh server ini, bukan identitas penandatangan.
            $table->string('verification_code', 64)->index();

            $table->timestamps();
            $table->index(['campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
