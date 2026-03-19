<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ayah_theme_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ayah_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ayah_theme_id')->constrained('ayah_themes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['ayah_id', 'ayah_theme_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ayah_theme_assignments');
    }
};
