<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tafseers', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title_urdu');
            $table->string('title_english')->nullable();
            $table->string('author')->nullable();
            $table->string('language', 10)->default('ur');
            $table->string('source_name')->nullable();
            $table->string('source_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tafseers');
    }
};