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
    private $connection;


    /**
     * @throws Exception
     */
    /**
     * @throws Exception
     */
    public function __construct()
    {
        try {
            $rabbitMqUri = getenv('RABBITMQ_URI');
            if (!$rabbitMqUri) {
                throw new Exception('RABBITMQ_URI is not set');
            }

            $parsedUrl = parse_url($rabbitMqUri);
            if (!$parsedUrl) {
                throw new Exception('Invalid RABBITMQ_URI format');
            }

            error_log("Tentative de connexion à RabbitMQ: " . $rabbitMqUri);

            $this->connection = new AMQPStreamConnection(
                $parsedUrl['host'] ?? 'localhost',
                $parsedUrl['port'] ?? 5672,
                $parsedUrl['user'] ?? 'user',
                $parsedUrl['pass'] ?? 'password',
            );

            error_log("Connexion établie avec succès, création du canal");
            $this->channel = $this->connection->channel();

            error_log("Canal créé avec succès, déclaration de la queue de callback");

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
                function($msg) {
                    $this->onResponse($msg);
                }
            );

            error_log("RabbitMqRpcClient initialisé avec succès");
        } catch (\Exception $e) {
            error_log("ERREUR lors de l'initialisation de RabbitMqRpcClient: " . $e->getMessage());
            // Nettoyage des ressources partiellement initialisées
            if (isset($this->channel)) {
                try { $this->channel->close(); } catch (\Exception $ex) {}
            }
            if (isset($this->connection) && $this->connection) {
                try { $this->connection->close(); } catch (\Exception $ex) {}
            }
            throw $e;
        }
    }

    private function onResponse(AMQPMessage $message): void
    {
        error_log("=== RÉPONSE REÇUE ===");
        error_log("Correlation ID reçu: " . $message->get('correlation_id'));
        error_log("Correlation ID attendu: " . $this->correlationId);
        error_log("Contenu brut de la réponse: " . $message->body);

        // Essayer de décoder le JSON pour voir ce qui est réellement reçu
        $jsonData = json_decode($message->body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            error_log("Décodage JSON réussi. Structure: " . print_r($jsonData, true));
        } else {
            error_log("ÉCHEC du décodage JSON: " . json_last_error_msg());
            error_log("Les 200 premiers caractères de la réponse: " . substr($message->body, 0, 200));
        }

        if ($message->get('correlation_id') === $this->correlationId) {
            error_log("IDs de corrélation correspondent - traitement de la réponse");
            $this->response = $message->body;
        } else {
            error_log("IDs de corrélation ne correspondent pas - message ignoré");
        }
    }
    /**
     * @throws Exception
     */
    public function call(string $userId, string $uniqueId): array
    {
        $this->response = null;
        $this->correlationId = uniqid();

        error_log("Début de l'appel RPC avec userId: $userId, uniqueId: $uniqueId, correlationId: {$this->correlationId}");

        // Préparer le message à envoyer
        $messageContent = json_encode(['userId' => $userId, 'uniqueId' => $uniqueId]);
        error_log("Contenu du message à envoyer: $messageContent");

        try {
            // Vérifier si l'exchange existe
            $exchangeName = 'PanierGetOne';
            $routingKey = 'PanierGetOne';

            error_log("Vérification/création de l'exchange '$exchangeName'");
            $this->channel->exchange_declare(
                $exchangeName,
                'direct',
                false,  // passive (false = créer si n'existe pas)
                false,   // durable
                false   // auto-delete
            );

            error_log("Exchange '$exchangeName' prêt avec routing key '$routingKey'");

            // Assurez-vous que la queue existe et est liée à l'exchange
            $queueName = 'panier.get_one.queue';
            $this->channel->queue_declare($queueName, false, true, false, false);
            $this->channel->queue_bind($queueName, $exchangeName, $routingKey);

            error_log("Queue '$queueName' prête et liée à l'exchange");
        } catch (\Exception $e) {
            error_log("Erreur lors de la déclaration de l'exchange: " . $e->getMessage());
            throw new Exception('Failed to declare exchange: ' . $e->getMessage());
        }

        // Création et envoi du message
        $message = new AMQPMessage(
            $messageContent,
            [
                'correlation_id' => $this->correlationId,
                'reply_to' => $this->callbackQueue,
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
            ]
        );

        error_log("Envoi du message à l'exchange '$exchangeName' avec routing key '$routingKey'");
        $this->channel->basic_publish(
            $message,
            $exchangeName,    // exchange
            $routingKey       // routing key
        );
        error_log("Message publié avec succès");

        // Attente de la réponse avec gestion du timeout
        $startTime = time();
        error_log("Début de l'attente de la réponse (timeout: " . self::TIMEOUT_SECONDS . " secondes)");

        while (!$this->response) {
            $elapsedTime = time() - $startTime;
            if ($elapsedTime >= self::TIMEOUT_SECONDS) {
                error_log("TIMEOUT après $elapsedTime secondes");
                throw new Exception("Timeout waiting for Panier service after $elapsedTime seconds");
            }

            if ($elapsedTime > 0 && $elapsedTime % 5 == 0) {
                error_log("Toujours en attente de réponse... ($elapsedTime secondes écoulées)");
            }

            $this->channel->wait(null, false, 1);
        }

        error_log("Réponse reçue après " . (time() - $startTime) . " secondes");


        // Traitement de la réponse
        $decodedResponse = json_decode($this->response, true);
        // Log complet de la réponse pour débogage
        error_log("RÉPONSE COMPLÈTE: " . print_r($decodedResponse, true));

        // Accepter n'importe quelle réponse tant qu'elle est un objet/array valide
        if (is_array($decodedResponse)) {
            return $decodedResponse;
        }

        throw new Exception("Invalid JSON response from Panier service");
    }

    /**
     * @throws Exception
     */
    public function __destruct()
    {
        try {
            if (method_exists($this->channel, 'is_open') && $this->channel->is_open()) {
                error_log("Fermeture du canal RabbitMQ");
                $this->channel->close();
            }

            if ($this->connection && method_exists($this->connection, 'isConnected') && $this->connection->isConnected()) {
                error_log("Fermeture de la connexion RabbitMQ");
                $this->connection->close();
            }
        } catch (\Exception $e) {
            error_log("Erreur lors de la fermeture des ressources RabbitMQ: " . $e->getMessage());
        }
    }
}