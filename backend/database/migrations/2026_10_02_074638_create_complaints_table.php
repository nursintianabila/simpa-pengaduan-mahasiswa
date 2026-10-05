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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            $table->enum('type', [
                'pengaduan',
                'aspirasi'
            ]);

            $table->string('title');

            $table->text('description');

            $table->text('attachment')->nullable();

            $table->text('ai_summary')->nullable();

            $table->enum('ai_priority', [
                'rendah',
                'sedang',
                'tinggi'
            ])->nullable();

            $table->enum('ai_sentiment', [
                'positif',
                'netral',
                'negatif'
            ])->nullable();

            $table->enum('status', [
                'menunggu',
                'diproses',
                'selesai',
                'ditolak'
            ])->default('menunggu');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
