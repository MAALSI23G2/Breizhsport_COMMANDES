<?php

namespace App\Controller;

use App\Message\PanierGetOne;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Uuid;

#[Route('/order', name: 'order')]
class OrderController extends AbstractController
{
    private MessageBusInterface $messageBus;
    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ExceptionInterface
     */
    #[Route('/send_order', name: 'get_send_order', methods: ['POST'])]
    public function sendOrder(Request $request): JsonResponse
    {
        // Décoder le contenu JSON envoyé par le frontend
        $data = json_decode($request->getContent(), true);

        // Vérifier que $data est bien un tableau et que userId est présent
        if (!is_array($data) || !isset($data['userId'])) {
            return new JsonResponse(['error' => 'userId is required'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier que userId est un entier avant de faire le cast
        if (is_numeric($data['userId']) && (int) $data['userId'] == $data['userId']) {
            // S'assurer que userId est bien un entier
            $userId = (int) $data['userId'];
        } else {
            return new JsonResponse(['error' => 'userId must be an integer'], Response::HTTP_BAD_REQUEST);
        }

        $uniqueId = Uuid::v4()->toRfc4122();

        // Envoi d'un message dans RabbitMQ via Symfony Messenger
        $this->messageBus->dispatch(new PanierGetOne($userId, $uniqueId));

        // Retourner une réponse indiquant que le message a bien été envoyé
        return new JsonResponse(['message' => "PanierGetOne UniqueId: $uniqueId  IdUser : $userId"], Response::HTTP_OK);
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
