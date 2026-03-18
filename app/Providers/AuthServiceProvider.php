<?php

namespace App\Providers;

use App\Domain\Artists\Models\Artist;
use App\Domain\Artworks\Models\Artwork;
use App\Domain\Markets\Models\Market;
use App\Policies\ArtistPolicy;
use App\Policies\ArtworkPolicy;
use App\Policies\MarketPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Artist::class => ArtistPolicy::class,
        Artwork::class => ArtworkPolicy::class,
        Market::class => MarketPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
