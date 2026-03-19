<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ayahs', function (Blueprint $table) {
            $table->text('urdu_translation_kanzuliman')->nullable()->after('urdu_translation_ahmedali');
            $table->text('urdu_translation_maududi')->nullable()->after('urdu_translation_kanzuliman');
        });
    }

    public function down(): void
    {
        Schema::table('ayahs', function (Blueprint $table) {
            $table->dropColumn([
                'urdu_translation_kanzuliman',
                'urdu_translation_maududi',
            ]);
        });
    }
};
