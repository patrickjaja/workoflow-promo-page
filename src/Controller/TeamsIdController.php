<?php

namespace App\Controller;

use App\Entity\TeamsId;
use App\Entity\User;
use App\Repository\TeamsIdRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TeamsIdController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TeamsIdRepository $teamsIdRepository,
        private UserRepository $userRepository
    ) {}

    #[Route('/account/teams-ids', name: 'account_teams_ids_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        $teamsIds = $this->teamsIdRepository->findByUser($user);
        
        return $this->render('account/teams_ids.html.twig', [
            'user' => $user,
            'teamsIds' => $teamsIds,
        ]);
    }

    #[Route('/account/teams-ids/list', name: 'account_teams_ids_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        $teamsIds = $this->teamsIdRepository->findByUser($user);
        
        $data = array_map(function(TeamsId $teamsId) {
            return [
                'id' => $teamsId->getId(),
                'teamsId' => $teamsId->getTeamsId(),
                'displayName' => $teamsId->getDisplayName(),
                'isPrimary' => $teamsId->isPrimary(),
                'integrationsCount' => $teamsId->getServiceIntegrations()->count(),
                'createdAt' => $teamsId->getCreatedAt()->format('Y-m-d H:i'),
            ];
        }, $teamsIds);
        
        return $this->json(['teamsIds' => $data]);
    }

    #[Route('/account/teams-ids', name: 'account_teams_ids_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        
        $data = json_decode($request->getContent(), true);
        $teamsIdValue = $data['teamsId'] ?? '';
        $displayName = $data['displayName'] ?? '';
        $makePrimary = $data['makePrimary'] ?? false;
        
        if (empty($teamsIdValue)) {
            return $this->json(['error' => 'Teams ID is required'], Response::HTTP_BAD_REQUEST);
        }
        
        // Check if Teams ID already exists for this user
        $existingTeamsId = $this->teamsIdRepository->findByUserAndTeamsId($user, $teamsIdValue);
        if ($existingTeamsId) {
            return $this->json(['error' => 'You already have this Teams ID'], Response::HTTP_BAD_REQUEST);
        }
        
        // Check if Teams ID is used by another user
        if ($this->teamsIdRepository->isTeamsIdUsedByOtherUser($teamsIdValue)) {
            return $this->json(['error' => 'This Teams ID is already in use by another user'], Response::HTTP_BAD_REQUEST);
        }
        
        $teamsId = new TeamsId();
        $teamsId->setUser($user);
        $teamsId->setTeamsId($teamsIdValue);
        $teamsId->setDisplayName($displayName ?: $teamsIdValue);
        
        // If this is the first Teams ID or user wants it as primary
        $existingTeamsIds = $this->teamsIdRepository->findByUser($user);
        if (empty($existingTeamsIds) || $makePrimary) {
            // Remove primary flag from other Teams IDs
            foreach ($existingTeamsIds as $existing) {
                $existing->setIsPrimary(false);
            }
            $teamsId->setIsPrimary(true);
        }
        
        $this->entityManager->persist($teamsId);
        $this->entityManager->flush();
        
        return $this->json([
            'success' => true,
            'teamsId' => [
                'id' => $teamsId->getId(),
                'teamsId' => $teamsId->getTeamsId(),
                'displayName' => $teamsId->getDisplayName(),
                'isPrimary' => $teamsId->isPrimary(),
            ]
        ]);
    }

    #[Route('/account/teams-ids/{id}', name: 'account_teams_ids_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        
        $teamsId = $this->teamsIdRepository->find($id);
        if (!$teamsId || $teamsId->getUser() !== $user) {
            return $this->json(['error' => 'Teams ID not found'], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        $displayName = $data['displayName'] ?? null;
        $makePrimary = $data['makePrimary'] ?? false;
        
        if ($displayName !== null) {
            $teamsId->setDisplayName($displayName);
        }
        
        if ($makePrimary && !$teamsId->isPrimary()) {
            // Remove primary flag from other Teams IDs
            $existingTeamsIds = $this->teamsIdRepository->findByUser($user);
            foreach ($existingTeamsIds as $existing) {
                $existing->setIsPrimary(false);
            }
            $teamsId->setIsPrimary(true);
        }
        
        $teamsId->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
        
        return $this->json([
            'success' => true,
            'teamsId' => [
                'id' => $teamsId->getId(),
                'teamsId' => $teamsId->getTeamsId(),
                'displayName' => $teamsId->getDisplayName(),
                'isPrimary' => $teamsId->isPrimary(),
            ]
        ]);
    }

    #[Route('/account/teams-ids/{id}', name: 'account_teams_ids_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        
        $teamsId = $this->teamsIdRepository->find($id);
        if (!$teamsId || $teamsId->getUser() !== $user) {
            return $this->json(['error' => 'Teams ID not found'], Response::HTTP_NOT_FOUND);
        }
        
        // Check if Teams ID is used by any integrations
        if ($teamsId->getServiceIntegrations()->count() > 0) {
            return $this->json(['error' => 'Cannot delete Teams ID that is used by integrations'], Response::HTTP_BAD_REQUEST);
        }
        
        $wasPrimary = $teamsId->isPrimary();
        
        $this->entityManager->remove($teamsId);
        $this->entityManager->flush();
        
        // If we deleted the primary Teams ID, make the first remaining one primary
        if ($wasPrimary) {
            $remainingTeamsIds = $this->teamsIdRepository->findByUser($user);
            if (!empty($remainingTeamsIds)) {
                $remainingTeamsIds[0]->setIsPrimary(true);
                $this->entityManager->flush();
            }
        }
        
        return $this->json(['success' => true]);
    }
}