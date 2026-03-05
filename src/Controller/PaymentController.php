<?php

declare(strict_types=1);

namespace CashierBundle\Controller;

use CashierBundle\Exception\IncompletePaymentException;
use CashierBundle\Model\Payment;
use Stripe\Exception\ExceptionInterface as StripeException;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/cashier/payment')]
class PaymentController extends AbstractController
{
    public function __construct(
        private readonly StripeClient $stripeClient,
        private readonly string $stripePublishableKey,
    ) {
    }

    /**
     * Show payment confirmation page for SCA payments.
     */
    #[Route('/{paymentIntentId}', name: 'cashier_payment_show', methods: ['GET'])]
    public function show(Request $request, string $paymentIntentId): Response
    {
        try {
            $paymentIntent = $this->stripeClient->paymentIntents->retrieve($paymentIntentId);

            $returnUrl = $request->query->get('return_url')
                ?? $this->generateUrl('home', [], UrlGeneratorInterface::ABSOLUTE_URL);

            return $this->render('@Cashier/payment/show.html.twig', [
                'stripeKey' => $this->stripePublishableKey,
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntentId,
                'return_url' => $returnUrl,
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency,
            ]);
        } catch (StripeException $e) {
            throw $this->createNotFoundException('Payment intent not found.', $e);
        }
    }

    /**
     * Handle payment confirmation.
     */
    #[Route('/{paymentIntentId}/confirm', name: 'cashier_payment_confirm', methods: ['POST'])]
    public function confirm(Request $request, string $paymentIntentId): Response
    {
        try {
            $paymentIntent = $this->stripeClient->paymentIntents->confirm(
                $paymentIntentId,
                ['return_url' => $request->request->get('return_url')],
            );

            if ($paymentIntent->status === 'succeeded') {
                $this->addFlash('success', 'Payment confirmed successfully.');

                return $this->redirect($request->request->get('return_url') ?? '/');
            }

            if ($paymentIntent->next_action?->type === 'redirect_to_url') {
                return $this->redirect($paymentIntent->next_action->redirect_to_url->url);
            }

            $this->addFlash('error', 'Payment could not be confirmed.');

            return $this->redirectToRoute('cashier_payment_show', [
                'paymentIntentId' => $paymentIntentId,
            ]);
        } catch (StripeException $e) {
            $this->addFlash('error', 'Payment confirmation failed: ' . $e->getMessage());

            return $this->redirectToRoute('cashier_payment_show', [
                'paymentIntentId' => $paymentIntentId,
            ]);
        }
    }

    /**
     * Handle incomplete payment exceptions.
     */
    public function handleIncompletePayment(IncompletePaymentException $exception): Response
    {
        $payment = $exception->payment();

        if ($payment->requiresAction()) {
            return $this->redirectToRoute('cashier_payment_show', [
                'paymentIntentId' => $payment->id(),
            ]);
        }

        if ($payment->requiresPaymentMethod()) {
            $this->addFlash('error', 'Payment requires a valid payment method.');

            return $this->redirectToRoute('cashier_payment_method');
        }

        if ($payment->requiresConfirmation()) {
            try {
                $payment->asStripePaymentIntent()->confirm();
            } catch (StripeException $e) {
                $this->addFlash('error', 'Payment confirmation failed: ' . $e->getMessage());

                return $this->redirectToRoute('cashier_payment_show', [
                    'paymentIntentId' => $payment->id(),
                ]);
            }
        }

        $this->addFlash('error', 'Payment incomplete. Please try again.');

        return $this->redirectToRoute('cashier_payment_show', [
            'paymentIntentId' => $payment->id(),
        ]);
    }
}
