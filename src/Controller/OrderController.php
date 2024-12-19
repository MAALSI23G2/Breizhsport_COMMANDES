<?php

namespace App\Controller;

use App\Message\OrderMessage;
use App\Message\PanierGetOne;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

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
    public function createOrder(): JsonResponse
    {
        $orderData = [
            'orderId' => 123,
            'userId' => 42,
            'total' => 59.99,
            'products' => [
                ['id' => 1, 'name' => 'T-shirt', 'qty' => 2, 'price' => 20],
                ['id' => 2, 'name' => 'Pantalon', 'qty' => 1, 'price' => 30],
            ]
        ];
        dump('Message envoyé au bus');
        // Publier le message dans RabbitMQ
        $this->messageBus->dispatch(new OrderMessage($orderData['orderId'], $orderData['userId'], $orderData['total'], (array)$orderData['products']));

        return new JsonResponse(['message' => 'Commande créée et message envoyé à RabbitMQ'], Response::HTTP_CREATED);
    }

    /**
     * @param int $userId
     * @return JsonResponse
     * @throws ExceptionInterface
     */
    #[Route('/send-to-panier', name: 'get_send_to_panier')]
    public function sendToPanier(): JsonResponse
    {
        //TODO : $userId = $user->getId();

        // Envoi du message PanierGetOne
        $this->messageBus->dispatch(new PanierGetOne(1));

        return new JsonResponse(['message' => 'PanierGetOne'], Response::HTTP_OK);
    }
    /**
     * @param int $userId
     * @param OrderRepository $orderRepository
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
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
