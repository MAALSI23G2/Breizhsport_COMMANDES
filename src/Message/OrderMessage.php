<?php

namespace App\Message;

class OrderMessage
{
    private int $orderId;
    private int $userId;
    private float $total;

    public function __construct(int $orderId, int $userId, float $total)
    {
        $this->orderId = $orderId;
        $this->userId = $userId;
        $this->total = $total;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getTotal(): float
    {
        return $this->total;
    }
}
