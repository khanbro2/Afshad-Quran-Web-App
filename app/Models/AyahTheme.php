<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AyahTheme extends Model
{
    protected $appends = [
        'has_clean_urdu_title',
        'display_title_urdu',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $fillable = [
        'slug',
        'title_english',
        'title_urdu',
        'description',
        'badge_color',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function ayahs(): BelongsToMany
    {
        return $this->belongsToMany(Ayah::class, 'ayah_theme_assignments')
            ->withTimestamps();
    }

    public function getHasCleanUrduTitleAttribute(): bool
    {
        return $this->getDisplayTitleUrduAttribute() !== null;
    }

    public function getDisplayTitleUrduAttribute(): ?string
    {
        $value = trim((string) $this->title_urdu);

        if ($value === '') {
            return null;
        }

        if (preg_match('/[A-Za-z]/', $value)) {
            return null;
        }

        if (preg_match('/[ØÙÚÛ]/u', $value)) {
            return null;
        }

        return preg_match('/[\x{0600}-\x{06FF}]/u', $value) ? $value : null;
    }
}
