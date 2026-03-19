<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE ayah_themes MODIFY title_english TEXT NOT NULL');
        DB::statement('ALTER TABLE ayah_themes MODIFY title_urdu TEXT NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE ayah_themes MODIFY title_english VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE ayah_themes MODIFY title_urdu VARCHAR(255) NOT NULL');
    }
};
