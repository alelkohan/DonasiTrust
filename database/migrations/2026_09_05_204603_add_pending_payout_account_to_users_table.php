<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pending_bank_name', 60)->nullable()->after('bank_account_holder');
            $table->string('pending_bank_account_number', 40)->nullable()->after('pending_bank_name');
            $table->string('pending_bank_account_holder', 120)->nullable()->after('pending_bank_account_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'pending_bank_name',
                'pending_bank_account_number',
                'pending_bank_account_holder',
            ]);
        });
    }
};
