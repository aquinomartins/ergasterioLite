<?php

namespace App\Policies;

use App\Domain\Markets\Models\Market;
use App\Models\User;

class MarketPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Market $market): bool
    {
        return in_array($market->status, ['open', 'closed', 'resolved'], true) || $user?->hasRole('admin') === true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Market $market): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Market $market): bool
    {
        return $user->hasRole('admin');
    }
}
