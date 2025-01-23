<?php

// src/Message/PanierGetOne.php

namespace App\Message;

use Symfony\Component\Uid\Uuid;

class PanierGetOne
{
    private int $userId;
    private string $uniqueId;

    public function __construct(int $userId, ?string $uniqueId = null)
    {
        $this->userId = $userId;
        $this->uniqueId = $uniqueId ?? Uuid::v4()->toRfc4122(); // Génère un UUID si aucun n'est fourni
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getUniqueId(): string
    {
        return $this->uniqueId;
    }
}
