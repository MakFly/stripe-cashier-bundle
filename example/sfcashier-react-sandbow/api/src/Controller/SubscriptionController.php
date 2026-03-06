<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiResponseHelper;
use App\Billing\SubscriptionCatalog;
use App\Serializer\SubscriptionSerializer;
use App\Service\Request\FrontendOriginResolver;
use App\Service\Request\LocaleResolver;
use App\Service\User\CurrentUserResolver;
use CashierBundle\Entity\Subscription;
use CashierBundle\Service\CheckoutService;
use CashierBundle\Service\SubscriptionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/subscriptions')]
class SubscriptionController extends AbstractController
{
    use ApiResponseHelper;

    public function __construct(
        private readonly SubscriptionCatalog $catalog,
        private readonly CheckoutService $checkoutService,
        private readonly SubscriptionService $subscriptionService,
        private readonly SubscriptionSerializer $subscriptionSerializer,
        private readonly CurrentUserResolver $currentUserResolver,
        private readonly LocaleResolver $localeResolver,
        private readonly FrontendOriginResolver $frontendOriginResolver,
    ) {
    }

    #[Route('/plans', name: 'api_subscriptions_plans', methods: ['GET'])]
    public function plans(): JsonResponse
    {
        $plans = $this->catalog->publicPlans();

        return $this->apiCollection(
            '/api/v1/contexts/SubscriptionPlan',
            '/api/v1/subscriptions/plans',
            $plans,
            count($plans),
        );
    }

    #[Route('/current', name: 'api_subscriptions_current', methods: ['GET'])]
    public function current(SessionInterface $session): JsonResponse
    {
        $user = $this->currentUserResolver->resolve($session, $this->getUser());
        if ($user === null) {
            return $this->apiError('Not authenticated', Response::HTTP_UNAUTHORIZED);
        }

        return $this->apiResource(
            'SubscriptionState',
            '/api/v1/subscriptions/current',
            $this->subscriptionSerializer->serialize($user->subscription()),
        );
    }

    #[Route('/checkout/session', name: 'api_subscriptions_checkout_session', methods: ['POST'])]
    public function createCheckoutSession(Request $request, SessionInterface $session): JsonResponse
    {
        $user = $this->currentUserResolver->resolve($session, $this->getUser());
        if ($user === null) {
            return $this->apiError('Not authenticated', Response::HTTP_UNAUTHORIZED);
        }

        $payload = json_decode($request->getContent(), true);
        $planCode = is_string($payload['planCode'] ?? null) ? $payload['planCode'] : '';
        $billingCycle = is_string($payload['billingCycle'] ?? null) ? $payload['billingCycle'] : '';

        $plan = $this->catalog->findCheckoutPlan($planCode, $billingCycle);
        if ($plan === null) {
            return $this->apiError('Unknown subscription plan', Response::HTTP_BAD_REQUEST);
        }

        $priceId = $plan['checkout']['priceId'] ?? null;
        if (!is_string($priceId) || $priceId === '') {
            return $this->apiError('Subscription plan is not configured yet', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $currentSubscription = $user->subscription();
        if ($currentSubscription instanceof Subscription && $currentSubscription->valid()) {
            $matchedPlan = $this->catalog->matchStripePrice($currentSubscription->getStripePrice());
            if (($matchedPlan['code'] ?? null) === $planCode && ($matchedPlan['billingCycle'] ?? null) === $billingCycle) {
                return $this->apiError('This subscription is already active', Response::HTTP_CONFLICT);
            }

            return $this->apiError('A subscription is already active. Use billing management to change it.', Response::HTTP_CONFLICT);
        }

        $origin = $this->frontendOriginResolver->resolve($request);
        $preferredLocale = $this->localeResolver->resolve($request);
        $customerOptions = [
            'preferred_locales' => [$preferredLocale],
        ];

        $user->createOrGetStripeCustomer($customerOptions);
        $user->updateStripeCustomer($customerOptions);

        $metadata = [
            'app_resource_type' => 'subscription_plan',
            'app_resource_id' => $planCode,
            'app_user_id' => (string) $user->getId(),
            'plan_code' => $planCode,
            'billing_cycle' => $billingCycle,
        ];

        $subscriptionData = [
            'metadata' => $metadata,
        ];

        if ((int) ($plan['trialDays'] ?? 0) > 0) {
            $subscriptionData['trial_period_days'] = (int) $plan['trialDays'];
        }

        $checkoutSession = $this->checkoutService->createSubscription($user, [[
            'price' => $priceId,
            'quantity' => 1,
        ]], [
            'payment_method_types' => ['card'],
            'success_url' => sprintf('%s/subscriptions?checkout=success&session_id={CHECKOUT_SESSION_ID}', $origin),
            'cancel_url' => sprintf('%s/subscriptions?checkout=canceled', $origin),
            'metadata' => $metadata,
            'subscription_data' => $subscriptionData,
        ]);

        return $this->apiResource(
            'SubscriptionCheckoutSession',
            '/api/v1/subscriptions/checkout/session',
            [
                'planCode' => $planCode,
                'billingCycle' => $billingCycle,
                'checkoutUrl' => $checkoutSession->url(),
                'sessionId' => $checkoutSession->id(),
            ],
            Response::HTTP_CREATED,
        );
    }

    #[Route('/portal', name: 'api_subscriptions_portal', methods: ['GET'])]
    public function portal(Request $request, SessionInterface $session): JsonResponse
    {
        $user = $this->currentUserResolver->resolve($session, $this->getUser());
        if ($user === null) {
            return $this->apiError('Not authenticated', Response::HTTP_UNAUTHORIZED);
        }

        $origin = $this->frontendOriginResolver->resolve($request);
        $url = $user->billingPortalUrl(sprintf('%s/subscriptions', $origin));

        return $this->apiResponse(['url' => $url]);
    }

    #[Route('/cancel', name: 'api_subscriptions_cancel', methods: ['POST'])]
    public function cancel(SessionInterface $session): JsonResponse
    {
        $user = $this->currentUserResolver->resolve($session, $this->getUser());
        if ($user === null) {
            return $this->apiError('Not authenticated', Response::HTTP_UNAUTHORIZED);
        }

        $subscription = $user->subscription();
        if (!$subscription instanceof Subscription) {
            return $this->apiError('No active subscription found', Response::HTTP_NOT_FOUND);
        }

        $updated = $this->subscriptionService->cancel($subscription);

        return $this->apiResource(
            'SubscriptionState',
            '/api/v1/subscriptions/current',
            $this->subscriptionSerializer->serialize($updated),
        );
    }

    #[Route('/resume', name: 'api_subscriptions_resume', methods: ['POST'])]
    public function resume(SessionInterface $session): JsonResponse
    {
        $user = $this->currentUserResolver->resolve($session, $this->getUser());
        if ($user === null) {
            return $this->apiError('Not authenticated', Response::HTTP_UNAUTHORIZED);
        }

        $subscription = $user->subscription();
        if (!$subscription instanceof Subscription) {
            return $this->apiError('No subscription found', Response::HTTP_NOT_FOUND);
        }

        if (!$subscription->onGracePeriod()) {
            return $this->apiError('Subscription cannot be resumed', Response::HTTP_CONFLICT);
        }

        $updated = $this->subscriptionService->resume($subscription);

        return $this->apiResource(
            'SubscriptionState',
            '/api/v1/subscriptions/current',
            $this->subscriptionSerializer->serialize($updated),
        );
    }
}
