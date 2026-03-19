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
        Schema::table('words', function (Blueprint $table) {
            $table->unsignedSmallInteger('surah_number')->nullable()->after('ayah_id');
            $table->unsignedSmallInteger('ayah_number')->nullable()->after('surah_number');
            $table->unsignedSmallInteger('segment_count')->nullable()->after('position');
            $table->string('form')->nullable()->after('normalized_text');
            $table->foreignId('root_id')->nullable()->after('form')->constrained()->nullOnDelete();
            $table->foreignId('lemma_id')->nullable()->after('root_id')->constrained()->nullOnDelete();

            $table->index(['surah_number', 'ayah_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('words', function (Blueprint $table) {
            $table->dropIndex(['surah_number', 'ayah_number']);
            $table->dropForeign(['root_id']);
            $table->dropForeign(['lemma_id']);
            $table->dropColumn([
                'surah_number',
                'ayah_number',
                'segment_count',
                'form',
                'root_id',
                'lemma_id',
            ]);
        });
    }
};
