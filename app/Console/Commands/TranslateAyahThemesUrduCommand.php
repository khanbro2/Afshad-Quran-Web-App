<?php

namespace App\Console\Commands;

use App\Models\AyahTheme;
use App\Support\AyahThemeUrduTranslator;
use Illuminate\Console\Command;

class TranslateAyahThemesUrduCommand extends Command
{
    protected $signature = 'app:translate-ayah-themes-urdu {--force : Rebuild existing Urdu titles even if already present}';

    protected $description = 'Generate Urdu titles for imported ayah themes using a local best-effort translator';

    public function handle(AyahThemeUrduTranslator $translator): int
    {
        $query = AyahTheme::query()->orderBy('id');

        if (! $this->option('force')) {
            $query->where(function ($builder) {
                $builder
                    ->whereNull('title_urdu')
                    ->orWhereColumn('title_urdu', 'title_english');
            });
        }

        $themes = $query->get();

        if ($themes->isEmpty()) {
            $this->info('No ayah themes require Urdu translation.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($themes->count());
        $bar->start();

        $updated = 0;

        foreach ($themes as $theme) {
            $theme->forceFill([
                'title_urdu' => $translator->translate((string) $theme->title_english),
            ])->save();

            $updated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->table(
            ['Metric', 'Value'],
            [
                ['Themes Processed', $themes->count()],
                ['Themes Updated', $updated],
            ]
        );

        return self::SUCCESS;
    }
}
