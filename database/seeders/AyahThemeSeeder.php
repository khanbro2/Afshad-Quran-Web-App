<?php

namespace Database\Seeders;

use App\Models\AyahTheme;
use Illuminate\Database\Seeder;

class AyahThemeSeeder extends Seeder
{
    public function run(): void
    {
        $themes = [
            [
                'slug' => 'fiqhi',
                'title_english' => 'Fiqhi',
                'title_urdu' => 'احکامی',
                'description' => 'Ayat related to legal rulings, commands, and practical matters of deen.',
                'badge_color' => '#0e7c66',
                'sort_order' => 1,
            ],
            [
                'slug' => 'qissa',
                'title_english' => 'Qissa',
                'title_urdu' => 'قصصی',
                'description' => 'Narrative ayat, stories, and historical accounts.',
                'badge_color' => '#cb7a00',
                'sort_order' => 2,
            ],
            [
                'slug' => 'aqaid',
                'title_english' => 'Aqaid',
                'title_urdu' => 'عقائد',
                'description' => 'Belief-centered ayat about iman, creed, and core doctrines.',
                'badge_color' => '#7048ff',
                'sort_order' => 3,
            ],
            [
                'slug' => 'akhlaq',
                'title_english' => 'Akhlaq',
                'title_urdu' => 'اخلاق',
                'description' => 'Ayat about character, manners, tazkiyah, and moral conduct.',
                'badge_color' => '#0c8a69',
                'sort_order' => 4,
            ],
            [
                'slug' => 'dua',
                'title_english' => 'Dua',
                'title_urdu' => 'دعا',
                'description' => 'Supplications, invocations, and prayer-focused ayat.',
                'badge_color' => '#1874d1',
                'sort_order' => 5,
            ],
            [
                'slug' => 'jannat-jahannam',
                'title_english' => 'Jannat / Jahannam',
                'title_urdu' => 'جنت / جہنم',
                'description' => 'Ayat about paradise, hell, reward, punishment, and akhirah outcomes.',
                'badge_color' => '#d53c63',
                'sort_order' => 6,
            ],
            [
                'slug' => 'anbiya',
                'title_english' => 'Anbiya',
                'title_urdu' => 'انبیاء',
                'description' => 'Ayat related to prophets, their missions, and lessons from their lives.',
                'badge_color' => '#9b7a47',
                'sort_order' => 7,
            ],
            [
                'slug' => 'munafiqeen',
                'title_english' => 'Munafiqeen',
                'title_urdu' => 'منافقین',
                'description' => 'Ayat that discuss hypocrisy and the traits of munafiqeen.',
                'badge_color' => '#8e5f00',
                'sort_order' => 8,
            ],
            [
                'slug' => 'tawheed',
                'title_english' => 'Tawheed',
                'title_urdu' => 'توحید',
                'description' => 'Ayat focused on Allahs oneness, rububiyyah, and uluhiyyah.',
                'badge_color' => '#0b8667',
                'sort_order' => 9,
            ],
            [
                'slug' => 'qiyamah',
                'title_english' => 'Qiyamah',
                'title_urdu' => 'قیامت',
                'description' => 'Ayat about resurrection, reckoning, and the Day of Judgment.',
                'badge_color' => '#4f63d6',
                'sort_order' => 10,
            ],
        ];

        foreach ($themes as $theme) {
            AyahTheme::updateOrCreate(
                ['slug' => $theme['slug']],
                $theme
            );
        }
    }
}
