<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ayahs', function (Blueprint $table) {
            $table->text('urdu_translation_mufti_taqi')->nullable()->after('urdu_translation_maududi');
            $table->text('english_translation_mufti_taqi')->nullable()->after('urdu_translation_mufti_taqi');
        });
    }

    public function down(): void
    {
        Schema::table('ayahs', function (Blueprint $table) {
            $table->dropColumn([
                'urdu_translation_mufti_taqi',
                'english_translation_mufti_taqi',
            ]);
        });
    }
};