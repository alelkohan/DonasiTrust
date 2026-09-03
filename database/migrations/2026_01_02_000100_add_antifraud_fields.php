<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tiga penutupan celah penipuan yang butuh perubahan skema.
 *
 * 1. Rekening tujuan pencairan — sebelumnya tidak tercatat sama sekali, sehingga
 *    tidak ada yang bisa membuktikan dana sampai ke pihak yang benar.
 * 2. Sidik jari (hash) berkas nota — mencegah satu nota dipakai ulang untuk
 *    beberapa pengeluaran, beberapa tahap, atau beberapa kampanye.
 * 3. Hash NIK — mencegah satu identitas dipakai banyak akun pengaju, tanpa
 *    perlu menyimpan NIK-nya sendiri di basis data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Rekening tujuan pencairan. Diisi saat verifikasi identitas dan
            // ikut diperiksa admin, supaya pengaju tidak bisa mengarahkan dana
            // ke rekening sembarangan pada saat pengajuan pencairan.
            $table->string('bank_name', 60)->nullable()->after('organization');
            $table->string('bank_account_number', 40)->nullable()->after('bank_name');
            $table->string('bank_account_holder', 120)->nullable()->after('bank_account_number');

            // HMAC-SHA256 dari NIK lengkap. NIK-nya sendiri TIDAK disimpan.
            // Unik, sehingga satu identitas hanya bisa dipakai satu akun.
            $table->char('identity_number_hash', 64)->nullable()->unique()->after('identity_number_last4');
        });

        Schema::table('disbursements', function (Blueprint $table) {
            // Salinan rekening tujuan pada saat pengajuan dibuat. Disalin, bukan
            // direlasikan, supaya catatan "ke mana dana dikirim" tetap utuh
            // walau profil pengaju berubah setelahnya.
            $table->string('payee_bank_name', 60)->nullable()->after('purpose');
            $table->string('payee_account_number', 40)->nullable()->after('payee_bank_name');
            $table->string('payee_account_holder', 120)->nullable()->after('payee_account_number');
        });

        Schema::table('expense_reports', function (Blueprint $table) {
            // SHA-256 isi berkas nota. Tidak dibuat unik di tingkat basis data
            // supaya penolakannya bisa memberi pesan yang jelas, bukan error SQL.
            $table->char('receipt_hash', 64)->nullable()->index()->after('receipt_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['identity_number_hash']);
            $table->dropColumn([
                'bank_name', 'bank_account_number', 'bank_account_holder', 'identity_number_hash',
            ]);
        });

        Schema::table('disbursements', function (Blueprint $table) {
            $table->dropColumn(['payee_bank_name', 'payee_account_number', 'payee_account_holder']);
        });

        Schema::table('expense_reports', function (Blueprint $table) {
            $table->dropIndex(['receipt_hash']);
            $table->dropColumn('receipt_hash');
        });
    }
};
