<?php

namespace App\AI\QuranChat\Services;

class EntityResolver
{
    /**
     * @return array<int, string>
     */
    public function extractTerms(string $question): array
    {
        $stopwords = [
            'about', 'aur', 'ayaat', 'ayah', 'ayat', 'bar', 'baray', 'bare', 'bary', 'batao', 'bataiye',
            'count', 'detail', 'dikhao', 'do', 'english', 'hai', 'hain', 'how', 'hy', 'in', 'is', 'ka',
            'kay', 'ke', 'ki', 'kis', 'kitna', 'kitni', 'kitny', 'kon', 'konsi', 'kya', 'kya hai', 'list',
            'main', 'mein', 'me', 'mein', 'mutalliq', 'par', 'poore', 'quran', 'related', 'say', 'se',
            'sirf', 'surah', 'tarjuma', 'tafsir', 'tafseer', 'the', 'this', 'urdu', 'verse', 'what', 'where',
            'which', 'with', 'ye', 'yeh',
        ];

        return collect(preg_split('/[^\p{L}\p{N}:]+/u', mb_strtolower($question)) ?: [])
            ->filter(fn ($term) => is_string($term) && mb_strlen(trim($term)) >= 2)
            ->map(fn ($term) => trim($term))
            ->reject(fn (string $term) => in_array($term, $stopwords, true))
            ->unique()
            ->values()
            ->all();
    }

    public function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[\x{0640}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $value) ?? $value;
        $value = str_replace(['-', '_', '\''], ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    /**
     * @param  array<int, string>  $terms
     * @return array<int, array<string, mixed>>
     */
    public function resolve(string $question, array $terms = []): array
    {
        $normalizedQuestion = $this->normalize($question);
        $aliases = config('ai_assistant.query_aliases', []);
        $resolved = [];

        foreach ($aliases as $canonical => $variants) {
            $normalizedVariants = collect($variants)
                ->map(fn ($variant) => $this->normalize((string) $variant))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $matched = collect($normalizedVariants)
                ->filter(function (string $variant) use ($normalizedQuestion, $terms) {
                    if ($variant === '') {
                        return false;
                    }

                    if (str_contains($normalizedQuestion, $variant)) {
                        return true;
                    }

                    return collect($terms)->contains(fn ($term) => $this->normalize((string) $term) === $variant);
                })
                ->values()
                ->all();

            if ($matched === []) {
                continue;
            }

            $resolved[] = [
                'canonical' => (string) $canonical,
                'matched' => $matched,
                'variants' => array_values($variants),
            ];
        }

        return $resolved;
    }

    /**
     * @param  array<int, array<string, mixed>>  $resolved
     * @param  array<int, string>  $terms
     * @return array<int, string>
     */
    public function expandSearchTerms(array $resolved, array $terms): array
    {
        $expanded = collect($terms);

        foreach ($resolved as $entity) {
            $expanded->push((string) ($entity['canonical'] ?? ''));
            $expanded = $expanded->merge($entity['variants'] ?? []);
        }

        return $expanded
            ->map(fn ($term) => trim((string) $term))
            ->filter(fn ($term) => $term !== '')
            ->unique()
            ->values()
            ->all();
    }

    public function detectSurahNumber(string $question): ?int
    {
        $normalizedQuestion = $this->normalize($question);

        if (preg_match('/\bsurah\s+(\d{1,3})\b/u', $normalizedQuestion, $matches) === 1) {
            return (int) $matches[1];
        }

        foreach (config('quran.surahs', []) as $number => $meta) {
            $transliteration = $this->normalize((string) ($meta['transliteration'] ?? ''));
            $english = $this->normalize((string) ($meta['english'] ?? ''));
            $arabic = $this->normalize((string) ($meta['arabic'] ?? ''));
            $candidates = [
                'surah ' . $number,
                'surah ' . $transliteration,
                'surah ' . preg_replace('/^(al|an|as|ar|at|ad)\s+/u', '', $transliteration),
                'surah ' . $english,
                'surah ' . $arabic,
            ];

            foreach ($candidates as $candidate) {
                if ($candidate !== 'surah' && str_contains($normalizedQuestion, $candidate)) {
                    return (int) $number;
                }
            }
        }

        return null;
    }

    /**
     * @return array{surah_number:int,ayah_number:int}|null
     */
    public function detectAyahReference(string $question): ?array
    {
        if (preg_match('/\b(\d{1,3})\s*[:\/]\s*(\d{1,3})\b/u', $question, $matches) !== 1) {
            return null;
        }

        return [
            'surah_number' => (int) $matches[1],
            'ayah_number' => (int) $matches[2],
        ];
    }
}
