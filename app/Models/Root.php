<?php

namespace App\Models;

use App\Support\QuranText;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Root extends Model
{
    use HasFactory;

    protected $fillable = [
        'root_arabic',
        'root_letters',
        'description',
    ];

    public function lemmas()
    {
        return $this->hasMany(Lemma::class);
    }

    public function words()
    {
        return $this->hasMany(Word::class);
    }

    protected function displayRootArabic(): Attribute
    {
        return Attribute::get(fn () => QuranText::buckwalterToArabic($this->root_arabic));
    }
}
