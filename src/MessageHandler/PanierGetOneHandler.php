<?php

// src/MessageHandler/PanierGetOneHandler.php
namespace App\MessageHandler;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Message\PanierGetOne;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class PanierGetOneHandler
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    // Injection de l'EntityManagerInterface et LoggerInterface dans le constructeur
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function __invoke(PanierGetOne $message): void
    {
        // Récupération de l'ID utilisateur
        $userId = $message->getUserId();

        // Logique pour récupérer les détails du panier (par exemple, via RabbitMQ ou une autre source)
        // Exemple fictif avec les produits du panier
        $panierDetails = [
            'userId' => $userId,
            'products' => [
                ['id' => 1, 'name' => 'toto', 'quantity' => 2, 'price' => 10.0],
                ['id' => 2, 'name' => 'tutu', 'quantity' => 1, 'price' => 20.0]
            ],
            'total' => 40.0
        ];
        $this->logger->info('Détails du panier récupérés', ['panierDetails' => $panierDetails]);
        // Créer la commande à partir des détails du panier
        $this->createOrder($panierDetails);
    }

    private function createOrder(array $panierDetails): void
    {
        // Créer la commande
        $order = new Order();
        $order->setUserId($panierDetails['userId']);
        $order->setTotal($panierDetails['total']);
        $order->setStatus('pending');
        $order->setCreatedAt(new \DateTime());

        // Créer les items de la commande
        foreach ($panierDetails['products'] as $product) {
            $orderItem = new OrderItem();
            $orderItem->setProductId($product['id']);
            $orderItem->setProductName($product['name']);
            $orderItem->setQuantity($product['quantity']);
            $orderItem->setPrice($product['price']);

            $order->addItem($orderItem);  // Ajouter l'item à la commande
        }

        // Persist la commande et ses items
        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }


}
