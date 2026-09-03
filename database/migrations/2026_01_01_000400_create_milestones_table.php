<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Tahapan pencairan dana. Dana tidak cair sekaligus. */
    public function up(): void
    {
        Schema::create('milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('amount');

            // locked | available | requested | approved | disbursed | reported
            $table->string('status', 20)->default('locked')->index();
            $table->timestamps();

            $table->unique(['campaign_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestones');
    }
};
