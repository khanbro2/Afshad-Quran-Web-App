<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ayah_tafseers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ayah_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tafseer_id')->constrained()->cascadeOnDelete();
            $table->longText('content')->nullable();
            $table->longText('content_html')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['ayah_id', 'tafseer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ayah_tafseers');
    }
};