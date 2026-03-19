<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_chat_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_id')->unique();
            $table->text('question');
            $table->string('language', 10)->default('ur');
            $table->longText('answer')->nullable();
            $table->string('status', 30)->default('success');
            $table->text('error_message')->nullable();
            $table->unsignedInteger('matched_ayah_count')->default(0);
            $table->unsignedInteger('matched_word_count')->default(0);
            $table->unsignedInteger('matched_theme_count')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('status');
            $table->index('language');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_chat_logs');
    }
};
