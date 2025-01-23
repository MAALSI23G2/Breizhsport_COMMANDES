<?php

namespace App\Tests\Integration\MessageHandler;

use App\Entity\Order;
use App\Message\PanierGetOne;
use App\MessageHandler\PanierGetOneHandler;
use App\Service\RabbitMqRpcClient;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PanierGetOneHandlerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testHandlePanierGetOne(): void
    {
        // Créer un mock de RabbitMqRpcClient
        $rpcClientMock = $this->createMock(RabbitMqRpcClient::class);

        // Configurer le mock pour renvoyer des données simulées
        $rpcClientMock->method('call')
            ->willReturn([
                'userId' => 1,
                'products' => [
                    ['id' => 1, 'name' => 'Product 1', 'quantity' => 2, 'price' => 10.0],
                    ['id' => 2, 'name' => 'Product 2', 'quantity' => 1, 'price' => 20.0],
                ],
                'total' => 40.0,
            ]);

        // Instancier le handler avec le mock
        $handler = new PanierGetOneHandler($this->entityManager, $rpcClientMock);
        $message = new PanierGetOne(1);

        // Appeler le handler
        $handler($message);

        // Vérifier que la commande a bien été créée
        $orders = $this->entityManager->getRepository(Order::class)->findAll();
        $this->assertCount(1, $orders);
        $this->assertEquals('pending', $orders[0]->getStatus());
        $this->assertEquals(40.0, $orders[0]->getTotal());
    }
}

