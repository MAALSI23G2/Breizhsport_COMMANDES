<?php

namespace App\Entity;

use App\Repository\BasketRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: BasketRepository::class)]
class Basket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups("order:read")]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups("order:read")]
    private ?string $product_name = null;

    #[ORM\Column]
    #[Groups("order:read")]
    private ?float $price = null;

    #[ORM\Column]
    #[Groups("order:read")]
    private ?int $quantity = null;

    #[ORM\OneToMany(mappedBy: 'basket', targetEntity: BasketProduct::class, cascade: ['persist', 'remove'])]
    #[Groups("order:read")]
    private Collection $basketProduct;

    public function __construct()
    {
        $this->basketProduct = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductName(): ?string
    {
        return $this->product_name;
    }

    public function setProductName(string $product_name): static
    {
        $this->product_name = $product_name;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getBasketProduct(): Collection
    {
        return $this->basketProduct;
    }

    public function setBasketProduct(Collection $basketProduct): void
    {
        $this->basketProduct = $basketProduct;
    }

}
