<?php

declare(strict_types=1);

namespace App\Models;

final class AdminAction
{
    public ?int $id;
    public int $adminUserId;
    public string $actionType;
    public string $targetType;
    public int $targetId;
    public ?string $justification;
    public ?string $createdAt;

    public function __construct(
        ?int $id,
        int $adminUserId,
        string $actionType,
        string $targetType,
        int $targetId,
        ?string $justification = null,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->adminUserId = $adminUserId;
        $this->actionType = $actionType;
        $this->targetType = $targetType;
        $this->targetId = $targetId;
        $this->justification = $justification;
        $this->createdAt = $createdAt;
    }
}
