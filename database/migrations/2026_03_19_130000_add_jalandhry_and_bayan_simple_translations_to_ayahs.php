<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ayahs', function (Blueprint $table): void {
            $table->longText('urdu_translation_jalandhry')->nullable()->after('urdu_translation_mufti_taqi');
            $table->longText('urdu_translation_bayan_simple')->nullable()->after('urdu_translation_jalandhry');
        });
    }

    public function down(): void
    {
        Schema::table('ayahs', function (Blueprint $table): void {
            $table->dropColumn([
                'urdu_translation_jalandhry',
                'urdu_translation_bayan_simple',
            ]);
        });
    }
};
