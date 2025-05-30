<?php

namespace App\Controller;

use App\Entity\Subscription;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PaymentController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
    }

    #[Route('/payment/create/{plan}', name: 'payment_create')]
    public function create(string $plan): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('connect_google_start');
        }

        $user = $this->getUser();

        if ($plan !== 'pro') {
            throw $this->createNotFoundException('Invalid payment plan');
        }

        try {
            $checkout_session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Workoflow Pro Subscription',
                            'description' => 'Monthly subscription to Workoflow Pro',
                        ],
                        'unit_amount' => 1000, // 10€ in cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $this->generateUrl('payment_success', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL),
                'cancel_url' => $this->generateUrl('payment_cancel', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL),
                'customer_email' => $user->getEmail(),
                'metadata' => [
                    'user_id' => $user->getId(),
                    'plan' => 'pro',
                ],
            ]);

            return $this->redirect($checkout_session->url);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Payment processing failed. Please try again.');
            return $this->redirectToRoute('index');
        }
    }

    public function success(Request $request): Response
    {
        $user = $this->getUser();
        
        if ($user) {
            $subscription = new Subscription();
            $subscription->setUser($user);
            $subscription->setPlan('pro');
            $subscription->setAmount('10.00');
            $subscription->setCurrency('EUR');
            $subscription->setStatus('active');
            $subscription->setExpiresAt((new \DateTime())->add(new \DateInterval('P1M')));

            $user->setSubscriptionPlan('pro');
            $user->setSubscriptionExpiresAt($subscription->getExpiresAt());

            $this->entityManager->persist($subscription);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'Payment successful! Welcome to Workoflow Pro!');
        }

        return $this->redirectToRoute('index');
    }

    public function cancel(): Response
    {
        $this->addFlash('warning', 'Payment was cancelled.');
        return $this->redirectToRoute('index');
    }
}