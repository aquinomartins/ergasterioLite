<?php

declare(strict_types=1);

namespace App\Policies;

final class MarketPolicy
{
    public function canCreate(?array $user): bool
    {
        return $user !== null;
    }

    public function canPublish(?array $user): bool
    {
        return $this->canManage($user);
    }

    public function canClose(?array $user): bool
    {
        return $this->canManage($user);
    }

    public function canResolve(?array $user): bool
    {
        return $this->canManage($user);
    }

    public function canManage(?array $user): bool
    {
        if ($user === null) {
            return false;
        }

        $status = strtolower((string) ($user['status'] ?? ''));
        $role = strtolower((string) ($user['role'] ?? ''));

        if (in_array($status, ['admin', 'curator'], true) || in_array($role, ['admin', 'curator'], true)) {
            return true;
        }

        return (int) ($user['id'] ?? 0) === 1;
    }
}
