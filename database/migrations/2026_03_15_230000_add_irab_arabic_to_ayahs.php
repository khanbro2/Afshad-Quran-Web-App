<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ayahs', function (Blueprint $table) {
            $table->longText('irab_arabic')->nullable()->after('uthmani_text');
        });
    }

    public function down(): void
    {
        Schema::table('ayahs', function (Blueprint $table) {
            $table->dropColumn('irab_arabic');
        });
    }
};
