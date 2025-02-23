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
        if ($message->get('correlation_id') === $this->correlationId) {
            $this->response = $message->body;
        }
    }

    /**
     * @throws Exception
     */
    public function call(string $userId, string $uniqueId): array
    {
        $this->response = null;
        $this->correlationId = uniqid();

        $this->channel->basic_publish(
            new AMQPMessage(
                json_encode(compact('userId', 'uniqueId')),
                [
                    'correlation_id' => $this->correlationId,
                    'reply_to' => $this->callbackQueue
                ]
            ),
            'PanierGetOne',
            'PanierGetOne'
        );

        $startTime = time();
        while (!$this->response) {
            if ((time() - $startTime) >= self::TIMEOUT_SECONDS) {
                throw new Exception('Timeout waiting for Panier service');
            }
            $this->channel->wait(null, false, 1);
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