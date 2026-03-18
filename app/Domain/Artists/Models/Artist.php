<?php

namespace App\Domain\Artists\Models;

use App\Domain\Artworks\Models\Artwork;
use App\Domain\Markets\Models\MarketOption;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Artist extends Model
{
    protected $fillable = [
        'user_id',
        'display_name',
        'slug',
        'biography',
        'status',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function artworks(): HasMany
    {
        return $this->hasMany(Artwork::class);
    }

    public function marketOptions(): HasMany
    {
        return $this->hasMany(MarketOption::class);
    }
}
