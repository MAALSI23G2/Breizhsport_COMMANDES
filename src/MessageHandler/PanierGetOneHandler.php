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
        $this->logger->info('PanierGetOne reçu', [
            'userId' => $message->getUserId(),
        ]);
    }
}
