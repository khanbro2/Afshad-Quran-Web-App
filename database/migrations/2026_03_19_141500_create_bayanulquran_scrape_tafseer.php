<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $existing = DB::table('tafseers')->where('slug', 'bayanulquran')->first();

        if ($existing) {
            DB::table('tafseers')
                ->where('slug', 'bayanulquran')
                ->update([
                    'title_urdu' => 'بیان القرآن',
                    'title_english' => 'Bayan-ul-Quran',
                    'author' => 'Dr. Israr Ahmed',
                    'language' => 'ur',
                    'source_name' => 'Local Dataset',
                    'sort_order' => 5,
                    'updated_at' => now(),
                ]);
        }

        if (! DB::table('tafseers')->where('slug', 'bayanulquran_scrape')->exists()) {
            DB::table('tafseers')->insert([
                'slug' => 'bayanulquran_scrape',
                'title_urdu' => 'بیان القرآن',
                'title_english' => 'Bayan-ul-Quran',
                'author' => 'Ashraf Ali Thanvi',
                'language' => 'ur',
                'source_name' => 'eQuranLibrary',
                'source_url' => null,
                'is_active' => false,
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('tafseers')->where('slug', 'bayanulquran_scrape')->delete();

        DB::table('tafseers')
            ->where('slug', 'bayanulquran')
            ->update([
                'title_urdu' => 'بیان القرآن',
                'title_english' => 'Bayan-ul-Quran',
                'author' => 'Ashraf Ali Thanvi',
                'language' => 'ur',
                'source_name' => 'Local Dataset',
                'sort_order' => 5,
                'updated_at' => now(),
            ]);
    }
};
