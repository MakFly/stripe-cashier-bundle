<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use CashierBundle\Repository\StripeCustomerRepository;
use CashierBundle\Repository\SubscriptionRepository;
use CashierBundle\Webhook\Handler\CheckoutSessionCompletedHandler;
use CashierBundle\Webhook\Handler\CustomerDeletedHandler;
use CashierBundle\Webhook\Handler\CustomerUpdatedHandler;
use CashierBundle\Webhook\Handler\InvoicePaidHandler;
use CashierBundle\Webhook\Handler\InvoicePaymentActionRequiredHandler;
use CashierBundle\Webhook\Handler\InvoicePaymentFailedHandler;
use CashierBundle\Webhook\Handler\PaymentMethodUpdatedHandler;
use CashierBundle\Webhook\Handler\SubscriptionCreatedHandler;
use CashierBundle\Webhook\Handler\SubscriptionDeletedHandler;
use CashierBundle\Webhook\Handler\SubscriptionUpdatedHandler;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // Subscription Handlers
    $services->set(SubscriptionCreatedHandler::class)
        ->args([
            service(StripeCustomerRepository::class),
            service(SubscriptionRepository::class),
            service('event_dispatcher'),
            param('cashier.default_subscription_type'),
        ])
        ->tag('cashier.webhook_handler');

    $services->set(SubscriptionUpdatedHandler::class)
        ->args([
            service(SubscriptionRepository::class),
            service('event_dispatcher'),
        ])
        ->tag('cashier.webhook_handler');

    $services->set(SubscriptionDeletedHandler::class)
        ->args([
            service(SubscriptionRepository::class),
            service('event_dispatcher'),
        ])
        ->tag('cashier.webhook_handler');

    // Customer Handlers
    $services->set(CustomerUpdatedHandler::class)
        ->args([
            service(StripeCustomerRepository::class),
            service('event_dispatcher'),
        ])
        ->tag('cashier.webhook_handler');

    $services->set(CustomerDeletedHandler::class)
        ->args([
            service(StripeCustomerRepository::class),
        ])
        ->tag('cashier.webhook_handler');

    // Payment Method Handlers
    $services->set(PaymentMethodUpdatedHandler::class)
        ->args([
            service(StripeCustomerRepository::class),
        ])
        ->tag('cashier.webhook_handler');

    // Invoice Handlers
    $services->set(InvoicePaidHandler::class)
        ->args([
            service('event_dispatcher'),
        ])
        ->tag('cashier.webhook_handler');

    $services->set(InvoicePaymentFailedHandler::class)
        ->args([
            service('event_dispatcher'),
        ])
        ->tag('cashier.webhook_handler');

    $services->set(InvoicePaymentActionRequiredHandler::class)
        ->tag('cashier.webhook_handler');

    // Checkout Handlers
    $services->set(CheckoutSessionCompletedHandler::class)
        ->args([
            service(StripeCustomerRepository::class),
            service(SubscriptionRepository::class),
        ])
        ->tag('cashier.webhook_handler');
};
