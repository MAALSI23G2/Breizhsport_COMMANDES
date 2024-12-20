<?php

namespace App\Entity;

use App\Repository\BasketProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: BasketProductRepository::class)]
class BasketProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups("order:read")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups("order:read")]
    private Product $product;

    #[ORM\ManyToOne(targetEntity: BasketItem::class, inversedBy: 'basketProduct')]
    #[ORM\JoinColumn(nullable: false)]
    private BasketItem $basket;

    #[ORM\Column(type: 'integer')]
    #[Groups("order:read")]
    private int $quantity;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): static
    {
        $this->product = $product;
        return $this;
    }

    public function getBasket(): BasketItem
    {
        return $this->basket;
    }

    public function setBasket(BasketItem $basket): static
    {
        $this->basket = $basket;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
        return $this;
    }
}


