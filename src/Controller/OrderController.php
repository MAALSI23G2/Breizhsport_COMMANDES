<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Service\OrderService;
use App\Service\RabbitMqRpcClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Uuid;

#[Route('/order', name: 'order')]
class OrderController extends AbstractController
{
    public function __construct()
    {
    }

    /**
     * @param Request $request
     * @param RabbitMqRpcClient $rpcClient
     * @param OrderService $orderService
     * @return JsonResponse
     */
    #[Route('/send_order', name: 'get_send_order', methods: ['POST'])]
    public function sendOrder(
        Request $request,
        RabbitMqRpcClient $rpcClient,
        OrderService $orderService
    ): JsonResponse
    {
        // Validation des données
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['userId'])) {
            return new JsonResponse(['error' => 'userId is required'], Response::HTTP_BAD_REQUEST);
        }

        if (!is_numeric($data['userId']) || (int) $data['userId'] != $data['userId']) {
            return new JsonResponse(['error' => 'userId must be an integer'], Response::HTTP_BAD_REQUEST);
        }

        $userId = (int) $data['userId'];
        $uniqueId = Uuid::v4()->toRfc4122();


        try {
            // Appel RPC direct pour obtenir les détails du panier
            $panierDetails = $rpcClient->call((string) $userId, $uniqueId);
            if (!$panierDetails) {
                throw new \Exception('Failed to retrieve panier details from the service');
            }

            // Utilisation du service pour créer la commande
            $order = $orderService->createOrderFromPanierDetails($panierDetails);

            return new JsonResponse([
                'message' => 'Order created successfully',
                'orderId' => $order->getId(),
                'uniqueId' => $uniqueId,
                'details' => [
                    'total' => $order->getTotal(),
                    'itemCount' => count($order->getItems())
                ]
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to process order',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param int $userId
     * @param OrderRepository $orderRepository
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/order_list/{userId}', name: 'get_user_orders', methods: ['GET'])]
    public function getUserOrders(int $userId, OrderRepository $orderRepository, SerializerInterface $serializer): JsonResponse
    {
        $orders = $orderRepository->findBy(['userId' => $userId]);
        if (empty($orders)) {
            return new JsonResponse(['message' => 'Pas de commande effectuée'], Response::HTTP_OK);
        }

        $data = $serializer->serialize($orders, 'json', ['groups' => 'order:read']);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}
