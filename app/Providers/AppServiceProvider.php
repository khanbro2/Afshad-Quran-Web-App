<?php

namespace App\Providers;

use App\Services\TafseerImport\EquranLibraryTafseerImporter;
use App\Services\TafseerImport\TafseerImporterInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TafseerImporterInterface::class, EquranLibraryTafseerImporter::class);
    }

    public function boot(): void
    {
        //
    }
}
