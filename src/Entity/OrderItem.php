<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource]
#[ORM\Entity]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    #[Groups(["order:read"])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Order", inversedBy: "items")]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["order:read"])]
    private ?Order $order;

    #[ORM\Column(type: "integer")]
    #[Groups(["order:read"])]
    private int $productId;

    #[ORM\Column(type: "string")]
    #[Groups(["order:read"])]
    private string $productName;

    #[ORM\Column(type: "integer")]
    #[Groups(["order:read"])]
    private int $quantity;

    #[ORM\Column(type: "float")]
    #[Groups(["order:read"])]
    private float $price;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(Order $order): self
    {
        $this->order = $order;
        return $this;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function setProductId(int $productId): self
    {
        $this->productId = $productId;
        return $this;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): self
    {
        $this->productName = $productName;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;
        return $this;
    }
}
