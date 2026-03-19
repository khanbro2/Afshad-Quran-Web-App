<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AyahTafseer extends Model
{
    protected $fillable = [
    'ayah_id',
    'tafseer_id',
    'content',
    'content_html',
    'meta',
];

    protected $casts = [
        'meta' => 'array',
    ];

    public function ayah(): BelongsTo
    {
        return $this->belongsTo(Ayah::class);
    }

    public function tafseer(): BelongsTo
    {
        return $this->belongsTo(Tafseer::class);
    }
}