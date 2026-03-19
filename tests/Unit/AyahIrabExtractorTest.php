<?php

namespace Tests\Unit;

use App\Support\AyahIrabExtractor;
use InvalidArgumentException;
use Tests\TestCase;

class AyahIrabExtractorTest extends TestCase
{
    public function test_it_extracts_irab_rows_from_json(): void
    {
        $path = storage_path('framework/testing/ayah-irab.json');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, json_encode([
            ['surah' => 1, 'ayah' => 1, 'irab' => 'بسم: جار ومجرور'],
            ['surah' => 1, 'ayah' => 2, 'irab' => 'الحمد: مبتدأ'],
        ], JSON_UNESCAPED_UNICODE));

        $rows = AyahIrabExtractor::extract($path);

        $this->assertSame([
            ['surah_number' => 1, 'ayah_number' => 1, 'text' => 'بسم: جار ومجرور'],
            ['surah_number' => 1, 'ayah_number' => 2, 'text' => 'الحمد: مبتدأ'],
        ], $rows);
    }

    public function test_it_extracts_irab_rows_from_text(): void
    {
        $path = storage_path('framework/testing/ayah-irab.txt');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, "1|1|بسم: جار ومجرور\n1|2|الحمد: مبتدأ\n");

        $rows = AyahIrabExtractor::extract($path);

        $this->assertSame('الحمد: مبتدأ', $rows[1]['text']);
    }

    public function test_it_rejects_invalid_rows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $path = storage_path('framework/testing/ayah-irab-invalid.txt');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, "bad-row\n");

        AyahIrabExtractor::extract($path);
    }
}
