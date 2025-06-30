<?php

namespace App\Controller;

use App\Entity\AccessToken;
use App\Entity\User;
use App\Repository\ServiceIntegrationRepository;
use App\Repository\IncomingConnectionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AccountSettingsController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ServiceIntegrationRepository $serviceIntegrationRepository,
        private UserRepository $userRepository,
        private IncomingConnectionRepository $incomingConnectionRepository
    ) {}

    public function settings(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        $serviceIntegrations = $this->serviceIntegrationRepository->findAllByUser($user);
        
        $integrationsByService = [];
        foreach ($serviceIntegrations as $integration) {
            $integrationsByService[$integration->getService()][] = $integration;
        }
        
        $incomingConnections = $this->incomingConnectionRepository->findByUser($user);

        return $this->render('account/settings.html.twig', [
            'user' => $user,
            'integrationsByService' => $integrationsByService,
            'availableServices' => AccessToken::AVAILABLE_SERVICES,
            'incomingConnections' => $incomingConnections,
        ]);
    }

    public function profile(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        
        return $this->render('account/profile.html.twig', [
            'user' => $user,
        ]);
    }

    public function updateProfile(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();

        $teamsAccountName = $request->request->get('teams_account_name');
        if ($teamsAccountName !== null) {
            // Check if Teams ID is already in use by another user
            if ($teamsAccountName) {
                $existingUser = $this->userRepository->findByTeamsAccountName($teamsAccountName);
                if ($existingUser && $existingUser->getId() !== $user->getId()) {
                    $this->addFlash('error', sprintf('This Microsoft Teams ID is already in use by %s', $existingUser->getEmail()));
                    return $this->redirectToRoute('account_settings');
                }
            }
            $user->setTeamsAccountName($teamsAccountName ?: null);
        }

        if ($user->isOrganizationAdmin()) {
            $organizationName = $request->request->get('organization_name');
            if ($organizationName !== null) {
                $user->setOrganizationName($organizationName ?: null);
            }
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Profile updated successfully.');
        
        return $this->redirectToRoute('account_settings');
    }
}