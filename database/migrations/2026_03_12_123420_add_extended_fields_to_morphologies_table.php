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
        Schema::table('morphologies', function (Blueprint $table) {
            $table->unsignedSmallInteger('segment_number')->nullable()->after('word_id');
            $table->text('raw_features')->nullable()->after('pos_tag');
            $table->string('lemma')->nullable()->after('raw_features');
            $table->string('root')->nullable()->after('lemma');
            $table->string('person')->nullable()->after('root');
            $table->string('gender')->nullable()->after('person');
            $table->string('number_type')->nullable()->after('gender');
            $table->string('case_type')->nullable()->after('number_type');
            $table->string('mood')->nullable()->after('case_type');
            $table->string('tense')->nullable()->after('mood');
            $table->string('voice')->nullable()->after('tense');
            $table->string('state')->nullable()->after('voice');
            $table->string('derived_form')->nullable()->after('state');

            $table->unique(['word_id', 'segment_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('morphologies', function (Blueprint $table) {
            $table->dropColumn([
                'segment_number',
                'raw_features',
                'lemma',
                'root',
                'person',
                'gender',
                'number_type',
                'case_type',
                'mood',
                'tense',
                'voice',
                'state',
                'derived_form',
            ]);
        });
    }
};
