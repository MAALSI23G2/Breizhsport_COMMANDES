<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;
class OrderController extends AbstractController
{
    public function __construct()
    {
    }

/*    #[Route('/create-order', name: 'create_order')]
    public function createOrder(): JsonResponse
    {
        $orderData = [
            'id' => 123,
            'items' => ['item1', 'item2'],
            'total' => 45.99,
        ];

        $this->orderProducer->publishOrder($orderData);

        return new JsonResponse(['status' => 'Order published']);
    }*/
    #[Route('/orders/{userId}', name: 'get_user_orders', methods: ['GET'])]
    public function getUserOrders(int $userId, OrderRepository $orderRepository, SerializerInterface $serializer): JsonResponse
    {
        $orders = $orderRepository->findBy(['user_id' => $userId]);
        $test = 'azea';
        if (empty($orders)) {
            return new JsonResponse(['message' => 'Pas de commande effectuée'], Response::HTTP_OK);
        }

        $data = $serializer->serialize($orders, 'json', ['groups' => 'order:read']);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}
