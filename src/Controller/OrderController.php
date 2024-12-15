<?php

namespace App\Controller;

use App\Message\OrderMessage;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;
class OrderController extends AbstractController
{
    private MessageBusInterface $messageBus;
    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route('/create-order', name: 'create_order', methods: ['POST'])]
    public function createOrder(OrderRepository $orderRepository): JsonResponse
    {
        // Exemple : Simuler une commande créée
        $order = [
            'orderId' => 123,
            'userId' => 42,
            'total' => 59.99
        ];

        // Publier le message dans RabbitMQ
        $this->messageBus->dispatch(new OrderMessage($order['orderId'], $order['userId'], $order['total']));

        return new JsonResponse(['message' => 'Commande créée et publiée'], Response::HTTP_CREATED);
    }
    #[Route('/orders/{userId}', name: 'get_user_orders', methods: ['GET'])]
    public function getUserOrders(int $userId, OrderRepository $orderRepository, SerializerInterface $serializer): JsonResponse
    {
        $orders = $orderRepository->findBy(['user_id' => $userId]);
        if (empty($orders)) {
            return new JsonResponse(['message' => 'Pas de commande effectuée'], Response::HTTP_OK);
        }

        $data = $serializer->serialize($orders, 'json', ['groups' => 'order:read']);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}
