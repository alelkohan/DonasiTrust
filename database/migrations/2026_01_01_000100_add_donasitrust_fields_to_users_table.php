<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // donatur | pengaju | admin
            $table->string('role', 20)->default('donatur')->after('email');
            $table->string('phone', 30)->nullable()->after('role');

            // Berkas identitas disimpan di disk privat, TIDAK pernah diakses publik.
            $table->string('identity_document_path')->nullable()->after('phone');
            $table->string('identity_number_last4', 4)->nullable()->after('identity_document_path');
            $table->string('organization')->nullable()->after('identity_number_last4');

            // unverified | pending | verified | rejected
            $table->string('verification_status', 20)->default('unverified')->after('organization');
            $table->text('verification_note')->nullable()->after('verification_status');
            $table->timestamp('verified_at')->nullable()->after('verification_note');
            $table->foreignId('verified_by')->nullable()->after('verified_at')
                ->constrained('users')->nullOnDelete();

            $table->index(['role', 'verification_status']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropIndex(['role', 'verification_status']);
            $table->dropColumn([
                'role', 'phone', 'identity_document_path', 'identity_number_last4',
                'organization', 'verification_status', 'verification_note',
                'verified_at', 'verified_by',
            ]);
        });
    }
};
