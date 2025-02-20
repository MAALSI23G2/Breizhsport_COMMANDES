<?php

namespace App\Service;

use Exception;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMqRpcClient
{
    private $connection;
    private $channel;
    private $callbackQueue;
    private $response;
    private $correlationId;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        // Récupérer l'URI de RabbitMQ à partir de la variable d'environnement
        $rabbitMqUri = getenv('RABBITMQ_URI');

        // Si l'URI n'est pas défini, vous pouvez gérer l'erreur ou définir une valeur par défaut
        if (!$rabbitMqUri) {
            throw new Exception('RABBITMQ_URI is not set in the environment');
        }

        // Parse the URI to extract host and port
        $parsedUrl = parse_url($rabbitMqUri);
        $host = $parsedUrl['host'] ?? 'localhost';
        $port = $parsedUrl['port'] ?? 5672;
        $user = $parsedUrl['user'] ?? 'user';
        $password = $parsedUrl['pass'] ?? 'password';

        // Créer la connexion à RabbitMQ
        $this->connection = new AMQPStreamConnection($host, $port, $user, $password);
        $this->channel = $this->connection->channel();

        // Déclarer une queue de réponse temporaire
        list($this->callbackQueue, ,) = $this->channel->queue_declare(
            'PanierDeclareResponse',
            false,
            false,
            true,
            false
        );

        // Consommer les réponses
        $this->channel->basic_consume(
            $this->callbackQueue,
            'PanierConsumeResponse',
            false,
            true,
            false,
            false,
            [$this, 'onResponse']
        );
    }

    public function onResponse(AMQPMessage $message): void
    {
        if ($message->get('correlation_id') === $this->correlationId) {
            $this->response = $message->body;
        }
         //Acknowledge the message
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    }

    public function call(string $userId, string $uniqueId): ?array
    {
        $this->response = null;
        $this->correlationId = uniqid();

        // Créer le message à envoyer au service panier
        $payload = json_encode([
            'userId' => $userId,
            'uniqueId' => $uniqueId,
            'replyQueue' => $this->callbackQueue,
        ]);
        $message = new AMQPMessage(
            $payload,
            [
                'correlation_id' => $this->correlationId,
                'reply_to' => $this->callbackQueue,
            ]
        );

        // Envoyer le message dans la queue `PanierGetOne`
        $this->channel->basic_publish($message, '', 'PanierGetOne');

        // Attendre la réponse
        while (!$this->response) {
            $this->channel->wait();
        }

        // Retourner la réponse décodée
        return json_decode($this->response, true);
    }

    /**
     * @throws Exception
     */
    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
