<?php

namespace App\Domain\Artworks\Models;

use App\Domain\Artists\Models\Artist;
use App\Domain\Markets\Models\MarketOption;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Artwork extends Model
{
    protected $fillable = [
        'artist_id',
        'title',
        'slug',
        'description',
        'artwork_type',
        'medium',
        'image_path',
        'status',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function marketOptions(): HasMany
    {
        return $this->hasMany(MarketOption::class);
    }
}
