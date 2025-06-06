<?php

namespace App\Controller;

use App\Entity\AccessToken;
use App\Entity\ServiceIntegration;
use App\Entity\User;
use App\Repository\AccessTokenRepository;
use App\Repository\ServiceIntegrationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

class AccountSettingsController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccessTokenRepository $accessTokenRepository,
        private ServiceIntegrationRepository $serviceIntegrationRepository,
        private UserRepository $userRepository
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

        return $this->render('account/settings.html.twig', [
            'user' => $user,
            'integrationsByService' => $integrationsByService,
            'availableServices' => AccessToken::AVAILABLE_SERVICES,
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
                    return $this->redirectToRoute('account_profile');
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
        
        return $this->redirectToRoute('account_profile');
    }

    public function createIntegration(Request $request, string $service): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        if (!in_array($service, AccessToken::AVAILABLE_SERVICES)) {
            throw $this->createNotFoundException('Invalid service');
        }

        /** @var User $user */
        $user = $this->getUser();
        
        $name = $request->request->get('name');
        $description = $request->request->get('description');
        
        if (empty($name)) {
            $this->addFlash('error', 'Integration name is required.');
            return $this->redirectToRoute('account_settings');
        }

        $integration = new ServiceIntegration();
        $integration->setUser($user);
        $integration->setService($service);
        $integration->setName($name);
        $integration->setDescription($description);
        
        $accessToken = new AccessToken();
        $accessToken->setServiceIntegration($integration);

        if ($service === AccessToken::SERVICE_ATLASSIAN) {
            $confluenceUrl = $request->request->get('confluence_url');
            $confluenceUsername = $request->request->get('confluence_username');
            $confluenceApiToken = $request->request->get('confluence_api_token');
            $jiraUrl = $request->request->get('jira_url');
            $jiraUsername = $request->request->get('jira_username');
            $jiraApiToken = $request->request->get('jira_api_token');

            if (empty($confluenceUrl) || empty($confluenceUsername) || empty($confluenceApiToken) || 
                empty($jiraUrl) || empty($jiraUsername) || empty($jiraApiToken)) {
                $this->addFlash('error', 'All Atlassian fields are required.');
                return $this->redirectToRoute('account_settings');
            }

            $accessToken->setConfluenceUrl($confluenceUrl);
            $accessToken->setConfluenceUsername($confluenceUsername);
            $accessToken->setConfluenceApiToken($confluenceApiToken);
            $accessToken->setJiraUrl($jiraUrl);
            $accessToken->setJiraUsername($jiraUsername);
            $accessToken->setJiraApiToken($jiraApiToken);
            $accessToken->setToken('atlassian_configured');
        } else {
            $token = $request->request->get('token');
            if (empty($token)) {
                $this->addFlash('error', 'Token cannot be empty.');
                return $this->redirectToRoute('account_settings');
            }
            $accessToken->setToken($token);
        }

        $integration->addAccessToken($accessToken);
        
        $existingIntegrations = $this->serviceIntegrationRepository->findByUserAndService($user, $service);
        if (empty($existingIntegrations)) {
            $integration->setIsDefault(true);
        }

        $this->entityManager->persist($integration);
        $this->entityManager->persist($accessToken);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('%s integration "%s" created successfully.', ucfirst($service), $name));
        
        return $this->redirectToRoute('account_settings');
    }

    public function updateIntegration(Request $request, int $integrationId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        
        $integration = $this->serviceIntegrationRepository->find($integrationId);
        
        if (!$integration || $integration->getUser() !== $user) {
            throw $this->createNotFoundException('Integration not found');
        }

        $name = $request->request->get('name');
        $description = $request->request->get('description');
        
        if ($name) {
            $integration->setName($name);
        }
        if ($description !== null) {
            $integration->setDescription($description);
        }
        
        $accessToken = $integration->getAccessTokens()->first();
        if (!$accessToken) {
            $accessToken = new AccessToken();
            $accessToken->setServiceIntegration($integration);
            $integration->addAccessToken($accessToken);
        }

        $service = $integration->getService();
        if ($service === AccessToken::SERVICE_ATLASSIAN) {
            $confluenceUrl = $request->request->get('confluence_url');
            $confluenceUsername = $request->request->get('confluence_username');
            $confluenceApiToken = $request->request->get('confluence_api_token');
            $jiraUrl = $request->request->get('jira_url');
            $jiraUsername = $request->request->get('jira_username');
            $jiraApiToken = $request->request->get('jira_api_token');

            if ($confluenceUrl) $accessToken->setConfluenceUrl($confluenceUrl);
            if ($confluenceUsername) $accessToken->setConfluenceUsername($confluenceUsername);
            if ($confluenceApiToken && $confluenceApiToken !== '••••••••••••••••') {
                $accessToken->setConfluenceApiToken($confluenceApiToken);
            }
            if ($jiraUrl) $accessToken->setJiraUrl($jiraUrl);
            if ($jiraUsername) $accessToken->setJiraUsername($jiraUsername);
            if ($jiraApiToken && $jiraApiToken !== '••••••••••••••••') {
                $accessToken->setJiraApiToken($jiraApiToken);
            }
        } else {
            $token = $request->request->get('token');
            if ($token && $token !== '••••••••••••••••') {
                $accessToken->setToken($token);
            }
        }

        $integration->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Integration "%s" updated successfully.', $integration->getName()));
        
        return $this->redirectToRoute('account_settings');
    }

    public function deleteIntegration(int $integrationId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        
        $integration = $this->serviceIntegrationRepository->find($integrationId);
        
        if (!$integration || $integration->getUser() !== $user) {
            throw $this->createNotFoundException('Integration not found');
        }

        $service = $integration->getService();
        $name = $integration->getName();
        
        $this->entityManager->remove($integration);
        $this->entityManager->flush();
        
        $remainingIntegrations = $this->serviceIntegrationRepository->findByUserAndService($user, $service);
        if (!empty($remainingIntegrations) && !array_filter($remainingIntegrations, fn($i) => $i->isDefault())) {
            $remainingIntegrations[0]->setIsDefault(true);
            $this->entityManager->flush();
        }

        $this->addFlash('success', sprintf('Integration "%s" deleted successfully.', $name));

        return $this->redirectToRoute('account_settings');
    }

    public function setDefaultIntegration(int $integrationId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        
        $integration = $this->serviceIntegrationRepository->find($integrationId);
        
        if (!$integration || $integration->getUser() !== $user) {
            throw $this->createNotFoundException('Integration not found');
        }

        $this->serviceIntegrationRepository->setDefaultForUserAndService($user, $integration->getService(), $integration);

        $this->addFlash('success', sprintf('Integration "%s" set as default.', $integration->getName()));

        return $this->redirectToRoute('account_settings');
    }
}