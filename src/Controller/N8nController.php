<?php

namespace App\Controller;

use App\Entity\N8nEnvironmentVariable;
use App\Entity\User;
use App\Repository\N8nEnvironmentVariableRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class N8nController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private N8nEnvironmentVariableRepository $n8nRepository
    ) {}

    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        $variables = $this->n8nRepository->findAllByUser($user);
        
        // Convert to associative array for easier template access
        $variablesByName = [];
        foreach ($variables as $variable) {
            $variablesByName[$variable->getVariableName()] = $variable;
        }
        
        // Ensure all available variables are represented
        foreach (N8nEnvironmentVariable::AVAILABLE_VARIABLES as $variableName => $displayName) {
            if (!isset($variablesByName[$variableName])) {
                $newVariable = new N8nEnvironmentVariable();
                $newVariable->setUser($user);
                $newVariable->setVariableName($variableName);
                $newVariable->setVariableValue(N8nEnvironmentVariable::DEFAULT_VALUES[$variableName] ?? '');
                $variablesByName[$variableName] = $newVariable;
            }
        }

        return $this->render('account/n8n.html.twig', [
            'user' => $user,
            'variables' => $variablesByName,
            'availableVariables' => N8nEnvironmentVariable::AVAILABLE_VARIABLES,
            'n8nInstanceUrl' => 'https://workflows.vcec.cloud/projects/pydKOmtbkGMGv7u6/workflows',
        ]);
    }

    public function updateVariable(Request $request, string $variableName): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        if (!array_key_exists($variableName, N8nEnvironmentVariable::AVAILABLE_VARIABLES)) {
            throw $this->createNotFoundException('Invalid variable name');
        }

        /** @var User $user */
        $user = $this->getUser();
        
        $variable = $this->n8nRepository->findByUserAndVariableName($user, $variableName);
        
        if (!$variable) {
            $variable = new N8nEnvironmentVariable();
            $variable->setUser($user);
            $variable->setVariableName($variableName);
            $this->entityManager->persist($variable);
        }

        $value = $request->request->get('value', '');
        $variable->setVariableValue($value);

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Environment variable "%s" updated successfully.', $variable->getDisplayName()));
        
        return $this->redirectToRoute('account_n8n');
    }

    public function resetVariable(string $variableName): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        if (!array_key_exists($variableName, N8nEnvironmentVariable::AVAILABLE_VARIABLES)) {
            throw $this->createNotFoundException('Invalid variable name');
        }

        /** @var User $user */
        $user = $this->getUser();
        $variable = $this->n8nRepository->findByUserAndVariableName($user, $variableName);

        if ($variable) {
            $defaultValue = N8nEnvironmentVariable::DEFAULT_VALUES[$variableName] ?? '';
            $variable->setVariableValue($defaultValue);
            $this->entityManager->flush();
            
            $this->addFlash('success', sprintf('Environment variable "%s" reset to default value.', $variable->getDisplayName()));
        }

        return $this->redirectToRoute('account_n8n');
    }
}