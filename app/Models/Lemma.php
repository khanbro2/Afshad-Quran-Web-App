<?php

namespace App\Models;

use App\Support\QuranText;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lemma extends Model
{
    use HasFactory;

    protected $fillable = [
        'lemma_arabic',
        'root_id',
    ];

    public function root()
    {
        return $this->belongsTo(Root::class);
    }

    public function words()
    {
        return $this->hasMany(Word::class);
    }

    protected function displayLemmaArabic(): Attribute
    {
        return Attribute::get(fn () => QuranText::buckwalterToArabic($this->lemma_arabic));
    }
}
