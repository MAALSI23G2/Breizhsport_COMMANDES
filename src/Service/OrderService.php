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
        // Extraction de l'ID utilisateur à partir de la structure correcte
        $userId = isset($panierDetails['user']['id']) ? (int)$panierDetails['user']['id'] : 0;

        // Calcul du total de la commande
        $total = 0;
        foreach ($panierDetails['products'] as $product) {
            $total += $product['price'] * $product['qte']; // utiliser 'qte' au lieu de 'quantity'
        }

        $order = new Order();
        $order->setUserId($userId);
        $order->setTotal($total);
        $order->setStatus('pending');
        $order->setCreatedAt(new \DateTime());

        foreach ($panierDetails['products'] as $product) {
            $orderItem = new OrderItem();
            $orderItem->setProductId($product['id']);
            $orderItem->setProductName($product['name']);
            $orderItem->setQuantity($product['qte']); // utiliser 'qte' au lieu de 'quantity'
            $orderItem->setPrice($product['price']);
            $order->addItem($orderItem);
        }

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }
}