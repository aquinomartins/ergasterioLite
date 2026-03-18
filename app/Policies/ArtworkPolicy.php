<?php

namespace App\Policies;

use App\Domain\Artworks\Models\Artwork;
use App\Models\User;

class ArtworkPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Artwork $artwork): bool
    {
        return $artwork->status === 'published' || $user?->hasRole('admin') === true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Artwork $artwork): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Artwork $artwork): bool
    {
        return $user->hasRole('admin');
    }
}
