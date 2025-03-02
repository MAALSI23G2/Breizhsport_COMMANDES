<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderService
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private ?ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        ?ValidatorInterface $validator = null
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->validator = $validator;
    }

    /**
     * @throws Exception
     */
    public function createOrderFromPanierDetails($panierDetails): Order
    {
        // Convertir en array si c'est une chaîne JSON
        if (is_string($panierDetails)) {
            $panierDetails = json_decode($panierDetails, true);
        }

        // Log complet des données reçues
        $this->logger->info("DONNÉES COMPLÈTES DU PANIER: " . print_r($panierDetails, true));

        // Créer la commande avec les données disponibles
        $order = new Order();

        // Extraire l'ID utilisateur de n'importe où dans la structure
        $userId = $this->extractUserId($panierDetails);
        $order->setUserId($userId);

        // Extraire les produits de n'importe où dans la structure
        $products = $this->extractProducts($panierDetails);

        if (empty($products)) {
            throw new Exception("No products found in cart data");
        }

        // Traiter les produits
        $total = 0;
        foreach ($products as $product) {
            $item = new OrderItem();

            // Tenter d'extraire l'ID du produit
            $productId = $product['id'] ?? $product['_id'] ?? $product['productId'] ?? 0;
            $item->setProductId($productId);

            // Tenter d'extraire le nom du produit
            $productName = $product['name'] ?? $product['productName'] ?? 'Unknown Product';
            $item->setProductName($productName);

            // Tenter d'extraire la quantité
            $quantity = $product['qte'] ?? $product['quantity'] ?? 1;
            $item->setQuantity($quantity);

            // Tenter d'extraire le prix
            $price = $product['price'] ?? 0;
            $item->setPrice($price);

            // Ajouter à la commande
            $order->addItem($item);
            $total += $price * $quantity;
        }

        $order->setTotal($total);
        $order->setStatus('pending');

        // Persister et retourner
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    private function extractUserId($data)
    {
        // Chercher l'userId partout dans les données
        if (isset($data['user']['id'])) return (int)$data['user']['id'];
        if (isset($data['userId'])) return (int)$data['userId'];
        if (isset($data['user_id'])) return (int)$data['user_id'];

        // Chercher récursivement dans les sous-objets
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (isset($value['id']) && $key === 'user') {
                    return (int)$value['id'];
                }
                // Chercher récursivement
                $result = $this->extractUserId($value);
                if ($result > 0) return $result;
            }
        }

        return 1; // Valeur par défaut
    }

    private function extractProducts($data): array
    {
        // Chercher les produits partout dans les données
        if (isset($data['products']) && is_array($data['products'])) {
            return $data['products'];
        }

        if (isset($data['items']) && is_array($data['items'])) {
            return $data['items'];
        }

        // Si la racine est un tableau d'objets avec un 'id', c'est peut-être des produits
        if (isset($data[0]['id']) && is_array($data) && !empty($data)) {
            return $data;
        }

        // Chercher récursivement dans les sous-objets
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($key === 'products' || $key === 'items') {
                    return $value;
                }
                // Chercher récursivement
                $result = $this->extractProducts($value);
                if (!empty($result)) return $result;
            }
        }

        return [];
    }
}