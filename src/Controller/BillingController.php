<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Entity\Subscription;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscription;
use Stripe\BillingPortal\Session as BillingPortalSession;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BillingController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
    }

    #[Route('/account/billing', name: 'account_billing')]
    public function billing(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('connect_google_start');
        }

        if (!$user->isOrganizationAdmin()) {
            $this->addFlash('error', 'Access denied. Only organization administrators can access billing information.');
            return $this->redirectToRoute('account_profile');
        }

        $subscription = $this->entityManager->getRepository(Subscription::class)->findOneBy([
            'user' => $user,
            'status' => 'active'
        ]);

        $invoices = $this->entityManager->getRepository(Invoice::class)->findByUser($user);

        $stripeSubscription = null;
        if ($subscription && $subscription->getStripeSubscriptionId()) {
            try {
                $stripeSubscription = StripeSubscription::retrieve($subscription->getStripeSubscriptionId());
            } catch (\Exception $e) {
                $stripeSubscription = null;
            }
        }

        return $this->render('account/billing.html.twig', [
            'subscription' => $subscription,
            'stripeSubscription' => $stripeSubscription,
            'invoices' => $invoices,
        ]);
    }

    #[Route('/account/billing/portal', name: 'billing_portal')]
    public function billingPortal(): Response
    {
        $user = $this->getUser();
        if (!$user || !$user->getStripeCustomerId()) {
            $this->addFlash('error', 'No billing information found.');
            return $this->redirectToRoute('account_billing');
        }

        if (!$user->isOrganizationAdmin()) {
            $this->addFlash('error', 'Access denied. Only organization administrators can access billing information.');
            return $this->redirectToRoute('account_profile');
        }

        try {
            $session = BillingPortalSession::create([
                'customer' => $user->getStripeCustomerId(),
                'return_url' => $this->generateUrl('account_billing', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

            return $this->redirect($session->url);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Unable to access billing portal. Please try again.');
            return $this->redirectToRoute('account_billing');
        }
    }

    #[Route('/account/billing/cancel', name: 'cancel_subscription')]
    public function cancelSubscription(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('connect_google_start');
        }

        if (!$user->isOrganizationAdmin()) {
            $this->addFlash('error', 'Access denied. Only organization administrators can access billing information.');
            return $this->redirectToRoute('account_profile');
        }

        $subscription = $this->entityManager->getRepository(Subscription::class)->findOneBy([
            'user' => $user,
            'status' => 'active'
        ]);

        if (!$subscription || !$subscription->getStripeSubscriptionId()) {
            $this->addFlash('error', 'No active subscription found.');
            return $this->redirectToRoute('account_billing');
        }

        try {
            $stripeSubscription = StripeSubscription::retrieve($subscription->getStripeSubscriptionId());
            $stripeSubscription->cancel();

            $subscription->setStatus('cancelled');
            $this->entityManager->persist($subscription);
            $this->entityManager->flush();

            $this->addFlash('success', 'Your subscription has been cancelled successfully.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Unable to cancel subscription. Please try again.');
        }

        return $this->redirectToRoute('account_billing');
    }
}