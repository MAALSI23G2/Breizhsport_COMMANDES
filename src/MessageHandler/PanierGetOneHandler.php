<?php

// src/MessageHandler/PanierGetOneHandler.php

namespace App\MessageHandler;

use App\Message\PanierGetOne;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class PanierGetOneHandler
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(PanierGetOne $message): void
    {
        // Log ou traitement de la demande du panier avec l'ID utilisateur
        $this->logger->info('Message PanierGetOne reçu', [
            'userId' => $message->getUserId(),
        ]);

        // Ici tu peux ajouter le traitement que tu veux faire avec le panier
        // Exemple : récupérer les articles dans le panier pour cet utilisateur
        // $panier = $this->panierService->getPanierByUserId($message->getUserId());
    }
}
