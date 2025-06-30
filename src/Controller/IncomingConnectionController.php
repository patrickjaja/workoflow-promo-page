<?php

namespace App\Controller;

use App\Entity\IncomingConnection;
use App\Entity\User;
use App\Repository\IncomingConnectionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IncomingConnectionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private IncomingConnectionRepository $incomingConnectionRepository,
        private UserRepository $userRepository
    ) {}

    #[Route('/account/incoming-connections', name: 'account_incoming_connections_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        $connections = $this->incomingConnectionRepository->findByUser($user);
        
        return $this->render('account/incoming_connections.html.twig', [
            'user' => $user,
            'connections' => $connections,
        ]);
    }

    #[Route('/account/incoming-connections/list', name: 'account_incoming_connections_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        $connections = $this->incomingConnectionRepository->findByUser($user);
        
        $data = array_map(function(IncomingConnection $connection) {
            return [
                'id' => $connection->getId(),
                'interfaceType' => $connection->getInterfaceType(),
                'interfaceTypeLabel' => $connection->getInterfaceTypeLabel(),
                'connectionId' => $connection->getConnectionId(),
                'integrationsCount' => $connection->getServiceIntegrations()->count(),
                'createdAt' => $connection->getCreatedAt()->format('Y-m-d H:i'),
            ];
        }, $connections);
        
        return $this->json(['connections' => $data]);
    }

    #[Route('/account/incoming-connections', name: 'account_incoming_connections_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        
        $data = json_decode($request->getContent(), true);
        $interfaceType = $data['interfaceType'] ?? '';
        $connectionId = $data['connectionId'] ?? '';
        $msAppId = $data['msAppId'] ?? null;
        $msAppPassword = $data['msAppPassword'] ?? null;
        
        if (empty($interfaceType) || empty($connectionId)) {
            return $this->json(['error' => 'Interface type and connection ID are required'], Response::HTTP_BAD_REQUEST);
        }
        
        if (!array_key_exists($interfaceType, IncomingConnection::AVAILABLE_INTERFACE_TYPES)) {
            return $this->json(['error' => 'Invalid interface type'], Response::HTTP_BAD_REQUEST);
        }
        
        // Validate MS Teams specific fields
        if ($interfaceType === IncomingConnection::INTERFACE_TYPE_MS_TEAMS) {
            if (empty($msAppId) || empty($msAppPassword)) {
                return $this->json(['error' => 'Microsoft App ID and Password are required for MS Teams connections'], Response::HTTP_BAD_REQUEST);
            }
        }
        
        // Check if connection ID already exists for this user
        $existingConnection = $this->incomingConnectionRepository->findByUserAndConnectionId($user, $connectionId);
        if ($existingConnection) {
            return $this->json(['error' => 'You already have this connection ID'], Response::HTTP_BAD_REQUEST);
        }
        
        // Check if connection ID is used by another user
        if ($this->incomingConnectionRepository->isConnectionIdUsedByOtherUser($connectionId)) {
            return $this->json(['error' => 'This connection ID is already in use by another user'], Response::HTTP_BAD_REQUEST);
        }
        
        $connection = new IncomingConnection();
        $connection->setUser($user);
        $connection->setInterfaceType($interfaceType);
        $connection->setConnectionId($connectionId);
        
        // Set MS Teams specific fields
        if ($interfaceType === IncomingConnection::INTERFACE_TYPE_MS_TEAMS) {
            $connection->setMsAppId($msAppId);
            $connection->setMsAppPassword($msAppPassword);
        }
        
        $this->entityManager->persist($connection);
        $this->entityManager->flush();
        
        return $this->json([
            'success' => true,
            'connection' => [
                'id' => $connection->getId(),
                'interfaceType' => $connection->getInterfaceType(),
                'interfaceTypeLabel' => $connection->getInterfaceTypeLabel(),
                'connectionId' => $connection->getConnectionId(),
            ]
        ]);
    }

    #[Route('/account/incoming-connections/{id}', name: 'account_incoming_connections_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        
        $connection = $this->incomingConnectionRepository->find($id);
        if (!$connection || $connection->getUser() !== $user) {
            return $this->json(['error' => 'Connection not found'], Response::HTTP_NOT_FOUND);
        }
        
        // Check if connection is used by any integrations
        if ($connection->getServiceIntegrations()->count() > 0) {
            return $this->json(['error' => 'Cannot delete connection that is used by integrations'], Response::HTTP_BAD_REQUEST);
        }
        
        $this->entityManager->remove($connection);
        $this->entityManager->flush();
        
        return $this->json(['success' => true]);
    }
}