<?php

namespace Database\Seeders;

use App\Models\BroadTheme;
use Illuminate\Database\Seeder;

class BroadThemeSeeder extends Seeder
{
    public function run(): void
    {
        $themes = [
            ['slug' => 'fiqhi', 'title_english' => 'Fiqhi', 'title_urdu' => $this->u('\u0627\u062d\u06a9\u0627\u0645\u06cc'), 'badge_color' => '#0e7c66', 'sort_order' => 1, 'description' => 'Legal rulings, commands, worship laws, and practical Ahkam.'],
            ['slug' => 'qissa', 'title_english' => 'Qissa', 'title_urdu' => $this->u('\u0642\u0635\u0635\u06cc'), 'badge_color' => '#cb7a00', 'sort_order' => 2, 'description' => 'Narratives, stories, past nations, and historical accounts.'],
            ['slug' => 'aqaid', 'title_english' => 'Aqaid', 'title_urdu' => $this->u('\u0639\u0642\u0627\u0626\u062f'), 'badge_color' => '#7048ff', 'sort_order' => 3, 'description' => 'Belief, iman, revelation, unseen, and creed-related topics.'],
            ['slug' => 'akhlaq', 'title_english' => 'Akhlaq', 'title_urdu' => $this->u('\u0627\u062e\u0644\u0627\u0642'), 'badge_color' => '#0c8a69', 'sort_order' => 4, 'description' => 'Character, manners, patience, justice, and moral conduct.'],
            ['slug' => 'dua', 'title_english' => 'Dua', 'title_urdu' => $this->u('\u062f\u0639\u0627'), 'badge_color' => '#1874d1', 'sort_order' => 5, 'description' => 'Supplications, prayers, and invocations.'],
            ['slug' => 'jannat-jahannam', 'title_english' => 'Jannat / Jahannam', 'title_urdu' => $this->u('\u062c\u0646\u062a / \u062c\u06c1\u0646\u0645'), 'badge_color' => '#d53c63', 'sort_order' => 6, 'description' => 'Paradise, Hell, reward, and punishment outcomes.'],
            ['slug' => 'anbiya', 'title_english' => 'Anbiya', 'title_urdu' => $this->u('\u0627\u0646\u0628\u06cc\u0627\u0621'), 'badge_color' => '#9b7a47', 'sort_order' => 7, 'description' => 'Prophets, their missions, and lessons from their lives.'],
            ['slug' => 'munafiqeen', 'title_english' => 'Munafiqeen', 'title_urdu' => $this->u('\u0645\u0646\u0627\u0641\u0642\u06cc\u0646'), 'badge_color' => '#8e5f00', 'sort_order' => 8, 'description' => 'Hypocrisy and the traits of munafiqeen.'],
            ['slug' => 'tawheed', 'title_english' => 'Tawheed', 'title_urdu' => $this->u('\u062a\u0648\u062d\u06cc\u062f'), 'badge_color' => '#0b8667', 'sort_order' => 9, 'description' => 'Oneness of Allah, worship, shirk, and divine attributes.'],
            ['slug' => 'qiyamah', 'title_english' => 'Qiyamah', 'title_urdu' => $this->u('\u0642\u06cc\u0627\u0645\u062a'), 'badge_color' => '#4f63d6', 'sort_order' => 10, 'description' => 'Resurrection, the Day of Judgment, and accountability.'],
        ];

        foreach ($themes as $theme) {
            BroadTheme::query()->updateOrCreate(
                ['slug' => $theme['slug']],
                $theme + ['is_active' => true]
            );
        }
    }

    protected function u(string $value): string
    {
        return json_decode('"' . $value . '"', true, 512, JSON_THROW_ON_ERROR);
    }
}
