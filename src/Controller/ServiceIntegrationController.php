<?php

namespace App\Controller;

use App\Entity\AccessToken;
use App\Entity\CustomService;
use App\Entity\CustomServiceHeader;
use App\Entity\ServiceIntegration;
use App\Entity\User;
use App\Repository\ServiceIntegrationRepository;
use App\Repository\IncomingConnectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ServiceIntegrationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ServiceIntegrationRepository $serviceIntegrationRepository,
        private IncomingConnectionRepository $incomingConnectionRepository
    ) {}

    public function create(Request $request, string $service): Response
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
        
        // Set Incoming Connection from request
        $connectionId = $request->request->get('incoming_connection');
        if ($connectionId) {
            $connection = $this->incomingConnectionRepository->find($connectionId);
            if ($connection && $connection->getUser() === $user) {
                $integration->setIncomingConnection($connection);
            }
        }
        
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
        } elseif ($service === AccessToken::SERVICE_DECIDALO || $service === AccessToken::SERVICE_SERVICEMAP) {
            $token = $request->request->get('token');
            $apiUrl = $request->request->get('api_url');
            
            if (empty($token) || empty($apiUrl)) {
                $this->addFlash('error', 'Bearer token and API URL are required.');
                return $this->redirectToRoute('account_settings');
            }
            
            $accessToken->setToken($token);
            $accessToken->setApiUrl($apiUrl);
        } elseif ($service === AccessToken::SERVICE_CUSTOM_CONTENT_SEARCH) {
            // Custom Content Search doesn't require any configuration
            $accessToken->setToken('custom_content_search_enabled');
        } elseif ($service === AccessToken::SERVICE_CUSTOM) {
            // Custom service configuration
            $baseUrl = $request->request->get('base_url');
            if (empty($baseUrl)) {
                $this->addFlash('error', 'Base URL is required for custom service.');
                return $this->redirectToRoute('account_settings');
            }
            
            $customService = new CustomService();
            $customService->setServiceIntegration($integration);
            $customService->setBaseUrl($baseUrl);
            
            // Handle headers
            $headerNames = $request->request->all('header_names');
            $headerValues = $request->request->all('header_values');
            
            if (is_array($headerNames) && is_array($headerValues)) {
                foreach ($headerNames as $index => $headerName) {
                    if (!empty($headerName) && isset($headerValues[$index]) && !empty($headerValues[$index])) {
                        $header = new CustomServiceHeader();
                        $header->setHeaderName($headerName);
                        $header->setHeaderValue($headerValues[$index]);
                        $customService->addHeader($header);
                    }
                }
            }
            
            $integration->setCustomService($customService);
            $accessToken->setToken('custom_service_configured');
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

    public function update(Request $request, int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        
        $integration = $this->serviceIntegrationRepository->find($id);
        
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
        
        // Update Teams ID if provided
        $teamsIdValue = $request->request->get('teams_id');
        if ($teamsIdValue) {
            $teamsId = $this->teamsIdRepository->find($teamsIdValue);
            if ($teamsId && $teamsId->getUser() === $user) {
                $integration->setTeamsId($teamsId);
            }
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
        } elseif ($service === AccessToken::SERVICE_DECIDALO || $service === AccessToken::SERVICE_SERVICEMAP) {
            $token = $request->request->get('token');
            $apiUrl = $request->request->get('api_url');
            
            if ($token && $token !== '••••••••••••••••') {
                $accessToken->setToken($token);
            }
            if ($apiUrl) {
                $accessToken->setApiUrl($apiUrl);
            }
        } elseif ($service === AccessToken::SERVICE_CUSTOM_CONTENT_SEARCH) {
            // Custom Content Search doesn't require any configuration updates
        } elseif ($service === AccessToken::SERVICE_CUSTOM) {
            // Update custom service configuration
            $baseUrl = $request->request->get('base_url');
            $customService = $integration->getCustomService();
            
            if (!$customService) {
                $customService = new CustomService();
                $customService->setServiceIntegration($integration);
                $integration->setCustomService($customService);
            }
            
            if ($baseUrl) {
                $customService->setBaseUrl($baseUrl);
            }
            
            // Update headers - remove all existing and add new ones
            foreach ($customService->getHeaders() as $header) {
                $customService->removeHeader($header);
                $this->entityManager->remove($header);
            }
            
            $headerNames = $request->request->all('header_names');
            $headerValues = $request->request->all('header_values');
            
            if (is_array($headerNames) && is_array($headerValues)) {
                foreach ($headerNames as $index => $headerName) {
                    if (!empty($headerName) && isset($headerValues[$index]) && !empty($headerValues[$index])) {
                        $header = new CustomServiceHeader();
                        $header->setHeaderName($headerName);
                        $header->setHeaderValue($headerValues[$index]);
                        $customService->addHeader($header);
                    }
                }
            }
            
            $customService->setUpdatedAt(new \DateTimeImmutable());
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

    public function delete(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        
        $integration = $this->serviceIntegrationRepository->find($id);
        
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

    public function setDefault(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        
        $integration = $this->serviceIntegrationRepository->find($id);
        
        if (!$integration || $integration->getUser() !== $user) {
            throw $this->createNotFoundException('Integration not found');
        }

        $this->serviceIntegrationRepository->setDefaultForUserAndService($user, $integration->getService(), $integration);

        $this->addFlash('success', sprintf('Integration "%s" set as default.', $integration->getName()));

        return $this->redirectToRoute('account_settings');
    }

    public function toggle(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        
        $integration = $this->serviceIntegrationRepository->find($id);
        
        if (!$integration || $integration->getUser() !== $user) {
            throw $this->createNotFoundException('Integration not found');
        }

        $integration->setIsEnabled(!$integration->isEnabled());
        $this->entityManager->flush();

        $status = $integration->isEnabled() ? 'enabled' : 'disabled';
        $this->addFlash('success', sprintf('Integration "%s" has been %s.', $integration->getName(), $status));

        return $this->redirectToRoute('account_settings');
    }
}