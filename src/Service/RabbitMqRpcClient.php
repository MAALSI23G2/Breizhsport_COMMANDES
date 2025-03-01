<?php

namespace App\Service;

use Exception;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMqRpcClient
{
    private const TIMEOUT_SECONDS = 60;
    private \PhpAmqpLib\Channel\AbstractChannel|\PhpAmqpLib\Channel\AMQPChannel $channel;
    private mixed $callbackQueue;
    private $response;
    private $correlationId;


    /**
     * @throws Exception
     */
    public function __construct()
    {
        $rabbitMqUri = getenv('RABBITMQ_URI') ?: throw new Exception('RABBITMQ_URI is not set');
        $parsedUrl = parse_url($rabbitMqUri) ?: throw new Exception('Invalid RABBITMQ_URI format');

        $connection = new AMQPStreamConnection(
            $parsedUrl['host'] ?? 'localhost',
            $parsedUrl['port'] ?? 5672,
            $parsedUrl['user'] ?? 'user',
            $parsedUrl['pass'] ?? 'password'
        );

        $this->channel = $connection->channel();

        // Setup queue and consumer
        list($this->callbackQueue, ,) = $this->channel->queue_declare(
            '', false, false, true, false);
        error_log("Queue de callback créée : " . $this->callbackQueue);
        $this->channel->basic_consume(
            $this->callbackQueue,
            '',
            false,
            true,
            false,
            false,
            fn($msg) => $this->onResponse($msg)
        );
    }
    private function onResponse(AMQPMessage $message): void
    {
        error_log("Réponse reçue avec correlation_id: " . $message->get('correlation_id'));
        error_log("Notre correlation_id attendu: " . $this->correlationId);

        if ($message->get('correlation_id') === $this->correlationId) {
            error_log("Correlation IDs correspondent, traitement de la réponse");
            $this->response = $message->body;
            error_log("Contenu de la réponse: " . $this->response);
        } else {
            error_log("Correlation IDs ne correspondent pas, message ignoré");
        }
    }
    /**
     * @throws Exception
     */

        public function call(string $userId, string $uniqueId): array
    {
        $this->response = null;
        $this->correlationId = uniqid();

        error_log("Envoi de la requête RPC avec userId: $userId et uniqueId: $uniqueId");

        // Déclarer explicitement l'exchange
        try {
            // Vérifier si l'exchange existe sans essayer de le créer
            $this->channel->exchange_declare(
                'PanierGetOne',    // nom
                'direct',          // type
                true,              // passive (true = vérifier seulement)
                false,             // durable
                false              // auto-delete
            );
        } catch (\Exception $e) {
            // Si l'exchange n'existe pas, le créer
            $this->channel->exchange_declare(
                'PanierGetOne',
                'direct',
                false,            // passive (false = créer)
                false,            // durable
                false
            );
        }

        $message = new AMQPMessage(
            json_encode(['userId' => $userId, 'uniqueId' => $uniqueId]),
            [
                'correlation_id' => $this->correlationId,
                'reply_to' => $this->callbackQueue,
                'content_type' => 'application/json'
            ]
        );

        $this->channel->basic_publish(
            $message,
            'PanierGetOne',    // exchange
            'PanierGetOne'     // routing key
        );

        // Suite du code avec la gestion du timeout...
        $startTime = time();
        while (!$this->response) {
            if ((time() - $startTime) >= self::TIMEOUT_SECONDS) {
                throw new Exception('Timeout waiting for Panier service');
            }
            $this->channel->wait(null, false, 60);
        }

        return json_decode($this->response, true)
            ?: throw new Exception('Invalid response from Panier service');
    }

    /**
     * @throws Exception
     */
    public function __destruct()
    {
        $this->channel?->close();
        $this->channel?->getConnection()?->close();
    }
}