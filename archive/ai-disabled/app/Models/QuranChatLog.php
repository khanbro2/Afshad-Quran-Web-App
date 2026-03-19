<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuranChatLog extends Model
{
    protected $fillable = [
        'request_id',
        'question',
        'language',
        'answer',
        'status',
        'error_message',
        'matched_ayah_count',
        'matched_word_count',
        'matched_theme_count',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
