<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class OrderService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @throws Exception
     */
    public function createOrderFromPanierDetails(array $panierDetails): Order
    {
        // Log pour déboguer
        error_log("Données reçues du service Panier: " . print_r($panierDetails, true));

        // Vérifier si les données sont imbriquées dans une clé 'content'
        if (isset($panierDetails['content']) && is_array($panierDetails['content'])) {
            $panierDetails = $panierDetails['content'];
            error_log("Utilisation des données depuis la clé 'content'");
        }

        // Vérifier si les produits existent
        if (!isset($panierDetails['products']) || !is_array($panierDetails['products'])) {
            error_log("ERREUR: La clé 'products' est manquante ou n'est pas un tableau");
            throw new Exception("Missing or invalid 'products' key in Panier service response");
        }

        // Extraction de l'ID utilisateur
        $userId = 0;
        if (isset($panierDetails['user']['id'])) {
            $userId = (int)$panierDetails['user']['id'];
        } elseif (isset($panierDetails['userId'])) {
            $userId = (int)$panierDetails['userId'];
        }

        error_log("ID utilisateur extrait: $userId");

        // Calcul du total de la commande
        $total = 0;
        foreach ($panierDetails['products'] as $product) {
            // Gérer les différentes façons dont la quantité peut être représentée
            $quantity = $product['qte'] ?? ($product['quantity'] ?? 1);

            $total += $product['price'] * $quantity;
        }

        error_log("Total calculé: $total");

        $order = new Order();
        $order->setUserId($userId);
        $order->setTotal($total);
        $order->setStatus('pending');
        $order->setCreatedAt(new \DateTime());

        foreach ($panierDetails['products'] as $product) {
            $orderItem = new OrderItem();
            $orderItem->setProductId($product['id']);
            $orderItem->setProductName($product['name']);

            // Gérer les différentes façons dont la quantité peut être représentée
            $quantity = $product['qte'] ?? ($product['quantity'] ?? 1);
            $orderItem->setQuantity($quantity);

            $orderItem->setPrice($product['price']);
            $order->addItem($orderItem);

            error_log("Ajout du produit ID: {$product['id']}, Nom: {$product['name']}, Qté: $quantity, Prix: {$product['price']}");
        }

        try {
            $this->entityManager->persist($order);
            $this->entityManager->flush();
            error_log("Commande créée avec succès, ID: " . $order->getId());
            return $order;
        } catch (\Exception $e) {
            error_log("ERREUR lors de la persistance de la commande: " . $e->getMessage());
            throw $e;
        }
    }
}