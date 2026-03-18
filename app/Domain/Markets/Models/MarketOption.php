<?php

namespace App\Domain\Markets\Models;

use App\Domain\Artists\Models\Artist;
use App\Domain\Artworks\Models\Artwork;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketOption extends Model
{
    protected $fillable = [
        'market_id',
        'label',
        'artwork_id',
        'artist_id',
        'sort_order',
    ];

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function artwork(): BelongsTo
    {
        return $this->belongsTo(Artwork::class);
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }
}
