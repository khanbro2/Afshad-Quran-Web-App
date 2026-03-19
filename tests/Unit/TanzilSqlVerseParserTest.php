<?php

namespace Tests\Unit;

use App\Support\TanzilSqlVerseParser;
use InvalidArgumentException;
use Tests\TestCase;

class TanzilSqlVerseParserTest extends TestCase
{
    public function test_it_parses_quran_text_insert_rows(): void
    {
        $sql = <<<'SQL'
INSERT INTO `quran_text` (`index`, `sura`, `aya`, `text`) VALUES
(1, 1, 1, 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ'),
(2, 1, 2, 'الْحَمْدُ لِلَّهِ رَبِّ الْعَالَمِينَ');
SQL;

        $rows = TanzilSqlVerseParser::parseInsertStatement($sql);

        $this->assertCount(2, $rows);
        $this->assertSame(1, $rows[0]['surah_number']);
        $this->assertSame(1, $rows[0]['ayah_number']);
        $this->assertSame('بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ', $rows[0]['text']);
    }

    public function test_it_fails_cleanly_for_invalid_sql_format(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TanzilSqlVerseParser::parseInsertStatement('INSERT INTO `quran_text` VALUES INVALID');
    }
}
