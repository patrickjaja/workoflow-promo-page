<?php

namespace App\Controller;

use App\Entity\Subscription;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class SubscriptionController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private MailerInterface $mailer;

    public function __construct(EntityManagerInterface $entityManager, MailerInterface $mailer)
    {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
    }

    public function subscribe(string $plan, Request $request): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('connect_google_start');
        }

        $user = $this->getUser();
        
        if (!$user->isOrganizationAdmin()) {
            $this->addFlash('error', 'Access denied. Only organization administrators can manage subscriptions.');
            return $this->redirectToRoute('account_profile');
        }

        switch ($plan) {
            case 'free':
                return $this->handleFreeSubscription($user);
            case 'pro':
                return $this->handleProSubscription($user, $request);
            case 'enterprise':
                return $this->handleEnterpriseSubscription($user);
            default:
                throw $this->createNotFoundException('Invalid subscription plan');
        }
    }

    private function handleFreeSubscription($user): Response
    {
        $subscription = new Subscription();
        $subscription->setUser($user);
        $subscription->setPlan('free');
        $subscription->setAmount('0.00');
        $subscription->setCurrency('EUR');
        $subscription->setStatus('active');

        $user->setSubscriptionPlan('free');

        $this->entityManager->persist($subscription);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $email = (new Email())
            ->from('noreply@workoflow.com')
            ->to('admin@workoflow.com')
            ->subject('New Teams AI Integration Demo Request')
            ->text(sprintf('User %s (%s) has signed up for a Teams AI integration proof of concept demo. 

Contact them to set up:
- Teams bot deployment demo
- Basic n8n workflow demonstration  
- GDPR compliance overview
- Next steps for production deployment

GitHub repos:
- Teams Bot: https://github.com/patrickjaja/workoflow-bot
- Hosting: https://github.com/patrickjaja/workoflow-hosting', 
                $user->getName(), $user->getEmail()));

        $this->mailer->send($email);

        $this->addFlash('success', 'Demo request submitted! We\'ll contact you to set up your Teams AI integration proof of concept.');
        return $this->redirectToRoute('index');
    }

    private function handleProSubscription($user, Request $request): Response
    {
        return $this->redirectToRoute('payment_create', ['plan' => 'pro']);
    }

    private function handleEnterpriseSubscription($user): Response
    {
        return $this->redirectToRoute('enterprise_contact');
    }
}