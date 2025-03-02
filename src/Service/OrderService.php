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
    public function createOrderFromPanierDetails(array $panierDetails): Order
    {
        $this->logger->info("Structure détaillée des données reçues", [
            'structure' => json_encode($panierDetails, JSON_PRETTY_PRINT)
        ]);

        // Tentative d'identification des produits
        $products = [];
        if (isset($panierDetails['products']) && is_array($panierDetails['products'])) {
            $products = $panierDetails['products'];
        } elseif (isset($panierDetails['items']) && is_array($panierDetails['items'])) {
            $products = $panierDetails['items'];
        } elseif (isset($panierDetails[0]['id'])) {
            // Si les données sont directement un tableau de produits
            $products = $panierDetails;
        }

        if (empty($products)) {
            $this->logger->error("Impossible de trouver des produits dans les données reçues");
            throw new Exception("Cannot find products in Panier service response");
        }

        // Extraction de l'ID utilisateur (avec davantage d'options)
        $userId = 0;
        if (isset($panierDetails['user']['id'])) {
            $userId = (int)$panierDetails['user']['id'];
        } elseif (isset($panierDetails['userId'])) {
            $userId = (int)$panierDetails['userId'];
        } elseif (isset($panierDetails['user_id'])) {
            $userId = (int)$panierDetails['user_id'];
        }


        if ($userId <= 0) {
            $this->logger->error("ID utilisateur invalide", ['userId' => $userId]);
            throw new Exception("Invalid user ID");
        }

        $this->logger->info("ID utilisateur extrait", ['userId' => $userId]);

        // Début de la transaction
        $this->entityManager->beginTransaction();

        try {
            $order = new Order();
            $order->setUserId($userId);
            $order->setStatus('pending');
            $order->setCreatedAt(new \DateTime());

            // Calcul du total de la commande
            $total = 0;

            foreach ($panierDetails['products'] as $product) {
                // Validation du produit
                if (!isset($product['id']) || !isset($product['name']) || !isset($product['price'])) {
                    throw new Exception("Missing required product fields (id, name, or price)");
                }

                // Gérer les différentes façons dont la quantité peut être représentée
                $quantity = $product['qte'] ?? ($product['quantity'] ?? 1);
                if ($quantity <= 0) {
                    $this->logger->warning("Quantité invalide pour le produit", [
                        'productId' => $product['id'],
                        'quantity' => $quantity
                    ]);
                    $quantity = 1; // Valeur par défaut
                }

                $price = (float)$product['price'];
                if ($price < 0) {
                    $this->logger->warning("Prix négatif pour le produit", [
                        'productId' => $product['id'],
                        'price' => $price
                    ]);
                    throw new Exception("Invalid product price");
                }

                $total += $price * $quantity;

                $orderItem = new OrderItem();
                $orderItem->setProductId($product['id']);
                $orderItem->setProductName($product['name']);
                $orderItem->setQuantity($quantity);
                $orderItem->setPrice($price);
                $order->addItem($orderItem);

                $this->logger->info("Ajout du produit", [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'quantity' => $quantity,
                    'price' => $price
                ]);
            }

            $order->setTotal($total);
            $this->logger->info("Total calculé", ['total' => $total]);

            // Validation de l'entité si le validateur est disponible
            if ($this->validator) {
                $errors = $this->validator->validate($order);
                if (count($errors) > 0) {
                    $errorMessages = [];
                    foreach ($errors as $error) {
                        $errorMessages[] = $error->getMessage();
                    }
                    throw new Exception("Order validation failed: " . implode(', ', $errorMessages));
                }
            }

            $this->entityManager->persist($order);
            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->logger->info("Commande créée avec succès", ['orderId' => $order->getId()]);

            return $order;
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error("Erreur lors de la création de la commande", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}