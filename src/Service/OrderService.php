<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\ORM\EntityManagerInterface;

class OrderService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function createOrderFromPanierDetails(array $panierDetails): Order
    {
        $order = new Order();
        $order->setUserId($panierDetails['userId']);
        $order->setTotal($panierDetails['total']);
        $order->setStatus('pending');
        $order->setCreatedAt(new \DateTime());

        foreach ($panierDetails['products'] as $product) {
            $orderItem = new OrderItem();
            $orderItem->setProductId($product['id']);
            $orderItem->setProductName($product['name']);
            $orderItem->setQuantity($product['quantity']);
            $orderItem->setPrice($product['price']);
            $order->addItem($orderItem);
        }

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }
}