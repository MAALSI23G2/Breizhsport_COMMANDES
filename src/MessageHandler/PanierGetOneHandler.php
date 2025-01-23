<?php
// src/MessageHandler/PanierGetOneHandler.php

namespace App\MessageHandler;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Message\PanierGetOne;
use App\Service\RabbitMqRpcClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class PanierGetOneHandler
{
    private EntityManagerInterface $entityManager;
    private RabbitMqRpcClient $rpcClient;

    function __construct(EntityManagerInterface $entityManager, RabbitMqRpcClient $rpcClient)
    {
        $this->entityManager = $entityManager;
        $this->rpcClient = $rpcClient;
    }
//Ou $_ENV['RABBITMQ_URI']
    public function __invoke(PanierGetOne $message): void
    {
        $userId = $message->getUserId();
        $uniqueId = $message->getUniqueId();

        // Utiliser RabbitMqRpcClient pour obtenir le panier depuis un autre service via RPC
        $panierDetails = $this->rpcClient->call((string) $userId, (string) $uniqueId);
        if (!$panierDetails) {
            throw new \Exception('Failed to retrieve panier details from the service');
        }

        // Logique pour enregistrer les détails du panier en base de données en tant que commande
        $this->createOrder($panierDetails);
    }

    private function createOrder(array $panierDetails): void
    {
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
            $order->addItem($orderItem); // Ajouter l'item à la commande
        }

        // Persist la commande et ses items
        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }
}
