<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups("order:read")]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups("order:read")]
    private ?string $status = null;

    #[ORM\Column(nullable: false)]
    #[Groups("order:read")]
    private ?int $user_id = null;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: BasketItem::class, cascade: ['persist', 'remove'])]
    #[Groups("order:read")]
    private Collection $baskets;

    #[ORM\Column(type: 'datetime')]
    #[Groups("order:read")]
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->baskets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(?int $user_id): static
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function getBaskets(): Collection
    {
        return $this->baskets;
    }

    public function addBasket(BasketItem $basket): static
    {
        if (!$this->baskets->contains($basket)) {
            $this->baskets[] = $basket;
            $basket->setOrder($this);
        }
        return $this;
    }

    public function removeBasket(BasketItem $basket): static
    {
        if ($this->baskets->removeElement($basket)) {
            if ($basket->getOrder() === $this) {
                $basket->setOrder(null);
            }
        }
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
