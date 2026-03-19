<?php

namespace Database\Seeders;

use App\Models\Tafseer;
use Illuminate\Database\Seeder;

class TafseerSeeder extends Seeder
{
    public function run(): void
    {
        $tafaseer = [
            [
                'slug' => 'ibnekaseer',
                'title_urdu' => 'تفسیر ابن کثیر',
                'title_english' => 'Tafsir Ibn Kathir',
                'author' => 'Ibn Kathir',
                'language' => 'ur',
                'source_name' => 'eQuranLibrary',
                'source_url' => null,
                'sort_order' => 1,
            ],
            [
                'slug' => 'tafheemulquran',
                'title_urdu' => 'تفہیم القرآن',
                'title_english' => 'Tafheem-ul-Quran',
                'author' => 'Abul Ala Maududi',
                'language' => 'ur',
                'source_name' => 'eQuranLibrary',
                'source_url' => null,
                'sort_order' => 2,
            ],
            [
                'slug' => 'maarifulquran',
                'title_urdu' => 'معارف القرآن',
                'title_english' => 'Maarif-ul-Quran',
                'author' => 'Mufti Muhammad Shafi',
                'language' => 'ur',
                'source_name' => 'eQuranLibrary',
                'source_url' => null,
                'sort_order' => 3,
            ],
            [
                'slug' => 'tafseerusmani',
                'title_urdu' => 'تفسیر عثمانی',
                'title_english' => 'Tafsir Usmani',
                'author' => 'Shabbir Ahmad Usmani',
                'language' => 'ur',
                'source_name' => 'eQuranLibrary',
                'source_url' => null,
                'sort_order' => 4,
            ],
            [
                'slug' => 'bayanulquran',
                'title_urdu' => 'بیان القرآن',
                'title_english' => 'Bayan-ul-Quran',
                'author' => 'Dr. Israr Ahmed',
                'language' => 'ur',
                'source_name' => 'Local Dataset',
                'source_url' => null,
                'sort_order' => 5,
            ],
            [
                'slug' => 'bayanulquran_scrape',
                'title_urdu' => 'بیان القرآن',
                'title_english' => 'Bayan-ul-Quran',
                'author' => 'Ashraf Ali Thanvi',
                'language' => 'ur',
                'source_name' => 'eQuranLibrary',
                'source_url' => null,
                'is_active' => false,
                'sort_order' => 6,
            ],
            [
                'slug' => 'arabic_jalalayn',
                'title_urdu' => 'تفسير الجلالين',
                'title_english' => 'Tafsir al-Jalalayn',
                'author' => 'Jalal al-Din al-Mahalli / Jalal al-Din al-Suyuti',
                'language' => 'ar',
                'source_name' => 'Local Dataset',
                'source_url' => null,
                'sort_order' => 7,
            ],
            [
                'slug' => 'al_baydawi',
                'title_urdu' => 'تفسير البيضاوي',
                'title_english' => 'Tafsir al-Baydawi',
                'author' => 'Nasir al-Din al-Baydawi',
                'language' => 'ar',
                'source_name' => 'Local Dataset',
                'source_url' => null,
                'sort_order' => 8,
            ],
            [
                'slug' => 'maarifulquran_english',
                'title_urdu' => 'Maarif-ul-Quran (English)',
                'title_english' => 'Maarif-ul-Quran',
                'author' => 'Mufti Muhammad Shafi',
                'language' => 'en',
                'source_name' => 'Local Dataset',
                'source_url' => null,
                'sort_order' => 9,
            ],
        ];

        foreach ($tafaseer as $tafseer) {
            Tafseer::updateOrCreate(
                ['slug' => $tafseer['slug']],
                $tafseer
            );
        }
    }
}
