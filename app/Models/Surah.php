<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Surah extends Model
{
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    protected $fillable = [
        'number',
        'arabic_name',
        'english_name',
        'transliterated_name',
        'revelation_type',
        'total_ayahs',
    ];

    public function ayahs()
    {
        return $this->hasMany(Ayah::class);
    }

    public function metadata(): array
    {
        return config('quran.surahs.' . $this->number, []);
    }

    protected function displayArabicName(): Attribute
    {
        return Attribute::get(fn () => $this->arabic_name ?: ($this->metadata()['arabic'] ?? null));
    }

    protected function displayEnglishName(): Attribute
    {
        return Attribute::get(fn () => $this->english_name ?: ($this->metadata()['english'] ?? null));
    }

    protected function displayTransliteratedName(): Attribute
    {
        return Attribute::get(fn () => $this->transliterated_name ?: ($this->metadata()['transliteration'] ?? null));
    }

    protected function displayTitle(): Attribute
    {
        return Attribute::get(fn () => $this->display_english_name ?: $this->display_transliterated_name ?: 'Surah ' . $this->number);
    }

    protected function displayAyahCount(): Attribute
    {
        return Attribute::get(function () {
            if (($this->attributes['total_ayahs'] ?? 0) > 0) {
                return (int) $this->attributes['total_ayahs'];
            }

            if (array_key_exists('ayahs_count', $this->attributes)) {
                return (int) $this->attributes['ayahs_count'];
            }

            return $this->ayahs()->count();
        });
    }
}
