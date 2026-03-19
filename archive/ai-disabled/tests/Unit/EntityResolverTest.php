<?php

namespace Tests\Unit;

use App\AI\QuranChat\Services\EntityResolver;
use Tests\TestCase;

class EntityResolverTest extends TestCase
{
    public function test_it_resolves_roman_urdu_and_english_aliases_to_a_canonical_entity(): void
    {
        $resolver = app(EntityResolver::class);
        $terms = $resolver->extractTerms('namaz k bary main quran kia kahta hy');
        $resolved = $resolver->resolve('namaz k bary main quran kia kahta hy', $terms);

        $this->assertNotEmpty($resolved);
        $this->assertSame('namaz', $resolved[0]['canonical']);
        $this->assertContains('salah', $resolver->expandSearchTerms($resolved, $terms));
    }

    public function test_it_detects_surah_and_ayah_reference(): void
    {
        $resolver = app(EntityResolver::class);

        $this->assertSame(2, $resolver->detectSurahNumber('Surah Baqarah mein sabr ki ayat'));
        $this->assertSame(
            ['surah_number' => 2, 'ayah_number' => 255],
            $resolver->detectAyahReference('2:255 ka tarjuma')
        );
    }
}
