<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
class BasketItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'baskets')]
    #[ORM\JoinColumn(nullable: false)]
    private Order $order;

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

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function setOrder(Order $order): static
    {
        $this->order = $order;
        return $this;
    }

    public function getBasketProduct(): Collection
    {
        return $this->basketProduct;
    }

    public function addBasketProduct(BasketProduct $basketProduct): static
    {
        if (!$this->basketProduct->contains($basketProduct)) {
            $this->basketProduct[] = $basketProduct;
            $basketProduct->setBasket($this);
        }
        return $this;
    }

    public function removeBasketProduct(BasketProduct $basketProduct): static
    {
        if ($this->basketProduct->removeElement($basketProduct)) {
            if ($basketProduct->getBasket() === $this) {
                $basketProduct->setBasket(null);
            }
        }
        return $this;
    }
}
