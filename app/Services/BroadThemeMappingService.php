<?php

namespace App\Services;

use App\Models\AyahTheme;
use App\Models\BroadTheme;
use Illuminate\Support\Facades\DB;

class BroadThemeMappingService
{
    public function remapAyahs(bool $reset = true): array
    {
        $broadThemeIds = BroadTheme::query()
            ->where('is_active', true)
            ->pluck('id', 'slug')
            ->all();

        if ($reset) {
            DB::table('ayah_broad_theme_assignments')->delete();
        }

        $stats = [
            'themes_checked' => 0,
            'exact_themes_matched' => 0,
            'ayahs_tagged' => 0,
            'assignments_created' => 0,
        ];

        AyahTheme::query()
            ->with('ayahs:id')
            ->chunkById(100, function ($themes) use (&$stats, $broadThemeIds) {
                foreach ($themes as $theme) {
                    $stats['themes_checked']++;
                    $matchedSlugs = $this->matchBroadThemeSlugs($theme);

                    if ($matchedSlugs === []) {
                        continue;
                    }

                    $stats['exact_themes_matched']++;
                    $rows = [];

                    foreach ($theme->ayahs as $ayah) {
                        foreach ($matchedSlugs as $slug) {
                            $broadThemeId = $broadThemeIds[$slug] ?? null;

                            if (! $broadThemeId) {
                                continue;
                            }

                            $rows[] = [
                                'ayah_id' => $ayah->id,
                                'broad_theme_id' => $broadThemeId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }

                        $stats['ayahs_tagged']++;
                    }

                    if ($rows !== []) {
                        $stats['assignments_created'] += DB::table('ayah_broad_theme_assignments')->insertOrIgnore($rows);
                    }
                }
            });

        return $stats;
    }

    public function matchBroadThemeSlugs(AyahTheme $theme): array
    {
        $haystack = $this->normalize($theme->title_english);
        $matched = [];

        foreach ($this->rules() as $slug => $rule) {
            if ($this->matchesRule($haystack, $rule)) {
                $matched[] = $slug;
            }
        }

        return $matched;
    }

    protected function rules(): array
    {
        return [
            'fiqhi' => [
                'any' => [
                    'marriage',
                    'divorce',
                    'inheritance',
                    'law',
                    'fasting',
                    'prayer',
                    'zakat',
                    'hajj',
                    'contracts',
                    'contract',
                    'witness',
                    'witnesses',
                    'ruling',
                    'duties',
                    'obligations',
                    'duties and obligations',
                    'rights',
                    'responsibility',
                    'responsibilities',
                    'islamic state',
                    'order of exile',
                    'exile',
                ],
            ],
            'qissa' => [
                'any' => ['story', 'parable', 'example', 'account of', 'deliverance', 'miracle', 'pharaoh', 'israelites', 'companions of'],
            ],
            'aqaid' => [
                'any' => ['faith', 'belief', 'disbeliever', 'disbelievers', 'angels', 'scripture', 'revelation', 'quran', 'guidance', 'real believers'],
            ],
            'akhlaq' => [
                'any' => ['patience', 'gratitude', 'forgiveness', 'justice', 'kindness', 'character', 'moral', 'advise others', 'disobedience', 'transgression'],
            ],
            'dua' => [
                'any' => ['dua', 'supplication', 'invoke', 'call upon', 'pray to allah'],
            ],
            'jannat-jahannam' => [
                'any' => ['paradise', 'garden', 'hell', 'fire', 'punishment', 'reward', 'jannah', 'jahannam'],
            ],
            'anbiya' => [
                'any' => ['adam', 'nuh', 'ibrahim', 'musa', 'isa', 'yusuf', 'yunus', 'prophet', 'messenger'],
            ],
            'munafiqeen' => [
                'any' => ['hypocrite', 'hypocrisy', 'munafiq'],
            ],
            'tawheed' => [
                'any' => ['allah', 'worship allah', 'oneness', 'creator', 'lord', 'shirk', 'idolatry', 'associate partners'],
                'exclude_if_only' => ['allah'],
            ],
            'qiyamah' => [
                'any' => ['day of judgment', 'day of judgement', 'resurrection', 'hereafter', 'reckoning', 'last day', 'trumpet'],
            ],
        ];
    }

    protected function matchesRule(string $haystack, array $rule): bool
    {
        $matches = [];

        foreach ($rule['any'] ?? [] as $keyword) {
            if ($this->containsKeyword($haystack, $keyword)) {
                $matches[] = $keyword;
            }
        }

        if ($matches === []) {
            return false;
        }

        if (isset($rule['exclude_if_only']) && count($matches) === 1 && in_array($matches[0], $rule['exclude_if_only'], true)) {
            return false;
        }

        return true;
    }

    protected function containsKeyword(string $haystack, string $keyword): bool
    {
        $keyword = $this->normalize($keyword);

        if (str_contains($keyword, ' ')) {
            return str_contains($haystack, $keyword);
        }

        return (bool) preg_match('/\b' . preg_quote($keyword, '/') . '\b/u', $haystack);
    }

    protected function normalize(string $value): string
    {
        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9\s]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);

        return $value;
    }
}
