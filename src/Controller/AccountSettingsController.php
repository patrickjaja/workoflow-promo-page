<?php

namespace App\Controller;

use App\Entity\AccessToken;
use App\Entity\User;
use App\Repository\AccessTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AccountSettingsController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccessTokenRepository $accessTokenRepository
    ) {}

    public function settings(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        $accessTokens = $this->accessTokenRepository->findAllByUser($user);
        
        $tokensByService = [];
        foreach ($accessTokens as $token) {
            $tokensByService[$token->getService()] = $token;
        }

        return $this->render('account/settings.html.twig', [
            'user' => $user,
            'tokensByService' => $tokensByService,
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

    public function updateToken(Request $request, string $service): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        if (!in_array($service, AccessToken::AVAILABLE_SERVICES)) {
            throw $this->createNotFoundException('Invalid service');
        }

        /** @var User $user */
        $user = $this->getUser();
        
        $accessToken = $this->accessTokenRepository->findByUserAndService($user, $service);
        
        if (!$accessToken) {
            $accessToken = new AccessToken();
            $accessToken->setUser($user);
            $accessToken->setService($service);
            $this->entityManager->persist($accessToken);
        }

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

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('%s integration updated successfully.', ucfirst($service)));
        
        return $this->redirectToRoute('account_settings');
    }

    public function deleteToken(string $service): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        if (!in_array($service, AccessToken::AVAILABLE_SERVICES)) {
            throw $this->createNotFoundException('Invalid service');
        }

        /** @var User $user */
        $user = $this->getUser();
        $accessToken = $this->accessTokenRepository->findByUserAndService($user, $service);

        if ($accessToken) {
            $this->entityManager->remove($accessToken);
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('%s token deleted successfully.', ucfirst($service)));
        }

        return $this->redirectToRoute('account_settings');
    }
}