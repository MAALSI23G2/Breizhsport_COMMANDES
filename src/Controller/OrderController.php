<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
class OrderController extends AbstractController
{
    /**
     * @Route("/orders/{userId}", name="get_user_orders", methods={"GET"})
     */
    public function getUserOrders(int $userId, OrderRepository $orderRepository, SerializerInterface $serializer): JsonResponse
    {
        $orders = $orderRepository->findBy(['userId' => $userId]);

        $data = $serializer->serialize($orders, 'json', ['groups' => 'order:read']);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}
