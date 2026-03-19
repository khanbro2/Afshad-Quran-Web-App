<?php

namespace App\Models;

use App\Support\GrammarLabelService;
use App\Support\QuranText;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Morphology extends Model
{
    use HasFactory;

    protected $fillable = [
        'word_id',
        'segment_number',
        'pos_tag',
        'raw_features',
        'lemma',
        'root',
        'person',
        'gender',
        'number_type',
        'case_type',
        'mood',
        'tense',
        'voice',
        'state',
        'derived_form',
        'extra_json',
    ];

    protected $casts = [
        'extra_json' => 'array',
    ];

    public function word()
    {
        return $this->belongsTo(Word::class);
    }

    protected function displayLemma(): Attribute
    {
        return Attribute::get(fn () => QuranText::buckwalterToArabic($this->lemma));
    }

    protected function displayRoot(): Attribute
    {
        return Attribute::get(fn () => QuranText::buckwalterToArabic($this->root));
    }

    protected function englishSummary(): Attribute
    {
        return Attribute::get(fn () => GrammarLabelService::englishLabel($this));
    }

    protected function arabicSummary(): Attribute
    {
        return Attribute::get(fn () => GrammarLabelService::arabicLabel($this));
    }
}
