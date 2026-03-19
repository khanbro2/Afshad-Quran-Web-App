<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tafseer extends Model
{
    protected $fillable = [
        'slug',
        'title_urdu',
        'title_english',
        'author',
        'language',
        'source_name',
        'source_url',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function ayahTafseers(): HasMany
    {
        return $this->hasMany(AyahTafseer::class);
    }
}