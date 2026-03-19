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
        Schema::table('ayahs', function (Blueprint $table) {
            $table->text('urdu_translation')->nullable()->after('uthmani_text');
        });

        Schema::table('words', function (Blueprint $table) {
            $table->text('translation_urdu')->nullable()->after('translation_basic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('words', function (Blueprint $table) {
            $table->dropColumn('translation_urdu');
        });

        Schema::table('ayahs', function (Blueprint $table) {
            $table->dropColumn('urdu_translation');
        });
    }
};
