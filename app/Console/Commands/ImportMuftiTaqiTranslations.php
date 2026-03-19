<?php

namespace App\Console\Commands;

use App\Models\Ayah;
use App\Models\Surah;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportMuftiTaqiTranslations extends Command
{
    protected $signature = 'app:import-mufti-taqi-translations
        {english=mufti_taqi_quran_translation.json : Path to Mufti Taqi English JSON file}
        {urdu=mufti_taqi_usmani_urdu.json : Path to Mufti Taqi Urdu JSON file}';

    protected $description = 'Import Mufti Taqi Usmani English and Urdu translations into ayahs table';

    public function handle(): int
    {
        $englishPath = base_path($this->argument('english'));
        $urduPath = base_path($this->argument('urdu'));

        if (!File::exists($englishPath)) {
            $this->error("English file not found: {$englishPath}");
            return self::FAILURE;
        }

        if (!File::exists($urduPath)) {
            $this->error("Urdu file not found: {$urduPath}");
            return self::FAILURE;
        }

        $englishData = json_decode(File::get($englishPath), true);
        $urduData = json_decode(File::get($urduPath), true);

        if (!is_array($englishData) || !is_array($urduData)) {
            $this->error('One or both JSON files are invalid.');
            return self::FAILURE;
        }

        $englishImported = 0;
        $urduImported = 0;
        $missingAyahs = 0;

        foreach ($englishData as $row) {
            $surahNumber = isset($row['surah_number']) ? (int) $row['surah_number'] : null;
            $ayahNumber = isset($row['ayah_number']) ? (int) $row['ayah_number'] : null;
            $translation = $row['translation'] ?? null;

            if (!$surahNumber || !$ayahNumber || !$translation) {
                continue;
            }

            $surah = Surah::where('number', $surahNumber)->first();
            if (!$surah) {
                $missingAyahs++;
                continue;
            }

            $ayah = Ayah::where('surah_id', $surah->id)
                ->where('ayah_number', $ayahNumber)
                ->first();

            if (!$ayah) {
                $missingAyahs++;
                continue;
            }

            $ayah->english_translation_mufti_taqi = $translation;
            $ayah->save();

            $englishImported++;
        }

        foreach ($urduData as $row) {
            $surahNumber = isset($row['surah']) ? (int) $row['surah'] : null;
            $ayahNumber = isset($row['ayah']) ? (int) $row['ayah'] : null;
            $translation = $row['translation_urdu'] ?? null;

            if (!$surahNumber || !$ayahNumber || !$translation) {
                continue;
            }

            $surah = Surah::where('number', $surahNumber)->first();
            if (!$surah) {
                $missingAyahs++;
                continue;
            }

            $ayah = Ayah::where('surah_id', $surah->id)
                ->where('ayah_number', $ayahNumber)
                ->first();

            if (!$ayah) {
                $missingAyahs++;
                continue;
            }

            $ayah->urdu_translation_mufti_taqi = $translation;
            $ayah->save();

            $urduImported++;
        }

        $this->info('Mufti Taqi translations imported successfully.');
        $this->line("English imported: {$englishImported}");
        $this->line("Urdu imported: {$urduImported}");
        $this->line("Missing ayahs/surahs: {$missingAyahs}");

        return self::SUCCESS;
    }
}