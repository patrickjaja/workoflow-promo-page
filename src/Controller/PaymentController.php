<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Entity\Subscription;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Invoice as StripeInvoice;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;
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
            $customer = null;
            if ($user->getStripeCustomerId()) {
                try {
                    $customer = Customer::retrieve($user->getStripeCustomerId());
                } catch (\Exception $e) {
                    $customer = null;
                }
            }

            if (!$customer) {
                $customer = Customer::create([
                    'email' => $user->getEmail(),
                    'name' => $user->getName(),
                    'metadata' => [
                        'user_id' => $user->getId(),
                    ],
                ]);
                $user->setStripeCustomerId($customer->id);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }

            $checkout_session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Workoflow Pro Subscription',
                            'description' => 'Monthly subscription to Workoflow Pro',
                        ],
                        'unit_amount' => 49900,
                        'recurring' => [
                            'interval' => 'month',
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'customer' => $customer->id,
                'success_url' => $this->generateUrl('payment_success', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL),
                'cancel_url' => $this->generateUrl('payment_cancel', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL),
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
            $subscription->setAmount('499.00');
            $subscription->setCurrency('EUR');
            $subscription->setStatus('active');
            $subscription->setExpiresAt((new \DateTime())->add(new \DateInterval('P1M')));

            $user->setSubscriptionPlan('pro');
            $user->setSubscriptionExpiresAt($subscription->getExpiresAt());

            $this->entityManager->persist($subscription);
            $this->entityManager->persist($user);

            $invoice = new Invoice();
            $invoice->setUser($user);
            $invoice->setSubscription($subscription);
            $invoice->setAmount('499.00');
            $invoice->setCurrency('EUR');
            $invoice->setDueDate(new \DateTime());
            $invoice->setDescription('Workoflow Pro Monthly Subscription');
            $invoice->markAsPaid();

            $this->entityManager->persist($invoice);
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

    #[Route('/invoice/{stripeInvoiceId}/download', name: 'invoice_download')]
    public function downloadInvoice(string $stripeInvoiceId): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('connect_google_start');
        }

        $invoice = $this->entityManager->getRepository(Invoice::class)->findOneBy([
            'stripeInvoiceId' => $stripeInvoiceId,
            'user' => $user
        ]);

        if (!$invoice) {
            throw $this->createNotFoundException('Invoice not found');
        }

        try {
            $stripeInvoice = StripeInvoice::retrieve($stripeInvoiceId);
            
            if (!$stripeInvoice->invoice_pdf) {
                throw $this->createNotFoundException('Invoice PDF not available');
            }

            $pdfContent = file_get_contents($stripeInvoice->invoice_pdf);
            $filename = sprintf('invoice-%s.pdf', $invoice->getInvoiceNumber());
            
            $response = new Response($pdfContent);
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', 
                $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename)
            );

            return $response;
        } catch (\Exception $e) {
            $this->addFlash('error', 'Unable to download invoice. Please try again later.');
            return $this->redirectToRoute('account_billing');
        }
    }

    #[Route('/webhook/stripe', name: 'stripe_webhook', methods: ['POST'])]
    public function stripeWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sig_header = $request->headers->get('stripe-signature');
        $endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';

        try {
            $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid payload'], 400);
        }

        switch ($event->type) {
            case 'customer.subscription.created':
                $this->handleSubscriptionCreated($event->data->object);
                break;
            case 'invoice.payment_succeeded':
                $this->handleInvoicePaymentSucceeded($event->data->object);
                break;
            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object);
                break;
        }

        return new JsonResponse(['status' => 'success']);
    }

    private function handleSubscriptionCreated($subscription): void
    {
        $user = $this->entityManager->getRepository(\App\Entity\User::class)->findOneBy([
            'stripeCustomerId' => $subscription->customer
        ]);

        if ($user) {
            $sub = new Subscription();
            $sub->setUser($user);
            $sub->setPlan('pro');
            $sub->setAmount('499.00');
            $sub->setCurrency('EUR');
            $sub->setStatus('active');
            $sub->setStripeSubscriptionId($subscription->id);
            $sub->setExpiresAt((new \DateTime())->setTimestamp($subscription->current_period_end));

            $user->setSubscriptionPlan('pro');
            $user->setSubscriptionExpiresAt($sub->getExpiresAt());

            $this->entityManager->persist($sub);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }

    private function handleInvoicePaymentSucceeded($stripeInvoice): void
    {
        $user = $this->entityManager->getRepository(\App\Entity\User::class)->findOneBy([
            'stripeCustomerId' => $stripeInvoice->customer
        ]);

        if ($user) {
            $subscription = $this->entityManager->getRepository(Subscription::class)->findOneBy([
                'stripeSubscriptionId' => $stripeInvoice->subscription
            ]);

            $invoice = new Invoice();
            $invoice->setUser($user);
            $invoice->setSubscription($subscription);
            $invoice->setAmount(number_format($stripeInvoice->amount_paid / 100, 2));
            $invoice->setCurrency(strtoupper($stripeInvoice->currency));
            $invoice->setDueDate((new \DateTime())->setTimestamp($stripeInvoice->due_date ?? $stripeInvoice->created));
            $invoice->setDescription('Workoflow Pro Monthly Subscription');
            $invoice->setStripeInvoiceId($stripeInvoice->id);
            $invoice->markAsPaid();

            $this->entityManager->persist($invoice);
            $this->entityManager->flush();
        }
    }

    private function handleSubscriptionDeleted($subscription): void
    {
        $user = $this->entityManager->getRepository(\App\Entity\User::class)->findOneBy([
            'stripeCustomerId' => $subscription->customer
        ]);

        if ($user) {
            $sub = $this->entityManager->getRepository(Subscription::class)->findOneBy([
                'stripeSubscriptionId' => $subscription->id
            ]);

            if ($sub) {
                $sub->setStatus('cancelled');
                $user->setSubscriptionPlan(null);
                $user->setSubscriptionExpiresAt(null);

                $this->entityManager->persist($sub);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }
        }
    }
}
