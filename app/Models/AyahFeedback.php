<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AyahFeedback extends Model
{
    use HasFactory;

    protected $table = 'ayah_feedback';

    protected $fillable = [
        'ayah_id',
        'name',
        'email',
        'comment',
        'admin_reply',
        'is_public',
        'is_approved',
    ];

    public function ayah()
    {
        return $this->belongsTo(Ayah::class);
    }
}