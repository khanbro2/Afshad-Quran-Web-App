<?php

namespace App\Models;

use App\Models\AyahFeedback;
use App\Models\AyahTafseer;
use App\Models\Ayah;
use App\Models\AyahTheme;
use App\Models\BroadTheme;
use App\Models\Surah;
use App\Models\Tafseer;
use RuntimeException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ayah extends Model
{
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'ayah_number';
    }

    public function tafseers()
{
    return $this->hasMany(\App\Models\AyahTafseer::class);
}


public function activeTafseers()
{
    return $this->hasMany(\App\Models\AyahTafseer::class)
        ->with('tafseer')
        ->whereHas('tafseer', function ($q) {
            $q->where('is_active', true);
        });
}

    public function feedback()
{
    return $this->hasMany(AyahFeedback::class)->latest();
}

    protected $fillable = [
    'surah_id',
    'ayah_number',
    'full_arabic_text',
    'simple_text',
    'uthmani_text',
    'irab_arabic',
    'urdu_translation',
    'urdu_translation_ahmedali',
    'urdu_translation_kanzuliman',
    'urdu_translation_maududi',
    'urdu_translation_mufti_taqi',
    'urdu_translation_jalandhry',
    'urdu_translation_bayan_simple',
    'english_translation_mufti_taqi',
];

    public function surah()
    {
        return $this->belongsTo(Surah::class);
    }

    public function words()
    {
        return $this->hasMany(Word::class);
    }

    public function themes()
    {
        return $this->belongsToMany(AyahTheme::class, 'ayah_theme_assignments')
            ->withTimestamps()
            ->orderBy('sort_order');
    }

    public function broadThemes()
    {
        return $this->belongsToMany(BroadTheme::class, 'ayah_broad_theme_assignments')
            ->withTimestamps()
            ->orderBy('sort_order');
    }

    protected function displayText(): Attribute
    {
        return Attribute::get(fn () => $this->uthmani_text ?: $this->full_arabic_text ?: $this->simple_text);
    }
}
