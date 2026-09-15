<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->json('ai_analysis')->nullable()->after('review_note');
            $table->string('ai_risk_level', 20)->nullable()->index()->after('ai_analysis');
            $table->timestamp('ai_analyzed_at')->nullable()->after('ai_risk_level');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['ai_analysis', 'ai_risk_level', 'ai_analyzed_at']);
        });
    }
};
