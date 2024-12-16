<?php

namespace App\Entity;

use App\Repository\OrderRepository;
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

    #[ORM\Column(nullable: true)]
    #[Groups("order:read")]
    private ?int $user_id = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[Groups("order:read")]
    private ?BasketItem $basket = null;

    #[ORM\Column(type: "datetime")]
    #[Groups("order:read")]
    private \DateTime $createdAt;

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

    public function getBasket(): ?BasketItem
    {
        return $this->basket;
    }

    public function setBasket(?BasketItem $basket): static
    {
        $this->basket = $basket;

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
