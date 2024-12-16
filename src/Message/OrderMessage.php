<?php

// src/Message/OrderMessage.php

namespace App\Message;

class  OrderMessage
{
    private int $orderId;
    private int $userId;
    private float $total;
    private array $products;  // Liste des produits de la commande (en option)

    public function __construct(int $orderId, int $userId, float $total, array $products = [])
    {
        $this->orderId = $orderId;
        $this->userId = $userId;
        $this->total = $total;
        $this->products = $products;
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

    public function getProducts(): array
    {
        return $this->products;
    }
}
