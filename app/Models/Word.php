<?php

namespace App\Models;

use App\Support\QuranText;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Word extends Model
{
    use HasFactory;

    protected $fillable = [
        'ayah_id',
        'surah_number',
        'ayah_number',
        'position',
        'segment_count',
        'form',
        'arabic_text',
        'normalized_text',
        'translation_basic',
        'translation_urdu',
        'transliteration',
        'root_id',
        'lemma_id',
    ];

    public function ayah()
    {
        return $this->belongsTo(Ayah::class);
    }

    public function morphologies()
    {
        return $this->hasMany(Morphology::class);
    }

    public function root()
    {
        return $this->belongsTo(Root::class);
    }

    public function lemma()
    {
        return $this->belongsTo(Lemma::class);
    }

    protected function displayForm(): Attribute
    {
        return Attribute::get(fn () => QuranText::buckwalterToArabic($this->arabic_text ?: $this->form));
    }
}
