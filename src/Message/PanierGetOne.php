<?php

// src/Message/PanierGetOne.php

namespace App\Message;

class PanierGetOne
{
    private int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
