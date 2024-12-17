<?php

namespace App\MessageHandler;

use App\Entity\BasketItem;
use App\Message\OrderMessage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Entity\Order;

#[AsMessageHandler]
class OrderMessageHandler
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    public function __invoke(OrderMessage $message): void
    {
        $this->logger->info('Message reçu dans OrderMessageHandler.', [
            'orderId' => $message->getOrderId(),
            'userId' => $message->getUserId(),
            'total' => $message->getTotal(),
            'products' => $message->getProducts()  // Vérifie les produits
        ]);
        // Crée une nouvelle commande
        $order = new Order();
        $order->setUserId($message->getUserId());
        $order->setStatus('en cours');
        $order->setCreatedAt(new \DateTime());

        // Associer les produits à la commande
        foreach ($message->getProducts() as $productData) {
            $orderItem = new BasketItem();
            $orderItem->setOrder($order);
            $orderItem->setProductId($productData['id']);
            $orderItem->setProductName($productData['name']);
            $orderItem->setQuantity($productData['qty']);
            $orderItem->setPrice($productData['price']);

            $this->em->persist($orderItem);
        }

        // Sauvegarder la commande et ses articles
        $this->em->persist($order);
        $this->em->flush();

        // Log ou confirmation
        echo 'Commande sauvegardée avec succès !';
    }
}
