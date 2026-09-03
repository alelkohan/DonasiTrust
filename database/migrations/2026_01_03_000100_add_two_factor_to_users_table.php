<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Verifikasi dua langkah (TOTP) untuk aksi yang memindahkan uang.
 *
 * Kata sandi saja tidak cukup untuk melepas dana: kata sandi bisa bocor lewat
 * phishing, dipakai ulang dari situs lain, atau sesi loginnya dibajak. Kode
 * TOTP hanya ada di ponsel pemiliknya dan berganti tiap 30 detik.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Disimpan TERENKRIPSI lewat cast 'encrypted' di model User (kunci
            // APP_KEY). Siapa pun yang cuma memegang dump basis data — hasil
            // backup bocor, misalnya — tidak bisa membangkitkan kode yang sah.
            $table->text('totp_secret')->nullable()->after('password');

            // Baru terisi setelah pengguna membuktikan aplikasinya benar-benar
            // berhasil membaca kunci. Kunci yang sudah dibuat tapi belum
            // dikonfirmasi TIDAK dianggap aktif — kalau dianggap aktif, orang
            // bisa terkunci dari akunnya sendiri karena salah pindai QR.
            $table->timestamp('totp_confirmed_at')->nullable()->after('totp_secret');

            // Selang waktu terakhir yang sudah terpakai. Sebuah kode hanya
            // berlaku sekali: tanpa ini, kode yang terbaca dari balik bahu
            // (atau tertinggal di riwayat proksi) masih bisa dipakai ulang
            // selama 30 detik jendelanya belum lewat.
            $table->unsignedBigInteger('totp_last_timestep')->nullable()->after('totp_confirmed_at');

            // Kode pemulihan sekali pakai, juga terenkripsi. Tanpa ini, ponsel
            // yang hilang berarti dana kampanye terkunci selamanya.
            $table->text('totp_recovery_codes')->nullable()->after('totp_last_timestep');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'totp_secret',
                'totp_confirmed_at',
                'totp_last_timestep',
                'totp_recovery_codes',
            ]);
        });
    }
};
