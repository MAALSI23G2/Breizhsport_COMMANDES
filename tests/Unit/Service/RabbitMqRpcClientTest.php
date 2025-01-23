<?php

namespace App\Tests\Unit\Service;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use App\Service\RabbitMqRpcClient;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;

class RabbitMqRpcClientTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testRpcCall(): void
    {
        // Mock de la connexion et du canal
        $amqpConnectionMock = $this->createMock(AMQPStreamConnection::class);
        $amqpChannelMock = $this->createMock(AMQPChannel::class);

        // Configurez le mock de la connexion pour retourner un canal mocké
        $amqpConnectionMock->method('channel')->willReturn($amqpChannelMock);

        // Mock des méthodes que vous souhaitez tester
        $amqpChannelMock->expects($this->once())
            ->method('basic_publish')
            ->with($this->isInstanceOf(AMQPMessage::class), '', 'rpc_queue');

        // Mock de la méthode 'createConnection'
        $rpcClient = $this->getMockBuilder(RabbitMqRpcClient::class)
            ->addMethods(['createConnection'])
            ->getMock();

        $rpcClient->method('createConnection')->willReturn($amqpConnectionMock);

        // Testez la méthode `call`
        $response = $rpcClient->call('1', 'uniqueId123');  // Exécutez la méthode
        $this->assertNull($response);  // Vérifiez le résultat attendu
    }
}

