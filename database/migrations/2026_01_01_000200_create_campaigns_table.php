<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category', 50)->index();
            $table->string('cover_path')->nullable();
            $table->text('summary');
            $table->longText('description');

            // Nominal disimpan dalam RUPIAH PENUH sebagai integer (bukan float),
            // supaya tidak ada galat pembulatan pada agregasi ledger.
            $table->unsignedBigInteger('target_amount');
            $table->unsignedBigInteger('collected_amount')->default(0);
            $table->unsignedBigInteger('disbursed_amount')->default(0);

            $table->date('deadline')->nullable();

            // draft | pending | approved | rejected | completed
            $table->string('status', 20)->default('draft')->index();
            $table->text('review_note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
